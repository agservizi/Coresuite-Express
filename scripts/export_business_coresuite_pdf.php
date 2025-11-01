<?php
declare(strict_types=1);

// Script di utilità per esportare la guida business-coresuite.md in PDF sfruttando Dompdf.
// Uso: php scripts/export_business_coresuite_pdf.php

require __DIR__ . '/../vendor/autoload.php';

$sourceHtml = realpath(__DIR__ . '/../docs/integrations/business-coresuite.html');
if ($sourceHtml === false || !is_readable($sourceHtml)) {
    fwrite(STDERR, "[errore] Impossibile leggere il file HTML di origine.\n");
    exit(1);
}

$html = file_get_contents($sourceHtml);
if ($html === false) {
    fwrite(STDERR, "[errore] Lettura del file HTML fallita.\n");
    exit(1);
}

$dompdfClass = 'Dompdf\\Dompdf';
if (!class_exists($dompdfClass)) {
    fwrite(STDERR, "[errore] Libreria Dompdf non disponibile.
");
    exit(1);
}

/** @var object $dompdf */
$dompdf = new $dompdfClass(['isRemoteEnabled' => true]);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$outputPath = realpath(__DIR__ . '/..');
if ($outputPath === false) {
    fwrite(STDERR, "[errore] Percorso di destinazione non trovato.\n");
    exit(1);
}

$pdfPath = $outputPath . '/docs/integrations/business-coresuite.pdf';

if (file_put_contents($pdfPath, $dompdf->output()) === false) {
    fwrite(STDERR, "[errore] Salvataggio del PDF fallito.\n");
    exit(1);
}

echo "PDF generato con successo in docs/integrations/business-coresuite.pdf\n";
