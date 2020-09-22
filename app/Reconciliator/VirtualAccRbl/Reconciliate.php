<?php

namespace RZP\Reconciliator\VirtualAccRbl;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Reconciliator\VirtualAccRbl\SubReconciliator\ReconciliationFields;

class Reconciliate extends Base\Reconciliate
{
    const HEADERS = [
        ReconciliationFields::TRANSACTION_TYPE,
        ReconciliationFields::AMOUNT,
        ReconciliationFields::UTR_NUMBER,
        ReconciliationFields::RRN_NUMBER,
        ReconciliationFields::SENDER_IFSC,
        ReconciliationFields::SENDER_ACCOUNT_NUMBER,
        ReconciliationFields::SENDER_ACCOUNT_TYPE,
        ReconciliationFields::SENDER_ACCOUNT_NAME,
        ReconciliationFields::BENEFICIARY_ACCOUNT_TYPE,
        ReconciliationFields::BENEFICIARY_ACCOUNT_NUMBER,
        ReconciliationFields::BENEF_NAME,
        ReconciliationFields::CREDIT_DATE,
        ReconciliationFields::CREDIT_ACCOUNT_NUMBER,
        ReconciliationFields::CORPORATE_CODE,
        ReconciliationFields::SENDER_INFORMATION,
        ReconciliationFields::TRAN_ID,
    ];

    /**
     *
     * @param $filename
     * @return string
     */
    protected function getTypeName($filename)
    {
        return self::PAYMENT;
    }

    /**
     * This header will be used when we get .txt or csv file without header.
     * For excel files, this is not used.
     *
     * @param $type
     * @return array
     */
    public function getColumnHeadersForType($type)
    {
        return self::HEADERS;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0,
        ];
    }
}
