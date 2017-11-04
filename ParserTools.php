<?php
/**
 * Created by PhpStorm.
 * User: ADrushka
 * Date: 04.11.2017
 * Time: 14:46
 */
require_once 'phpQuery/phpQuery/phpQuery.php';

class ParserTools{
    private $sro;

    private $main_link_rel;

    private $links;

    private $htmlpages;

    private $domen;

    private $sroDBFields = [
        'Полное наименование:' => 'name',
        'Сокращенное наименование' => 'shortName',
        'Номер в гос. реестре:' => 'gosNumber',
        'ИНН:' => 'inn',
        'ОГРН:' => 'ogrn',
        'ОКРУГ:' => 'okrug',
        'Адрес местонахождения:' => 'address',
        'Телефон:' => 'phone',
        'E-mail:' => 'email',
        'Адрес сайта:' => 'site',
        'Руководитель коллегиального органа СРО:' => 'kollegSro',
        'Руководитель исполнительного органа СРО:' => 'ispolitSro',
    ];

    private $sroDBFieldsItems = [
        'СРО' => 'sroName',
        'Тип члена СРО' => 'type',
        'Полное наименование:' => 'name',
        'Сокращенное наименование:' => 'shortName',
        'Статус члена:' => 'status',
        'Регистрационный номер члена в реестре СРО:' => 'regNumber',
        'Дата регистрации в реестре СРО:' => 'dateReestr',
        'Дата прекращения членства:' => 'dateStop',
        'Основание прекращения членства:' => 'purposeStop',
        'ОГРН:' => 'ogrn',
        'ИНН:' => 'inn',
        'Дата государственной регистрации:' => 'dateReg',
        'Номер контактного телефона:' => 'phone',
        'Адрес местонахождения юридического лица:' => 'address',
        'ФИО, осуществляющего функции единоличного исполнительного органа юридического лица и (или) руководителя коллегиального исполнительного органа юридического лица:' => 'fio',
        'Сведения о соответствии члена СРО условиям членства в СРО, предусмотренным законодательством РФ и (или) внутренними документами СРО:' => 'dop',
        'Обновлено:' => 'updateSRODate',
    ];

    public function __construct(string $sro)
    {
        $this->sro = $sro;
    }


    /**
     * Парсит страницу СРО члена по странице, указанной в $htmlpages
     *
     * @return mixed
     */
    public function parseSROItem(){
        $page = $this->getHtmlpages();
        $pq = phpQuery::newDocument($page);
        //в этих дивах находится содержание страницы
        $fields = $pq->find(".items tr");
        foreach($fields as $field){
            $html = pq($field)->html();
            $newstr = phpQuery::newDocument($html);
            $title = $newstr->find('th');
            $title = pq($title)->text();
            $val = $newstr->find('td');
            $val = pq($val)->text();
            unset($newstr);
            if(isset($this->sroDBFieldsItems[trim($title)])){
                $dbField = $this->sroDBFieldsItems[trim($title)];
                $ret[$dbField] = trim(htmlspecialchars($val));
            }
        }
        return $ret;
    }



    /**
     * Получает пагинацию на странице и формирует ссылки членов СРО
     *
     * @return int|string
     */
    public function getPagination(){
        $page = $this->getHtmlpages();
        $pq = phpQuery::newDocument($page);
        //в этих дивах находится содержание страницы
        $ul = $pq->find(".pagination li");
        $maxPages = 0;
        foreach ($ul as $item){
            $li = pq($item);
            $num = trim($li->text());
            if(is_numeric($num)){
                $maxPages = $num;
            }
        }

        //Формируем ссылки
        $links = [];
        for ($maxPage = 1; $maxPage <= $maxPages; $maxPage++) {
            $links[] = $this->domen."/reestr".$this->main_link_rel."/members?sort=m.id&direction=desc&page=$maxPage";
        }
        return $links;
    }


    /**
     * Формирует ссылку на 1 страницу членов сро для получения пагинации
     * в дальнейшем
     *
     * @return string|void
     */
    public function formFirstLinkSROItem(){
        if(!$this->domen){
            return null;
        }
        $l = $this->domen."/reestr".$this->main_link_rel."/members?sort=m.id&direction=desc&page=1";
        return $l;
    }

    /**
     * Парсит страницу СРО по странице, указанной в $htmlpages
     *
     * @return mixed
     */
    public function parseRSO(){
        $page = $this->getHtmlpages();
        $pq = phpQuery::newDocument($page);
        //в этих дивах находится содержание страницы
        $fields = $pq->find(".field-row");
        foreach($fields as $field){
            $html = pq($field)->html();
            $newstr = phpQuery::newDocument($html);
            $title = $newstr->find('.field-title');
            $title = pq($title)->text();
            $val = $newstr->find('.field-data');
            $val = pq($val)->text();
            unset($newstr);
            if(isset($this->sroDBFields[$title])){
                $dbField = $this->sroDBFields[$title];
                $ret[$dbField] = trim(htmlspecialchars($val));
            }
        }
        return $ret;
    }

    /**
     * Ищет ссылки в таблице и формирует все ссылки
     *
     * @return array|null
     */
    public function parserTable(){
        $page = $this->getHtmlpages();
        $pq = phpQuery::newDocument($page);
        $links = $pq->find(".sro-link");
        $ret = null;
        foreach ($links as $link) {
            $el = pq($link);
            $rel = $el->attr('rel');
            if(!$rel){
                continue;
            }
            $ret[] = $this->domen.$rel;
            $this->main_link_rel = $rel;
        }
        return $ret;
    }


    /**
     * Ищет ссылки в таблице и формирует все ссылки
     * для массива страниц
     *
     * @return array|null
     */
    public function parserTableItems(){
        $pages = $this->getHtmlpages();
        $ret = null;
        foreach($pages as $page){
            $pq = phpQuery::newDocument($page);
            $links = $pq->find(".sro-link");
            foreach ($links as $link) {
                $el = pq($link);
                $rel = $el->attr('rel');
                if(!$rel){
                    continue;
                }
                $ret[] = $this->domen.$rel;
                $this->main_link_rel = $rel;
            }
        }
        return $ret;
    }



    /**
     * Получение произвольных страниц из массива ссылок или из ссылки
     *
     * @param $links
     * @return array
     */
    public function getPages($links = null){
        if(!$links){
            $links = $this->getLinks();
        }
        $ret = [];
        if(is_string($links)){
            $ret[] = file_get_contents($links);
        }
        if (is_array($links)){
            foreach($links as $l){
                $ret[] = file_get_contents($l);
            }
        }
        return $ret;
    }


    /**
     * @return string
     */
    public function getSro(): string
    {
        return $this->sro;
    }

    /**
     * @param string $sro
     */
    public function setSro(string $sro)
    {
        $this->sro = $sro;
    }

    /**
     * @return mixed
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param mixed $links
     */
    public function setLinks($links)
    {
        $this->links = $links;
    }

    /**
     * @return mixed
     */
    public function getHtmlpages()
    {
        return $this->htmlpages;
    }

    /**
     * @param mixed $htmlpages
     */
    public function setHtmlpages($htmlpages)
    {
        $this->htmlpages = $htmlpages;
    }

    /**
     * @return mixed
     */
    public function getDomen()
    {
        return $this->domen;
    }

    /**
     * @param mixed $domen
     */
    public function setDomen($domen)
    {
        $this->domen = $domen;
    }

}