<?php

namespace RZP\Reconciliator\NetbankingEquitas;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDelimiter()
    {
        return ',';
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::CSV;
    }

    public function shouldUse7z($zipFileDetails)
    {
        return true;
    }

    public function getReconPassword($fileDetails)
    {
        /**
         * Formatting done on the filename to extract password from it.
         * General format for it, is if the date in the filename is 21-oct,
         * the password would be RAZ2110.
         */
        $dateAndMonth = str_replace(['razorpay ', '.7z'], '', $fileDetails['file_name']);

        return 'RAZ' . date('dm', strtotime($dateAndMonth));
    }
}
