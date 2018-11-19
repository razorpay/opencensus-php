<?php

namespace RZP\Reconciliator\CardFssHdfc;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    //
    // Note about START_ROW :
    // As of now cardFss MIS file data/header stars from 7th row. The file has
    // payment header/data and refund header/data and some metadata in the same sheet,
    // and thus we check each row to identify header type and data.
    //
    // Currently we can return start row as 7. But if we return the start row as 1,
    // it will work even when the header/data moves upwards by few rows in the incoming
    // MIS file in future. Going forward we should we identifying the headers by ourselves
    // and should not rely on the start row concept .
    //
    // Since here we are identifying the headers by ourselves, its safe and more robust
    // to keep the start row as 1. Thus not defining getStartRow() function here.
    //

    const KEY_COLUMN_NAMES = [
        'transaction_type'     =>  self::PAYMENT,
        'action_code'          =>  self::REFUND,
    ];

    /**
     * These columns will be used to identify headers in an MIS file
     * This is needed when we get payments and refunds in the same sheet
     * and the headers are different.
     *
     * @param array $fileDetails
     * @return array
     */
    public function getKeyColumnNames(array $fileDetails = [])
    {
        return self::KEY_COLUMN_NAMES;
    }

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        //
        // Earlier support team used to separate out the refunds and payments and put them
        // in separate sheets with sheet names 'refund' and 'payment' respectively.
        //
        // Now, we will be processing the original MIS file directly,
        // and thus all payments and refunds will be put in the same sheet
        //
        return self::COMBINED;
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        //
        // We process only those files who have 'alltransaction'
        // in their file names, thus return false for such files.
        //
        if (strpos($fileDetails[FileProcessor::FILE_NAME], 'alltransaction') !== false)
        {
            return false;
        }

        return true;
    }
}
