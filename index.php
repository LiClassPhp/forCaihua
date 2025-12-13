<?php
require 'Base.php';
require 'php.php';
require 'Qv.php';
require 'SvnLog.php';
require 'NginxLog.php';

class Analyzer extends Base
{
    // 获取最终需要导出Excel的数据
    public function getExcelData()
    {
        $qvData = (new Qv())->getData();
        $svnData = (new SvnLog())->getData();
        $nginxData = (new NginxLog())->getData();
        $data = self::mergeData($svnData, $qvData, $nginxData); // 合并企微 SvnLog NginxLog
        return self::format($data);
    }

    // 合并企业微信打卡记录和SVN代码提交记录
    private function mergeData($svnData, $qvData, $nginxData)
    {
        $mergedArray = [];// 创建合并后的数组
        foreach($qvData as $item){// 处理企业微信打卡记录
            $standardDate = $item['日期'];
            $mergedArray[$standardDate]['qv_data'] = $item;
        }
        foreach($svnData as $item){// 处理 svnLog日志
            $standardDate = $item['Ymd'];
            $mergedArray[$standardDate]['svn_data'] = $item;
        }
        foreach($nginxData as $item){// 处理nginx Log日志
            $standardDate = $item['Ymd'];
            $mergedArray[$standardDate]['nginx_data'] = $item;
        }
        krsort($mergedArray);
        return $mergedArray;
    }

    private function format($data)
    {
        $finalData = $arr = [];
        foreach($data as $Ymd => $item){
            $isWeekday = $this->isWeekday($Ymd);
            $svnData = $item['svn_data'] ?? [];
            $nginxData = $item['nginx_data'] ?? [];
            $qvData = $item['qv_data'] ?? [];
            $arr['date'] = $Ymd; // 将字符串转换为时间戳
            $timestamp = strtotime($Ymd);
            $weekdays = ['日', '一', '二', '三', '四', '五', '六'];//星期映射
            $weekdayIndex = date('w', $timestamp);// 获取星期索引（0=星期日，1=星期一，...，6=星期六）
            $arr['星期'] = "星期" . $weekdays[$weekdayIndex];
            $arr['svn_加班时长'] = $svnData['totalMinutes'] ?? '/';
            $arr['svn_加班时长说明'] = $svnData['remark'] ?? '/';
            $arr['svn_最早提交时间'] = ($isWeekday && isset($svnData['timestampEarliest'])) ? date('H:i:s', $svnData['timestampEarliest']) : '/';
            $arr['svn_最后提交时间'] = isset($svnData['timestamp']) ? date('H:i:s', $svnData['timestamp']) : '/';
            $arr['svn_加班费'] = $svnData['money'] ?? '/';
            $arr['nginx_加班时长'] = $nginxData['totalMinutes'] ?? '/';
            $arr['nginx_加班时长说明'] = $nginxData['remark'] ?? '/';
            $arr['nginx_最早提交时间'] = ($isWeekday && isset($nginxData['timestampEarliest'])) ? date('H:i:s', $nginxData['timestampEarliest']) : '/';
            $arr['nginx_最后提交时间'] = isset($nginxData['timestamp']) ? date('H:i:s', $nginxData['timestamp']) : '/';
            $arr['nginx_加班费'] = $nginxData['money'] ?? '/';
            $arr['打卡时间_上班'] = $qvData['考勤概况-最早'] ?? '/';
            $arr['打卡时间_下班'] = $qvData['考勤概况-最晚'] ?? '/';
            $arr['企业微信聊天记录/加班视频最晚时间'] = '/'; //todo
            $sum = self::calcSum($Ymd, $svnData, $nginxData, $qvData);
            $arr = array_merge($arr, $sum);
            $arr['备注'] = '';
            $finalData[] = $arr;
        }
        return $finalData;

    }

    // 计算最终加班时长 加班说明 加班费用
    private function calcSum($Ymd, $svnData, $nginxData, $qvData)
    {
        $totalMinutes = 0;
        $remark = '';
        $sum = $arr = [];
        if(isset($svnData['detail']) && $svnData['detail']){
            foreach($svnData['detail'] as $type => $val){
                $arr[$type]['minutes'][] = $val['minutes'];
            }
        }
        if(isset($nginxData['detail']) && $nginxData['detail']){
            foreach($nginxData['detail'] as $type => $val){
                $arr[$type]['minutes'][] = $val['minutes'];
            }
        }
        if(self::isWeekday($Ymd)){
            $arr['早']['minutes'][] = $qvData['早上加班时长'] ?? 0;
            $arr['晚']['minutes'][] = $qvData['晚上加班时长'] ?? 0;

        }
        foreach($arr as $type => $val){
            $minutes = max($val['minutes']);
            $totalMinutes += $minutes;
            $minutes && $remark .= $type . '(' . $minutes . '分钟) '; // (早/中/晚)加班分钟数

        }
        $sum['sum_加班时长'] = $totalMinutes;
        $sum['sum_加班时长说明'] = $remark;
        $sum['sum_加班费'] = self::calcMoney($Ymd, $totalMinutes);
        return $sum;
    }
}

$result = (new Analyzer())->getExcelData();// 执行分析加班时长
// $data[] = ['日期', '提交备注', '提交文件', '最后一次代码提交日期', '加班时长', '加班时长说明', '加班费用', '日期类型', '打卡-时间', '打卡-考勤概况-最早', '打卡-考勤概况-最晚', '打卡-时间考勤概况-实际工作时长(小时)', '打卡-考勤概况-考勤结果', '打卡-下班1-打卡时间', '打卡-下班1-打卡状态', '打卡-打卡时间记录'];
$data[] = ['日期', '星期', 'svn提交日志', 'svn提交日志', 'svn提交日志', 'svn提交日志', 'svn提交日志', 'nginx日志', 'nginx日志', 'nginx日志', 'nginx日志', 'nginx日志', '企业微信', '企业微信', '企业微信聊天记录/加班视频最晚时间', '最终汇总', '最终汇总', '最终汇总', '备注'];
$data[] = ['日期', '星期', '加班时长', '加班时长说明', '最早提交时间(周末)', '最晚提交时间', '加班费', '加班时长', '加班时长说明', '最早提交时间(周末)', '最晚提交时间', '加班费', '打卡时间-上班', '打卡时间-下班', '企业微信聊天记录/加班视频最晚时间', '加班时长', '加班时长说明', '加班费', '备注'];
foreach($result as $value){
    $data[] = array_values($value);
}

$filePath = 'export_data.csv';// 文件保存路径
$file = fopen($filePath, 'w');// 打开文件句柄
fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));// 写入BOM头解决中文乱码
foreach($data as $row){
    fputcsv($file, $row);// 写入数据
}
fclose($file);// 关闭文件
echo "CSV文件已生成到: " . realpath($filePath);
