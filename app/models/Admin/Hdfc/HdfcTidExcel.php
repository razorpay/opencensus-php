<?php

namespace Models\Admin;

use Excel;
use PHPExcel_IOFactory;

class HdfcTidExcel
{
    public static function generateExcel($data)
    {
        $merchant = $data['merchant'];
        $merchantDetails = $data['merchant']['merchant_details'];

        $excelData = require ('HdfcTidExcelData.php');

        $txnValue = $merchantDetails['transaction_value'] ?: 1;

        $filePath = __DIR__ . '/hdfc_merchant_template.xlsx';

        // Read the file
        $inputFileType = PHPExcel_IOFactory::identify($filePath);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $excel = $objReader->load($filePath);

        $excel->setActiveSheetIndex(0)
            ->setCellValue('B10', $merchantDetails['business_operation_address'])
            ->setCellValue('B13', $merchantDetails['business_operation_pin'])
            ->setCellValue('B14', $merchantDetails['business_operation_city'])
            ->setCellValue('B15', $merchantDetails['business_operation_state'])
            ->setCellValue('B17', $merchantDetails['contact_name'])
            ->setCellValue('B18', $merchantDetails['contact_mobile'])
            ->setCellValue('B25', $merchant['category'])
            ->setCellValue('B26', $merchantDetails['business_website'])
            ->setCellValue('B28', $merchantDetails['business_doe'])
            ->setCellValue('B32', $merchantDetails['business_website'])
            ->setCellValue('B34', $merchantDetails['business_type'])
            ->setCellValue('B50', $merchantDetails['transaction_volume'])
            ->setCellValue('B51', $merchantDetails['transaction_volume'] / 12)
            ->setCellValue('B52', $merchantDetails['transaction_volume'] / $txnValue)
            ->setCellValue('B61', $merchantDetails['website_privacy'])
            ->setCellValue('B62', $merchantDetails['website_refund'])
            ->setCellValue('B63', $merchantDetails['website_terms'])
            ->setCellValue('B64', $merchantDetails['website_about'])
            ->setCellValue('B65', $merchantDetails['website_pricing'])
            ->setCellValue('B66', $merchantDetails['business_website'])
            ->setCellValue('B68', $merchantDetails['website_contact'])
            ->setCellValue('B69', $merchantDetails['website_login']);

        $excelWriter = PHPExcel_IOFactory::createWriter($excel, $inputFileType);

        $dir = storage_path('files/Hdfc');

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $filePath = $dir.'/hdfc_excel_'.$merchant['id'].'.xlsx';
        $excelWriter->save($filePath);

        $excel = Excel::load($filePath);

        return $excel;

        // ob_end_clean();

        // // return $excelWriter;
        // // We'll be outputting an excel file
        // header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // // It will be called file.xls
        // header('Content-Disposition: attachment; filename="hdfc_file.xlsx"');

        $excelWriter->save("php://output");
    }
}
