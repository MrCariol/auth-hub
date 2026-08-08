<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;
use ZipArchive;

#[Signature('app:apply-deploy')]
#[Description('Se presente un deploy.trigger caricato via FTP, estrae release.zip e aggiorna l\'app')]
class ApplyDeploy extends Command
{
    /**
     * Eseguito dallo scheduler Laravel (routes/console.php), non a mano.
     * Il trigger e l'archivio sono caricati da deploy/deploy.sh in
     * storage/app/deploy/. Non tocca mai .env né il database, che deploy.sh
     * non include nell'archivio.
     */
    public function handle(): int
    {
        $deployDir = storage_path('app/deploy');
        $trigger = $deployDir.'/deploy.trigger';
        $archive = $deployDir.'/release.zip';

        if (! file_exists($trigger)) {
            return self::SUCCESS;
        }

        if (! file_exists($archive)) {
            $this->error('Trigger presente ma release.zip mancante.');
            @unlink($trigger);

            return self::FAILURE;
        }

        $this->info('Deploy avviato');

        try {
            $zip = new ZipArchive;

            if ($zip->open($archive) !== true) {
                throw new \RuntimeException('Impossibile aprire release.zip');
            }

            if (! $zip->extractTo(base_path())) {
                throw new \RuntimeException('Estrazione fallita');
            }

            $zip->close();

            $this->info('Estrazione completata');

            $sqlite = database_path('database.sqlite');
            if (! file_exists($sqlite)) {
                touch($sqlite);
                $this->info('Creato database/database.sqlite (primo deploy)');
            }

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            $this->info('Migrate + cache completati con successo');

            @unlink($archive);
        } catch (Throwable $e) {
            $this->error('ERRORE: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($trigger);
        }

        return self::SUCCESS;
    }
}
