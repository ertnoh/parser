<?php

/**
 * Created by PhpStorm.
 * User: ADrushka
 * Date: 04.11.2017
 * Time: 22:39
 */
require_once('PHPExcel/Classes/PHPExcel.php');
require_once('PHPExcel/Classes/PHPExcel/Writer/Excel5.php');
require_once "DB.php";

class ExcelTools
{
    private $th = [
        "СРО",
        "Компания",
        "ИНН",
        "Адрес компании",
        "Контакты",
        "Директор",
    ];

    public function createFile()
    {
        $conn = DB::getInstance();
        $sro = $conn->getData("name, gosNumber", "sro", "id = 1");
        $sro = $sro[0];
        $sroItems = $conn->getData("shortName, inn, address, phone, fio", "sroItem");
        $header = $this->th;

        // Создаем объект класса PHPExcel
        $xls = new PHPExcel();
        // Устанавливаем индекс активного листа
        $xls->setActiveSheetIndex(0);
        // Получаем активный лист
        $sheet = $xls->getActiveSheet();
        // Подписываем лист
        $sheet->setTitle('СРО');
        //Заголовки
        $sheet->setCellValue("A1", $header[0]);
        $sheet->setCellValue("B1", $header[1]);
        $sheet->setCellValue("C1", $header[2]);
        $sheet->setCellValue("D1", $header[3]);
        $sheet->setCellValue("E1", $header[4]);
        $sheet->setCellValue("F1", $header[5]);

        $j = 2;
        foreach ($sroItems as $item) {
            $sheet->setCellValueByColumnAndRow(0, $j, $sro['name']);
            $i = 1;
            foreach ($item as $val) {
                $sheet->setCellValueByColumnAndRow($i, $j, $val);
                $i++;
            }
            $j++;
        }
        $objWriter = new PHPExcel_Writer_Excel5($xls);
        $path = "files/".$sro['gosNumber'].md5(microtime(true)) . ".xls";
        $objWriter->save($path);
        return $path;
    }

}