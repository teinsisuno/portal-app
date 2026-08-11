#!/bin/sh
# Entrypoint untuk container portal-app (Central uranop → megakomsel.com)
# 1. Siapkan struktur storage
# 2. Tunggu MySQL siap
# 3. Jalankan migrasi otomatis (idempotent — aman dijalanin tiap start)
# 4. Link storage untuk public/uploads
# 5. Jalankan Apache
#
# PENTING: artisan dijalankan sebagai www-data (user Apache) supaya file
# yang dibuat bisa ditulis app, bukan root.

set -e

echo "[entrypoint] Menyiapkan struktur storage..."
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

if [ ! -f /var/www/html/.env ]; then
    echo "[entrypoint] ERROR: .env tidak ditemukan! Mount .env ke /var/www/html/.env"
    exit 1
fi

echo "[entrypoint] Menunggu MySQL..."
# Retry sampai MySQL di service 'mysql' siap terima koneksi
for i in $(seq 1 30); do
    if php -r "try { new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'ok'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
        echo "[entrypoint] MySQL siap."
        break
    fi
    echo "[entrypoint] Menunggu MySQL... (${i}/30)"
    sleep 3
done

echo "[entrypoint] Menjalankan migrasi..."
su -s /bin/sh www-data -c "cd /var/www/html && php artisan migrate --force --no-interaction"

echo "[entrypoint] Link storage..."
su -s /bin/sh www-data -c "cd /var/www/html && php artisan storage:link --no-interaction" || true

echo "[entrypoint] Seed apps (idempotent)..."
su -s /bin/sh www-data -c "cd /var/www/html && php artisan db:seed --class=AppSeeder --force --no-interaction" || true

echo "[entrypoint] Memulai Apache..."
exec "$@"
