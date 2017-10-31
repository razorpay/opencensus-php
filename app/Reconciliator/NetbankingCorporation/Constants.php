<?php

namespace RZP\Reconciliator\NetbankingCorporation;

class Constants
{
    const MERCHANT_CODE    = 'merchant_code';
    const PAYMENT_ID       = 'payment_id';
    const CLIENT_ID        = 'client_id';
    const AMOUNT           = 'amount';
    const TYPE             = 'type';
    const BANK_REF_ID      = 'bank_ref_id';
    const STATUS           = 'status';
    const DATE             = 'date';
    const EMPTY_FIELD      = 'empty_field';
    const REMARKS          = 'remarks';
    const BRANCH_CODE      = 'branch_code';
    const ACCOUNT_TYPE     = 'account_type';
    const ACCOUNT_SUB_TYPE = 'account_sub_type';
    const ACCOUNT_NUMBER   = 'account_number';

    const PAYMENT_COLUMN_HEADERS = [
        self::MERCHANT_CODE,
        self::PAYMENT_ID,
        self::CLIENT_ID,
        self::AMOUNT,
        self::TYPE,
        self::BANK_REF_ID,
        self::STATUS,
        self::DATE,
        self::EMPTY_FIELD,
        self::REMARKS,
        self::BRANCH_CODE,
        self::ACCOUNT_TYPE,
        self::ACCOUNT_SUB_TYPE,
        self::ACCOUNT_NUMBER,
    ];
}
