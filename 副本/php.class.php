<?php

function p(){//格式化输出数组
	_p(...func_get_args());
}

function _p(){
	$arr = debug_backtrace();
	$arr = $arr[1];
	$p = func_get_args();
	php::$temp['php_startTime'] = microtime(true);
	func_num_args() === 1 && $p = $p[0];
	if((defined('IN_PHPAPP') && IN_PHPAPP) || 1){
		ob_start();
		echo '<pre>◆ ' . $arr['file'] . ':' . $arr['line'] . "\n";
		print_r($p);
		php::output(ob_get_clean());
	}else{
		echo '<pre>◆ ' . $arr['file'] . ':' . $arr['line'] . "\n";
		print_r($p);
		php::exec();
		die();
	}
}

function pf(){//格式化输出带数据类型的数组
	$arr = debug_backtrace(); //获取当前调用位置
	$arr = $arr[0];
	$p = func_get_args();
	func_num_args() === 1 && $p = $p[0];
	php::$temp['php_startTime'] = microtime(true);;
	if(php::$temp['php_startTime']){
		$con = php::var_dump_get($arr, $p);
		php::output($con);
	}else{
		die(php::var_dump_get($arr, $p));
	}
}

class php{
	//CGI结束后再执行
	private static $list = [];
	public static  $temp = [];

	public static function output($out){
		$out = str_replace('<?php', '&lt;?php', $out);
		$out = str_replace('<script', '&lt;script', $out);
		$out = str_replace('</script>', '&lt;/script&gt;', $out);
		echo '<i>运行时间：' . (microtime(true) - self::$temp['php_startTime']) . ' 秒，当前时间：' . self::gmdate('Y-m-d H:i:s') . '</i><br/>' . $out;
		self::exec();
		die();
	}

	//快速返回给客户端
	public static function exec(){
		if(!self::$list) return;
		function_exists('fastcgi_finish_request') && fastcgi_finish_request();
		$temp = self::$list;
		self::$list = [];//防止死循环
		foreach($temp as $args){
			self::run($args);
		}
	}

	//执行PHP，fastcgi与cli共用，只有【一个数组】参数，如：lib\fastcgi::run([['lib\log', 'debug'], '参数1', '参数2']);
	private static function run($a){
		$fn = array_shift($a);
		$ret = null;
		if(is_callable($fn)){//lib\fastcgi::init([$this, 'public方法名'], '参数1', '参数2', '参数n') 或 lib\fastcgi::init('lib\testClass::test', '参数1', '参数2', '参数n');
			try{
				$ret = call_user_func_array($fn, $a);
			}catch(\Throwable $e){
				$ret = $e->getMessage();
				self::err($fn, $a, $ret);
			}
		}else{
			//lib\fastcgi::init(['lib\testClass', 'test'], '参数1', '参数2', '参数n');//强烈推荐，支持代码定位跳转
			if(is_array($fn)) $fn = $fn[0] . '::I()->' . $fn[1];
			$p = [];
			if($a){
				foreach($a as $k => $v){
					$p[] = '$a[' . $k . ']';
				}
			}
			$php = '$ret=' . $fn . '(' . implode(',', $p) . ');';
			try{
				eval($php);
			}catch(\Throwable $e){
				$ret = $e->getMessage();
				self::err($fn, $a, $ret);
			}
		}
		return $ret;
	}

	private static function err(...$args){
		// lib\log::put($args, 'fastcgi_run_err');
	}

	//北京时间格式化
	private static function gmdate($s, $time = 0, $h = 8){
		$time = ($time > 0 ? $time : time()) + 3600 * $h;
		return gmdate($s, $time);
	}

	// 对 var_dump 的输出进行格式化（替换空格、箭头等）。
	public static function var_dump_get($arr, $p){
		ob_start();
		var_dump($p);
		$con = ob_get_clean();
		$con = str_replace(' ', "\t", $con);
		$con = str_replace("=>\n", '=>', $con);
		while(str_contains($con, "=>\t")){
			$con = str_replace("=>\t", '=>', $con);
		}
		$con = str_replace("\t\t", "\t", $con);
		$con = str_replace(")\t", ") ", $con);
		$con = str_replace("=>", " => ", $con);
		$con = str_replace('"', "'", $con);
		return '<pre>◆ ' . $arr['file'] . ':' . $arr['line'] . "\n" . $con;
	}
}


