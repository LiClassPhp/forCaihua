<?php
class NginxLog{
	public function log(){
		$log = '[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 33896 "GET /css/chunk-vendors.c288c747.css HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/css/chunk-vendors.c288c747.css 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 20257 "GET /css/main.2ba3792a.css HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/css/main.2ba3792a.css 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 161286 "GET /js/chunk-vendors.e3fb9ab1.js HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/js/chunk-vendors.e3fb9ab1.js 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 331623 "GET /js/main.60f14463.js HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/js/main.60f14463.js 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.125 818 "GET /apis/user/login.info?PHPSESSID=f6j3PLtCFv5LWUfOrHpHR93rEpx3ISCN&ch_uid=0&ch_newui=1 HTTP/1.1" "http://admin.vm-72mao.com/" 172.18.0.5:9000 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/api.php 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.041 30047 "POST /apis/hr.api?PHPSESSID=f6j3PLtCFv5LWUfOrHpHR93rEpx3ISCN&ch_uid=15472&ch_newui=1 HTTP/1.1" "http://admin.vm-72mao.com/" 172.18.0.5:9000 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/api.php 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.036 2838 "GET /apis/appconfig/appconfig.select?PHPSESSID=f6j3PLtCFv5LWUfOrHpHR93rEpx3ISCN&ch_uid=15472&ch_newui=1 HTTP/1.1" "http://admin.vm-72mao.com/" 172.18.0.5:9000 - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/api.php 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 6048 "GET /js/chunk-33215b84.6d48645f.js HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/js/chunk-33215b84.6d48645f.js 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 6184 "GET /js/chunk-50fbb790.9b69bb3f.js HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/js/chunk-50fbb790.9b69bb3f.js 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 18119 "GET /js/chunk-3d471e3c.c6fafb28.js HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/js/chunk-3d471e3c.c6fafb28.js 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 811 "GET /css/chunk-33215b84.727a809e.css HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/css/chunk-33215b84.727a809e.css 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 4439 "GET /css/chunk-50fbb790.b47a1bf4.css HTTP/1.1" "http://admin.vm-72mao.com/" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/css/chunk-50fbb790.b47a1bf4.css 
[17/Aug/2023:12:11:46 +0000] 192.168.56.1 200 0.000 35482 "GET /platform/data/logo/2207/71ebd545.png HTTP/1.1" "http://admin.vm-72mao.com/home" - - "-" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36" /data/wwwroot/72mao/admin/platform/data/logo/2207/71ebd545.png 
';
		// $log = file_get_contents('/data/wwwroot/log/abc/tax.vm-72mao.com.log');
		// $log = file_get_contents('/data/wwwroot/log/abc/auth.vm-72mao.com.log');
		// $log = file_get_contents('/data/wwwroot/log/abc/scm.vm-72mao.com.log');
		return $log;
	}

	//按删除日志修复 app\admin\temp::I()->fixByDelLog(abc,'')
	public function fixByDelLog(string $explodeStr = '192.168.56.1 200'){
		$logStr = $this->log();
		$aLogStr = explode("\n", $logStr);
		$aData = [];
		$aTimeForRepeat = [];//同一时间(精确到分)只保留一条
		foreach($aLogStr as $v){
			if(!$v) continue;
			$aStr = explode($explodeStr, $v);//一般用IP来分割
			if(!trim($aStr[1])) continue;
			$detail = $this->parseLogString(trim($aStr[1]));
			// $filePath = substr(trim($aStr[1]), strpos(trim($aStr[1]), '/data/wwwroot/')); //文件路径
			$timestamp = \DateTime::createFromFormat(
				'[d/M/Y:H:i:s O]',
				trim($aStr[0])
			)->setTimezone(new \DateTimeZone('Asia/Shanghai'))->getTimestamp();

            // 同一分钟 只保留一条数据
			// if($aTimeForRepeat[date('Y-m-d h:i', $timestamp)]) continue;
			// $aTimeForRepeat[date('Y-m-d h:i', $timestamp)] = 1;
			if($ret = $this->isWork($timestamp)){
				$Ymd = date('Ymd', $timestamp); //相同日期 相同加班区间(早 中 晚)合并在一起
				$aData[$Ymd][$ret['name']][] = [
					'orginTime' => $aStr[0],
					'timestamp' => $timestamp,
					'detail' => $detail,
					'work' => $ret
				];
			}
		}
		foreach($aData as &$value){
			foreach($value as $timeType => &$val){
				$key = 'timestamp';// 按指定key降序排列
				usort($val, function($a, $b) use ($key){
					$valueA = $a[$key] ?? null;
					$valueB = $b[$key] ?? null;

					if($valueA === $valueB) return 0;
					return ($valueA > $valueB) ? -1 : 1; // 降序排列
				});
				$val = current($val);
			}
			$value[$timeType] = current($value);
		}
		p(count($aData), $aData);
	}

	public function parseLogString($str){
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
	public function parseUserAgent($userAgent){
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

	public function isWork($timestamp){
		/**
		 * 判断时间戳是否在指定时间段内
		 * @param int $timestamp 要判断的时间戳
		 * @return array 返回包含判断结果的数组
		 */
		// 如果没有提供时间戳，使用当前时间
		if($timestamp === null){
			$timestamp = time();
		}

		// 获取小时和分钟
		$hour = (int)date('H', $timestamp);
		$minute = (int)date('i', $timestamp);

		// 转换为分钟数，便于比较
		$totalMinutes = $hour * 60 + $minute;

		// 定义时间段
		$periods = [
			'morning' => [
				'name' => '早',
				'start' => 0,      // 00:00
				'end' => 9 * 60    // 09:00 (540分钟)
			],
			'noon' => [
				'name' => '中',
				'start' => 12 * 60, // 12:00 (720分钟)
				'end' => 14 * 60    // 14:00 (840分钟)
			],
			'evening' => [
				'name' => '晚',
				'start' => 18 * 60 + 30, // 18:30 (1110分钟)
				'end' => 24 * 60         // 24:00 (1440分钟)
			]
		];

		$results = [];
		foreach($periods as $key => $period){
			if($totalMinutes >= $period['start'] && $totalMinutes < $period['end']){
				$results = [
					'name' => $period['name'],
					'in_period' => $totalMinutes - $period['start'],
					'current_time' => date('H:i', $timestamp)
				];
			}
		}
		return $results;
	}
}
$obj = new NginxLog();
$obj->fixByDelLog();