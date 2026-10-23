# Patch Source-Built PHP

Building PHP is deployment-specific. Reuse the existing configure flags,
extensions, library versions, SAPI, service definitions, and packaging process.
Do not copy a generic binary over a production installation.

## Official fixed source releases

| Branch | Release | Official `tar.xz` SHA-256 |
|---|---:|---|
| PHP 8.2 | 8.2.35 | `5737b98601fe5ab1b8c5db0c82ed3158acb9283ed3081ffe2faffb9ac81455e6` |
| PHP 8.3 | 8.3.36 | `dd54693aa334c5c4d72e9aee96b082c2473750a18a6c1d105789c93a3b1692fd` |
| PHP 8.4 | 8.4.27 | `dcf93923b0e38e0d5252b25b796520b5a47761a08b3488b6d0b5d1115764d3f0` |
| PHP 8.5 | 8.5.12 | `08bc2eba2a588654ade1357174aa646b761556c862dda0678345ee29ed481209` |

The checksums above are published on the official PHP downloads page.

Example for PHP 8.4.27:

```bash
curl -fLO https://www.php.net/distributions/php-8.4.27.tar.xz
printf '%s  %s\n' \
  'dcf93923b0e38e0d5252b25b796520b5a47761a08b3488b6d0b5d1115764d3f0' \
  'php-8.4.27.tar.xz' | sha256sum -c -
tar -xf php-8.4.27.tar.xz
cd php-8.4.27
```

Before compiling, record the existing build configuration:

```bash
php -i | grep '^Configure Command'
php -m
php --ini
```

Build through the same packaging or automation used for the existing
installation. A typical source workflow is:

```bash
./configure <the-existing-reviewed-configure-options>
make -j"$(nproc)"
make test
```

Install into a staging prefix or package, run application tests, then perform
an atomic deployment. Restart every PHP and web-server worker after switching
to the fixed build.

## Emergency source backports for EOL branches

The PHP security team published minimal backport branches for environments
that cannot immediately leave PHP 7.4, 8.0, or 8.1:

- [PHP 7.4 backport](https://github.com/php/php-src/tree/PHP-7.4-CVE-2026-109618-109619)
- [PHP 8.0 backport](https://github.com/php/php-src/tree/PHP-8.0-CVE-2026-109618-109619)
- [PHP 8.1 backport](https://github.com/php/php-src/tree/PHP-8.1-CVE-2026-109618-109619)

Fetch the exact official branch required by the legacy deployment:

```bash
git clone --depth 1 \
  --branch PHP-8.1-CVE-2026-109618-109619 \
  https://github.com/php/php-src.git php-8.1-security-backport
git -C php-8.1-security-backport rev-parse HEAD
```

Replace `8.1` in both directory and branch names with `8.0` or `7.4` for the
other official backports. Record the commit ID, build with the original branch's
toolchain and configuration, and test all extensions.

These branches are emergency source assistance, not a restoration of normal
support for EOL PHP. Migration to a supported branch remains required.

## Do not cherry-pick only one visible hunk

The upstream repair spans request lifecycle and state-reset behavior. Apply the
official fixed release, official vendor package, or complete official backport.
Do not hand-select only the session or OOM-looking portion of the patch.
