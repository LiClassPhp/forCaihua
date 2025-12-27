<?php

/**
 * Video 下班视频录像
 */
class Video extends base
{

    public function getData()
    {
        $logText = '';
        foreach(glob("../video/*.txt") as $filename){          // 获取所有SVN文件
            $logText .= file_get_contents($filename) . "\n"; // 字符串合并
        }
        $aTime = array_filter(explode("\n", $logText));
        $data = [];
        foreach($aTime as $date){
            $data[] = ['timestamp' => strtotime($date)];
        }
        $svnData = [];
        foreach($data as $d){
            $timestamp = $d['timestamp'];
            if($ret = $this->isWork($timestamp)){
                $Ymd = date('Ymd', $timestamp); // 相同日期 相同加班区间(早 中 晚)合并在一起
                $d = array_merge($d, $ret); // 合并
                $svnData[$Ymd][$d['name']][] = $d;
            }
        }
        return self::analyze($svnData, '视频');
    }

}
