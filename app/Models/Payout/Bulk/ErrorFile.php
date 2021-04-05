<?php

namespace RZP\Models\Payout\Bulk;

use RZP\Excel\PayoutErrorExportSheet;
use RZP\Models\Batch\Header;
use RZP\Excel\PayoutExportSheet;
use RZP\Excel\Export as ExcelExport;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ErrorFile extends Base
{
    const EXCEL_HEADERS_FOR_PAYOUT_FILE = [
        '',
        'Mandatory Fields',
        'Mandatory Fields',
        'Mandatory Fields',
        'Mandatory Fields',
        'Mandatory Fields',
        '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
        '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
        '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
        '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
        '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
        '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
        '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
        'Optional Fields',
        'Optional Fields',
        'Optional Fields',
        'Optional Fields',
        'Optional Fields',
        'Optional Fields',
        'Optional Fields',
        'Optional Fields',
    ];

    public function createExcelObject($data, $dir, $name, $extension, $columnFormat = [], $sheetNames = ['Sheet 1'])
    {
        // The extra space in the end is being added so that the number doesn't get converted to scientific notation
        foreach ($data as &$rows)
        {
            $rows[Header::RAZORPAYX_ACCOUNT_NUMBER] = $rows[Header::RAZORPAYX_ACCOUNT_NUMBER] . ' ';
            $rows[Header::FUND_ACCOUNT_NUMBER]      = $rows[Header::FUND_ACCOUNT_NUMBER] . ' ';
            $rows[Header::CONTACT_MOBILE_2]         = $rows[Header::CONTACT_MOBILE_2] . ' ';
        }

        // Forcing all columns to store data as text.
        for ($columnNumber = self::START_COLUMN; $columnNumber <= self::END_COLUMN; $columnNumber++)
        {
            $columnFormat[$columnNumber] = NumberFormat::FORMAT_TEXT;
        }

        $sheetNames = (is_array($sheetNames) === false) ? [$sheetNames] : $sheetNames;

        // todo: Update custom export for this
        $path = $dir . DIRECTORY_SEPARATOR . $name . '.' . $extension;

        $excel = (new ExcelExport)->setSheets(function() use ($sheetNames, $data, $columnFormat) {
            $sheetsInfo = [];
            foreach ($sheetNames as $sheetName)
            {
                $sheetsInfo[$sheetName] = (new PayoutErrorExportSheet(($data[$sheetName] ?? $data)))->setTitle($sheetName)->setColumnFormat($columnFormat);
            }

            return $sheetsInfo;
        })->store($path, 'local_storage');

        return [
            'full'  => $path,
            'path'  => $dir,
            'file'  => $name . '.' . $extension,
            'title' => $name,
            'ext'   => $extension
        ];
    }
}
