<?php

namespace RZP\Reconciliator\paypal;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Mozart\WalletPaypal\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        ReconFields::AMOUNT ,
        ReconFields::CHARGES,
        ReconFields::ACTION_ID,
        ReconFields::PAYPAL_MERCHANT_ID,
        ReconFields::TRANSACTION_ID,
        ReconFields::GATEWAY,
        ReconFields::PAY_ID,
        ReconFields::TYPE,
        ReconFields::CURRENCY,
        ReconFields::TIME,
    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0,
        ];
    }
}