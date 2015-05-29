<?php
require_once 'vendor/autoload.php';

use Sunra\PhpSimple\HtmlDomParser;

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = null;
$sku = isset($_REQUEST['sku']) ? $_REQUEST['sku'] : null;
$baseUrl = "http://www.hendi.pl";
$urlquery = "http://www.hendi.pl/product/search.html?query={$sku}&category_id=&limit=";
//$searchContent = file_get_contents($urlquery);
$dom = HtmlDomParser::file_get_html($urlquery);

/**
 * Znajdz link po wyszukaniu produktow po SKU
 */

$links = $dom->find('ul.products_list li a');
$linksCount = count($links);
foreach ($links as $key => $link) {
    if (strpos($link->href, $sku) !== FALSE) {
        $data['href'] = $baseUrl . $link->href;
        break;
    }

    if ($key == $linksCount - 1) {
        $data['href'] = $baseUrl . $link->href;
        break;
    }
}

//$data['href'] = $baseUrl . $dom->find('ul.products_list li a', 0)->href;

if (!$data['href']) {
    echo json_encode($data);
    return;
}

$urlquery = $data['href'];
$dom = HtmlDomParser::file_get_html($urlquery);


/**
 * SKU
 */
$data['sku'] = $sku;

/**
 * Nazwa
 */
$nazwa = $dom->find('h1.product__data__name', 0);
$data['nazwa'] = $nazwa ? $nazwa->innertext : '';

//Format
$data['nazwa'] = str_replace(" - kod " . $sku, "", $data['nazwa']);
$data['nazwa'] = str_replace("kod " . $sku, "", $data['nazwa']);
$data['nazwa'] = str_replace(" - " . $sku, "", $data['nazwa']);
$data['nazwa'] = trim($data['nazwa']);
/**
 * Opis
 */
$data['opis'] = array();

foreach ($dom->find('div.product__desc__content p') as $pEl) {
    $inner = $pEl->innertext;
    $opis = preg_replace('/[ ]*[< ]*br[ \/><]*/i', "\n", $inner);
    $opis = preg_replace('/[ ]*&#8211;[ ]*/i', '-', $opis);
    $opis = str_replace("\n\n", "\n", $opis);
    $opis = trim(htmlspecialchars_decode($opis));
    $data['opis'][] = $opis;
}

$data['opis'][] = "-H " . $sku;
$data['opis'] = implode("\n", $data['opis']);

/**
 * Wymiary
 */

function parseWymiary($tytul, $wartosc, &$data)
{
    /**
     * Wymiary
     */

    if (empty($data['wymiary'])) {
        $data['wymiary'] = array();
    }

    if (empty($data['moc'])) {
        $data['moc'] = 0;
    }

    $matches = array();
    preg_match_all('!\d+!', $wartosc, $matches);

    $isWysokosc = count($matches[0]) && strpos($tytul, "wysokość") !== FALSE;
    $isSzerokosc = count($matches[0]) && strpos($tytul, "szerokość") !== FALSE;
    $isDlugosc = count($matches[0]) && (strpos($tytul, "głębokość") !== FALSE || strpos($tytul, "długość") !== FALSE);
    $isMoc = count($matches[0]) && (strpos($tytul, "moc") !== FALSE || strpos($tytul, "(w)") !== FALSE);
    $isNapiecie = count($matches[0]) == 1 && (strpos($tytul, "napięcie") !== FALSE || strpos($tytul, "(V)") !== FALSE || strpos($tytul, "(v)") !== FALSE);
    $isWymiar = strpos($tytul, "wymiar") !== FALSE || strpos($tytul, "rozmiar") !== FALSE && !$isWysokosc && !$isSzerokosc && !$isDlugosc && !$isMoc;
    $pojedynczeWymiary = array();

    if ($isWymiar) {
        if (count($matches[0]) == 3) {
            $data['wymiary'] = array(
                'dl' => $matches[0][0],
                'szer' => $matches[0][1],
                'wys' => $matches[0][2],
            );
        } elseif (count($matches[0]) == 1) {
            $data['wymiary'] = array(
                'dl' => $matches[0][0],
            );
        } elseif (count($matches[0]) == 2) {
            $data['wymiary'] = array(
                'dl' => $matches[0][0],
                'szer' => $matches[0][1],
            );
        }
    }

    if ($isWysokosc) {
        $pojedynczeWymiary['wys'] = $matches[0][0];
    }

    if ($isSzerokosc) {
        $pojedynczeWymiary['szer'] = $matches[0][0];
    }

    if ($isDlugosc) {
        $pojedynczeWymiary['dl'] = $matches[0][0];
    }

    $data['wymiary'] = array_merge($pojedynczeWymiary, $data['wymiary']);

    if ($isMoc) {
        // Usun czesc 2x
        if (count($matches[0]) == 2) {
            $matches[0][0] =  $matches[0][1];
        }

        if (strlen($matches[0][0]) >= 3 && strpos($matches[0][0], '.') === false) {
            $moc = number_format(intval($matches[0][0]) / 1000, 2);
        } elseif (strpos($matches[0][0], '.') !== false) {
            $moc = floatval($matches[0][0]);
        }

        $data['moc'] = isset($moc) ? $moc : 0;
    }

    if ($isNapiecie) {
        $data['napiecie'] = intval($matches[0][0]);
    }
}

$parametryTrs = $dom->find('table.product_description_table tr');

foreach ($parametryTrs as $parametrTr) {
    $tytul = $parametrTr->find('th', 0)->innertext;
    $wartosc = $parametrTr->find('td', 0)->innertext;

    parseWymiary($tytul, $wartosc, $data);
}

/**
 * Zdjecie
 */

$zdjecieUrl = $baseUrl . "/" . $dom->find('div.product__images__big a img.slider__list__item__anchor__img', 0)->src;
$data['zdjecie'] = $zdjecieUrl;

echo json_encode($data);