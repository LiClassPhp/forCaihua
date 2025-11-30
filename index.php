<?php
require 'php.php';
require 'Qv.php';
require 'SvnLog.php';

class Analyzer{
	public $workHours = 8;
	public $hourlyRate= 91.95; // 假设每小时基准工资100元，请根据实际情况调整
    // 分析SVN记录
	public function analyze($data){
		$groupedByDate = [];// 按日期分组
		foreach($data as $entry){
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
				if(isset($entry['考勤概况-最晚']) && $entry['考勤概况-最晚']){
					$start = new DateTime($dateStr . ' 18:30:00');
					if(strpos($entry['考勤概况-最晚'],'次日')!== false){
						$qvEndDate = str_replace('次日','',$entry['考勤概况-最晚']);
						$end = DateTime::createFromFormat('Y-m-d H:i', $dateStr.' '.$qvEndDate)->modify('+1day');// 结束时间设为次日
					}else{
						$end = DateTime::createFromFormat('Y-m-d H:i', $dateStr . ' '. $entry['考勤概况-最晚']);
					};
                    $end && $diff = $start->diff($end);// 计算时间差(20230613加班到凌晨 然后14号请假 变成未打卡没有打卡日期)
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
					'打卡-时间'=>$entry['时间']??'',
					'打卡-考勤概况-最早'=>$entry['考勤概况-最早']??'',
					'打卡-考勤概况-最晚'=>$entry['考勤概况-最晚']??'',
					'打卡-时间考勤概况-实际工作时长(小时)'=>$entry['考勤概况-实际工作时长(小时)']??'',
					'打卡-考勤概况-考勤结果'=>$entry['考勤概况-考勤结果']??'',
					'打卡-下班1-打卡时间'=>$entry['下班1-打卡时间']??'',
					'打卡-下班1-打卡状态'=>$entry['下班1-打卡状态']??'',
					'打卡-打卡时间记录'=>$entry['打卡时间记录']??'',
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

    // 合并企业微信打卡记录和SVN代码提交记录
    public function merge($svnData,$qvData){
        $mergedArray = [];// 创建合并后的数组
        foreach ($svnData as $item) {// 处理 svnLog日志
            $standardDate = self::convertDateToStandard($item['date'], 'svn');
            if (!isset($mergedArray[$standardDate])) {
                $mergedArray[$standardDate] = [
                    'date' => $standardDate,
                    'arr1_data' => [],
                    'arr2_data' => []
                ];
            }
            $mergedArray[$standardDate]['arr2_data'][] = $item;
        }

        foreach ($qvData as $item) {// 处理企业微信打卡记录
            $standardDate = self::convertDateToStandard($item['时间'], 'qv');
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
        $aFinal = []; //最终数组
        foreach($mergedArray as $value){
            foreach($value['arr2_data'] as $val){
                $aFinal[] = array_merge($value['arr1_data'][0] ?? [],$val ?? []);//qv打卡只有一条 所以可以用0；
            }
        }
        usort($aFinal, function($a, $b) { //排序
            $aTime = $bTime = 0;
            if(isset($a['时间']) && $a['时间']){
                $aTime = strtotime(str_replace('/', '-', explode(' ', $a['时间'])[0]));
            }elseif($a['date']){
                $aTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $a['date'])->getTimestamp();
            }

            if(isset($b['时间']) && $b['时间']){
                $bTime = strtotime(str_replace('/', '-', explode(' ', $b['时间'])[0]));
            }elseif($b['date']){
                $bTime = DateTime::createFromFormat('Y年m月d日 H:i:s', $b['date'])->getTimestamp();
            }
            return $aTime - $bTime;
        });
        return $aFinal;
    }

    // 转换日期格式为统一格式进行匹配
   public function convertDateToStandard($dateStr, $type) {
        if ($type === 'qv') {
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

    // 获取最终需要导出Excel的数据
    public function getExcelData(){
        $qvObj = new Qv();
        $qvData = $qvObj->getData();
        $svnObj = new SvnLog();
        $svnData = $svnObj->getData();
        $data = self::merge($svnData,$qvData); //合并企业微信打卡记录和SVN代码提交记录
        $result = self::analyze($data);
        return $result;
    }
}

$analyzerObj = new Analyzer();
$result = $analyzerObj->getExcelData();// 执行分析加班时长

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
