<?php

/**
 * QvChat 文件夹扫描类 - 极简版
 */
class QvChat extends base
{
    private $baseDir;

    public function __construct()
    {
        $baseDir = '../qvChat';
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function getData()
    {
        $data = self::getTimesList();
        $svnData = [];
        foreach($data as $d){
            $timestamp = $d['timestamp'];
            if($ret = $this->isWork($timestamp)){
                $Ymd = date('Ymd', $timestamp); // 相同日期 相同加班区间(早 中 晚)合并在一起
                $d = array_merge($d, $ret); // 合并
                $svnData[$Ymd][$d['name']][] = $d;
            }
        }
        return self::analyze($svnData, '截图');
    }

    /**
     * 获取所有日期和时间
     * @return array [日期 => [时间列表]]
     */
    private function scanAll()
    {
        if(!is_dir($this->baseDir)) p('文件夹地址有误,请检查');
        $result = [];
        $items = scandir($this->baseDir);
        foreach($items as $item){
            if($item === '.' || $item === '..') continue; //排除不需要的目录
            $fullPath = $this->baseDir . '/' . $item;
            // 检查是否是8位数字的日期文件夹
            if(is_dir($fullPath) && preg_match('/^\d{8}$/', $item)){
                $date = $item;
                $times = [];
                $files = glob($fullPath . '/*.png');// 扫描PNG文件
                foreach($files as $file){
                    $filename = basename($file);
                    // 从文件名提取时间
                    if(preg_match('/(\d{1,2}\.\d{2})/', $filename, $matches)){
                        $time = $matches[1];
                        $times[] = [
                            'time' => $time,
                            'full' => $date . ' ' . $time,
                            'timestamp' => $this->convertToTimestamp($date, $time),
                            'filename' => $filename
                        ];
                    }
                }

                if(!empty($times)){
                    usort($times, function($a, $b){ // 按时间排序
                        return strcmp($a['time'], $b['time']);
                    });
                    $result[$date] = $times;
                }
            }
        }
        krsort($result);// 按日期降序排序
        return $result;
    }

    /**
     * 将时间字符串转换为时间戳
     * @param string $date 日期（格式：Ymd）
     * @param string $time 时间（格式：HH.MM）
     * @return int 时间戳
     */
    private function convertToTimestamp($date, $time)
    {
        // 格式转换：20231008 + 19.06 → 2023-10-08 19:06
        $dateFormatted = sprintf(
            '%s-%s-%s',
            substr($date, 0, 4),
            substr($date, 4, 2),
            substr($date, 6, 2)
        );
        $timeFormatted = str_replace('.', ':', $time);
        return strtotime($dateFormatted . ' ' . $timeFormatted);
    }

    /**
     * 获取扁平化的时间列表
     * @return array 所有完整时间字符串
     */
    private function getTimesList()
    {
        $times = [];
        $data = $this->scanAll();
        foreach($data as $date => $timeItems){
            foreach($timeItems as $item){
                $times[] = [
                    'date' => $date,
                    'timestamp' => $item['timestamp'],
                    'filename' => $item['filename']
                ];
            }
        }
        return $times;
    }
}
