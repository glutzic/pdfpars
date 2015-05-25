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

$data['href'] = $baseUrl . $dom->find('ul.products_list li a', 0)->href;

if (!$data['href']) {
    echo json_encode($data);
    return;
}

$urlquery = $data['href'];
$dom = HtmlDomParser::file_get_html($urlquery);

/**
 * Nazwa
 */
$nazwa = $dom->find('h1.product__data__name', 0);
$data['nazwa'] = $nazwa ? $nazwa->innertext : '';

//Format
$data['nazwa'] = str_replace(" - kod " . $sku, "", $data['nazwa']);
$data['nazwa'] = trim($data['nazwa']);
/**
 * Opis
 */
$data['opis'] = array();

foreach ($dom->find('div.product__desc__content p') as $pEl) {
    $inner = $pEl->innertext;
    $opis = preg_replace('/[ ]*<br[ \/><]*/i', "\n", $inner);
    $opis = preg_replace('/[ ]*&#8211;[ ]*/i', '-', $opis);
    //$opis = str_replace(array("<br /> ", "<br< "), "\n", $inner);
    //$opis = str_replace("&#8211; ", "-", $opis);
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
    $isWymiar = strpos($tytul, "wymiar") !== FALSE || strpos($tytul, "rozmiar") !== FALSE || strpos($tytul, "mm") !== FALSE;

    if ($isWymiar) {
        $matches = array();
        preg_match_all('!\d+!', $wartosc, $matches);

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