<?php
/**
 * Estrattore di emergenza, SENZA passare da vendor/autoload.php o da
 * Laravel: quando l'autoload di Composer è rotto, "php artisan" stesso
 * non parte più, quindi il comando app:apply-deploy (che gira dentro
 * artisan) non è raggiungibile. Questo script usa solo PHP nativo
 * (ZipArchive) per estrarre storage/app/deploy/release.zip sopra
 * l'app corrente, sovrascrivendo anche vendor/ con quello completo
 * costruito in locale da deploy.ps1/deploy.sh (composer install già
 * eseguito lì, non serve rifarlo sul server).
 *
 * Dopo l'estrazione l'autoload dovrebbe tornare sano e "php artisan"
 * ricominciare a funzionare, a quel punto le migration/cache le fa
 * server-fix.sh normalmente via artisan.
 *
 * Uso: php raw-extract.php
 */

$appRoot = dirname(__DIR__);
$deployDir = $appRoot.'/storage/app/deploy';
$trigger = $deployDir.'/deploy.trigger';
$archive = $deployDir.'/release.zip';

function out(string $msg): void
{
    // Niente STDOUT: sul server questo script gira sotto PHP CGI (non CLI),
    // dove la costante STDOUT non è definita.
    echo '['.gmdate('Y-m-d H:i:s').' UTC] '.$msg.PHP_EOL;
}

if (! file_exists($trigger)) {
    out('Nessun deploy.trigger trovato, niente da estrarre.');
    exit(0);
}

if (! file_exists($archive)) {
    out('ERRORE: trigger presente ma release.zip mancante.');
    exit(1);
}

if (! class_exists('ZipArchive')) {
    out('ERRORE: estensione PHP Zip non disponibile, impossibile estrarre.');
    exit(1);
}

out('Estrazione di '.$archive.' su '.$appRoot.' in corso...');

$zip = new ZipArchive;

if ($zip->open($archive) !== true) {
    out('ERRORE: impossibile aprire release.zip.');
    exit(1);
}

if (! $zip->extractTo($appRoot)) {
    out('ERRORE: estrazione fallita.');
    $zip->close();
    exit(1);
}

$zip->close();

out('Estrazione completata.');

$sqlite = $appRoot.'/database/database.sqlite';
if (! file_exists($sqlite)) {
    touch($sqlite);
    out('Creato database/database.sqlite (primo deploy).');
}

@unlink($archive);
@unlink($trigger);

out('release.zip e deploy.trigger rimossi. Ora dovrebbe funzionare "php artisan".');
