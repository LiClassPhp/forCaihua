<?php

/**
 * meeting 统计开会培训日期
 */
class Meeting extends base
{
    private $baseDir;

    public function __construct()
    {
        $baseDir = '../meeting';
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function getData()
    {
        $data = self::scanAll();
        return array_count_values(array_column($data, 'Ymd')); //计算日期下有多少张截图
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
            if(is_dir($fullPath) && preg_match('/^\d{4}$/', $item)){
                $files = glob($fullPath . '/*.png');// 扫描PNG文件
                foreach($files as $file){
                    $filename = basename($file);
                    // 从文件名提取时间
                    if(preg_match('/\d{8}/', $filename, $matches)){
                        $Ymd = (int)$matches[0];
                        $result[] = [
                            'Ymd' => $Ymd,
                            'full' => $filename,
                            'timestamp' => strtotime($Ymd),
                            'filename' => $filename
                        ];
                    }
                }

                if(!empty($result)){
                    usort($result, function($a, $b){ // 按时间排序
                        return strcmp($a['Ymd'], $b['Ymd']);
                    });
                }
            }
        }
        return $result;
    }
}
