<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Models\Settlement;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const CHANNEL           = 'channel';
    const DESCRIPTION       = 'description';
    const TRANSACTION_ID    = 'transaction_id';
    const SETTLEMENT_ID     = 'settlement_id';

    // For report
    const DISPUTE_ID        = 'dispute_id';

    protected static $sign = 'adj';

    protected $entity = 'adjustment';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::DESCRIPTION,
        self::CURRENCY,
        self::SETTLEMENT_ID,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::TRANSACTION_ID,
        self::SETTLEMENT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::TRANSACTION_ID,
        self::SETTLEMENT_ID,
        self::CREATED_AT
    ];

    protected static $modifiers = [
        self::SETTLEMENT_ID
    ];

    protected $defaults = [
        self::ENTITY_ID   => null,
        self::ENTITY_TYPE => null,
    ];

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function settlement()
    {
        return $this->belongsTo('RZP\Models\Settlement\Entity');
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    protected function modifySettlementId(&$input)
    {
        if (isset($input[self::SETTLEMENT_ID]) === false)
        {
            return;
        }

        $settlementId = $input[self::SETTLEMENT_ID];

        $input[self::SETTLEMENT_ID] = Settlement\Entity::verifyIdAndSilentlyStripSign($settlementId);
    }
}
