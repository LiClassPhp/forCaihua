<?php
require 'Base.php';
require 'php.php';
require 'Qv.php';
require 'SvnLog.php';
require 'NginxLog.php';
require 'QvChat.php';
require 'Video.php';
require 'Meeting.php';
require 'ExcelExport.php';

class Analyzer extends Base
{
    // 获取最终需要导出Excel的数据
    public function getExcelData()
    {
        $qvChatData = (new QvChat())->getData();
        $qvData = (new Qv())->getData();
        $svnData = (new SvnLog())->getData();
        $nginxData = (new NginxLog())->getData();
        $videoData = (new Video())->getData();
        $data = $this->mergeData($svnData, $qvData, $nginxData, $qvChatData, $videoData); // 合并企微 SvnLog NginxLog 企微聊天记录
        return $this->format($data);
    }

    // 合并企业微信打卡记录和SVN代码提交记录
    private function mergeData($svnData, $qvData, $nginxData, $qvChatData, $videoData)
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
        foreach($qvChatData as $item){// 处理企微聊天记录
            $standardDate = $item['Ymd'];
            $mergedArray[$standardDate]['qv_chat_data'] = $item;
        }
        foreach($videoData as $item){// 处理企微聊天记录
            $standardDate = $item['Ymd'];
            $mergedArray[$standardDate]['video_data'] = $item;
        }
        krsort($mergedArray);
        return $mergedArray;
    }

    private function format($data)
    {
        $finalData = $arr = [];
        $weekdays = ['日', '一', '二', '三', '四', '五', '六'];//星期映射
        foreach($data as $Ymd => $item){
            $isWeekday = $this->isWeekEnd($Ymd);
            $svnData = $item['svn_data'] ?? [];
            $nginxData = $item['nginx_data'] ?? [];
            $qvData = $item['qv_data'] ?? [];
            $qvChatData = $item['qv_chat_data'] ?? [];
            $videoData = $item['video_data'] ?? [];
            $arr['date'] = $Ymd; // 将字符串转换为时间戳
            $timestamp = strtotime($Ymd);
            $weekdayIndex = date('w', $timestamp);// 获取星期索引（0=星期日，1=星期一，...，6=星期六）
            $arr['星期'] = "星期" . $weekdays[$weekdayIndex];
            $arr['svn_最早提交时间'] = ($isWeekday && isset($svnData['timestampEarliest'])) ? date('H:i:s', $svnData['timestampEarliest']) : '/';
            $arr['svn_最后提交时间'] = isset($svnData['timestamp']) ? date('H:i:s', $svnData['timestamp']) : '/';
            $arr['svn_加班时长'] = $svnData['totalMinutes'] ?? '/';
            $arr['svn_加班时长说明'] = $svnData['remark'] ?? '/';
            $arr['nginx_最早提交时间'] = ($isWeekday && isset($nginxData['timestampEarliest'])) ? date('H:i:s', $nginxData['timestampEarliest']) : '/';
            $arr['nginx_最后提交时间'] = isset($nginxData['timestamp']) ? date('H:i:s', $nginxData['timestamp']) : '/';
            $arr['nginx_加班时长'] = $nginxData['totalMinutes'] ?? '/';
            $arr['nginx_加班时长说明'] = $nginxData['remark'] ?? '/';
            $arr['企业微信聊天_加班时长'] = $qvChatData['totalMinutes'] ?? '/';
            $arr['企业微信聊天_加班说明'] = $qvChatData['remark'] ?? '/';
            $arr['下班视频记录_加班时长'] = $videoData['totalMinutes'] ?? '/';
            $arr['下班视频记录_加班说明'] = $videoData['remark'] ?? '/';
            $arr['打卡时间_上班'] = $qvData['考勤概况-最早'] ?? '/';
            $arr['打卡时间_下班'] = $qvData['考勤概况-最晚'] ?? '/';
            $sum = $this->calcSum($Ymd, $svnData, $nginxData, $qvData, $qvChatData);
            $arr = array_merge($arr, $sum);
            $arr['备注'] = $this->beiZhu($Ymd);
            $finalData[] = $arr;
        }
        return $finalData;
    }

    // 获取备注 目前只有一个 是否是培训
    private function beiZhu($Ymd)
    {

        $meetingData = (new Meeting())->getData();
        return isset($meetingData[$Ymd]) ? '当天有开会/培训(截图' . $meetingData[$Ymd] . '张)' : '';
    }

    // 计算最终加班时长 加班说明 加班费用
    private function calcSum($Ymd, $svnData, $nginxData, $qvData, $qvChatData)
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
        if(isset($qvChatData['detail']) && $qvChatData['detail']){
            foreach($qvChatData['detail'] as $type => $val){
                $arr[$type]['minutes'][] = $val['minutes'];
            }
        }
        if($this->isWeekEnd($Ymd)){ //如果是周末的话 企微的打卡时间一并计算
            $arr['早']['minutes'][] = $qvData['早上加班时长'] ?? 0;
            $arr['晚']['minutes'][] = $qvData['晚上加班时长'] ?? 0;

        }
        foreach($arr as $type => $val){
            $minutes = max($val['minutes']);// 取加班最大值
            $totalMinutes += $minutes; //当天累计加班时长(分钟)
            $minutes && $remark .= $type . '(' . $minutes . '分钟) '; // (早/中/晚)加班分钟数

        }
        $sum['sum_加班时长'] = $totalMinutes;
        $sum['sum_加班时长说明'] = $remark;
        $sum['sum_加班费'] = $this->calcMoney($Ymd, $totalMinutes);
        return $sum;
    }
}

$result = (new Analyzer())->getExcelData();// 获取Excel所需数据

foreach($result as $value){
    $data[] = array_values($value);
}
// $data = array_slice($data, 0, 3);
$filename = (new ExcelExport())->createExcel($data, '加班记录表.xlsx');
echo "Excel 文件已生成到: " . realpath($filename);
