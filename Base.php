<?php

class Base
{
    /**
     * 判断时间戳是否在指定时间段内
     * @param int $timestamp 要判断的时间戳
     * @return array 返回包含判断结果的数组
     */
    
    public function isWork($timestamp)
    {
        if(!$timestamp)
            return [];
        $hour = (int)date('H', $timestamp);
        $minute = (int)date('i', $timestamp);
        $totalMinutes = $hour * 60 + $minute;  // 转换为分钟数，便于比较
        // p($hour,$minute,$totalMinutes);
        $aMap = [ // 定义时间段
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
            if($totalMinutes >= $map['start'] && $totalMinutes < $map['end']){
                $results = ['name' => $map['name'], 'totalMinutes' => $totalMinutes - $map['start'], 'currentTime' => date('H:i', $timestamp)];
            }
        }
        return $results;
    }
}