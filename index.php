<?php
require_once 'vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();

$pdf = $parser->parseFile('HENDI_2015.pdf');
$txtoutput = fopen("HENDI_2015.txt", "w");

foreach ($pdf->getPages() as $no => $page) {
    $line = mb_convert_encoding($page->getText(), "UTF-8", "auto");
    fwrite($txtoutput, "[page:" . $no . "]" . "\n" . $line . "\n");
}

fclose($txtoutput);
echo 'done';