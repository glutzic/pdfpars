<?php

echo "<pre>";
$txt = file_get_contents('hendi.txt');
$arr = explode("\n", $txt);
$map = array();

function findCodes(array $arr, $from, $to)
{
    $allCodes = array();

    for ($i = $from; $i < $to; $i++) {
        $line = $arr[$i];
        $numbers = array();
        preg_match_all('!\d+!', $line, $numbers);

        if (empty($numbers[0])) continue;

        foreach ($numbers[0] as $number) {
            if (strlen($number) >= 5) {
                $allCodes[] = $number;
            }
        }
    }

    return $allCodes;
}

function findPrices(array $arr, $from, $to)
{
    $allPrices = array();

    for ($i = $from; $i < $to; $i++) {
        $line = $arr[$i];
        $prices = array();
        preg_match_all('!\d+,-!', $line, $prices);

        if (empty($prices[0])) continue;

        foreach ($prices[0] as $price) {
            if (strlen($price) >= 3) {
                $allPrices[] = $price;
            }
        }
    }

    return $allPrices;
}

function getCodePrices(array $codes, array $prices) {
    $codeLen = count($codes);
    $codePrices = array();
    $lengthsEqual = $codeLen == count($prices);

    for ($i = 0; $i < $codeLen; $i++) {
        if ($lengthsEqual && isset($codes[$i]) && isset($prices[$i])) {
            $codePrices[$codes[$i]] = $prices[$i];
        } elseif (isset($codes[$i])) {
            $codePrices[$codes[$i]] = 0;
        }
    }

    return $codePrices;
}

$kodPos = false;
$cenaPos = false;
$endPos = false;
$codes = false;
foreach ($arr as $key => $val) {
    /**
     * Wyszukiwanie 'kod' jesli nie znaleziono wczesniej
     */
    if (!$kodPos && strpos($val, "kod") !== FALSE) {
        $kodPos = $key;
    }

    /**
     * Wyszukiwanie poprzedzajacego 'kod' znacznika 'cena' w celu znalezienia wszystkich kodow
     */
    if ($kodPos && !$cenaPos && strpos($val, "cena") !== FALSE) {
        $cenaPos = $key;
    }

    if ($cenaPos && !$codes) {
        $codes = findCodes($arr, $kodPos, $cenaPos);

        if (!$codes) {
            $kodPos = $endPos = $cenaPos = $codes = $prices = null;
        }
    }

    if ($kodPos && $cenaPos && $codes && !$endPos && strpos($val, "kod") !== FALSE) {
        $endPos = $key;
    }

    if ($codes && $cenaPos && $endPos) {
        $prices = findPrices($arr, $cenaPos, $endPos);
        $codePrices = getCodePrices($codes, $prices);
        $map = $map + $codePrices;
        $kodPos = $endPos;
        $endPos = $cenaPos = $codes = $prices = null;
    }
}

$txtoutput = fopen("hendi2.txt", "w");

foreach ($map as $code => $price) {
    fwrite($txtoutput, trim($code) . " " . trim($price) . "\n");
}

fclose($txtoutput);