<?php

namespace RZP\Models\Merchant;

use AWS;
use App;
use Excel;
use Config;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Header;
use Razorpay\Trace\Logger as Trace;
use RZP\Excel\Import as ExcelImport;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\ExportSheet as ExcelSheetExport;
use RZP\Models\FileStore\Storage\AwsS3\Handler;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use RZP\Excel\MultipleSheetsImport as ExcelMultipleSheetsImport;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder as PhpSpreadsheetDefaultValueBinder;

trait MerchantCsvCreateTrait
{
    public function createCsvFile($data, $name, $fullName, $dir, $append = false)
    {
        $dir = storage_path($dir);

        if (file_exists($dir) === false)
        {
            mkdir($dir);
        }

        $fullpath = $dir . '/' . $name . '.csv';

        // open the file in append mode
        $handle = fopen($fullpath, 'a');

        $first = true;

        foreach ($data as $row)
        {
            if (($append === false) and ($first === true))
            {
                $headers = array_keys($row);

                fputcsv($handle, $headers);

                $first = false;
            }

            $row = $this->flatten2($row);

            fputcsv($handle, $row);
        }

        fclose($handle);

        if ($fullName !== null)
        {
            rename($fullpath, $fullName);

            $fullpath = $fullName;
        }

        return $fullpath;
    }

    protected function flatten2(array $row)
    {
        foreach ($row as &$value)
        {
            if (is_array($value))
            {
                $value = json_encode($value);
            }
        }

        return $row;
    }
}
