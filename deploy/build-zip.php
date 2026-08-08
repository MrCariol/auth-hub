<?php

/**
 * Crea $argv[2] (zip) a partire dal contenuto di $argv[1] (cartella).
 * Usato da deploy.sh: eseguendolo con PHP invece che con un tool esterno
 * (zip/tar) evitiamo dipendenze extra e i problemi di ZipArchive/PharData
 * con i percorsi lunghi tipici di vendor/.
 */

[, $source, $destination] = $argv;

if (! is_dir($source)) {
    fwrite(STDERR, "Cartella sorgente non trovata: {$source}\n");
    exit(1);
}

if (file_exists($destination)) {
    unlink($destination);
}

$zip = new ZipArchive;

if ($zip->open($destination, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Impossibile creare {$destination}\n");
    exit(1);
}

$source = rtrim($source, '/\\');
$sourceLength = strlen($source) + 1;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $localName = str_replace('\\', '/', substr($item->getPathname(), $sourceLength));

    if ($item->isDir()) {
        $zip->addEmptyDir($localName);
    } else {
        $zip->addFile($item->getPathname(), $localName);
    }
}

$zip->close();

echo "Archivio creato: {$destination}\n";
