<?php

namespace RZP\Reconciliator\Axis;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const SALE = 'sale';

    const ACCEPTED_SHEET_NAMES = [
        'Refund', 'REFUND', 'refund', 'Refunds', 'refunds', 'REFUNDS',
        'Sale', 'SALE', 'sale', 'Sales', 'sales', 'SALES',
        'Visa Sale', 'Master Sale', 'DI Sale',
        'Visa Refund', 'Master Refund', 'DI Refund',
    ];

    //
    // Note about START_ROW :
    // As of now Axis MIS file data/header stars from 1st row (when filename starts with 'razorpayadd')
    // or 3rd row (when filename is razorpay.xlsx).
    //
    const KEY_COLUMN_NAMES = [
        //
        // In Axis MIS file, the headers are exactly
        // same for payment and refund sheets.
        //
        // Keeping two columns here so that even if one column is absent
        // in MIS file (in future), we will still be able to identify
        // the header with the other column name.
        //
        // Note : In Axis Migs, We are not using the corresponding value
        // 'combined' anywhere (it is just dummy).
        //
        'merchant_trans_ref'    =>  self::COMBINED,
        'arn'                   =>  self::COMBINED,
    ];

    /*
     * In Axis recon file, where we used to set start_row as 3 earlier,
     * we faced the following issue.
     *
     * While taking rows in chunk from excel file, the chunk would take
     * rows starting from 3rd row.This is desired for the first chunk.
     * But as a side effect, this was happening for 2nd chunk onwards
     * as well and after every chunk we were missing out 2 rows (unprocessed).
     * So now we want to avoid giving start_row due to this issue and thus
     * will use key_column names to get the header.
     *
     * default start row = 1 will be used.
     */
    public function getKeyColumnNames(array $fileDetails = [])
    {
        return self::KEY_COLUMN_NAMES;
    }

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
     * @param array $fileDetails
     * @return array
     */
    public function getSheetNames(array $fileDetails = [])
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

            // default value is shared mid name
            default:
                return 'RAZORPAYADD';
        }
    }
}
