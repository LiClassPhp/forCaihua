<?php

class SvnLog extends base
{

    public function getData()
    {
        $svnData = [];
        $data = self::format(self::parse());
        usort($data, function($a, $b){ // 按照日期倒序排列
            // 将中文日期转换为时间戳进行比较
            $dateA = DateTime::createFromFormat('Y年m月d日 H:i:s', $a['date']);
            $dateB = DateTime::createFromFormat('Y年m月d日 H:i:s', $b['date']);
            return $dateB->getTimestamp() - $dateA->getTimestamp();
        });
        foreach($data as $value){
            $timestamp = $value['timestamp'];
            if($ret = $this->isWork($timestamp)){
                $Ymd = date('Ymd', $timestamp); // 相同日期 相同加班区间(早 中 晚)合并在一起
                $svnData[$Ymd][$ret['name']][] = ['originTime' => $value['date'], 'timestamp' => $timestamp, 'work' => $ret];
            }
        }
        return self::analyze($svnData);

    }

    /** 使用正则解析SVN日志信息 进行分组
     * @return array
     */
    private function parse()
    {
        /*
        $logText = "
        版本: 214652
        作者: ClassLi
        日期: 2025年5月21日 18:48:23
        信息:
        使用千分位格式化数字
        ----
        已修改 : /zh/app/zt/exports/zcfzb.class.php
        已修改 : /zv/zx/x/

        版本: 214646
        作者: ClassLi
        日期: 2025年5月21日 18:43:59
        信息:
        优化
        ----
        已修改 : /zh/app/zt/exports/lrb.class.php

        版本: 214645
        作者: ClassLi
        日期: 2025年5月21日 18:40:18
        信息:
        使用千分位格式化数字
        ----
        已修改 : /zh/app/zt/exports/lrb.class.php

        版本: 214626
        作者: ClassLi
        日期: 2025年5月21日 12:40:02
        信息:
        注释
        ----
        已修改 : /zh/app/contract/contract.class.php

        版本: 214609
        作者: ClassLi
        日期: 2025年5月21日 12:09:45
        信息:
        支持筛选
        ----
        已修改 : /zh/app/contract/contract.class.php

        版本: 214604
        作者: ClassLi
        日期: 2025年9月18日 10:51:24
        信息:
        增加筛选
        ----
        已修改 : /zh/app/customer/customer.class.php

        版本: 214597
        作者: ClassLi
        日期: 2025年9月21日 10:51:24
        信息:
        单据编号为 string 类型
        ----
        已修改 : /scm/app/exports/exports.class.php";
         */
        $logText = '';
        foreach(glob("../svn/*.txt") as $filename){          // 获取所有SVN文件
            $logText .= file_get_contents($filename) . "\n"; // 字符串合并
        }
        $data = [];
        // 匹配完整的SVN记录模式
        // $pattern = '/版本:\s*(\d+)\s*作者:\s*([^\n]+)\s*日期:\s*([^\n]+)\s*信息:\s*([^\n]+)\s*----\s*已修改\s*:\s*([^\n]+)/';
        $pattern = '/版本: (\d+)\s+作者: (\S+)\s+日期: (.*?)\s+信息:\s*(.*?)\s*-{4}\s*(已修改 : .*?)(?=\s*版本: |$)/s';
        preg_match_all($pattern, $logText, $matches, PREG_SET_ORDER);

        foreach($matches as $match){ // 格式化
            $data[] = ['version' => trim($match[1]), 'author' => trim($match[2]), 'date' => trim($match[3]), 'message' => trim($match[4]), 'files' => trim($match[5]) . '【' . trim($match[3]) . '】',];
        }
        return $data;
    }

    /** 进一步格式化数据 早中晚 加班时间段 只保留一条数据
     * @param $data
     * @return mixed
     */
    private function format($data)
    {
        $arrZw = $arrWs = $arrWeek = [];// 中午 晚上 周末
        foreach($data as $key => $value){
            $dateTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $value['date']);
            $timeStr = $dateTime->format('H:i:s');
            $dateStr = $dateTime->format('Y-m-d');
            $isWeek = $dateTime->format('w'); // 0=周日, 1=周一, ..., 6=周六
            // 如果是周末 应该按照企微打卡记录来算 这里简单算一整天480分钟加班时间 周末暂时只保留一条数据
            $data[$key]['timestamp'] = strtotime($dateStr . ' ' . $timeStr);
            if(($isWeek == 0 || $isWeek == 6)){ // 判断是否是周末
                if(isset($arrWeek[$dateStr]) && $arrWeek[$dateStr]){
                    unset($data[$key]); // 删除本次数据
                }else{
                    $arrWeek[$dateStr] = 1;
                }
            }else{
                if($timeStr >= '12:00:00' && $timeStr < '14:00:00'){ // 中午加班
                    if(isset($arrZw[$dateStr]) && $arrZw[$dateStr]){
                        $lastKey = $arrZw[$dateStr]['key'];
                        $lastTime = $arrZw[$dateStr]['time'];
                        if($timeStr > $lastTime){                                   // 当前时间大于 上次记录的时间
                            unset($data[$lastKey]);                                 // 把上次记录的时间对应的数据unset掉
                            $arrZw[$dateStr] = ['key' => $key, 'time' => $timeStr]; // 更新当前时间
                        }else{                  // 当前时间小于 上次记录的时间
                            unset($data[$key]); // 删除本次数据
                        }
                        continue;
                    }else{
                        $arrZw[$dateStr] = ['key' => $key, 'time' => $timeStr];
                    }
                }
                if($timeStr >= '18:30:00'){ // 晚上加班
                    if(isset($arrWs[$dateStr]) && $arrWs[$dateStr]){
                        $lastKey = $arrWs[$dateStr]['key'];
                        $lastTime = $arrWs[$dateStr]['time'];
                        if($timeStr > $lastTime){                                   // 当前时间大于 上次记录的时间
                            unset($data[$lastKey]);                                 // 把上次记录的时间对应的数据unset掉
                            $arrWs[$dateStr] = ['key' => $key, 'time' => $timeStr]; // 更新当前时间
                        }else{                  // 当前时间小于 上次记录的时间
                            unset($data[$key]); // 删除本次数据
                        }
                    }else{
                        $arrWs[$dateStr] = ['key' => $key, 'time' => $timeStr];
                    }
                }
            }
        }
        return $data;
    }

}