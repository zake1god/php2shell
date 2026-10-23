# Patch Distribution Packages

Prefer the operating-system or LTS vendor package when PHP was installed from
that vendor. Do not replace distribution PHP with an unrelated repository
during an emergency unless the migration has been tested.

## Inventory before updating

```bash
php -v
php --ini
php -r 'printf("version=%s sapi=%s\n", PHP_VERSION, PHP_SAPI);'
```

Record the installed package set:

```bash
# Debian and Ubuntu
dpkg-query -W 'php*' 2>/dev/null

# RHEL, Fedora, Rocky, or AlmaLinux
rpm -qa 'php*' 2>/dev/null

# Alpine
apk info -vv | grep '^php'
```

## Debian and Ubuntu

Refresh metadata and inspect the candidate version before deployment. The
example below uses PHP 8.4; substitute `8.2`, `8.3`, or `8.5` for the installed
branch.

```bash
sudo apt update
apt-cache policy php8.4-common php8.4-cli php8.4-fpm libapache2-mod-php8.4
sudo apt install --only-upgrade \
  php8.4-common php8.4-cli php8.4-fpm libapache2-mod-php8.4
```

`--only-upgrade` prevents installation of SAPI packages that were not already
present. It is normal for one of the FPM or Apache packages to be absent.

Restart the SAPI actually in use:

```bash
sudo systemctl restart php8.4-fpm
sudo systemctl restart apache2
```

If only FPM is installed, restart FPM and the frontend proxy. If `mod_php` is
installed, restart Apache so every worker loads the fixed module.

## RHEL, Fedora, Rocky Linux, and AlmaLinux

```bash
sudo dnf check-update 'php*'
sudo dnf upgrade 'php*'
sudo systemctl restart php-fpm
sudo systemctl restart httpd
```

Run only the service restart commands relevant to the installed SAPI. Preserve
the package transaction and changelog as patch evidence:

```bash
dnf history info last
rpm -q --changelog php | grep -Ei \
  'CVE-2026-109618|CVE-2026-109619|OOM|stale global' | head
```

The PHP security announcement specifically warns that the volunteer Remi
repository may not provide same-day updates for this event. Confirm the actual
repository and fixed package instead of assuming availability.

## Alpine Linux

Inspect the installed branch, then upgrade the matching package family:

```bash
apk info -vv | grep '^php'
sudo apk update
sudo apk upgrade 'php84*'
```

The command shows the PHP 8.4 package family as an example. Replace `php84`
with `php82`, `php83`, or `php85` for the installed branch. Restart the relevant
service or recreate the container after the package transaction.

## Vendor-backported packages

A package can be fixed while retaining an upstream-looking version below the
table in the main README. Accept that state only when the vendor advisory or
package changelog explicitly maps its build to both CVEs. Save:

- the complete package version including distribution suffix;
- repository origin;
- changelog/advisory text naming both CVEs;
- installation timestamp; and
- post-restart web-SAPI verification.

Continue with [05-VERIFY.md](05-VERIFY.md).
