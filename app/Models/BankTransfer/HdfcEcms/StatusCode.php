<?php

namespace RZP\Models\BankTransfer\HdfcEcms;

class StatusCode
{
    const SUCCESS                     = 'Success';
    const ALREADY_PROCESSED           = 'Already Processed';
    const AMOUNT_MISMATCH             = 'Amount Mismatch';
    const DUPLICATE_TRANSACTION       = 'Duplicate Transaction';
    const CHALLAN_EXPIRED             = 'Challan Expired';
    const INVALID_UTR                 = 'Invalid UTR';
    const TRANSACTION_NOT_FOUND       = 'Transaction Not Found';
    const ORDER_AMOUNT_MISMATCH       = 'Order Amount Mismatch';
    const CHALLAN_DOES_NOT_HAVE_ORDER = 'Challan does not have order';
    const REPUSH                      = 'Re-push';
    const ACCESS_DENIED               = 'Access Denied';

    const statusToCodeMapping = [
        StatusCode::SUCCESS                     => 0,
        StatusCode::ALREADY_PROCESSED           => 0,
        StatusCode::AMOUNT_MISMATCH             => 1,
        StatusCode::DUPLICATE_TRANSACTION       => 1,
        StatusCode::CHALLAN_EXPIRED             => 1,
        StatusCode::INVALID_UTR                 => 1,
        StatusCode::TRANSACTION_NOT_FOUND       => 1,
        StatusCode::ORDER_AMOUNT_MISMATCH       => 1,
        StatusCode::CHALLAN_DOES_NOT_HAVE_ORDER => 1,
        StatusCode::REPUSH                      => 2,
        StatusCode::ACCESS_DENIED               => 9,
    ];

    public static function getStatusCodeForEcms($status): int
    {
        return isset(static::statusToCodeMapping[$status]) ? static::statusToCodeMapping[$status] : 2;
    }
}
