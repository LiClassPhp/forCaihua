<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

/**
 * Excel 导出工具类
 */
class ExcelExport
{
    private $spreadsheet;
    private $sheet;
    private $currentRow = 1;
    private $aMergeCell = []; //合并列 例如:月份合并
    private $rowColorConfig = []; // 存储行背景色配置

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();

        // 初始化默认配置（可按需修改或通过setRowColorConfig方法覆盖）
        // $this->initDefaultRowColorConfig();
    }

    /**
     * 创建 Excel 文件
     * @param array $data 数据
     * @param string $filename 文件名
     * @param array|null $rowColors 可选：自定义行颜色配置
     */
    public function createExcel($data, $filename = 'export_data.xlsx', $rowColors = null)
    {
        // 设置第一个表头（合并表头）
        $this->setFirstHeader();
        $this->setSecondHeader();// 设置第二个表头（列标题）
        $this->writeData($data);// 写入数据
        if($rowColors !== null){
            $this->setRowColorConfig($rowColors);
            $this->setRowBackground();// 根据配置添加背景色与字体颜色
        }
        $this->setDynamicRowStyles($data);// 可选：根据数据内容动态设置样式
        $this->setColumnStyles();// 设置列宽和样式
        $this->saveFile($filename);// 保存文件

        return $filename;
    }

    /**
     * 初始化默认的行背景色配置
     */
    // private function initDefaultRowColorConfig()
    // {
    //     // 默认配置示例
    //     $this->rowColorConfig = [
    //         [
    //             'row' => 12,           // 行号
    //             'end' => 'T',          // 结束列
    //             'color' => 'E6F3FF',   // 背景色
    //             'fontColor' => '000000' // 字体颜色
    //         ],
    //         [
    //             'row' => 15,           // 行号
    //             'start' => 'C',        // 起始列（可选，默认为A）
    //             'end' => 'G',          // 结束列
    //             'color' => 'FFF2CC',   // 背景色
    //             'fontColor' => '333333' // 字体颜色
    //         ],
    //         [
    //             'row' => [5, 12, 19, 26], // 多行，例如每个周六的行
    //             'start' => 'A',
    //             'end' => 'T',
    //             'color' => 'F5F5F5',
    //             'fontColor' => '333333'
    //         ]
    //     ];
    // }

    /**
     * 设置自定义的行背景色配置
     * @param array $config 配置数组
     */
    public function setRowColorConfig(array $config)
    {
        $this->rowColorConfig = $config;
    }

    /**
     * 根据配置添加背景色与字体颜色
     * 支持：单个行、多个行、根据条件动态设置等
     */
    private function setRowBackground()
    {
        if(empty($this->rowColorConfig)) return;// 如果没有配置，则返回
        foreach($this->rowColorConfig as $config){
            $rows = $config['row'] ?? null;// 获取行号配置
            if(is_array($rows)){// 如果是数组，处理多行
                foreach($rows as $rowNumber){
                    $this->applyRowStyle(
                        $rowNumber,
                        $config['start'] ?? 'A',
                        $config['end'] ?? 'T',
                        $config['color'] ?? 'FFFFFF',
                        $config['fontColor'] ?? '000000',
                        $config['condition'] ?? null
                    );
                }
            }elseif($rows !== null){
                $this->applyRowStyle(// 单个行号
                    $rows,
                    $config['start'] ?? 'A',
                    $config['end'] ?? 'T',
                    $config['color'] ?? 'FFFFFF',
                    $config['fontColor'] ?? '000000',
                    $config['condition'] ?? null
                );
            }
        }
        $this->setZebraStripes();// 同时设置斑马条纹效果（可选，提高可读性）
    }

    /**
     * 为单行应用样式
     * @param int $rowNumber 行号
     * @param string $startColumn 起始列
     * @param string $endColumn 结束列
     * @param string $color 背景色
     * @param string $fontColor 字体颜色
     * @param string|null $condition 条件标识（可选）
     */
    private function applyRowStyle($rowNumber, $startColumn, $endColumn, $color, $fontColor, $condition = null)
    {
        // 检查行号是否有效
        if($rowNumber < 3){
            // 前两行是表头，跳过或特殊处理
            return;
        }

        // 检查行是否存在（不超过当前最大行）
        $maxRow = max($this->currentRow - 1, 3); // 至少从第3行开始
        if($rowNumber > $maxRow){
            return;
        }

        // 构建范围字符串
        $range = $startColumn . $rowNumber . ':' . $endColumn . $rowNumber;

        // 构建样式数组
        $styleArray = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => $color
                ]
            ]
        ];

        // 如果指定了字体颜色
        if($fontColor && $fontColor !== '000000'){
            $styleArray['font'] = [
                'color' => [
                    'rgb' => $fontColor
                ]
            ];
        }

        // 根据条件添加特殊样式
        if($condition === 'weekend'){
            // 周末样式
            $styleArray['borders'] = [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => 'FF6B6B']
                ]
            ];
        }elseif($condition === 'holiday'){
            // 节假日样式
            $styleArray['font']['bold'] = true;
        }

        // 应用样式
        $this->sheet->getStyle($range)->applyFromArray($styleArray);
    }

    /**
     * 设置斑马条纹效果（交替行颜色）
     * @param string $oddColor 奇数行颜色
     * @param string $evenColor 偶数行颜色
     * @param int $startRow 起始行
     */
    private function setZebraStripes($oddColor = 'FFFFFF', $evenColor = 'F9F9F9', $startRow = 3)
    {
        // 确定结束行（当前数据行的最后一行）
        $endRow = max($this->currentRow - 1, $startRow);

        // 设置斑马条纹
        for($row = $startRow; $row <= $endRow; $row++){
            $color = ($row % 2 == 0) ? $evenColor : $oddColor;
            $range = 'A' . $row . ':T' . $row;

            $this->sheet->getStyle($range)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color]
                ]
            ]);
        }
    }

    /**
     * 根据数据内容动态设置行样式
     * @param array $data 数据数组
     */
    private function setDynamicRowStyles($data)
    {
        $groupCount = []; //需要合并的数量 用于月份合并
        $rowNumber = 3; // 数据起始行
        foreach($data as $index => $rowData){
            $currentRow = $rowNumber + $index;
            if($currentRow < 3) continue;// 跳过表头
            // 示例1：根据星期设置颜色
            if(str_ends_with($rowData['date'], '星期六') && $rowData['sum_加班费'] > 0){ //最终汇总存在加班费
                $this->applyRowStyle($currentRow, 'A', 'S', 'FFF0F0', 'FF0000', 'weekend');
            }

            // // 示例2：根据加班时长设置颜色
            // $overtimeMinutes = intval($rowData[16] ?? 0); // Q列是汇总加班时长（索引16）
            // if($overtimeMinutes > 300){
            //     // 加班超过5小时，高亮显示
            //     $this->applyRowStyle($currentRow, 'Q', 'S', 'FFCCCC', '990000');
            // }elseif($overtimeMinutes > 0){
            //     // 有加班但不超过5小时
            //     $this->applyRowStyle($currentRow, 'Q', 'S', 'E6F3FF', '000000');
            // }

            // 示例3：根据备注内容设置颜色
            $remark = $rowData['备注'] ?? ''; // T列是备注（索引20）
            if(!empty($remark)){
                $this->applyRowStyle($currentRow, 'U', 'U', 'FFE6CC', '663300');
            }
            $Ym = $rowData['Ym'];
            if(!isset($groupCount[$Ym])){
                $groupCount[$Ym] = ['start' => $currentRow, 'count' => 0];
            }
            $groupCount[$Ym]['count']++;
        }
        // 特殊处理 王律需要每月的工资
        foreach($groupCount as $info){
            $endRow = $info['start'] + $info['count'] - 1;
            $this->sheet->mergeCells("A{$info['start']}:A{$endRow}"); // 合并列 年月
            $tEnd = $this->sheet->getCell("T{$endRow}")->getValue(); //获取合并列最后的值
            $this->sheet->setCellValue("T{$info['start']}", $tEnd); // 赋值给初始列 因为最终合并后的值为起始列的值
            $this->sheet->mergeCells("T{$info['start']}:T{$endRow}"); // 合并列 每月加班费

        }
    }

    public function mergeCells()
    {
        foreach($this->aMergeCell as $range){
            p($range);
            $this->sheet->mergeCells($range); // 合并列
        }
    }

    public function addComment($column, $row)
    {
        $column_row = $column . $row;
        // 给 A1 单元格添加备注
        $comment = $this->sheet->getComment($column_row);
        $comment->getText()->createTextRun("这是 $column_row 单元格的备注内容");
        $comment->getText()->createTextRun("\n");
        $comment->getText()->createTextRun('第二行备注')->getFont()->setBold(true);
        // 设置备注作者（可选）
        $comment->setAuthor('LiClass');
        // 设置备注大小和位置
        $commentB2 = $this->sheet->getComment($column_row);
        $commentB2->setWidth('200px');
        $commentB2->setHeight('200px');
        // 设置备注显示方式
        // $commentB2->setVisible(true); // 默认打开时就显示备注 开启会很乱
    }

    /**
     * 设置第一个表头（合并单元格）
     */
    private function setFirstHeader()
    {
        $headers = [
            'A1' => '日期',
            'C1' => 'svn提交日志',
            'G1' => 'nginx日志',
            'K1' => '企业微信聊天截图',
            'M1' => '下班视频记录',
            'O1' => '企业微信打卡',
            'Q1' => '最终汇总',
            'U1' => '备注',
        ];

        // 写入表头文字
        foreach($headers as $cell => $value){
            $this->sheet->setCellValue($cell, $value);
        }

        // 设置合并单元格
        $this->sheet->mergeCells('A1:B1'); // 日期（2列）
        $this->sheet->mergeCells('C1:F1'); // svn提交日志（4列）
        $this->sheet->mergeCells('G1:J1'); // nginx日志（4列）
        $this->sheet->mergeCells('K1:L1'); // 企业微信聊天截图（2列）
        $this->sheet->mergeCells('M1:N1'); // 下班视频记录（2列）
        $this->sheet->mergeCells('O1:P1'); // 企业微信打卡（2列）
        $this->sheet->mergeCells('Q1:T1'); // 最终汇总（4列）

        // 设置第一个表头的样式
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'] // 深蓝色
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $this->sheet->getStyle('A1:U1')->applyFromArray($headerStyle);
        $this->sheet->getRowDimension(1)->setRowHeight(30);// 设置行高
        $this->currentRow = 2;
    }

    /**
     * 设置第二个表头（列标题）
     */
    private function setSecondHeader()
    {
        $headers = [
            // 日期
            'A2' => '年-月',
            'B2' => '年-月-日-星期',
            // svnLog
            'C2' => '最早提交时间(周末)',
            'D2' => '最晚提交时间',
            'E2' => '加班时长(分钟)',
            'F2' => '加班时长说明',
            // nginxLog
            'G2' => '最早提交时间(周末)',
            'H2' => '最晚提交时间',
            'I2' => '加班时长(分钟)',
            'J2' => '加班时长说明',
            // qvChat
            'K2' => '加班时长(分钟)',
            'L2' => '加班时长说明',
            // video
            'M2' => '加班时长(分钟)',
            'N2' => '加班时长说明',
            // qv
            'O2' => '上班时间',
            'P2' => '下班时间',
            // sum
            'Q2' => '加班时长(分钟)',
            'R2' => '加班时长说明',
            'S2' => '加班费',
            'T2' => '加班费(按月统计)',
            // 备注
            'U2' => '备注',
        ];

        // 写入列标题
        foreach($headers as $cell => $value){
            $this->sheet->setCellValue($cell, $value);
        }

        // 设置第二个表头的样式
        $subHeaderStyle = [
            'font' => [
                'bold' => true,
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C5D9F1'] // 浅蓝色
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];
        $this->sheet->getStyle('A2:U2')->applyFromArray($subHeaderStyle);
        // 设置行高
        $this->sheet->getRowDimension(2)->setRowHeight(25);
        $this->currentRow = 3;
    }

    /**
     * 写入数据行
     */
    private function writeData($data)
    {
        foreach($data as $row){
            $col = 'A';
            foreach($row as $value){
                $cell = $col . $this->currentRow;
                $this->sheet->setCellValue($cell, $value);

                // 设置数据行样式
                $dataStyle = [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9D9D9']
                        ]
                    ]
                ];

                // 根据列设置不同的背景色
                $bgColors = [
                    'A:B' => 'B3E5FC', // 日期和星期 - 浅天蓝色（时间基础类）
                    'C:F' => 'C8E6C9', // svn提交日志 - 淡绿色（开发日志类）
                    'G:J' => 'A5D6A7', // nginx日志 - 稍深的绿色（运维日志类）
                    'K:L' => 'FFCC80', // 企业微信聊天截图 - 浅橙色（沟通记录类）
                    'M:N' => 'CE93D8', // 下班视频记录 - 浅紫色（个人记录类）
                    'O:P' => '90CAF9', // 企业微信打卡 - 蓝色（考勤管理类）
                    'Q:T' => 'EF9A9A', // 最终汇总 - 浅珊瑚红（汇总统计类）
                    'U' => 'E0E0E0',  // 备注 - 浅灰色（辅助信息类）
                ];
                $colIndex = ord($col) - ord('A') + 1; // A=1, B=2, ...// 判断当前列属于哪个范围
                if($colIndex <= 2){
                    $range = 'A:B';
                }elseif($colIndex <= 6){
                    $range = 'C:F';
                }elseif($colIndex <= 10){
                    $range = 'G:J';
                }elseif($colIndex <= 12){
                    $range = 'K:L';
                }elseif($colIndex <= 14){
                    $range = 'M:N';
                }elseif($colIndex <= 16){
                    $range = 'O:P';
                }elseif($colIndex <= 20){
                    $range = 'Q:T';
                }elseif($colIndex == 21){
                    $range = 'U';
                }

                if(isset($bgColors[$range])){
                    $dataStyle['fill'] = [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColors[$range]]
                    ];
                }
                $this->sheet->getStyle($cell)->applyFromArray($dataStyle);
                // 特别处理加班费列，设置为货币格式
                if($col == 'S'){
                    $this->sheet->getStyle($cell)->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
                $col++;
            }
            $this->sheet->getRowDimension($this->currentRow)->setRowHeight(20);// 设置行高

            $this->currentRow++;
        }
    }

    /**
     * 设置列宽
     */
    private function setColumnStyles()
    {
        $this->sheet->getColumnDimension('A')->setWidth(12); // 日期
        $this->sheet->getColumnDimension('B')->setWidth(18); // 星期
        $this->sheet->getColumnDimension('C')->setWidth(18); // svn-最早时间
        $this->sheet->getColumnDimension('D')->setWidth(15); // svn-最晚时间
        $this->sheet->getColumnDimension('E')->setWidth(20); // svn-加班时长
        $this->sheet->getColumnDimension('F')->setWidth(35); // svn-加班说明
        $this->sheet->getColumnDimension('G')->setWidth(18); // nginx-最早时间
        $this->sheet->getColumnDimension('H')->setWidth(15); // nginx-最晚时间
        $this->sheet->getColumnDimension('I')->setWidth(20); // nginx-加班时长
        $this->sheet->getColumnDimension('J')->setWidth(35); // nginx-加班说明
        $this->sheet->getColumnDimension('K')->setWidth(20); // 微信聊天记录-加班时长
        $this->sheet->getColumnDimension('L')->setWidth(35); // 微信聊天记录-说明
        $this->sheet->getColumnDimension('M')->setWidth(20); // 下班视频记录-加班时长
        $this->sheet->getColumnDimension('N')->setWidth(35); // 下班视频记录-说明
        $this->sheet->getColumnDimension('O')->setWidth(12); // 打卡-上班时间
        $this->sheet->getColumnDimension('P')->setWidth(12); // 打卡-下班时间
        $this->sheet->getColumnDimension('Q')->setWidth(20); // 汇总-加班时长
        $this->sheet->getColumnDimension('R')->setWidth(30); // 汇总-说明
        $this->sheet->getColumnDimension('S')->setWidth(12); // 汇总-加班费
        $this->sheet->getColumnDimension('T')->setWidth(16); // 汇总每月-加班费
        $this->sheet->getColumnDimension('U')->setWidth(25); // 备注

        $this->sheet->setTitle('加班记录表');// 设置工作表的标题
        $this->sheet->freezePane('A3');// 冻结前两行表头
    }

    /**
     * 保存文件
     */
    private function saveFile($filename)
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($filename);
    }
}