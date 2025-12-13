<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class Qv extends base
{
    public function getData()
    {
        $qvData = [];
        foreach(glob("../qv/*.xlsx") as $filename){
            $qvData = array_merge(self::getOne($filename), $qvData);
        }
        return $qvData;
    }

    private function getOne($inputFileName)
    {
        if(!file_exists($inputFileName)) die('文件不存在: ' . $inputFileName);
        $spreadsheet = IOFactory::load($inputFileName);// 加载Excel文件
        $sheet = $spreadsheet->getActiveSheet();       // 获取第一个工作表
        $rows = $sheet->getRowIterator();              // 获取工作表的行迭代器

        $excelData = [];
        foreach($rows as $row){                                // 遍历每一行
            $cellIterator = $row->getCellIterator();           // 获取行中的单元格迭代器
            $cellIterator->setIterateOnlyExistingCells(false); // 这个设置会遍历所有单元格，即使为空
            $rowData = [];                                     // 存储一行的数据
            foreach($cellIterator as $cell){
                $rowData[] = $cell->getValue();
            }
            $excelData[] = $rowData;
        }
        $titleMap = array_flip($this->mergeHeaders($excelData[2], $excelData[3]));
        // $needTitle = ['时间', '考勤概况-最早', '考勤概况-最晚', '考勤概况-实际工作时长(小时)', '考勤概况-考勤结果', '下班1-打卡时间', '下班1-打卡状态', '打卡时间记录'];
        $needTitle = ['时间', '考勤概况-最早', '考勤概况-最晚'];
        $data = array_slice($excelData, 4); // 数据真正开始行
        $qvData = [];
        foreach($data as $value){
            $arr = [];
            foreach($needTitle as $field){
                $arr[$field] = $value[$titleMap[$field]];
            }
            $date = explode(' ', $arr['时间'])[0];
            $arr['日期'] = str_replace('/', '', $date);
            $Ymd = str_replace('/', '-', $date);
            $arr['晚上加班时长'] = $this->calcMinutes($Ymd, '晚', latest: $arr['考勤概况-最晚']);
            $arr['早上加班时长'] = $this->calcMinutes($Ymd, '早', $arr['考勤概况-最早']);

            $qvData[] = $arr;
        }
        // 排序
        foreach($qvData as &$value){
            $key = '日期';// 按指定key降序排列
            uksort($value, function($a, $b) use ($key){
                $valueA = $a[$key] ?? null;
                $valueB = $b[$key] ?? null;

                if($valueA === $valueB) return 0;
                return ($valueA > $valueB) ? -1 : 1; // 降序排列
            });
        }
        return $qvData;
    }

    // 计算加班时长(周末/工作日)
    private function calcMinutes($Ymd, $name, $earliest = 0, $latest = 0)
    {
        $isWeekday = self::isWeekEnd(str_replace('-', '', $Ymd));
        if($name === '早'){
            // $earliest 可能是(未打卡/ --) 或者在早上忘记打卡 下午才打卡(20250614)
            if($isWeekday && str_contains($earliest, ':') && $earliest < '12:00'){
                $start = new DateTime($Ymd . ' ' . $earliest);
                $end = DateTime::createFromFormat('Y-m-d H:i', $Ymd . ' 12:00');
                $diff = $start->diff($end);// 计算时间差(晚上加班)
                $endMinutes = ($diff->h * 60) + $diff->i; // 将小时转换为分钟并加上剩余分钟
            }else{
                $endMinutes = 0;
            }
        }else{ //晚
            if($isWeekday){
                $start = new DateTime($Ymd . ' 14:00');
            }else{
                $start = new DateTime($Ymd . ' 18:30');
            }
            if(str_contains($latest, '次日')){
                $qvEndDate = str_replace('次日', '', $latest);
                $end = DateTime::createFromFormat('Y-m-d H:i', $Ymd . ' ' . $qvEndDate)->modify('+1day');// 结束时间设为次日
            }else{
                $end = DateTime::createFromFormat('Y-m-d H:i', $Ymd . ' ' . $latest);
            }
        }
        if(isset($end) && $end){
            $diff = $start->diff($end);// 计算时间差(晚上加班)
            $endMinutes = ($diff->h * 60) + $diff->i; // 将小时转换为分钟并加上剩余分钟
        }
        return $endMinutes ?? 0;
    }

    private function mergeHeaders($row1, $row2)
    {
        $lastValue = '';
        foreach($row1 as &$cell){ // 处理父标题 填充空数据
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

}




