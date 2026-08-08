# Auth example.it

Hub di autenticazione centralizzato per le PWA su sottodomini di `example.it`
(es. `routine.example.it`). Un solo utente/database per tutte le PWA: ognuna
gestisce i propri dati in autonomia, collegati all'utente tramite un
identificatore pubblico (UUID) restituito dall'hub.

## Come funziona

- **Login unico**: solo l'hub (`auth.example.it`) ha login, registrazione,
  reset password (Laravel Breeze). Le PWA non hanno un proprio form di login.
- **Identificatore utente**: ogni utente ha, oltre alla PK interna, un campo
  `uuid` pubblico (`users.uuid`). È questo — mai la PK interna — l'ID che le
  PWA devono salvare per collegare i propri dati all'utente.
- **Token per PWA**: dopo il login, l'hub emette un token Sanctum legato alla
  PWA richiedente, valido 30 giorni **a scorrimento** (si rinnova ad ogni uso,
  vedi `App\Http\Middleware\TouchSanctumToken`).
- **Whitelist PWA**: la tabella `pwa_clients` (`name`, `domain`,
  `redirect_path`) elenca le PWA autorizzate a ricevere un redirect con
  token. Righe inserite a mano nel DB, nessun comando/seeder dedicato.
- **SSO silenzioso**: l'hub mantiene un proprio cookie di sessione
  (`remember me` di Breeze). Se già loggato sull'hub, passare da una PWA
  all'altra non richiede di rifare login: la pagina di login rileva la
  sessione attiva e reindirizza subito con un nuovo token.

## Integrazione lato PWA

### 1. Reindirizzare al login

Quando l'utente non è autenticato (nessun token valido salvato localmente),
reindirizzalo all'hub passando il nome della tua PWA (quello registrato in
`pwa_clients.name`):

```js
window.location.href = `https://auth.example.it/login?client=routine`;
```

### 2. Ricevere il token

L'hub reindirizza a `https://{domain}{redirect_path}#token=...` (il valore di
`redirect_path` configurato per quella PWA in `pwa_clients`). Nella pagina di
callback, leggi il token dal fragment (mai inviato al server) e ripulisci
subito l'URL:

```js
const hash = new URLSearchParams(window.location.hash.slice(1));
const token = hash.get('token');

if (token) {
    localStorage.setItem('auth_token', token);
    history.replaceState(null, '', window.location.pathname);
}
```

### 3. Recuperare i dati utente / validare il token

```js
const res = await fetch('https://auth.example.it/api/user', {
    headers: { Authorization: `Bearer ${localStorage.getItem('auth_token')}` },
});

if (res.status === 401) {
    // token scaduto/invalido: richiama il passo 1
} else {
    const user = await res.json(); // { id (uuid), name, email }
}
```

Il CORS è già configurato sull'hub per accettare richieste da qualunque
sottodominio `*.example.it` (`config/cors.php`).

### 4. Dati propri della PWA

Ogni PWA gestisce il proprio storage (DB, tabelle, anche motori diversi) in
totale autonomia. L'unico collegamento con l'hub è il campo `id` (UUID)
restituito da `/api/user`: usalo come chiave esterna verso l'utente nei tuoi
dati.

### 5. Token scaduto dopo inattività

Se il token locale scade (30 giorni senza uso), la chiamata a `/api/user`
torna 401: rifai il redirect del passo 1. Se l'hub ha ancora una sessione
attiva (SSO silenzioso), l'utente non vedrà nessun form di login.

## Aggiungere una nuova PWA

Inserisci una riga nella tabella `pwa_clients`:

| campo | esempio |
|---|---|
| `name` | `routine` (va nel parametro `?client=`) |
| `domain` | `routine.example.it` |
| `redirect_path` | `/auth/callback` |

## Notifiche

Ogni nuova registrazione invia una mail a
`MAIL_ADMIN_NOTIFICATION_ADDRESS` (`.env`). In produzione la spedizione usa
`sendmail` locale del server (nessuna credenziale SMTP necessaria); in
locale usa il driver `log`.

## Deploy

Hosting condiviso (cPanel/Plesk) senza accesso shell: il deploy avviene via
FTP + lo scheduler di Laravel. Dettagli completi in [`deploy/`](deploy/):

- `deploy/deploy.sh` — build locale (asset, dipendenze PHP di produzione),
  crea l'archivio e lo carica via FTP.
- `App\Console\Commands\ApplyDeploy` (`app:apply-deploy`), schedulato in
  `routes/console.php` — quando trova un deploy caricato, lo applica
  (estrazione, migration, cache), non tocca mai `.env` né il database.
- `deploy/.env.deploy.example` — credenziali FTP (copiare in
  `deploy/.env.deploy`, mai committare).
- `deploy/env.production.example` — contenuto di riferimento per il `.env`
  di produzione.

**Setup una tantum sul pannello hosting:**
1. Sottodominio `auth.example.it` con document root su `{APP_ABS_PATH}/public`.
2. Cron ogni minuto: `php {APP_ABS_PATH}/artisan schedule:run >> /dev/null 2>&1`
   (unico cron per tutto ciò che è schedulato in Laravel, deploy incluso).
3. Primo deploy: l'app va portata online una prima volta a mano (File
   Manager) prima che lo scheduler possa prendere il controllo — vedi
   `deploy/env.production.example` per il `.env` iniziale.

**Deploy successivi:**

```bash
bash deploy/deploy.sh
```

Verrà applicato entro un minuto dal cron. Log su `storage/logs/deploy.log`
sul server.
