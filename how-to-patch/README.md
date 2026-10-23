# How to Patch PHP2Shell

This directory contains deployment-oriented guidance for
CVE-2026-109618 and CVE-2026-109619. Updating is the only complete solution.
Configuration changes reduce selected attack paths but do not replace the
upstream state-reset fix.

## Required version by branch

| Installed branch | Affected upstream versions | Minimum fixed release | Recommended action |
|---|---:|---:|---|
| PHP 8.2 | `< 8.2.35` | `8.2.35` | Update to 8.2.35 or a later supported release |
| PHP 8.3 | `< 8.3.36` | `8.3.36` | Update to 8.3.36 or later |
| PHP 8.4 | `< 8.4.27` | `8.4.27` | Update to 8.4.27 or later |
| PHP 8.5 | `< 8.5.12` | `8.5.12` | Update to 8.5.12 or later |
| PHP 7.4 | EOL | official emergency source-backport branch | Prefer migration to a supported branch |
| PHP 8.0 | EOL | official emergency source-backport branch | Prefer migration to a supported branch |
| PHP 8.1 | EOL | official emergency source-backport branch | Prefer migration to a supported branch |

Linux and LTS vendors can backport the fix without changing the visible
upstream version. A lower-looking version is acceptable only when the package
vendor explicitly documents the CVE backport.

## Choose the applicable guide

- [Docker and container images](01-DOCKER.md)
- [Debian, Ubuntu, RHEL, Fedora, Rocky, AlmaLinux, and Alpine packages](02-LINUX-PACKAGES.md)
- [Official source releases and emergency PHP 7.4/8.0/8.1 backports](03-SOURCE-BUILD.md)
- [Microsoft Windows](04-WINDOWS.md)
- [Verify the effective web SAPI and worker restart](05-VERIFY.md)
- [Temporary exposure reductions when an immediate update is impossible](06-TEMPORARY-MITIGATIONS.md)
- [Authorized website-owner assessment from domain/IP to staging proof](07-OWNER-ASSESSMENT.md)

## Safe update sequence

1. Inventory the exact package, SAPI, loaded INI files, extensions, and worker
   services.
2. Back up configuration and application data. Take a rollback snapshot where
   the platform supports it.
3. Update in staging using the same SAPI and extensions as production.
4. Run application and extension compatibility tests.
5. Deploy the fixed package or image.
6. Restart every persistent PHP and web-server worker; a package update alone
   does not replace already running vulnerable processes.
7. Verify through the actual web SAPI, not only `php -v` from the shell.
8. Confirm the package changelog or SBOM and retain the evidence with the
   change ticket.

Do not run the exploit against production to verify the patch. Use version,
package/backport evidence, process restart evidence, and a controlled staging
regression test.

## Authoritative sources

- [PHP security announcement](https://www.php.net/archive/2026.php)
- [CVE-2026-109618 advisory](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w)
- [CVE-2026-109619 advisory](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp)
- [Official PHP downloads and checksums](https://www.php.net/downloads.php)
- [PHP supported versions](https://www.php.net/supported-versions.php)
