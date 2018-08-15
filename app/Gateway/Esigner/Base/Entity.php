<?php

namespace RZP\Gateway\Esigner\Base;

use RZP\Gateway\Base;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const GATEWAY               = 'gateway';
    const ACTION                = 'action';
    const MANDATE_ID            = 'mandate_id';
    const STATUS                = 'status';
    const ERROR_CODE            = 'error_code';
    const ERROR_MESSAGE         = 'error_message';

    protected $entity = 'esigner';

    protected $fields = [
        self::ID,
        self::PAYMENT_ID,
        self::GATEWAY,
        self::ACTION,
        self::MANDATE_ID,
        self::STATUS,
        self::ERROR_CODE,
        self::ERROR_MESSAGE,
    ];

    protected $fillable = [
        self::PAYMENT_ID,
        self::GATEWAY,
        self::ACTION,
        self::MANDATE_ID,
        self::STATUS,
        self::ERROR_CODE,
        self::ERROR_MESSAGE,
    ];

    protected $defaults = [
        self::PAYMENT_ID      => null,
        self::GATEWAY         => null,
        self::ACTION          => null,
        self::MANDATE_ID      => null,
        self::STATUS          => null,
        self::ERROR_CODE      => null,
        self::ERROR_MESSAGE   => null,
    ];

    public function payment()
    {
        return $this->belongsTo(\RZP\Models\Payment\Entity::class);
    }
}
