<?php

namespace RZP\Reconciliator\HdfcDebitEmi;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const COMBINED_FILE_NAMES = [
        'merchantreconcilationreport',
        'merchantpaymentsummaryreport',
    ];

    // Override this from base, since we need to know the file name instead of the sheet name
    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    protected function getTypeName($fileName)
    {
        foreach (self::COMBINED_FILE_NAMES as $name)
        {
            if (strpos($fileName, $name) === 0)
            {
                return self::COMBINED;
            }
        }
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    public function getReconPassword($fileDetails)
    {
        return Carbon::now(Timezone::IST)->format('dmY');
    }
}
