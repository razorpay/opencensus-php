<?php

namespace RZP\Reconciliator\Walnut369;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const MERCHANT_NAME                      = 'merchant_name';
    const MERCHANT_ID                        = 'merchant_id';
    const DATE                               = 'date_of_txn';
    const PURCHASE_OR_CANCELLED_AMOUNT       = 'purchased_or_cancelled_amount';
    const TXN_TYPE                           = 'txn_type';
    const UTR                                = 'utr';
    const RZP_TXN_ID                         = 'rzp_txn_id';
    const GATEWAY_PAYMENT_ID                 = 'finalizechargetransactionid';
    const TENURE                             = 'tennure';
    const SUBVENTION_AMOUNT                  = 'subvention_amount';
    const MDR                                = 'mdr';
    const PARTNER_FEES                       = 'partner_fees';
    const NET_TRANSFER_AMOUNT                = 'net_transfer_amount';

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}
