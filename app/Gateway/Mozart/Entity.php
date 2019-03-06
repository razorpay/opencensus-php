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
    const GATEWAY               = 'gateway';
    const RAW                   = 'raw';

    protected $entity = 'mozart';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::GATEWAY,
        self::RECEIVED,
        self::AMOUNT,
        self::ACTION,
        self::RAW,
    ];

    protected $fillable = [
        self::AMOUNT,
        self::RECEIVED,
        self::RAW,
    ];

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setReceived($received)
    {
        $this->setAttribute(self::RECEIVED, $received);
    }

    public function setRaw($raw)
    {
        $this->setAttribute(self::RAW, $raw);
    }

    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }
}
