# Verify the Patch and Running Workers

Verification must cover the package and the effective web SAPI. `php -v` can
report a different binary or INI tree from Apache, FPM, CGI, or LiteSpeed.

## 1. Record package evidence

Keep one of the following:

- an upstream fixed release at or above the branch minimum;
- a vendor advisory naming both CVEs; or
- a package changelog explicitly documenting both backports.

## 2. Verify CLI and service binaries

```bash
php -r 'printf("version=%s\nsapi=%s\n", PHP_VERSION, PHP_SAPI);'
php --ini
php -m
```

For FPM, run the installed versioned binary where applicable:

```bash
php-fpm -v
systemctl status php-fpm --no-pager
```

For Apache `mod_php`:

```bash
apachectl -M 2>/dev/null | grep -i php
systemctl status apache2 --no-pager 2>/dev/null || \
  systemctl status httpd --no-pager
```

## 3. Verify the actual web SAPI

Create a temporary file in a private health-check location that is reachable
only from localhost or an authenticated administrator path:

```php
<?php
header('Content-Type: application/json');
echo json_encode([
    'version' => PHP_VERSION,
    'version_id' => PHP_VERSION_ID,
    'sapi' => PHP_SAPI,
    'loaded_ini' => php_ini_loaded_file(),
], JSON_PRETTY_PRINT), "\n";
```

Request it locally, retain the response with the change record, then delete the
file immediately. Do not leave a public version-disclosure endpoint behind.

For this repository the equivalent safe lab endpoint is:

```bash
curl -fsS http://127.0.0.1:8089/status.php | python3 -m json.tool
```

Expected fixed-control fields include:

```text
php_version = 8.4.27
sapi = apache2handler
```

## 4. Confirm worker replacement

Restart, not merely reload, every persistent PHP/web worker. Then record new
process start times:

```bash
ps -eo pid,lstart,cmd | grep -E \
  '[p]hp-fpm|[a]pache2|[h]ttpd|[p]hp-cgi'
```

Check load balancers, autoscaling groups, job workers, CLI daemons, and old
containers so traffic cannot reach a forgotten vulnerable pool member.

## 5. Validate behavior safely

Run application smoke tests, upload tests, sessions, authentication, queues,
and extension-specific tests in staging. Do not run the OOM/RCE exploit against
production as a patch check.

The included local matrix can be used as a regression reference:

```bash
cd ~/php2shell
docker compose -f lab/compose.yaml up -d --wait
python3 poc-cve-2026-109618.py \
  -u http://127.0.0.1:8089/index.php \
  --profile php-8.4.26-bookworm-amd64 \
  --docker-container php2shell-patched \
  --offsets-file out/patched/offsets.env \
  --proof-file out/patched/p1 \
  --retries 2 \
  --cycles 1
```

The patched control must not produce `out/patched/p1`. A failed exploit alone
does not prove a production package is fixed; retain package/backport evidence
as the primary patch proof.
