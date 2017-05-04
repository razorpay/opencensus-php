<?php

namespace RZP\Gateway\Aeps\Base;

use RZP\Constants;
use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const AMOUNT                = 'amount';
    const RECEIVED              = 'received';
    const REVERSED              = 'reversed';
    const ERROR_CODE            = 'error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const REV_ERROR_CODE        = 'rev_error_code';
    const REV_ERROR_DESCRIPTION = 'rev_error_description';
    const AADHAAR_NUMBER        = 'aadhaar_number';
    const RRN                   = 'rrn';
    const COUNTER               = 'counter';
    const PAYMENT_ID            = 'payment_id';
    const REFUND_ID             = 'refund_id';

    protected $fields = [
        self::AMOUNT,
        self::RECEIVED,
        self::ERROR_CODE,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::REVERSED,
        self::RRN,
        self::ERROR_DESCRIPTION,
    ];

    protected $fillable = [
        self::RRN,
        self::AMOUNT,
        self::RECEIVED,
        self::REVERSED,
        self::ERROR_CODE,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ERROR_DESCRIPTION,
    ];

    protected $casts = [
        self::AMOUNT        => 'int',
    ];

    protected $entity = Constants\Entity::AEPS;
}
