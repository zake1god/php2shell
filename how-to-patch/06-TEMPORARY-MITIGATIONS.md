# Temporary Exposure Reductions

These controls are not a patch. Use them only while an emergency update is in
progress, document the residual risk, and continue to a fixed release or
documented vendor backport.

## Reduce CVE-2026-109618 OOM paths

Administratively enforce unlimited PHP memory so applications cannot restore a
finite `memory_limit`:

```ini
memory_limit = -1
```

For an FPM pool:

```ini
php_admin_value[memory_limit] = -1
```

For an Apache virtual host using `mod_php`:

```apache
php_admin_value memory_limit -1
```

Use conservative request limits at the reverse proxy and PHP layers. The PHP
security announcement suggests `post_max_size=8M` when uploads are unnecessary,
or `32M` when they are required, with `upload_max_filesize` kept below it.
Also bound decoded request size, multipart part count, per-part headers, input
variables, and nesting depth—including requests without a usable
`Content-Length`.

Do not use `RLIMIT_AS` or `RLIMIT_DATA` as the mitigation; allocator failure can
enter the vulnerable path.

## Reduce known CVE-2026-109619 session paths

If the application does not use session upload progress, disable it
administratively:

```ini
session.upload_progress.enabled = Off
```

FPM pool form:

```ini
php_admin_value[session.upload_progress.enabled] = Off
```

Apache `mod_php` form:

```apache
php_admin_value session.upload_progress.enabled Off
```

For persistent FPM workers, `pm.max_requests=1` can reduce cross-request reuse
but has major performance cost and does not cover every vulnerable path.

## Restart and verify

Configuration changes do not affect existing workers until they are restarted:

```bash
sudo systemctl restart php-fpm
sudo systemctl restart apache2 2>/dev/null || sudo systemctl restart httpd
```

Use the service commands applicable to the deployment, then verify the effective
values through the real web SAPI. Remove temporary mitigations only after the
fixed build is deployed and confirmed; keeping compatible request-hardening
limits is generally beneficial.
