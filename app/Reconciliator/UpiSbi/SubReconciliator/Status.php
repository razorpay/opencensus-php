<?php

namespace RZP\Reconciliator\UpiSbi;

use RZP\Models\Payment;

class Status
{
    const SUCCESS = 'success';
    const FAILED  = 'failed';

    const STATUS_PAYMENT_STATUS_MAP = [
        self::SUCCESS => Payment\Status::AUTHORIZED,
        self::FAILED  => Payment\Status::FAILED
    ];

    public static function getPaymentStatus(string $status)
    {
        $rowStatus = strtolower($status);

        return self::STATUS_PAYMENT_STATUS_MAP[$rowStatus] ?? Payment\Status::FAILED;
    }
}