<?php

namespace RZP\Reconciliator\PaylaterLazypay;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const MERCHANT_ID                        = 'merchant_id';
    const MERCHANT_NAME                      = 'merchant_name';
    const PAYMENT_MODE                       = 'payment_mode';
    const TRANSACTION_TYPE                   = 'transaction_type';
    const TRANSACTION_DATE                   = 'transaction_date';
    const TRANSACTION_AMOUNT                 = 'transaction_amount';
    const MERCHANT_REFERENCE_NUMBER          = 'merchant_reference_number';
    const GATEWAY_PAYMENT_ID                 = 'issuer_txn_ref_number';
    const AGGREGATOR_REFERENCE_NUMBER        = 'aggregator_txn_no';
    const PG_TXN_NO                          = 'pg_txn_no';
    const MSF                                = 'discountmsfamount';
    const IGST                               = 'igst_amount';


    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
