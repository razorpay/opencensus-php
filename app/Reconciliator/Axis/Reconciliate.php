<?php

namespace RZP\Reconciliator\Axis;

use RZP\Reconciliator\Base;
use RZP\Models\FileStore\Format;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const SALE = 'sale';
    const ACCEPTED_SHEET_NAMES = [
        'Refund', 'REFUND', 'refund', 'Refunds', 'refunds', 'REFUNDS',
        'Sale', 'SALE', 'sale', 'Sales', 'sales', 'SALES',
        'Visa Sale', 'Master Sale',
        'Visa Refund', 'Master Refund'
    ];

    const START_ROW = 3;

    const XLSX_START_ROW = 2;

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        if (strpos($fileName, self::REFUND) !== false)
        {
            $typeName = self::REFUND;
        }
        else if (strpos($fileName, self::SALE) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else
        {
            return null;
        }

        return $typeName;
    }

    /**
     * The list of sheet names in the excel file which should be
     * used to run reconciliation.
     * Some excel files have sheets that should not be considered for
     * reconciliation.
     *
     * @return array
     */
    public function getSheetNames()
    {
        return self::ACCEPTED_SHEET_NAMES;
    }

    public function shouldUse7z($zipFileDetails)
    {
        //
        // All axis zip files should go via 7z flow.
        //
        return true;
    }

    public function getReconPassword($fileDetails)
    {
        switch ($fileDetails[FileProcessor::FILE_NAME])
        {
            // in case of file name is axis account number, use yatra MID for unzip
            case '917020041206002.zip':
                return 'YAONPLRAZP';

            // password will be account number in this case
            case 'razorpay.zip':
                return '917020041206002';

            // default valie is shared mid name
            default:
                return 'RAZORPAYADD';
        }
    }

    public function getStartRow($fileDetails)
    {
        //
        // We get two different types of files from Axis. For one of the files,
        // the start row is different from `1`.
        //
        if (($fileDetails[FileProcessor::EXTENSION] === Format::XLS) and
            (strpos('RAZORPAYADD', $fileDetails[FileProcessor::FILE_NAME]) === false))
        {
            return self::START_ROW;
        }
        else if (($fileDetails[FileProcessor::EXTENSION] === Format::XLSX) and
                ($fileDetails[FileProcessor::FILE_NAME] === 'razorpay.xlsx'))
        {
            return self::XLSX_START_ROW;
        }

        return Base\Reconciliate::DEFAULT_START_ROW;
    }
}
