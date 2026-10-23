# Patch Docker and Container Deployments

## 1. Identify the branch and SAPI

Replace `<service>` with the PHP service name from your Compose file:

```bash
docker compose exec -T <service> php -r \
  'printf("version=%s\nsapi=%s\n", PHP_VERSION, PHP_SAPI);'
docker compose images
```

For this repository, the service names are `vulnerable`, `patched`, and
`mitigated`.

## 2. Update the base image

Use at least the fixed release for the installed branch:

```dockerfile
# PHP 8.2
FROM php:8.2.35-apache

# PHP 8.3
FROM php:8.3.36-apache

# PHP 8.4
FROM php:8.4.27-apache

# PHP 8.5
FROM php:8.5.12-apache
```

Use the equivalent `-fpm` tag for FPM deployments. Keep the existing SAPI,
Debian/Alpine family, architecture, extensions, and build flags consistent
during the emergency update.

After validation, pin the resolved image digest rather than relying only on a
mutable tag:

```bash
docker pull php:8.4.27-apache
docker image inspect php:8.4.27-apache \
  --format '{{index .RepoDigests 0}}'
```

## 3. Rebuild without a stale base-image cache

```bash
docker compose pull
docker compose build --pull --no-cache
docker compose up -d --force-recreate --wait
docker compose ps
```

If the application uses a registry-built image, rebuild it in CI, scan it,
publish a new immutable digest, and update the deployment manifest to that
digest.

## 4. Verify the running container

```bash
docker compose exec -T <service> php -r \
  'printf("version=%s\nsapi=%s\n", PHP_VERSION, PHP_SAPI);'
docker compose logs --since=10m <service>
```

Also verify through the application web path as described in
[05-VERIFY.md](05-VERIFY.md). CLI PHP and the serving SAPI can load different
binaries or INI files.

## Test the fixed control in this repository

```bash
cd ~/php2shell
docker compose -f lab/compose.yaml up -d --wait patched
curl -fsS http://127.0.0.1:8089/status.php | python3 -m json.tool
```

The response must show PHP `8.4.27` and SAPI `apache2handler`.
