<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class Qv{
    public function getOne($inputFileName){
        if(!file_exists($inputFileName)) die('文件不存在: ' . $inputFileName);
        $spreadsheet = IOFactory::load($inputFileName);// 加载Excel文件
        $sheet = $spreadsheet->getActiveSheet();// 获取第一个工作表
        $rows = $sheet->getRowIterator();// 获取工作表的行迭代器
        $excelData = [];
        foreach($rows as $row){// 遍历每一行
            $cellIterator = $row->getCellIterator();// 获取行中的单元格迭代器
            $cellIterator->setIterateOnlyExistingCells(false); // 这个设置会遍历所有单元格，即使为空
            $rowData = [];    // 存储一行的数据
            foreach($cellIterator as $cell){
                $rowData[] = $cell->getValue();
            }
            $excelData[] = $rowData;
        }
        $titleMap = array_flip($this->mergeHeaders($excelData[2], $excelData[3]));
        $needTitle = ['时间', '考勤概况-最早', '考勤概况-最晚', '考勤概况-实际工作时长(小时)', '考勤概况-考勤结果', '下班1-打卡时间', '下班1-打卡状态', '打卡时间记录'];
        $data = array_slice($excelData, 4); //数据真正开始行
        $temp = [];
        foreach($data as $value){
            $arr = [];
            foreach($needTitle as $field){
                $arr[$field] = $value[$titleMap[$field]];
            }
            $temp[] = $arr;
        }
        return $temp;
    }

    private function mergeHeaders($row1, $row2){
        $lastValue = '';
        foreach($row1 as &$cell){ //处理父标题 填充空数据
            if($cell && !empty(trim($cell))){
                $lastValue = $cell;
            }
            $cell = $lastValue;
        }
        $merged = [];
        $maxLength = max(count($row1), count($row2));
        for($i = 0; $i < $maxLength; $i++){
            $cell1 = $row1[$i] ?? '';
            $cell2 = $row2[$i] ?? '';
            if(!empty($cell1) && !empty($cell2)){
                $merged[] = $cell1 . '-' . $cell2;
            }else if(!empty($cell1)){
                $merged[] = $cell1;
            }else if(!empty($cell2)){
                $merged[] = $cell2;
            }else{
                $merged[] = '';
            }
        }
        return $merged;
    }

    public function getData(){
        $qvData = [];
        foreach (glob("../qv/*.xlsx") as $filename) {
            $qvData= array_merge($qvData,self::getOne($filename));
        }
        return $qvData;
    }

}




