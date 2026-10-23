#!/bin/sh
# End-to-end test of docker-compose.yml (the Coolify "Docker Compose" setup):
# first install, redeploy, an update with a new migration and a full
# restart must all keep the data (database rows, photos, logos, APP_KEY).
set -eu
cd "$(dirname "$0")/../.."

ENV_FILE=$(mktemp)
cat > "$ENV_FILE" <<'ENV'
APP_URL=http://localhost:8080
DB_PASSWORD=ci-db-secret
INITIAL_ADMIN_PASSWORD=ci-admin-123
ENV
MIGRATION=database/migrations/2099_01_01_000000_ci_update_test.php

dc() { docker compose -p absensi-ci -f docker-compose.yml -f docker-compose.local.yml --env-file "$ENV_FILE" "$@"; }
in_app() { dc exec -T app "$@"; }
sql() { dc exec -T db psql -U absensi -d absensi -tAc "$1"; }
step() { printf '\n=== %s\n' "$*"; }
fail() { echo "FAIL: $*"; dc logs app 2>&1 | tail -150; exit 1; }
cleanup() { rm -f "$MIGRATION"; dc down -v > /dev/null 2>&1 || true; }
trap cleanup EXIT

login_status() {
    jar=$(mktemp)
    token=$(curl -s -c "$jar" -b "$jar" http://localhost:8080/login \
        | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
    curl -s -o /dev/null -c "$jar" -b "$jar" \
        -d "_token=$token&username=admin&password=ci-admin-123" http://localhost:8080/login
    curl -s -o /dev/null -w '%{http_code}' -c "$jar" -b "$jar" http://localhost:8080/admin/dashboard
}

check_data() {
    [ "$(sql "select value from settings where key = 'ci_marker'")" = "tetap-ada" ] || fail "database row lost"
    in_app test -f /var/www/html/public/storage/selfies/student/ci.jpg || fail "photo lost"
    in_app test -f /var/www/html/public/uploads/logos/ci.png || fail "logo lost"
    [ "$(in_app cat /var/www/html/storage/app/private/.app_key)" = "$APP_KEY_BEFORE" ] || fail "APP_KEY changed"
    [ "$(login_status)" = 200 ] || fail "admin login after $1"
    echo "data intact after $1"
}

step "1. First deploy (empty database)"
dc up -d --build --wait || fail "first deploy"
[ "$(login_status)" = 200 ] || fail "admin login"
in_app pgrep -f "schedule:work" > /dev/null || fail "scheduler (daily backup) is not running"
if dc logs app 2>&1 | grep -q "is NOT a persistent volume"; then fail "volumes not detected"; fi
APP_KEY_BEFORE=$(in_app cat /var/www/html/storage/app/private/.app_key)

step "2. Add data: a database row, a photo, a logo"
sql "insert into settings (key, value, created_at, updated_at) values ('ci_marker', 'tetap-ada', now(), now())"
in_app sh -c 'echo foto > /var/www/html/public/storage/selfies/student/ci.jpg && echo logo > /var/www/html/public/uploads/logos/ci.png'

step "3. Redeploy (rebuild + new containers)"
dc up -d --build --force-recreate --wait || fail "redeploy"
check_data "redeploy"
dc logs app 2>&1 | grep -q "first install skipped" || fail "first install ran again"

step "4. Update that changes the database (new migration)"
cat > "$MIGRATION" <<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ci_update_test', fn (Blueprint $table) => $table->id());
    }

    public function down(): void
    {
        Schema::dropIfExists('ci_update_test');
    }
};
PHP
# Coolify always starts new containers on deploy
dc up -d --build --force-recreate --wait || fail "update deploy"
dc logs app 2>&1 | grep -q "pending migration(s): backing up" || fail "no backup before the migration"
in_app sh -c 'ls /var/www/html/storage/app/backups/backup_premigrate_*.zip' || fail "pre-migration backup file missing"
[ "$(sql "select to_regclass('public.ci_update_test') is not null")" = "t" ] || fail "migration did not run"
check_data "update"

step "5. Backup command (what the daily schedule runs)"
in_app su-exec www-data php artisan app:backup-database --no-interaction
in_app sh -c 'ls /var/www/html/storage/app/backups/backup_auto_*.zip' || fail "daily backup file missing"

step "6. Full stop and start (server reboot)"
dc down
dc up -d --wait || fail "start after down"
check_data "restart"

echo
echo "All Docker Compose data-safety checks passed."
