# Patch PHP on Microsoft Windows

The PHP project publishes official Windows binaries from the downloads page.
Use at least PHP 8.2.35, 8.3.36, 8.4.27, or 8.5.12 for the corresponding
supported branch.

## 1. Inventory the existing build

From the PHP installation directory:

```powershell
php.exe -v
php.exe -i | Select-String 'Thread Safety|Architecture|Loaded Configuration'
php.exe -m
```

Record whether the deployment is:

- Thread Safe (TS) or Non Thread Safe (NTS);
- x64 or x86;
- IIS/FastCGI, Apache module, or another SAPI; and
- dependent on third-party extensions requiring matching ABI builds.

Do not switch TS/NTS, architecture, or SAPI during an untested emergency
replacement.

## 2. Download a fixed official build

Use the Windows link from the
[official PHP downloads page](https://www.php.net/downloads.php). Download the
matching TS/NTS and architecture package and verify the published checksum or
signature.

Extract it to a new versioned directory, for example:

```text
C:\PHP\8.4.27\
```

Do not overwrite binaries in the running directory. Copy and review the
existing `php.ini` and required extension configuration, then test the new
directory side by side.

## 3. Switch and restart

For IIS/FastCGI, update the FastCGI executable path to the new `php-cgi.exe`
and recycle the affected application pools. For Apache, update the module or
FastCGI path and restart the Apache service.

Examples from an elevated PowerShell session:

```powershell
iisreset
Restart-Service -Name Apache2.4
```

Run only the command applicable to the deployment. Confirm that no old
`php-cgi.exe`, Apache worker, or PHP service process remains.

## 4. Verify through the web server

CLI verification alone is insufficient. Follow [05-VERIFY.md](05-VERIFY.md)
and confirm that the request-serving SAPI reports the fixed build.

PHP 7.4, 8.0, and 8.1 are EOL and do not receive normal Windows releases from
the PHP project. Prefer migration to a supported branch or obtain a documented
vendor/LTS backport; do not treat the emergency source branches as prebuilt
Windows packages.
