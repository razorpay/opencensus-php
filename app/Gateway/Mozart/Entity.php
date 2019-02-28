<?php

namespace RZP\Gateway\Mozart;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const RECEIVED              = 'received';
    const AMOUNT                = 'amount';
    const ACTION                = 'action';

    protected $entity = 'mozart';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::BANK,
        self::RECEIVED,
        self::AMOUNT,
        self::ACTION,
    ];

    protected $fillable = [
        self::AMOUNT,
        self::RECEIVED,
    ];

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setReceived($received)
    {
        $this->setAttribute(self::RECEIVED, $received);
    }
}
