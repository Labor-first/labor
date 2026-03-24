<?php

namespace app\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelService
{
    /**
     * 导入 Excel
     */
    public function import(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];

        // 从第2行开始读取（第1行是表头）
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }

            // 根据实际 Excel 列顺序映射
            if (!empty($cells[0])) {
                $rowData = [
                    'student_id' => $cells[0],
                    'name' => $cells[1],
                    'email' => $cells[2],
                    'phone' => $cells[3] ?? null,
                    'password' => $cells[4] ?? null
                ];
                $data[] = $rowData;
            }
        }

        return $data;
    }

    /**
     * 导出 Excel
     */
    public function export(array $data, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // 设置表头
        $headers = array_keys($data[0] ?? []);
        $worksheet->fromArray([$headers], null, 'A1');

        // 设置数据
        $worksheet->fromArray($data, null, 'A2');

        // 自动调整列宽
        foreach (range('A', $headers[count($headers) - 1]) as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 保存文件
        $filename .= '.xlsx';
        $path = 'exports/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/' . $path));

        return $path;
    }
}
