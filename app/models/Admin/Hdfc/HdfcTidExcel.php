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

        $txnValue = $merchantDetails['transaction_value'] ?: 1;

        $filePath = __DIR__ . '/HdfcMtExcelTemplate.xlsx';

        // Read the file
        $inputFileType = PHPExcel_IOFactory::identify($filePath);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $excel = $objReader->load($filePath);

        $excel->setActiveSheetIndex(0)
            ->setCellValue('C10', $merchantDetails['business_operation_address'])
            ->setCellValue('C14', $merchantDetails['business_operation_pin'])
            ->setCellValue('C15', $merchantDetails['business_operation_city'])
            ->setCellValue('C16', $merchantDetails['business_operation_state'])
            ->setCellValue('C18', $merchantDetails['contact_name'])
            ->setCellValue('C19', $merchantDetails['contact_mobile'])
            ->setCellValue('C25', $merchant['category'])
            ->setCellValue('C27', $merchantDetails['business_website'])
            ->setCellValue('C28', $merchantDetails['business_doe'])
            ->setCellValue('C33', $merchantDetails['business_website'])
            ->setCellValue('C35', $merchantDetails['business_type'])
            ->setCellValue('C51', $merchantDetails['transaction_volume'])
            ->setCellValue('C52', $merchantDetails['transaction_volume'] / 12)
            ->setCellValue('C53', $merchantDetails['transaction_volume'] / $txnValue)
            ->setCellValue('C62', $merchantDetails['website_privacy'])
            ->setCellValue('C63', $merchantDetails['website_refund'])
            ->setCellValue('C64', $merchantDetails['website_terms'])
            ->setCellValue('C65', $merchantDetails['website_about'])
            ->setCellValue('C66', $merchantDetails['website_pricing'])
            ->setCellValue('C67', $merchantDetails['business_website'])
            ->setCellValue('C69', $merchantDetails['website_contact'])
            ->setCellValue('C70', $merchantDetails['website_login']);

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
