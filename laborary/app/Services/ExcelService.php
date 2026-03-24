<?php

namespace App\Services;

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

        $headerRow = $worksheet->getRowIterator(1)->current();
        $headers = [];
        foreach ($headerRow->getCellIterator() as $cell) {
            $headers[] = trim($cell->getValue() ?? '');
        }

        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }

            if (!empty($cells[0])) {
                $rowData = [];
                foreach ($headers as $index => $header) {
                    if (!empty($header)) {
                        $rowData[$header] = $cells[$index] ?? null;
                    }
                }
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
        if (empty($data)) {
            throw new \Exception('导出数据不能为空');
        }

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $headers = array_keys($data[0]);
        $worksheet->fromArray([$headers], null, 'A1');

        $worksheet->fromArray($data, null, 'A2');

        $lastColumn = chr(ord('A') + count($headers) - 1);
        foreach (range('A', $lastColumn) as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename .= '.xlsx';
        $path = 'exports/' . $filename;
        $fullPath = storage_path('app/exports');
        
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/' . $path));

        return $path;
    }
}