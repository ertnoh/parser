<?php
/**
 * Created by PhpStorm.
 * User: ADrushka
 * Date: 04.11.2017
 * Time: 14:45
 */
require_once "ParserTools.php";
require_once "DB.php";
require_once "ExcelTools.php";
ini_set("max_execution_time", "86400");
ini_set("memory_limit", "256M");
$t1 = microtime(true);

$post = $_POST;
if (empty($post) || empty(trim($post['sro']))) {
    error_end_script("Ничего не введено");
}
$sro = trim($post['sro']);
$db = DB::getInstance();
$db->truncate("sro");
$db->truncate("sroItem");

//Начинаем поиск
//---------------------------------------------------------------------------------
$parser = new ParserTools($sro);
$parser->setLinks("http://reestr.nostroy.ru/?u.registrationnumber=$sro&u.fulldescription=&u.place=&brn.id=&fd.id=&u.enabled=");
$seach_page = $parser->getPages();
if (!$seach_page || empty($seach_page)) {
    error_end_script("Неверная ссылка для поиска, обратитесь к администратору");
}
//Получаем СРО массив для записи в базу
$sroArray[] = getSRO($seach_page, $parser);
$db->save("sro", $sroArray);

$links = getLinksItemsSRO($parser);
$parser->setLinks($links);

//Парсим членов СРО
//-------------------------------------------------------------------------------
$SROItemsPagesCommon = $parser->getPages();
if (!$SROItemsPagesCommon || empty($SROItemsPagesCommon)) {
    error_end_script("Члены СРО не найдены");
}
$parser->setHtmlpages($SROItemsPagesCommon);
$SROItemslinks = $parser->parserTableItems();

//Парсим конечные страницы, так как их много - в цикле
for ($i = 0; $i < count($SROItemslinks); $i++) {
    $page = $parser->getPages($SROItemslinks[$i]);
    if ($page && isset($page[0]) && !empty($page[0])) {
        $parser->setHtmlpages($page[0]);
        $ItemsArray[] = $parser->parseSROItem();
    }

    if ($i % 100 == 0 || $i == (count($SROItemslinks) - 1)) {
        $db->save("sroItem", $ItemsArray);
        unset($ItemsArray);
    }
}

$file = new ExcelTools();
$path = $file->createFile();
$t2 = microtime(true);
echo "Парсинг прошёл успешно! <br>";
echo "Время работы скрипта: " .intdiv(($t2 - $t1), 60) ." мин " . ($t2 - $t1)%60 . " сек<br>";
echo "Файл для скачивания: <a href='$path'><button>Скачать</button></a>";


//Получаем список ссылок для членов СРО
function getLinksItemsSRO($parser)
{
    //-------------------------------------------------------------------------------
    $link = $parser->formFirstLinkSROItem();
    $parser->setLinks($link);
    $itemsPageFirst = $parser->getPages();
    if (!$itemsPageFirst || empty($itemsPageFirst)) {
        error_end_script("Члены СРО не найдены");
    }
    $parser->setHtmlpages($itemsPageFirst[0]);
    $links = $parser->getPagination();
    if (empty($links)) {
        error_end_script("Члены СРО не найдены");
    }
    return $links;
//-------------------------------------------------------------------------------
}


//Получаем и парсим СРО
function getSRO($seach_page, $parser)
{
    $parser->setDomen("http://reestr.nostroy.ru");
    $parser->setHtmlpages($seach_page[0]);
    $sroMainlink = $parser->parserTable();
    if (!$sroMainlink || empty($sroMainlink)) {
        error_end_script("СРО не найдено");
    }
    $parser->setLinks($sroMainlink[0]);
    $sroPage = $parser->getPages();
    if (!$sroPage || empty($sroPage)) {
        error_end_script("СРО не найдено");
    }
    $parser->setHtmlpages($sroPage[0]);
    $sroArray = $parser->parseRSO();
    if (!$sroArray || empty($sroArray)) {
        error_end_script("Ошибка при парсинге страницы СРО, обратитесь к администратору");
    }
    return $sroArray;
}


function error_end_script($message)
{
    echo $message;
    exit();
}