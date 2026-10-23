#!/bin/sh
# Start-up script for the Absensi container.
# Prepares folders, waits for the database, runs migrations and caches,
# then starts nginx + php-fpm (the CMD).
set -e

cd /var/www/html

log() { echo "[entrypoint] $*"; }
as_www() { su-exec www-data "$@"; }

# Only do the app set-up when starting the web server (not for one-off
# commands like `docker run image php artisan ...`).
if [ "$1" = "supervisord" ]; then

    # ---------------------------------------------------------------
    # 1. Folders (volumes may be mounted empty)
    # ---------------------------------------------------------------
    mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/app/backups \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        public/storage/selfies/employee \
        public/storage/selfies/student \
        public/storage/permissions \
        public/storage/attendance_corrections \
        public/uploads/logos

    # Put the default school logos into a new, empty uploads volume
    if [ -d /opt/app-defaults/uploads ]; then
        cp -an /opt/app-defaults/uploads/. public/uploads/ 2>/dev/null || true
    fi

    chown -R www-data:www-data storage bootstrap/cache public/storage public/uploads

    # Uploads and backups must live on volumes, or a redeploy wipes them
    for dir in /var/www/html/storage /var/www/html/public/storage /var/www/html/public/uploads; do
        if ! awk '{print $5}' /proc/self/mountinfo | grep -qx "$dir"; then
            log "WARNING: $dir is NOT a persistent volume: its files will be LOST on the next redeploy."
            log "         Add it under Persistent Storage in Coolify (see DEPLOY_COOLIFY.md)."
        fi
    done

    # ---------------------------------------------------------------
    # 2. APP_KEY: must stay the same forever (it encrypts sessions and
    #    data). If it is not set in Coolify, create one and keep it in
    #    the storage volume so it survives redeploys.
    # ---------------------------------------------------------------
    if [ -z "${APP_KEY:-}" ]; then
        KEY_FILE=storage/app/private/.app_key
        if [ ! -s "$KEY_FILE" ]; then
            log "APP_KEY is empty: generating one in $KEY_FILE"
            php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$KEY_FILE"
            chown www-data:www-data "$KEY_FILE"
            chmod 600 "$KEY_FILE"
        fi
        APP_KEY="$(cat "$KEY_FILE")"
        export APP_KEY
        log "Using the APP_KEY saved in the storage volume. Tip: copy it into Coolify's APP_KEY variable."
    fi

    # php-fpm pool size (see docker/php/fpm-pool.conf)
    case "${PHP_FPM_MAX_CHILDREN:-}" in
        ''|*[!0-9]*) PHP_FPM_MAX_CHILDREN=10 ;;
    esac
    if [ "$PHP_FPM_MAX_CHILDREN" -lt 4 ]; then
        PHP_FPM_MAX_CHILDREN=4
    fi
    export PHP_FPM_MAX_CHILDREN

    # ---------------------------------------------------------------
    # 3. Wait for the database
    # ---------------------------------------------------------------
    DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-60}"
    log "Waiting for the database (${DB_HOST:-?}:${DB_PORT:-5432}, max ${DB_WAIT_TIMEOUT}s)..."
    waited=0
    # shellcheck disable=SC2016 # PHP code, not shell variables
    until php -r '
        $c = getenv("DB_CONNECTION") ?: "pgsql";
        if ($c !== "pgsql") { exit(0); }
        try {
            new PDO(
                sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST") ?: "127.0.0.1", getenv("DB_PORT") ?: "5432", getenv("DB_DATABASE") ?: "forge"),
                getenv("DB_USERNAME") ?: "forge",
                getenv("DB_PASSWORD") ?: "",
                [PDO::ATTR_TIMEOUT => 3]
            );
        } catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); }
    ' 2>/tmp/db-error; do
        waited=$((waited + 2))
        if [ "$waited" -ge "$DB_WAIT_TIMEOUT" ]; then
            log "ERROR: cannot connect to the database after ${DB_WAIT_TIMEOUT}s:"
            cat /tmp/db-error
            log "Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in Coolify."
            exit 1
        fi
        sleep 2
    done
    rm -f /tmp/db-error
    log "Database is reachable."

    # ---------------------------------------------------------------
    # 4. Caches, migrations, first install
    # ---------------------------------------------------------------
    # Remove caches of a previous start (they may hold old settings)
    rm -f bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        # Safety net for updates: back up the database before a deploy that
        # changes it. The backup shows up in Admin > Backup.
        if [ "${PRE_MIGRATION_BACKUP:-true}" = "true" ]; then
            if ! as_www php artisan app:backup-database --kind=premigrate --if-pending-migrations --no-interaction; then
                log "ERROR: the backup before the database update failed, so the update was NOT run (your data is unchanged)."
                log "Fix the error above, or set PRE_MIGRATION_BACKUP=false to update without a backup."
                exit 1
            fi
        fi
        log "Running database migrations..."
        as_www php artisan migrate --force --no-interaction
    fi

    if [ -n "${INITIAL_ADMIN_PASSWORD:-}" ]; then
        as_www php artisan app:first-install --no-interaction || \
            log "WARNING: first install failed, see the message above."
    fi

    log "Caching config, routes, views and events..."
    as_www php artisan config:cache
    as_www php artisan route:cache
    as_www php artisan view:cache
    as_www php artisan event:cache

    log "Ready. Starting nginx + php-fpm."
fi

exec "$@"
