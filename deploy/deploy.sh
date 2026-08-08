#!/usr/bin/env bash
#
# Build locale + upload via FTP. Non esegue nulla lato server: l'estrazione
# e le migration vengono applicate dal comando Artisan `app:apply-deploy`
# (app/Console/Commands/ApplyDeploy.php), schedulato in routes/console.php
# e lanciato dal cron `php artisan schedule:run` configurato una tantum sul
# pannello di hosting.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_DIR="$ROOT/deploy"
STAGE="$DEPLOY_DIR/.stage"
ARCHIVE="$DEPLOY_DIR/release.zip"
CONFIG_FILE="$DEPLOY_DIR/.env.deploy"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Manca $CONFIG_FILE. Copia deploy/.env.deploy.example in deploy/.env.deploy e compilalo." >&2
    exit 1
fi

# shellcheck source=/dev/null
source "$CONFIG_FILE"

: "${FTP_HOST:?FTP_HOST mancante in deploy/.env.deploy}"
: "${FTP_USER:?FTP_USER mancante in deploy/.env.deploy}"
: "${FTP_PASS:?FTP_PASS mancante in deploy/.env.deploy}"
: "${FTP_REMOTE_DIR:?FTP_REMOTE_DIR mancante in deploy/.env.deploy}"

CURL_FTP_OPTS=()
if [ "${FTP_SECURE:-true}" = "true" ]; then
    FTP_URL="ftps://${FTP_HOST}"
    CURL_FTP_OPTS+=(--ssl-reqd)
else
    FTP_URL="ftp://${FTP_HOST}"
fi

echo "==> Pulizia build precedente"
rm -rf "$STAGE" "$ARCHIVE"
mkdir -p "$STAGE"

echo "==> Copia sorgenti (esclusi file di sviluppo/segreti)"
tar \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='deploy' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='database/database.sqlite' \
    --exclude='storage/app/deploy' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache/data' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='tests' \
    -cf - -C "$ROOT" . | tar -xf - -C "$STAGE"

echo "==> Build asset frontend"
(cd "$ROOT" && npm run build)
mkdir -p "$STAGE/public"
cp -r "$ROOT/public/build" "$STAGE/public/build"

echo "==> Installazione dipendenze PHP (solo produzione, non tocca il vendor locale)"
(cd "$STAGE" && composer install --no-dev --optimize-autoloader --no-interaction)

echo "==> Creazione archivio"
php "$DEPLOY_DIR/build-zip.php" "$STAGE" "$ARCHIVE"

echo "==> Upload archivio via FTP"
curl -sS --ftp-create-dirs "${CURL_FTP_OPTS[@]}" \
    -u "${FTP_USER}:${FTP_PASS}" \
    -T "$ARCHIVE" \
    "${FTP_URL}${FTP_REMOTE_DIR}/storage/app/deploy/release.zip"

echo "==> Upload trigger (fa partire l'estrazione al prossimo giro dello scheduler Laravel)"
TRIGGER_FILE="$DEPLOY_DIR/.trigger.tmp"
: > "$TRIGGER_FILE"
curl -sS --ftp-create-dirs "${CURL_FTP_OPTS[@]}" \
    -u "${FTP_USER}:${FTP_PASS}" \
    -T "$TRIGGER_FILE" \
    "${FTP_URL}${FTP_REMOTE_DIR}/storage/app/deploy/deploy.trigger"
rm -f "$TRIGGER_FILE"

echo "==> Fatto. Verrà applicato entro 5 minuti da 'php artisan schedule:run' (controlla storage/logs sul server)."
