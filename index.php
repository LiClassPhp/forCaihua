<?php
require 'Base.php';
require 'php.php';
require 'Qv.php';
require 'SvnLog.php';
require 'NginxLog.php';

class Analyzer
{

    // 合并企业微信打卡记录和SVN代码提交记录
    public function mergeData($svnData, $qvData)
    {
        $mergedArray = [];// 创建合并后的数组
        foreach($svnData as $item){// 处理 svnLog日志
            $standardDate = self::convertDateToStandard($item['date'], 'svn');
            if(!isset($mergedArray[$standardDate])){
                $mergedArray[$standardDate] = ['date' => $standardDate, 'qv_data' => [], 'svn_data' => []];
            }
            $mergedArray[$standardDate]['svn_data'][] = $item;
        }

        foreach($qvData as $item){// 处理企业微信打卡记录
            $standardDate = self::convertDateToStandard($item['时间'], 'qv');
            if(!isset($mergedArray[$standardDate])){
                $mergedArray[$standardDate] = ['date' => $standardDate, 'qv_data' => [], 'svn_data' => []];
            }
            $mergedArray[$standardDate]['qv_data'][] = $item;
        }
        // $mergedArray= ['2025-09-21' =>$mergedArray['2025-09-21']];
        // p($mergedArray);
        $aFinal = [];     // 最终数组
        foreach($mergedArray as $value){
            foreach($value['svn_data'] as $val){
                $aFinal[] = array_merge($value['qv_data'][0] ?? [], $val ?? []);// qv打卡只有一条 所以可以用0；
            }
        }
        usort($aFinal, function($a, $b){ // 排序
            $aTime = $bTime = 0;
            if(isset($a['时间']) && $a['时间']){
                $aTime = strtotime(str_replace('/', '-', explode(' ', $a['时间'])[0]));
            }elseif($a['date']){
                $aTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $a['date'])->getTimestamp();
            }

            if(isset($b['时间']) && $b['时间']){
                $bTime = strtotime(str_replace('/', '-', explode(' ', $b['时间'])[0]));
            }elseif($b['date']){
                $bTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $b['date'])->getTimestamp();
            }
            return $aTime - $bTime;
        });
        return $aFinal;
    }

    // 转换日期格式为统一格式进行匹配
    public function convertDateToStandard($dateStr, $type)
    {
        if($type === 'qv'){
            // 处理 arr1 的日期格式：2023/06/01 星期四 -> 2023-06-01
            $datePart = explode(' ', $dateStr)[0];
            return date('Y-m-d', strtotime(str_replace('/', '-', $datePart)));
        }else{
            // 处理 arr2 的日期格式：2025年9月1日 16:33:45 -> 2025-09-01
            $datePart = explode(' ', $dateStr)[0];
            // 将中文日期转换为标准格式
            $datePart = str_replace(['年', '月', '日'], ['-', '-', ''], $datePart);
            // 处理月份和日期为两位数
            $parts = explode('-', $datePart);
            if(count($parts) === 3){
                $parts[1] = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $parts[2] = str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                return implode('-', $parts);
            }
            return $datePart;
        }
    }

    // 获取最终需要导出Excel的数据
    public function getExcelData()
    {
        $qvData = (new Qv())->getData();
        $svnData = (new SvnLog())->getData();
        $nginxData = (new NginxLog())->getData();
        p($nginxData);
        $data = self::mergeData($svnData, $qvData); // 合并企业微信打卡记录和SVN代码提交记录
        return [];
    }
}

$analyzerObj = new Analyzer();
$result = $analyzerObj->getExcelData();// 执行分析加班时长

$data[] = ['日期', '提交备注', '提交文件', '最后一次代码提交日期', '加班时长', '加班时长说明', '加班费用', '日期类型', '打卡-时间', '打卡-考勤概况-最早', '打卡-考勤概况-最晚', '打卡-时间考勤概况-实际工作时长(小时)', '打卡-考勤概况-考勤结果', '打卡-下班1-打卡时间', '打卡-下班1-打卡状态', '打卡-打卡时间记录'];
foreach($result as $value){
    $data[] = array_values($value);
}

$filePath = 'export_data.csv';                   // 文件保存路径
$file = fopen($filePath, 'w');                   // 打开文件句柄
fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));// 写入BOM头解决中文乱码
foreach($data as $row){
    fputcsv($file, $row);// 写入数据
}
fclose($file);// 关闭文件
echo "CSV文件已生成到: " . realpath($filePath);
