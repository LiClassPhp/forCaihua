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

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    /**
     * 创建 Excel 文件
     * @param array $data 数据
     * @param string $filename 文件名
     */
    public function createExcel($data, $filename = 'export_data.xlsx')
    {
        // 设置第一个表头（合并表头）
        $this->setFirstHeader();

        // 设置第二个表头（列标题）
        $this->setSecondHeader();

        // 写入数据
        $this->writeData($data);

        // 设置列宽和样式
        $this->setColumnStyles();

        // 保存文件
        $this->saveFile($filename);

        return $filename;
    }

    /**
     * 设置第一个表头（合并单元格）
     */
    private function setFirstHeader()
    {
        $headers = [
            'A1' => '日期',
            'B1' => '星期',
            'C1' => 'svn提交日志',
            'H1' => 'nginx日志',
            'M1' => '企业微信聊天截图',
            'P1' => '企业微信打卡',
            'R1' => '最终汇总',
            'U1' => '备注'
        ];

        // 写入表头文字
        foreach($headers as $cell => $value){
            $this->sheet->setCellValue($cell, $value);
        }

        // 设置合并单元格
        $this->sheet->mergeCells('C1:G1'); // svn提交日志（5列）
        $this->sheet->mergeCells('H1:L1'); // nginx日志（5列）
        $this->sheet->mergeCells('M1:O1'); // 企业微信聊天截图（3列）
        $this->sheet->mergeCells('P1:Q1'); // 企业微信打卡（2列）
        $this->sheet->mergeCells('R1:T1'); // 最终汇总（3列）

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

        // 设置行高
        $this->sheet->getRowDimension(1)->setRowHeight(30);

        $this->currentRow = 2;
    }

    /**
     * 设置第二个表头（列标题）
     */
    private function setSecondHeader()
    {
        $headers = [
            'A2' => '日期',
            'B2' => '星期',
            'C2' => '加班时长',
            'D2' => '加班时长说明',
            'E2' => '最早提交时间(周末)',
            'F2' => '最晚提交时间',
            'G2' => '加班费',
            'H2' => '加班时长',
            'I2' => '加班时长说明',
            'J2' => '最早提交时间(周末)',
            'K2' => '最晚提交时间',
            'L2' => '加班费',
            'M2' => '加班时长',
            'N2' => '加班时长说明',
            'O2' => '加班费',
            'P2' => '上班时间',
            'Q2' => '下班时间',
            'R2' => '加班时长',
            'S2' => '加班时长说明',
            'T2' => '加班费',
            'U2' => '备注'
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
                    'A:B' => 'E6F3FF', // 日期和星期 - 浅蓝色
                    'C:G' => 'FFF2CC', // svn提交日志 - 浅黄色
                    'H:L' => 'E2EFDA', // nginx日志 - 浅绿色
                    'M:O' => 'FCE4D6', // 企业微信聊天截图 - 浅橙色
                    'P:Q' => 'D9E1F2', // 企业微信打卡 - 浅紫色
                    'R:T' => 'FFE6E6', // 最终汇总 - 浅粉色
                    'U' => 'F2F2F2'  // 备注 - 灰色
                ];
                $colIndex = ord($col) - ord('A') + 1; // A=1, B=2, ...// 判断当前列属于哪个范围
                if($colIndex <= 2){
                    $range = 'A:B';
                }elseif($colIndex <= 7){
                    $range = 'C:G';
                }elseif($colIndex <= 12){
                    $range = 'H:L';
                }elseif($colIndex <= 15){
                    $range = 'M:O';
                }elseif($colIndex <= 17){
                    $range = 'P:Q';
                }elseif($colIndex <= 20){
                    $range = 'R:T';
                }else{
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
                if($col == 'G' || $col == 'L' || $col == 'O' || $col == 'T'){
                    $this->sheet->getStyle($cell)->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                $col++;
            }

            // 设置行高
            $this->sheet->getRowDimension($this->currentRow)->setRowHeight(20);

            $this->currentRow++;
        }
    }

    /**
     * 设置列宽
     */
    private function setColumnStyles()
    {
        // 设置列宽
        $this->sheet->getColumnDimension('A')->setWidth(12); // 日期
        $this->sheet->getColumnDimension('B')->setWidth(10); // 星期
        $this->sheet->getColumnDimension('C')->setWidth(12); // svn-加班时长
        $this->sheet->getColumnDimension('D')->setWidth(20); // svn-说明
        $this->sheet->getColumnDimension('E')->setWidth(18); // svn-最早时间
        $this->sheet->getColumnDimension('F')->setWidth(15); // svn-最晚时间
        $this->sheet->getColumnDimension('G')->setWidth(12); // svn-加班费
        $this->sheet->getColumnDimension('H')->setWidth(12); // nginx-加班时长
        $this->sheet->getColumnDimension('I')->setWidth(20); // nginx-说明
        $this->sheet->getColumnDimension('J')->setWidth(18); // nginx-最早时间
        $this->sheet->getColumnDimension('K')->setWidth(15); // nginx-最晚时间
        $this->sheet->getColumnDimension('L')->setWidth(12); // nginx-加班费
        $this->sheet->getColumnDimension('M')->setWidth(12); // 微信-加班时长
        $this->sheet->getColumnDimension('N')->setWidth(20); // 微信-说明
        $this->sheet->getColumnDimension('O')->setWidth(12); // 微信-加班费
        $this->sheet->getColumnDimension('P')->setWidth(12); // 打卡-上班时间
        $this->sheet->getColumnDimension('Q')->setWidth(12); // 打卡-下班时间
        $this->sheet->getColumnDimension('R')->setWidth(12); // 汇总-加班时长
        $this->sheet->getColumnDimension('S')->setWidth(20); // 汇总-说明
        $this->sheet->getColumnDimension('T')->setWidth(12); // 汇总-加班费
        $this->sheet->getColumnDimension('U')->setWidth(25); // 备注

        // 设置工作表的标题
        $this->sheet->setTitle('加班记录表');

        // 冻结前两行表头
        $this->sheet->freezePane('A3');
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
