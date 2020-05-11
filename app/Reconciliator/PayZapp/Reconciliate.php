<?php

namespace RZP\Reconciliator\PayZapp;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Wallet\Payzapp\ReconHeaders;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        if (strpos($fileDetails['file_name'], 'summary') !== false)
        {
            return true;
        }

        return false;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    public function getColumnHeadersForType($type)
    {
        return [
            ReconHeaders::TERMINAL_ID,
            ReconHeaders::MERCHANT_NAME,
            ReconHeaders::TRANSACTION_TYPE,
            ReconHeaders::CARD_NUMBER,
            ReconHeaders::GROSS_AMT,
            ReconHeaders::COMMISSION_AMT,
            ReconHeaders::CGST,
            ReconHeaders::SGST,
            ReconHeaders::IGST,
            ReconHeaders::UTGST,
            ReconHeaders::NET_AMT,
            ReconHeaders::TRAN_DATE,
            ReconHeaders::AUTH_CODE,
            ReconHeaders::TRACK_ID,
            ReconHeaders::PG_TXN_ID,
            ReconHeaders::PG_SALE_ID,
            ReconHeaders::CREDIT_DEBIT_CARD_FLAG,
            ReconHeaders::GSTN,
            ReconHeaders::INVOICE_NUMBER,
            ReconHeaders::CGST_PERCENTAGE_RENAME,
            ReconHeaders::SGST_PERCENTAGE_RENAME,
            ReconHeaders::IGST_PERCENTAGE_RENAME,
            ReconHeaders::UTGST_PERCENTAGE_RENAME,
            ReconHeaders::CGSTCESS1,
            ReconHeaders::CGSTCESS2,
            ReconHeaders::CGSTCESS3,
            ReconHeaders::SGSTCESS1,
            ReconHeaders::SGSTCESS2,
            ReconHeaders::SGSTCESS3,
            ReconHeaders::IGSTCESS1,
            ReconHeaders::IGSTCESS2,
            ReconHeaders::IGSTCESS3,
            ReconHeaders::UTGSTCESS1,
            ReconHeaders::UTGSTCESS2,
            ReconHeaders::UTGSTCESS3,
        ];
    }
}
