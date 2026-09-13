# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Cos'è questo progetto

Hub di autenticazione centralizzato Laravel (13, PHP 8.3, Breeze + Sanctum),
riutilizzabile per PWA sui sottodomini di un qualunque dominio comune
(configurato via `APP_DOMAIN`, non hardcodato nel codice). Un solo DB utenti
per tutte le PWA: l'hub gestisce login/registrazione/reset password; le PWA
figlie non hanno un proprio form di login e si autenticano tramite token
Sanctum emessi dall'hub. Il [README.md](README.md) descrive il protocollo di
integrazione lato PWA in dettaglio (redirect di login, formato del callback,
token) — leggerlo prima di modificare il flusso di autenticazione.

Questo repository specifico è configurato (via `.env`, non versionato) per
girare su `example.it`, ma il codice sorgente non deve mai avere quel
dominio (o un altro) hardcodato: qualunque valore specifico di dominio va
sempre letto da config (`APP_DOMAIN`, `APP_URL`) o dal DB
(`pwa_clients`), mai scritto letteralmente in PHP/Blade/config di default.

## Comandi

```bash
composer install                 # dipendenze PHP
npm install                      # dipendenze frontend
composer run dev                 # server+queue+log+vite insieme (concurrently)
php artisan serve                # solo il server Laravel
npm run dev                      # solo Vite (watch)
npm run build                    # build asset di produzione

composer test                    # (= artisan config:clear + artisan test) intera suite
php artisan test --filter=NomeTest        # singolo test/classe
php artisan test tests/Feature/Auth/AuthenticationTest.php  # singolo file

vendor/bin/pint                  # code style (Laravel Pint)
```

Il DB di sviluppo è SQLite (`database/database.sqlite`); i test girano su
SQLite in-memory (vedi `phpunit.xml`).

## Architettura SSO

Concetti chiave (vedi anche [README.md](README.md)):

- **UUID pubblico**: `users.uuid` è l'identificatore che le PWA devono
  salvare, mai la PK interna. Generato in `User::booted()` (`app/Models/User.php`).
- **`pwa_clients`** (`app/Models/PwaClient.php`): whitelist delle PWA
  autorizzate (`name`, `domain`, `redirect_path`). **Le righe si inseriscono a
  mano nel DB** — nessun comando artisan o seeder dedicato, e non crearne uno
  (l'utente gestisce questa tabella manualmente).
- **Handoff/redirect**: `AuthenticatedSessionController` (`app/Http/Controllers/Auth/AuthenticatedSessionController.php`)
  è il cuore del flusso — sia su login esplicito (`store`) sia su sessione hub
  già attiva (`create`, SSO silenzioso), chiama `handoff()` che crea un token
  Sanctum con TTL da `config('sanctum.pwa_token_ttl_minutes')` e reindirizza a
  `{domain}{redirect_path}#token=...` (fragment, mai inviato al server).
- **Token a scorrimento**: `App\Http\Middleware\TouchSanctumToken` (alias
  `touch.token`, usato in `routes/api.php`) rinnova `expires_at` a ogni
  richiesta autenticata — un token usato attivamente non scade mai.
- **Stato utente**: `users.status` (`pending`/`active`/`blocked`) più
  `is_admin`. `App\Http\Middleware\EnsureUserIsActive` (alias `active`)
  disconnette a metà sessione un utente diventato non attivo. Gli admin
  bloccano/sospendono un utente da `Admin\UserController::update()`, che
  revoca subito i suoi token Sanctum se lo stato passa da `active`.
- **Autorizzazione admin**: `Gate::define('access-admin', ...)` in
  `AppServiceProvider` — richiede `is_admin`; le rotte sotto `/admin` (in
  `routes/web.php`) usano `can:access-admin`. Include anche una rotta
  `admin.database` che espone Adminer da `storage/app/adminer/index.php` (file
  non versionato, va caricato a mano sul server).
- **CORS**: `config/cors.php` accetta qualunque sottodominio di
  `APP_DOMAIN` via regex (`allowed_origins_patterns`), non whitelist
  esplicita.
- **Soft delete**: `User` usa `SoftDeletes` — un utente eliminato sparisce
  anche dalle query del provider di autenticazione, quindi non può più
  accedere né restare autenticato su una sessione già aperta.

## Deploy

Hosting condiviso (cPanel/Plesk) **senza accesso shell**: niente SSH, niente
`artisan` diretto sul server. Il deploy è interamente via FTP + scheduler
Laravel:

1. `.github/workflows/deploy.yml` — ad ogni push su `main`, GitHub Actions
   fa la build (npm build, composer install --no-dev), crea `release.zip` con
   `deploy/build-zip.php` e carica via FTP archivio + trigger in
   `storage/app/deploy/` sul server. Le credenziali FTP (`FTP_HOST`,
   `FTP_USER`, `FTP_PASS`, `FTP_REMOTE_DIR`, `FTP_SECURE`) sono GitHub
   Secrets del repo, non file locali.
2. Sul server, `Schedule::command('app:apply-deploy')` (`routes/console.php`,
   ogni 5 minuti tramite l'unico cron `php artisan schedule:run` configurato
   sul pannello hosting) fa girare `App\Console\Commands\ApplyDeploy`: estrae
   `release.zip`, esegue `migrate --force` + cache, poi cancella trigger e
   zip. **Non tocca mai `.env` né il database** — l'archivio dal workflow li
   esclude esplicitamente.
3. Log del deploy applicato: `storage/logs/deploy.log` sul server.

Un push su `main` è quindi già sufficiente: nessuno script locale da
lanciare manualmente dopo una modifica.
