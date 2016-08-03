<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Base;
use RZP\Constants\Table;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const NAME                  = 'name';
    const CITY                  = 'city';
    const MODE                  = 'mode';
    const STATE                 = 'state';
    const PHONE                 = 'phone';
    const EMAIL                 = 'email';
    const STATUS                = 'status';
    const ACTION                = 'action';
    const AMOUNT                = 'amount';
    const CHANNEL               = 'channel';
    const COUNTRY               = 'country';
    const ADDRESS               = 'address';
    const CURRENCY              = 'currency';
    const RECEIVED              = 'received';
    const REFUND_ID             = 'refund_id';
    const REQUEST_ID            = 'request_id';
    const PAYMENT_ID            = 'payment_id';
    const ACCOUNT_ID            = 'account_id';
    const ERROR_CODE            = 'error_code';
    const POSTAL_CODE           = 'postal_code';
    const DESCRIPTION           = 'description';
    const PAYMENT_MODE          = 'payment_mode';
    const REFERENCE_ID          = 'reference_id';
    const TRANSACTION_ID        = 'transaction_id';
    const ERROR_DESCRIPTION     = 'error_description';
    const IS_FLAGGED            = 'is_flagged';

    protected $fields = [
        self::STATUS,
        self::CHANNEL,
        self::AMOUNT,
        self::RECEIVED,
        self::REFUND_ID,
        self::ERROR_CODE,
        self::IS_FLAGGED,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::PAYMENT_MODE,
        self::REFERENCE_ID,
        self::TRANSACTION_ID,
        self::ERROR_DESCRIPTION,
    ];

    protected $fillable = [
        self::STATUS,
        self::AMOUNT,
        self::CHANNEL,
        self::RECEIVED,
        self::REFUND_ID,
        self::ERROR_CODE,
        self::IS_FLAGGED,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::PAYMENT_MODE,
        self::REFERENCE_ID,
        self::TRANSACTION_ID,
        self::ERROR_DESCRIPTION,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
        self::IS_FLAGGED    => 'boolean',
    ];

    protected $table = Table::EBS;

    protected $entity = Constants\Entity::EBS;

}
