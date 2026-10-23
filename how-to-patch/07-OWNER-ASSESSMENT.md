# Authorized Website-Owner Assessment

This workflow lets a website owner assess an IP or domain without running the
OOM/RCE chain against a public production worker. It separates three different
claims: visible exposure, internal patch status, and staging-only exploit
regression.

## 1. Record authorization and scope

Record the exact hostname/IP, testing window, source IP, technical contact,
and whether resource-exhaustion or worker-crash testing is permitted. A normal
web pentest authorization does not automatically make destructive OOM testing
safe for production.

## 2. Run the passive remote check

From the operator workstation:

```bash
cd ~/php2shell
python3 poc-cve-2026-109618.py check \
  -u https://owned.example/ \
  --json
```

The command makes a TLS-verified GET request and reads at most 8 KiB per
response. It follows up to three same-host redirects, records the complete HTTP
chain, and refuses cross-host redirects or an HTTPS-to-HTTP downgrade. It does
not send multipart OOM payloads, manipulate sessions, rotate workers, derive
addresses, execute commands, or open a shell.

For a private CA, keep certificate and hostname verification enabled:

```bash
python3 poc-cve-2026-109618.py check \
  -u https://php-staging.internal/ \
  --ca-file /path/to/private-ca.pem \
  --json
```

`POTENTIALLY_AFFECTED` means only that a disclosed version falls below the
upstream fix boundary. It is a review trigger, not proof of vulnerability.
`NO_PHP_VERSION` is inconclusive rather than safe.

## 3. Collect internal package evidence

Run the commands on the server or container that handles web requests.

Common inventory commands:

```bash
php -r 'printf("version=%s\nsapi=%s\n", PHP_VERSION, PHP_SAPI);'
php --ini
php -m
```

Debian or Ubuntu:

```bash
dpkg-query -W -f='${Package}\t${Version}\n' 'php*' 2>/dev/null
apt-cache policy php php8.2 php8.3 php8.4 php8.5 2>/dev/null
zgrep -iE 'CVE-2026-109618|CVE-2026-109619' \
  /usr/share/doc/php*/changelog* 2>/dev/null
```

RHEL, Fedora, Rocky Linux, or AlmaLinux:

```bash
rpm -qa 'php*' | sort
rpm -q --changelog php 2>/dev/null | \
  grep -iE 'CVE-2026-109618|CVE-2026-109619'
```

Alpine:

```bash
apk info -vv | grep '^php'
```

For containers, retain the immutable image digest and SBOM/package listing.
An older-looking upstream version may already contain a documented vendor
backport, so the complete package revision matters.

## 4. Confirm the effective web SAPI

CLI PHP can differ from the runtime serving the website. Follow
[`05-VERIFY.md`](05-VERIFY.md) to query a temporary localhost-only or
authenticated endpoint, retain its version/SAPI result, and delete it
immediately. Do not leave `phpinfo()` or a public diagnostics page deployed.

Also determine the worker model and service actually receiving traffic:

```bash
apachectl -M 2>/dev/null | grep -i php || true
ps -eo pid,lstart,cmd | grep -E \
  '[p]hp-fpm|[a]pache2|[h]ttpd|[p]hp-cgi'
```

## 5. Patch and restart

Use the deployment-specific guide in this directory. Upgrade to a fixed
upstream release or a package whose vendor changelog explicitly documents both
CVE backports. Restart every persistent PHP and web worker; a configuration
reload is not sufficient evidence that old workers disappeared.

## 6. Use RCE regression only in disposable staging

Build a private clone that matches the production package lineage, SAPI,
architecture, extensions, allocator, and relevant INI settings. Do not assume
the bundled PHP 8.4.26 Docker offsets are portable to another build.

Run the repository's positive and negative Docker controls first:

```bash
cd ~/php2shell
docker compose -f lab/compose.yaml up -d --wait
python3 poc-cve-2026-109618.py \
  -u http://127.0.0.1:8088/index.php \
  --no-shell
```

Exploit and calibration modes intentionally accept only loopback. If a custom
staging build needs a new profile, calibrate it locally under a debugger and
retain a patched-build and precondition-disabled control. A crash, timeout,
HTTP 500, OOM warning, or script label is not execution proof.

## 7. Retain a defensible evidence pack

Keep the following with the change ticket:

- passive-check JSON and timestamp;
- exact package revision, repository, changelog, or vendor advisory;
- effective web-SAPI output;
- old and new worker process start times;
- immutable container digest where applicable;
- application smoke-test results; and
- staging positive and negative controls, if an exploit regression was
  necessary.

Do not retain credentials, session cookies, real customer data, or shell
tokens in a public repository.
