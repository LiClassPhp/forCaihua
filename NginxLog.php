<?php

class NginxLog extends Base
{
    public function getData()
    {
        $nginxData = [];
        $data = $this->parse();
        foreach($data as $d){
            if($ret = $this->isWork($d['timestamp'])){
                $Ymd = date('Ymd', $d['timestamp']); // 相同日期 相同加班区间(早 中 晚)合并在一起
                $d['work'] = $ret;
                $nginxData[$Ymd][$ret['name']][] = $d;
            }
        }
        // 排序
        foreach($nginxData as &$value){
            foreach($value as &$val){
                $key = 'timestamp';// 按指定key降序排列
                usort($val, function($a, $b) use ($key){
                    $valueA = $a[$key] ?? null;
                    $valueB = $b[$key] ?? null;

                    if($valueA === $valueB) return 0;
                    return ($valueA > $valueB) ? -1 : 1; // 降序排列
                });
                // $val = current($val); //是否只保留一条数据(analyze会处理 保留一条会解析异常)
            }
        }
        return self::analyze($nginxData);
    }

    /** 原始数据解析
     * @return array
     */
    private function parse()
    {
        ini_set('memory_limit', '512M');
        $logStr = $this->logStr();
        $aLogStr = explode("\n", $logStr);
        $data = $filterForRepeat = [];// 同一时间(精确到分)只保留一条
        foreach($aLogStr as $v){
            if(!$v) continue;
            $aStr = explode('192.168.56.1 200', $v);// 一般用IP来分割
            if(!isset($aStr[1]) || !trim($aStr[1])) continue;
            $detail = $this->parseLogString(trim($aStr[1]));
            if(!isset($detail['ch_uid']) || $detail['ch_uid'] == 0) continue;
            $timestamp = DateTime::createFromFormat('[d/M/Y:H:i:s O]', trim($aStr[0]))->setTimezone(new \DateTimeZone('Asia/Shanghai'))->getTimestamp();
            // 同一分钟 只保留一条数据
            if(isset($filterForRepeat[date('Y-m-d h:i', $timestamp)])) continue;
            $filterForRepeat[date('Y-m-d h:i', $timestamp)] = 1;
            $data[] = [
                'originTime' => $aStr[0],
                'timestamp' => $timestamp,
                'uid' => $detail['ch_uid'],
                // 'detail' => $detail,
            ];
        }
        return $data;
    }

    // 使用正则解析字符串
    private function parseLogString($str)
    {
        $result = [];
        // 使用正则表达式匹配各个部分
        $pattern = '/^(\S+)\s+(\S+)\s+"(\S+)\s+(\S+)\s+([^"]*)"\s+"([^"]*)"\s+(\S+)\s+(\S+)\s+"([^"]*)"\s+"([^"]*)"\s+(\S+)$/';
        if(preg_match($pattern, $str, $matches)){
            // 基础信息
            // $result['response_time'] = floatval($matches[1]); // 响应时间
            // $result['response_size'] = intval($matches[2]);   // 响应大小
            $result['request_method'] = $matches[3];          // 请求方法
            $result['request_uri'] = $matches[4];             // 请求URI
            // $result['http_version'] = $matches[5];            // HTTP版本

            // 解析完整的URL路径和参数
            $urlParts = explode('?', $result['request_uri']);
            $result['request_path'] = $urlParts[0];           // 请求路径

            // 解析查询参数
            if(isset($urlParts[1])){
                parse_str($urlParts[1], $queryParams);
                // $result['query_params'] = $queryParams;

                // 提取特定参数
                $result['phpsessid'] = $queryParams['PHPSESSID'] ?? null;
                $result['ch_uid'] = $queryParams['ch_uid'] ?? null;
                $result['ch_newui'] = $queryParams['ch_newui'] ?? null;
            }

            // 其他信息
            // $result['referer'] = $matches[6];                 // 来源页面
            // $result['upstream_server'] = $matches[7];         // 上游服务器
            // $result['remote_addr'] = $matches[8];             // 远程地址
            // $result['http_x_forwarded_for'] = $matches[9];    // X-Forwarded-For
            // $result['user_agent'] = $matches[10];             // 用户代理
            // $result['script_path'] = $matches[11];            // 脚本路径

            // 解析User-Agent详细信息
            // $result['browser_info'] = $this->parseUserAgent($result['user_agent']);
        }

        return $result;
    }

    // 解析User-Agent的函数
    public function parseUserAgent($userAgent)
    {
        $browserInfo = [];
        // 匹配浏览器信息
        if(preg_match('/\((.*?)\)/', $userAgent, $matches)){
            $browserInfo['os'] = $matches[1];
        }
        if(preg_match('/(Chrome|Firefox|Safari|Edge)\/(\d+\.\d+)/', $userAgent, $matches)){
            $browserInfo['browser'] = $matches[1];
            $browserInfo['version'] = $matches[2];
        }

        return $browserInfo;
    }

    // 获取文件数据
    private function logStr()
    {
        $logText = '[9/Oct/2023:10:30:48 +0000] 192.168.56.1 200 0.073 7361 "POST /apps/zt/kemu/kemu.config?PHPSESSID=KQCjKXPEz3RWdZQLqJq5LHRKLd1LIrbF&ch_uid=15472&ch_id=23625&ch_Ym=202308&ch_kjzd=99&scmid=23&iframe=scm&ch_key=u4k7%2FtUxMYB4FKgW86s%2B%2BqTe9D2HM3zO8ujulWhSYVwEkaFhDubfYetXLrteg2VF&ch_newui=1&ch_adm=1&ch_mid=1 HTTP/1.1" "http://localhost:8083/" 172.18.0.5:9000 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/fms/api.php 
[09/Oct/2023:04:01:09 +0000] 192.168.56.1 200 0.129 856 "POST /apps/zt/zichan/zichanNew.select?PHPSESSID=uBnD6EyO2aK1MJYC84I0K5hNfMrHETyF&ch_uid=15472&ch_id=23625&ch_Ym=202308&ch_kjzd=99&ch_newui=1&ch_adm=1 HTTP/1.1" "http://fms.vm-72mao.com/zichan?adm=1&id=23625" 172.18.0.5:9000 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/fms/api.php 
';

        $logText = '';
        foreach(glob("../nginx/scm.vm-72mao.com.log") as $filename){          // 获取所有SVN文件
            $logText .= file_get_contents($filename) . "\n";                  // 字符串合并
        }

        return $logText;
    }
}
