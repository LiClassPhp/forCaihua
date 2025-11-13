<?php

class SVNOvertimeAnalyzer{
	private $entries = [];

	/**
	 * 添加SVN记录
	 * @param string $version 版本号
	 * @param string $author 作者
	 * @param string $date 日期时间
	 * @param string $message 提交信息
	 * @param string $files 修改的文件
	 */
	public function addEntry($version, $author, $date, $message, $files){
		$this->entries[] = [
			'version' => $version,
			'author' => $author,
			'date' => $date,
			'message' => $message,
			'files' => $files
		];

	}

	/**
	 * 分析SVN记录并生成报告
	 * @return array 分析结果
	 */
	public function analyze(){
		// 按日期分组
		$groupedByDate = [];
		foreach($this->entries as $entry){
			// 解析日期时间
			$dateTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $entry['date']);
			if(!$dateTime){
				continue; // 跳过格式不正确的记录
			}

			$dateStr = $dateTime->format('Y-m-d');
			$timeStr = $dateTime->format('H:i:s');
			$dayOfWeek = $dateTime->format('w'); // 0=周日, 1=周一, ..., 6=周六

			// 判断是否是周末
			$isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
			$dayType = $isWeekend ? '周末' : '工作日';

			if($isWeekend){
				
			}else{
				
			}

			// 检查是否在加班时间段内
			$overtimeMinutes = 0;
			$overtimeDescription = '';

			// 12:00-14:00之间
			if($timeStr >= '12:00:00' && $timeStr < '14:00:00'){
				$start = new DateTime($dateStr . ' 12:00:00');
				$interval = $start->diff($dateTime);
				$minutes = $interval->h * 60 + $interval->i;
				$overtimeMinutes += $minutes;
				$overtimeDescription .= "午休加班: {$minutes}分钟";
			}


			// 18:30之后
			if($timeStr >= '18:30:00'){
				$start = new DateTime($dateStr . ' 18:30:00');
				$interval = $start->diff($dateTime);
				$minutes = $interval->h * 60 + $interval->i;
				$overtimeMinutes += $minutes;

				if(!empty($overtimeDescription)){
					$overtimeDescription .= ", ";
				}
				$overtimeDescription .= "晚间加班: {$minutes}分钟";
			}

			// 如果没有加班，跳过此记录
			if($overtimeMinutes == 0){
				continue;
			}

			// 计算加班费用 (工作日1.5倍，周末2倍)
			$hourlyRate = 91.95; // 假设每小时基准工资100元，请根据实际情况调整
			$minuteRate = $hourlyRate / 60;

			if($isWeekend){
				$overtimePay = $overtimeMinutes * $minuteRate * 2; // 周末2倍
			}else{
				$overtimePay = $overtimeMinutes * $minuteRate * 1.5; // 工作日1.5倍
			}

			// 按日期分组
			if(!isset($groupedByDate[$dateStr])){
				$groupedByDate[$dateStr] = [
					'日期' => $dateStr,
					'提交备注' => [],
					'提交文件' => [],
					'加班时长' => 0,
					'加班时长说明' => '',
					'加班费用' => 0,
					'日期类型' => $dayType
				];
			}

			// 添加信息到该日期
			$groupedByDate[$dateStr]['提交备注'][] = $entry['message'];
			$groupedByDate[$dateStr]['提交文件'][] = $entry['files'];
			$groupedByDate[$dateStr]['加班时长'] += $overtimeMinutes;

			if(!empty($groupedByDate[$dateStr]['加班时长说明'])){
				$groupedByDate[$dateStr]['加班时长说明'] .= "; ";
			}
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


function parseSVNLogRegex(){

// 	$logText = "
// 版本: 214652
// 作者: ClassLi
// 日期: 2025年5月24日 18:48:23
// 信息:
// 使用千分位格式化数字
// ----
// 已修改 : /zh/app/zt/exports/zcfzb.class.php
// 已修改 : /zv/zx/x/

// 版本: 214646
// 作者: ClassLi
// 日期: 2025年5月24日 18:43:59
// 信息:
// 优化
// ----
// 已修改 : /zh/app/zt/exports/lrb.class.php

// 版本: 214645
// 作者: ClassLi
// 日期: 2025年5月24日 18:40:18
// 信息:
// 使用千分位格式化数字
// ----
// 已修改 : /zh/app/zt/exports/lrb.class.php

// 版本: 214626
// 作者: ClassLi
// 日期: 2025年5月24日 12:40:02
// 信息:
// 注释
// ----
// 已修改 : /zh/app/contract/contract.class.php

// 版本: 214609
// 作者: ClassLi
// 日期: 2025年5月24日 12:09:45
// 信息:
// 支持筛选
// ----
// 已修改 : /zh/app/contract/contract.class.php

// 版本: 214604
// 作者: ClassLi
// 日期: 2025年5月24日 11:48:15
// 信息:
// 增加筛选
// ----
// 已修改 : /zh/app/customer/customer.class.php

// 版本: 214597
// 作者: ClassLi
// 日期: 2025年5月24日 10:51:24
// 信息:
// 单据编号为 string 类型
// ----
// 已修改 : /scm/app/exports/exports.class.php";

foreach (glob("svn/*.txt") as $filename) {
 $logText .= file_get_contents($filename) ."\n";
}

// $logText = file_get_contents('svn/svnlog-72mao-20230401-20250612.txt');
// print_r($logText);
// exit();
	$entries = [];
	// 匹配完整的SVN记录模式
	// $pattern = '/版本:\s*(\d+)\s*作者:\s*([^\n]+)\s*日期:\s*([^\n]+)\s*信息:\s*([^\n]+)\s*----\s*已修改\s*:\s*([^\n]+)/';
	$pattern = '/版本: (\d+)\s+作者: (\S+)\s+日期: (.*?)\s+信息:\s*(.*?)\s*-{4}\s*(已修改 : .*?)(?=\s*版本: |$)/s';
	preg_match_all($pattern, $logText, $matches, PREG_SET_ORDER);
// var_dump($matches);
// exit();
	foreach($matches as $match){
		$entries[] = [
			'version' => trim($match[1]),
			'author' => trim($match[2]),
			'date' => trim($match[3]),
			'message' => trim($match[4]),
			'files' => trim($match[5])
		];
	}
	return $entries;
}

$entries = parseSVNLogRegex();
// print_r($entries);
// exit();
$arrZw = $arrWs = [];
foreach($entries as $key =>$value){
    $dateTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $value['date']);
    $timeStr = $dateTime->format('H:i:s');
    $dateStr = $dateTime->format('Y-m-d');

    if($timeStr >= '12:00:00' && $timeStr < '14:00:00'){ // 中午加班
		if($arrZw[$dateStr]){
			$lastKey = $arrZw[$dateStr]['key'];
			$lastTime = $arrZw[$dateStr]['time'];
			if($timeStr > $lastTime){ //当前时间大于 上次记录的时间 
				unset($entries[$lastKey]);// 把上次记录的时间对应的数据unset掉
				$arrZw[$dateStr] = ['key'=>$key,'time'=>$timeStr]; //更新当前时间
			}else{//当前时间小于 上次记录的时间
				unset($entries[$key]); //删除本次数据
			}
			continue;
		}else{
		 	$arrZw[$dateStr] = ['key'=>$key,'time'=>$timeStr]; 
		}
    }




    if($timeStr >= '18:30:00'){ // 晚上加班
		if($arrWs[$dateStr]){
			$lastKey = $arrWs[$dateStr]['key'];
			$lastTime = $arrWs[$dateStr]['time'];
			if($timeStr > $lastTime){ //当前时间大于 上次记录的时间 
				unset($entries[$lastKey]);// 把上次记录的时间对应的数据unset掉
				$arrWs[$dateStr] = ['key'=>$key,'time'=>$timeStr]; //更新当前时间
			}else{//当前时间小于 上次记录的时间
				unset($entries[$key]); //删除本次数据
			}
			continue;
		}else{
		 	$arrWs[$dateStr] = ['key'=>$key,'time'=>$timeStr]; 
		}
        
    }


}
// print_r($entries);
// exit();
// var_dump($entries);

// 使用解析后的数据
$analyzer = new SVNOvertimeAnalyzer();

foreach($entries as $entry){
	$analyzer->addEntry(
		$entry['version'],
		$entry['author'],
		$entry['date'],
		$entry['message'],
		$entry['files']
	);
}
// var_dump($analyzer->addEntr);

// 执行分析
$result = $analyzer->analyze();
$data[] = ['日期', '提交备注', '提交文件', '加班时长', '加班时长说明', '加班费用', '日期类型'];
foreach($result as $value){
	$data[] = array_values($value);
}
// 文件保存路径
$filePath = 'export_data.csv';
// 打开文件句柄
$file = fopen($filePath, 'w');

// 写入BOM头解决中文乱码
fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

// 写入数据
foreach($data as $row){
	fputcsv($file, $row);
}

// 关闭文件
fclose($file);

echo "CSV文件已生成到: " . realpath($filePath);
