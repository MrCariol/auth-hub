#!/usr/bin/env bash
#
# Script di emergenza da lanciare via cron sul server (bash, non PHP).
# Serve a sbloccare l'app quando l'autoload di Composer è rotto (es. pacchetto
# mancante in vendor/) e quindi "php artisan" stesso non parte più: in quel
# caso lo scheduler Laravel (routes/console.php) non riesce mai ad applicare
# un deploy in coda, perché crasha prima di arrivarci.
#
# Cosa fa, in ordine:
#   1. Trova un binario PHP e uno Composer funzionanti (prova i percorsi
#      tipici cPanel/EasyApache, poi il PATH).
#   2. Se "php artisan --version" fallisce, lancia "composer install
#      --no-dev" per rigenerare vendor/ e l'autoload direttamente sul
#      server (non serve che l'app funzioni per farlo: Composer non passa
#      dall'autoload dell'app).
#   3. Se un deploy è in coda (storage/app/deploy/deploy.trigger), lo
#      applica con "php artisan app:apply-deploy" (estrae release.zip,
#      migrate --force, config/route/view:cache) invece di duplicare
#      quella logica qui.
#   4. Logga ogni passo con timestamp in storage/logs/server-fix.log così
#      è ispezionabile via FTP/File Manager anche se qualcosa fallisce.
#
# Uso: aggiungere a cPanel > Cron Jobs una riga tipo
#   * * * * * /bin/bash /home/tuoutente/auth.example.com/deploy/server-fix.sh >> /home/tuoutente/auth.example.com/storage/logs/server-fix-cron.log 2>&1
#
# È idempotente: una volta che vendor/ e il deploy sono a posto, le run
# successive sono no-op veloci (composer install senza modifiche, migrate
# senza migration pendenti). Un lock file evita run sovrapposte se un
# passo impiega più di un minuto.

set -uo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_FILE="$APP_ROOT/storage/logs/server-fix.log"
LOCK_FILE="$APP_ROOT/storage/framework/server-fix.lock"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    printf '[%s] %s\n' "$(date -u '+%Y-%m-%d %H:%M:%S UTC')" "$1" >> "$LOG_FILE"
}

# --- lock per evitare esecuzioni sovrapposte ---
exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    log "Run già in corso (lock presente), esco."
    exit 0
fi

log "=== server-fix.sh avviato ==="

# --- individua un binario PHP CLI funzionante (non CGI: niente header HTTP,
# argv passati correttamente ad artisan) ---
PHP_BIN=""
for candidate in \
    /opt/cpanel/ea-php83/root/usr/bin/php \
    /opt/cpanel/ea-php82/root/usr/bin/php \
    /opt/cpanel/ea-php81/root/usr/bin/php \
    /usr/local/bin/php \
    "$(command -v php 2>/dev/null || true)" \
    /usr/bin/php
do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then
        sapi="$("$candidate" -r 'echo php_sapi_name();' 2>/dev/null)"
        if [ "$sapi" = "cli" ]; then
            PHP_BIN="$candidate"
            break
        fi
        log "Scartato $candidate: SAPI '$sapi' (serve 'cli')."
    fi
done

if [ -z "$PHP_BIN" ]; then
    log "ERRORE: nessun binario PHP con SAPI 'cli' trovato (provati percorsi ea-phpXX + PATH). Impossibile proseguire."
    exit 1
fi
log "PHP: $PHP_BIN ($("$PHP_BIN" -v 2>&1 | head -n1))"

# --- individua un binario Composer funzionante ---
COMPOSER_BIN=""
for candidate in \
    "$(command -v composer 2>/dev/null || true)" \
    "$HOME/composer.phar" \
    "$HOME/bin/composer" \
    /opt/cpanel/composer/bin/composer \
    /usr/local/bin/composer \
    "$APP_ROOT/composer.phar"
do
    if [ -n "$candidate" ] && [ -f "$candidate" ]; then
        COMPOSER_BIN="$candidate"
        break
    fi
done

if [ -n "$COMPOSER_BIN" ]; then
    log "Composer: $COMPOSER_BIN"
else
    log "Composer non trovato sul server (ok se non serve: viene usato solo per l'auto-riparazione di vendor/)."
fi

# --- fase 1: l'app risponde? se no, ripara l'autoload ---
cd "$APP_ROOT" || { log "ERRORE: impossibile entrare in $APP_ROOT"; exit 1; }

if ! "$PHP_BIN" artisan --version > /tmp/server-fix-artisan-check.$$ 2>&1; then
    log "artisan non parte (autoload rotto?). Output:"
    sed 's/^/    /' /tmp/server-fix-artisan-check.$$ >> "$LOG_FILE"
    rm -f /tmp/server-fix-artisan-check.$$

    # Primo tentativo: estrai release.zip in coda con PHP nudo (nessuna
    # dipendenza da vendor/autoload.php). Il vendor/ dentro lo zip è già
    # completo, costruito in locale da deploy.ps1/deploy.sh.
    if [ -f "$APP_ROOT/storage/app/deploy/deploy.trigger" ]; then
        log "Provo prima l'estrazione diretta di release.zip (deploy/raw-extract.php)..."
        "$PHP_BIN" "$APP_ROOT/deploy/raw-extract.php" >> "$LOG_FILE" 2>&1
    else
        log "Nessun deploy.trigger in coda: l'estrazione diretta non può aiutare."
    fi

    if "$PHP_BIN" artisan --version >> "$LOG_FILE" 2>&1; then
        log "artisan ora funziona (risolto da raw-extract.php)."
    elif [ -n "$COMPOSER_BIN" ]; then
        log "Ancora rotto. Lancio '$COMPOSER_BIN install --no-dev --optimize-autoloader' per rigenerare vendor/..."
        if "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction >> "$LOG_FILE" 2>&1 \
            && "$PHP_BIN" artisan --version >> "$LOG_FILE" 2>&1; then
            log "artisan ora funziona (risolto da composer install)."
        else
            log "ERRORE: artisan continua a non partire anche dopo composer install."
            exit 1
        fi
    else
        log "ERRORE: artisan continua a non partire e Composer non è disponibile sul server. Serve intervento manuale (caricare un vendor/ completo via FTP)."
        exit 1
    fi
else
    log "artisan --version OK, autoload sano."
fi

# --- fase 2: se npm/vite serve e gli asset mancano, prova a costruirli ---
# Normalmente NON serve: deploy.ps1/deploy.sh già eseguono "npm run build"
# in locale e includono public/build nello zip caricato. Questo è solo un
# fallback difensivo se public/build manca del tutto.
if [ ! -f "$APP_ROOT/public/build/manifest.json" ]; then
    log "public/build/manifest.json assente."
    if command -v npm >/dev/null 2>&1; then
        log "npm trovato, provo 'npm ci && npm run build'..."
        if (cd "$APP_ROOT" && npm ci >> "$LOG_FILE" 2>&1 && npm run build >> "$LOG_FILE" 2>&1); then
            log "npm run build completato."
        else
            log "ATTENZIONE: npm run build fallito (vedi output sopra). Gli asset frontend potrebbero mancare."
        fi
    else
        log "npm non disponibile sul server: normale su molti hosting condivisi. Gli asset vanno costruiti in locale e inclusi nello zip di deploy."
    fi
fi

# --- fase 3: applica un deploy in coda, se presente ---
if [ -f "$APP_ROOT/storage/app/deploy/deploy.trigger" ]; then
    log "Trigger di deploy trovato, lancio 'artisan app:apply-deploy'..."
    if "$PHP_BIN" artisan app:apply-deploy >> "$LOG_FILE" 2>&1; then
        log "app:apply-deploy completato."
    else
        log "ERRORE: app:apply-deploy ha restituito un errore (vedi output sopra)."
    fi
else
    log "Nessun deploy in coda."
fi

# --- fase 4: assicura che cache/migrazioni siano allineate anche senza trigger ---
"$PHP_BIN" artisan migrate --force >> "$LOG_FILE" 2>&1
"$PHP_BIN" artisan config:cache >> "$LOG_FILE" 2>&1
"$PHP_BIN" artisan route:cache >> "$LOG_FILE" 2>&1
"$PHP_BIN" artisan view:cache >> "$LOG_FILE" 2>&1

log "=== server-fix.sh terminato ==="
