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
        $h = (int)date('H', $timestamp);//小时数
        $m = $h * 60 + (int)date('i', $timestamp);  // 转换为分钟数，便于比较
        $results = [];
        foreach(self::map($timestamp) as $key => $aMap){
            foreach($aMap as $map){
                if($m > $map['start'] && $m < $map['end']){
                    if($key === '周末'){//周末: 早:(12:00 减 早上上班时间) 晚:(晚上下班时间 减 14:00)
                        $minutes = $map['name'] === '早' ? ($map['end'] - $m) : $m - $map['start'];
                        $results = ['name' => $map['name'], 'minutes' => $minutes, 'currentTime' => date('H:i', $timestamp)];
                    }else{//工作日: 实际工作结束时间 减去 上班开始时间
                        $results = ['name' => $map['name'], 'minutes' => $m - $map['start'], 'currentTime' => date('H:i', $timestamp)];
                    }
                }
            }
        }
        return $results;
    }

    private function map($timestamp)
    {
        $weekdayNum = date('w', $timestamp);
        if(in_array($weekdayNum, [0, 6])){
            $aMap['周末'] = [ // 定义时间段 如果是周末 早: 最早工作时间-13.00为加班时长  晚: 14:00到最晚工作时间为加班时长
                'morning' => [
                    'name' => '早',
                    'start' => 9 * 60,  // 09:00 (540分钟)
                    'end' => 12 * 60    // 12:00 (780分钟)
                ],

                'evening' => [
                    'name' => '晚',
                    'start' => 14 * 60, // 14:00 (840分钟)
                    'end' => 24 * 60    // 24:00 (1440分钟)
                ]
            ];

        }else{
            $aMap['工作日'] = [ // 定义时间段
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
        }
        return $aMap;
    }

    public function analyze($data)
    {
        $ret = [];
        foreach($data as $Ymd => $value){
            $totalMinutes = 0;
            $remark = '';
            $aTimestamp = $detail = [];
            foreach($value as $type => $val){ // type: 早中晚
                $val = array_column($val, null, 'minutes');
                $minutes = $val[max(array_keys($val))]['minutes']; // 当前type类型 取加班分钟数最大的值 为当前区间的最终加班时长
                $aTimestamp = array_merge($aTimestamp, array_column($val, 'timestamp')); //取val的时间戳 而非arr里的时间戳 可以获得当天工作最早时间
                $detail[$type] = ['minutes' => $minutes, 'money' => self::calcMoney($Ymd, $minutes)]; // 早中晚各加班时长 用于与svn对比取最大值
                $totalMinutes += $minutes; // 当天合计总共加班时长
                $remark .= $type . '(' . $minutes . '分钟,提交' . count($val) . '次) '; // (早/中/晚)加班分钟数 和 (svn提交/Nginx请求)次数
            }
            $ret[] = [
                'timestamp' => max($aTimestamp),// 当天工作最晚时间
                'timestampEarliest' => min($aTimestamp), // 当天工作最早时间 用于周末加班
                'YmdHis' => date('Y-m-d H:i:s', max($aTimestamp)),
                'Ymd' => $Ymd,
                'totalMinutes' => $totalMinutes,
                'detail' => $detail,
                'remark' => $remark,
                'money' => self::calcMoney($Ymd, $totalMinutes)
            ];
        }
        return $ret;
    }

    // 根据日期和加班时间 计算具体加班费
    protected function calcMoney($Ymd, $minutes)
    {
        $minuteRate = $this->hourlyRate / 60;
        return $minutes * $minuteRate * (self::isWeekday($Ymd) ? 2 : 1.5);
    }

    public function isWeekday($Ymd)
    {
        if(!$Ymd){
            p(111);
        }
        $weekdayNum = DateTime::createFromFormat('Ymd', $Ymd)->format('w');
        return in_array($weekdayNum, [0, 6]);
    }
}