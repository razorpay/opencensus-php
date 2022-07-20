<?php

namespace RZP\Models\Settlement\InternationalRepatriation;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{

    const ID                                = "id";
    const MERCHANT_ID                       = "merchant_id";
    const INTEGRATION_ENTITY                = "integration_entity";
    const PARTNER_MERCHANT_ID               = "partner_merchant_id";
    const PARTNER_SETTLEMENT_ID             = "partner_settlement_id";
    const PARTNER_TRANSACTION_ID            = "partner_transaction_id";
    const AMOUNT                            = "amount";
    const CURRENCY                          = "currency";
    const CREDIT_AMOUNT                     = "credit_amount";
    const CREDIT_CURRENCY                   = "credit_currency";
    const SETTLEMENT_IDS                    = "settlement_ids";
    const FOREX_RATE                        = "forex_rate";
    const SETTLED_AT                        = "settled_at";
    const CREATED_AT                        = "created_at";
    const UPDATED_AT                        = "updated_at";


    protected $entity      = 'settlement_international_repatriation';

    protected $primaryKey  = self::ID;

    protected $fillable    = [
        self::MERCHANT_ID,
        self::INTEGRATION_ENTITY,
        self::PARTNER_MERCHANT_ID,
        self::PARTNER_SETTLEMENT_ID,
        self::PARTNER_TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT_AMOUNT,
        self::CREDIT_CURRENCY,
        self::SETTLEMENT_IDS,
        self::FOREX_RATE,
        self::SETTLED_AT,
        self::UPDATED_AT,

    ];

    protected $public      = [
        self::ID,
        self::MERCHANT_ID,
        self::INTEGRATION_ENTITY,
        self::PARTNER_MERCHANT_ID,
        self::PARTNER_SETTLEMENT_ID,
        self::PARTNER_TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT_AMOUNT,
        self::CREDIT_CURRENCY,
        self::SETTLEMENT_IDS,
        self::FOREX_RATE,
        self::SETTLED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates        = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SETTLED_AT,
    ];

    protected $defaults     = [
        self::SETTLEMENT_IDS            => [],
        self::UPDATED_AT                => null,
        self::SETTLED_AT                => null,
        self::PARTNER_MERCHANT_ID       => null,
        self::PARTNER_SETTLEMENT_ID     => null,
        self::PARTNER_TRANSACTION_ID    => null
    ];

    protected $casts = [
        self::SETTLEMENT_IDS   => 'array',
        self::AMOUNT           => 'int',
        self::CREDIT_AMOUNT    => 'int',
        self::FOREX_RATE       => 'float',
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }
}
