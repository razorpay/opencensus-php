<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Models\Settlement;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const CHANNEL           = 'channel';
    const DESCRIPTION       = 'description';
    const TRANSACTION_ID    = 'transaction_id';
    const SETTLEMENT_ID     = 'settlement_id';

    protected static $sign = 'adj';

    protected $entity = 'adjustment';

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::AMOUNT,
        self::DESCRIPTION,
        self::CURRENCY,
        self::SETTLEMENT_ID);

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::TRANSACTION_ID,
        self::SETTLEMENT_ID,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::TRANSACTION_ID,
        self::SETTLEMENT_ID,
        self::CREATED_AT);

    protected static $modifiers = array(
        self::SETTLEMENT_ID);

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

    protected function modifySettlementId(&$input)
    {
        if (isset(self::SETTLEMENT_ID) === false)
        {
            return;
        }

        $settlementId = $input[self::SETTLEMENT_ID];

        $input[self::SETTLEMENT_ID] = Settlement::verifyIdAndSilentlyStripSign($settlementId);
    }
}
