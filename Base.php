<?php

class Base
{
    public $workHours = 8;
    public $hourlyRate = 91.95; // 假设每小时基准工资100元，请根据实际情况调整

    /**
     * 判断时间戳是否在指定时间段内
     * @param int $timestamp 要判断的时间戳
     * @return array 返回包含判断结果的数组
     */

    public function isWork($timestamp)
    {
        if(!$timestamp) return [];
        $hour = (int)date('H', $timestamp);
        $minute = (int)date('i', $timestamp);
        $totalMinutes = $hour * 60 + $minute;  // 转换为分钟数，便于比较
        // p($hour,$minute,$totalMinutes);
        $aMap = [ // 定义时间段  todo 如果是周末 中午: 最早工作时间-12.00为加班时长  晚上: 14:00到最晚工作时间为加班时长
            'morning' => [
                'name' => '早',
                'start' => 0,  // 00:00
                'end' => 9 * 60// 09:00 (540分钟)
            ],
            'noon' => [
                'name' => '中',
                'start' => 12 * 60,// 12:00 (720分钟)
                'end' => 14 * 60   // 14:00 (840分钟)
            ],
            'evening' => [
                'name' => '晚',
                'start' => 18 * 60 + 30,// 18:30 (1110分钟)
                'end' => 24 * 60        // 24:00 (1440分钟)
            ]
        ];
        $results = [];
        foreach($aMap as $map){
            if($totalMinutes > $map['start'] && $totalMinutes < $map['end']){
                $results = ['name' => $map['name'], 'totalMinutes' => $totalMinutes - $map['start'], 'currentTime' => date('H:i', $timestamp)];
            }
        }
        return $results;
    }

    public function analyze($data)
    {
        $ret = [];
        foreach($data as $Ymd => $value){
            $workMinutes = 0;
            $remark = '';
            $aTimestamp = [];
            foreach($value as $type => $val){ // type: 早中晚
                $val = array_column($val, null, 'timestamp');
                $arr = $val[max(array_keys($val))]; // 取最大的值 为当前区间的最终加班时长
                $aTimestamp[] = $arr['timestamp'];
                $workMinutes += $arr['work']['totalMinutes'];
                $remark .= $type . '(' . $arr['work']['totalMinutes'] . ')' . ' ';
            }
            $ret[] = [
                'timestamp' => max($aTimestamp), // 当天工作最晚时间
                'YmdHis' => date('Y-m-d H:i:s', max($aTimestamp)),
                'Ymd' => $Ymd,
                'workMinutes' => $workMinutes,
                'remark' => $remark,
                'money' => self::calcMoney($Ymd, $workMinutes)
            ];
        }
        return $ret;
    }

    private function calcMoney($Ymd, $workMinutes)
    {
        $weekdayNum = DateTime::createFromFormat('Ymd', $Ymd)->format('w');
        $minuteRate = $this->hourlyRate / 60;
        switch($weekdayNum){
            case 0:
            case 6:
                $money = $workMinutes * $minuteRate * 2;
                break;
            default:
                $money = $workMinutes * $minuteRate * 1.5;
                break;
        }
        return $money;
    }

    // 分析SVN记录
    public function analyze1($data): array
    {
        $groupedByDate = [];// 按日期分组
        foreach($data as $entry){
            $dateTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $entry['date']);// 解析日期时间
            if(!$dateTime){
                continue; // 跳过格式不正确的记录
            }
            $overtimePay = $overtimeMinutes = 0;  // 计算加班费用 (工作日1.5倍，周末2倍) && 加班时长
            $minuteRate = $this->hourlyRate / 60; // 分钟薪资
            $dateStr = $dateTime->format('Y-m-d');
            $timeStr = $dateTime->format('H:i:s');
            $dayOfWeek = $dateTime->format('w');               // 0=周日, 1=周一, ..., 6=周六

            // 判断是否是周末
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6); // 目前只有周六加班
            $dayType = $isWeekend ? '周六' : '工作日';
            $overtimeDescription = ''; // 加班描述
            if($isWeekend){
                $overtimeMinutes = 480;                            // 暂时写死
                $overtimePay = $overtimeMinutes * $minuteRate * 2; // 周末2倍
            }else{// 检查是否在加班时间段内
                // 12:00-14:00之间
                if($timeStr >= '12:00:00' && $timeStr < '14:00:00'){
                    $start = new DateTime($dateStr . ' 12:00:00');
                    $interval = $start->diff($dateTime);
                    $minutes = $interval->h * 60 + $interval->i;
                    $overtimeMinutes += $minutes;
                    $minutes && $overtimeDescription .= "中午加班: {$minutes}分钟";
                }

                $qvEndMinutes = 0; // 计算企业微信 加班时间
                if(isset($entry['考勤概况-最晚']) && $entry['考勤概况-最晚']){
                    $start = new DateTime($dateStr . ' 18:30:00');
                    if(strpos($entry['考勤概况-最晚'], '次日') !== false){
                        $qvEndDate = str_replace('次日', '', $entry['考勤概况-最晚']);
                        $end = DateTime::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $qvEndDate)->modify('+1day');// 结束时间设为次日
                    }else{
                        $end = DateTime::createFromFormat('Y-m-d H:i', $dateStr . ' ' . $entry['考勤概况-最晚']);
                    };
                    $end && $diff = $start->diff($end);         // 计算时间差(20230613加班到凌晨 然后14号请假 变成未打卡没有打卡日期)
                    $qvEndMinutes = ($diff->h * 60) + $diff->i; // 将小时转换为分钟并加上剩余分钟
                }

                if($timeStr >= '18:30:00'){
                    $start = new DateTime($dateStr . ' 18:30:00');
                    $interval = $start->diff($dateTime);
                    $minutesSvn = $interval->h * 60 + $interval->i;
                    $minutes = max($qvEndMinutes, $minutesSvn); // 打卡记录与SVN提交记录取最大值
                    $overtimeMinutes += $minutes;

                    if(!empty($overtimeDescription)){
                        $overtimeDescription .= ", ";
                    }
                    $minutes && $overtimeDescription .= "晚上加班: {$minutes}分钟,";
                }
                $overtimePay = $overtimeMinutes * $minuteRate * 1.5; // 工作日1.5倍
            }

            // 如果没有加班，跳过此记录
            if($overtimeMinutes == 0){
                // continue;
            }
            // 按日期分组
            if(!isset($groupedByDate[$dateStr])){
                $groupedByDate[$dateStr] = ['日期' => $dateStr, '提交备注' => [], '提交文件' => [], '最后一次代码提交日期' => $dateStr . ' ' . $timeStr, '加班时长' => 0, '加班时长说明' => '', '加班费用' => 0, '日期类型' => $dayType, '打卡-时间' => $entry['时间'] ?? '', '打卡-考勤概况-最早' => $entry['考勤概况-最早'] ?? '', '打卡-考勤概况-最晚' => $entry['考勤概况-最晚'] ?? '', '打卡-时间考勤概况-实际工作时长(小时)' => $entry['考勤概况-实际工作时长(小时)'] ?? '', '打卡-考勤概况-考勤结果' => $entry['考勤概况-考勤结果'] ?? '', '打卡-下班1-打卡时间' => $entry['下班1-打卡时间'] ?? '', '打卡-下班1-打卡状态' => $entry['下班1-打卡状态'] ?? '', '打卡-打卡时间记录' => $entry['打卡时间记录'] ?? '',];
            }
            // 添加信息到该日期
            $groupedByDate[$dateStr]['提交备注'][] = $entry['message'];
            $groupedByDate[$dateStr]['提交文件'][] = $entry['files'];
            $groupedByDate[$dateStr]['加班时长'] += $overtimeMinutes;

            // if(!empty($groupedByDate[$dateStr]['加班时长说明'])){
            // 	$groupedByDate[$dateStr]['加班时长说明'] .= "; ";
            // }
            $groupedByDate[$dateStr]['加班时长说明'] .= $overtimeDescription;

            $groupedByDate[$dateStr]['加班费用'] += $overtimePay;
        }

        // 处理重复的备注和文件
        foreach($groupedByDate as &$dayData){
            $dayData['提交备注'] = implode("; ", array_unique($dayData['提交备注']));
            $dayData['提交文件'] = implode("; ", array_unique($dayData['提交文件']));
        }
        return array_values($groupedByDate);
    }
}