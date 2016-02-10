<?php

namespace Models\Order;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const MERCHANT_ID = 'merchant_id';
    const AMOUNT      = 'amount';
    const CURRENCY    = 'currency';
    const ATTEMPTS    = 'attempts';
    const STATUS      = 'status';
    const RECEIPT     = 'receipt';
    // const METHOD      = 'method';
    // const ACCOUNT_ID  = 'account_id';
    // const CREATED_AT  = 'created_at';
    // const VALIDITY    = 'validity';
    // const VALID_TILL  = 'valid_till';

    // Auto capture if set
    // const CAPTURE     = 'capture';

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::ATTEMPTS,
        self::STATUS,
        self::RECEIPT);

    protected $table = \Constants\Order::PAYMENT;
}
