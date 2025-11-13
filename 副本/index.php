<?php

require 'php.class.php';
require 'excel.php';
class SVNOvertimeAnalyzer{
	public $workHours = 8;
	public $hourlyRate= 91.95; // 假设每小时基准工资100元，请根据实际情况调整
	private $entries = [];

	/**
	 * 添加SVN记录
	 * @param string $version 版本号
	 * @param string $author 作者
	 * @param string $date 日期时间
	 * @param string $message 提交信息
	 * @param string $files 修改的文件
	 */
	public function addEntry($entry){
		$this->entries[] = $entry;

	}

	/**
	 * 分析SVN记录并生成报告
	 * @return array 分析结果
	*/
	public function analyze(){
		$groupedByDate = [];// 按日期分组
		foreach($this->entries as $entry){
			
			$dateTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $entry['date']);// 解析日期时间
			if(!$dateTime){
				continue; // 跳过格式不正确的记录
			}
			$overtimePay = 0;// 计算加班费用 (工作日1.5倍，周末2倍)
			$minuteRate = $this->hourlyRate / 60; //分钟薪资
			$dateStr = $dateTime->format('Y-m-d');
			$timeStr = $dateTime->format('H:i:s');
			$dayOfWeek = $dateTime->format('w'); // 0=周日, 1=周一, ..., 6=周六

			// 判断是否是周末
			$isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
			$dayType = $isWeekend ? '周末' : '工作日';
			$overtimeMinutes = 0; // 
			$overtimeDescription = ''; // 加班描述
			if($isWeekend){
				$overtimeMinutes = 480; //暂时写死
				$overtimePay = $overtimeMinutes * $minuteRate * 2; // 周末2倍
			}else{// 检查是否在加班时间段内
				// 12:00-14:00之间
				if($timeStr >= '12:00:00' && $timeStr < '14:00:00'){
					$start = new DateTime($dateStr . ' 12:00:00');
					$interval = $start->diff($dateTime);
					$minutes = $interval->h * 60 + $interval->i;
					$overtimeMinutes += $minutes;
					$minutes && $overtimeDescription .= "午休加班: {$minutes}分钟";
				}

				$qvEndMinutes = 0;
				if($entry['考勤概况-最晚']){
					$start = new DateTime($dateStr . ' 18:30:00');
					if(strpos($entry['考勤概况-最晚'],'次日')!== false){
						$qvEndDate = str_replace('次日','',$entry['考勤概况-最晚']);
						$end = DateTime::createFromFormat('Y-m-d H:i', $dateStr.' '.$qvEndDate)->modify('+1day');// 结束时间设为次日
							
					}else{
						$end = DateTime::createFromFormat('Y-m-d H:i', $dateStr . ' '. $entry['考勤概况-最晚']);
					};
					$diff = $start->diff($end);// 计算时间差
					$qvEndMinutes = ($diff->h * 60) + $diff->i; // 将小时转换为分钟并加上剩余分钟
				}
				
				if($timeStr >= '18:30:00'){
					$start = new DateTime($dateStr . ' 18:30:00');
					$interval = $start->diff($dateTime);
					$minutesSvn = $interval->h * 60 + $interval->i;
					$minutes = $qvEndMinutes>$minutesSvn ? $qvEndMinutes : $minutesSvn; //打卡记录与SVN提交记录取最大值
					$overtimeMinutes += $minutes;

					if(!empty($overtimeDescription)){
						$overtimeDescription .= ", ";
					}
					$minutes && $overtimeDescription .= "晚间加班: {$minutes}分钟,";
				}
				$overtimePay = $overtimeMinutes * $minuteRate * 1.5; // 工作日1.5倍
			}
		
			// 如果没有加班，跳过此记录
			if($overtimeMinutes == 0){
				// continue;
			}
			// 按日期分组
			if(!isset($groupedByDate[$dateStr])){
				$groupedByDate[$dateStr] = [
					'日期' => $dateStr,
					'提交备注' => [],
					'提交文件' => [],
					'最后一次代码提交日期'=>$dateStr.' '.$timeStr,
					'加班时长' => 0,
					'加班时长说明' => '',
					'加班费用' => 0,
					'日期类型' => $dayType,
					'打卡-时间'=>$entry['时间'],
					'打卡-考勤概况-最早'=>$entry['考勤概况-最早'],
					'打卡-考勤概况-最晚'=>$entry['考勤概况-最晚'],
					'打卡-时间考勤概况-实际工作时长(小时)'=>$entry['考勤概况-实际工作时长(小时)'],
					'打卡-考勤概况-考勤结果'=>$entry['考勤概况-考勤结果'],
					'打卡-下班1-打卡时间'=>$entry['下班1-打卡时间'],
					'打卡-下班1-打卡状态'=>$entry['下班1-打卡状态'],
					'打卡-打卡时间记录'=>$entry['打卡时间记录'],
				];
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

// 使用正则解析SVN日志信息 进行分组
function parseSVNLogRegex(){

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


	foreach (glob("svn/*.txt") as $filename) { //获取所有SVN文件
	$logText .= file_get_contents($filename) ."\n"; //字符串合并
	}

	$entries = [];
	// 匹配完整的SVN记录模式
	// $pattern = '/版本:\s*(\d+)\s*作者:\s*([^\n]+)\s*日期:\s*([^\n]+)\s*信息:\s*([^\n]+)\s*----\s*已修改\s*:\s*([^\n]+)/';
	$pattern = '/版本: (\d+)\s+作者: (\S+)\s+日期: (.*?)\s+信息:\s*(.*?)\s*-{4}\s*(已修改 : .*?)(?=\s*版本: |$)/s';
	preg_match_all($pattern, $logText, $matches, PREG_SET_ORDER);

	foreach($matches as $match){ //格式化
		$entries[] = [
			'version' => trim($match[1]),
			'author' => trim($match[2]),
			'date' => trim($match[3]),
			'message' => trim($match[4]),
			'files' => trim($match[5]) . '【' .trim($match[3]). '】',
		];
	}
	return $entries;
}

$entries = parseSVNLogRegex();




$arrZw = $arrWs = $week = []; //进一步格式化数据
foreach($entries as $key =>$value){
    $dateTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $value['date']);
    $timeStr = $dateTime->format('H:i:s');
    $dateStr = $dateTime->format('Y-m-d');

	$dayOfWeek = $dateTime->format('w'); // 0=周日, 1=周一, ..., 6=周六

	//如果是周末 应该按照企微打卡记录来算 这里简单算一整天480分钟加班时间 周末暂时只保留一条数据
	if(($dayOfWeek == 0 || $dayOfWeek == 6)){ //判断是否是周末
		if($week[$dateStr]){
			unset($entries[$key]); //删除本次数据
		}else{
			$week[$dateStr] = 1;
		}

	}else{
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
}

// // 按照日期倒序排列
// usort($entries, function($a, $b) {
//     // 将中文日期转换为时间戳进行比较
//     $dateA = DateTime::createFromFormat('Y年m月d日 H:i:s', $a['date']);
//     $dateB = DateTime::createFromFormat('Y年m月d日 H:i:s', $b['date']);
    
//     return $dateB->getTimestamp() - $dateA->getTimestamp();
// });



$Qv = new Qv();
$fileName = '上下班打卡_日报_';
$qvData = [];
foreach (glob("qv/*.xlsx") as $filename) {
 $qvData= array_merge($qvData,$Qv->getData($filename));
}

// 按照日期倒序排列
// usort($qvData, function($a, $b) {
//     // 直接处理日期字符串，将/替换为-，并提取日期部分
//     $qvA = str_replace('/', '-', explode(' ', $a['时间'])[0]);
//     $qvB = str_replace('/', '-', explode(' ', $b['时间'])[0]);
    
//     return strtotime($qvB) - strtotime($qvA);
// });


/*********************************合并svn与qv数据开始 *********************************/
// 转换日期格式为统一格式进行匹配
function convertDateToStandard($dateStr, $type) {
    if ($type === 'arr1') {
        // 处理 arr1 的日期格式：2023/06/01 星期四 -> 2023-06-01
        $datePart = explode(' ', $dateStr)[0];
        return date('Y-m-d', strtotime(str_replace('/', '-', $datePart)));
    } else {
        // 处理 arr2 的日期格式：2025年9月1日 16:33:45 -> 2025-09-01
        $datePart = explode(' ', $dateStr)[0];
        // 将中文日期转换为标准格式
        $datePart = str_replace(['年', '月', '日'], ['-', '-', ''], $datePart);
        // 处理月份和日期为两位数
        $parts = explode('-', $datePart);
        if (count($parts) === 3) {
            $parts[1] = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $parts[2] = str_pad($parts[2], 2, '0', STR_PAD_LEFT);
            return implode('-', $parts);
        }
        return $datePart;
    }
}

// 创建合并后的数组
$mergedArray = [];

// 处理 arr2
foreach ($entries as $item) {
    $standardDate = convertDateToStandard($item['date'], 'arr2');
    if (!isset($mergedArray[$standardDate])) {
        $mergedArray[$standardDate] = [
            'date' => $standardDate,
            'arr1_data' => [],
            'arr2_data' => []
        ];
    }
    $mergedArray[$standardDate]['arr2_data'][] = $item;
}
// 处理 arr1
foreach ($qvData as $item) {
    $standardDate = convertDateToStandard($item['时间'], 'arr1');
    if (!isset($mergedArray[$standardDate])) {
        $mergedArray[$standardDate] = [
            'date' => $standardDate,
            'arr1_data' => [],
            'arr2_data' => []
        ];
    }
    $mergedArray[$standardDate]['arr1_data'][] = $item;
}


// $mergedArray= ['2025-09-21' =>$mergedArray['2025-09-21']];
// p($mergedArray);
$aFinal=[];
foreach($mergedArray as $value){
	foreach($value['arr2_data'] as $val){
			$aFinal[] = array_merge($value['arr1_data'][0] ?? [],$val ?? []);//qv打卡只有一条 所以可以用0；
		}
}
usort($aFinal, function($a, $b) {
	$aTime = $bTime = 0;
	if($a['时间']){
		$aTime = strtotime(str_replace('/', '-', explode(' ', $a['时间'])[0]));
	}elseif($a['date']){
		$aTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $a['date'])->getTimestamp();
	}

	if($b['时间']){
		$bTime = strtotime(str_replace('/', '-', explode(' ', $b['时间'])[0]));
	}elseif($b['date']){
		$bTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $b['date'])->getTimestamp();
		// p($bTime);
	}

    return $aTime - $bTime;
});
/*********************************合并svn与qv数据开始 *********************************/
$analyzer = new SVNOvertimeAnalyzer();
foreach($aFinal as $arr){ //追加到类属性中
	$analyzer->addEntry($arr);
	
}
$result = $analyzer->analyze();// 执行分析加班时长

$data[] = ['日期', '提交备注', '提交文件', '最后一次代码提交日期','加班时长', '加班时长说明', '加班费用', '日期类型','打卡-时间','打卡-考勤概况-最早','打卡-考勤概况-最晚','打卡-时间考勤概况-实际工作时长(小时)','打卡-考勤概况-考勤结果','打卡-下班1-打卡时间','打卡-下班1-打卡状态','打卡-打卡时间记录'];
foreach($result as $value){
	$data[] = array_values($value);
}
$filePath = 'export_data.csv';// 文件保存路径
$file = fopen($filePath, 'w');// 打开文件句柄
fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));// 写入BOM头解决中文乱码
foreach($data as $row){
	fputcsv($file, $row);// 写入数据
}
fclose($file);// 关闭文件
echo "CSV文件已生成到: " . realpath($filePath);
