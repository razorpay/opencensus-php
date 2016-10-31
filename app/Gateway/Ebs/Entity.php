<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Base;
use RZP\Constants\Table;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const RECEIVED              = 'received';
    const REFUND_ID             = 'refund_id';
    const REQUEST_ID            = 'request_id';
    const PAYMENT_ID            = 'payment_id';
    const ERROR_CODE            = 'error_code';
    const IS_FLAGGED            = 'is_flagged';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const TRANSACTION_ID        = 'transaction_id';
    const ERROR_DESCRIPTION     = 'error_description';

    protected $fields = [
        self::AMOUNT,
        self::RECEIVED,
        self::REFUND_ID,
        self::ERROR_CODE,
        self::IS_FLAGGED,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::TRANSACTION_ID,
        self::ERROR_DESCRIPTION,
    ];

    protected $fillable = [
        self::AMOUNT,
        self::RECEIVED,
        self::REFUND_ID,
        self::ERROR_CODE,
        self::IS_FLAGGED,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::TRANSACTION_ID,
        self::ERROR_DESCRIPTION,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::IS_FLAGGED    => 'boolean',
    ];

    protected $entity = Constants\Entity::EBS;
}
