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
    const DATA                  = 'data';

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

    protected $appends = [
        self::DATA,
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

    public function setAccountNumber($accountNumber)
    {
        $raw = $this->getAttribute(self::RAW);

        $data = json_decode($raw, true);

        $data['account_number'] = $accountNumber;

        $raw = json_encode($data);

        $this->setRaw($raw);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getRaw()
    {
        return $this->getAttribute(self::RAW);
    }

    public function getDataAttribute()
    {
        return json_decode($this->getRaw(), true);
    }
}
