<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Models\Settlement;
use RZP\Models\Base\Traits\HasBalance;

class Entity extends Base\PublicEntity
{
    use HasBalance;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const CHANNEL           = 'channel';
    const DESCRIPTION       = 'description';
    const TRANSACTION_ID    = 'transaction_id';
    const BALANCE_ID        = 'balance_id';
    const SETTLEMENT_ID     = 'settlement_id';
    const STATUS            = 'status';

    // For report
    const DISPUTE_ID        = 'dispute_id';
    const ENTITY            = 'entity';

    // Input parameters
    const FEES              = 'fees';
    const TYPE              = 'type';

    // for Sub balance
    const SOURCE_BALANCE_ID      = 'source_balance_id';
    const DESTINATION_BALANCE_ID = 'destination_balance_id';

    // Any changes to this sign will affect LedgerStatus Job as well
    protected static $sign = 'adj';

    protected $entity = 'adjustment';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::STATUS,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CHANNEL,
        self::DESCRIPTION,
        self::BALANCE_ID,
        self::TRANSACTION_ID,
        self::SETTLEMENT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STATUS,
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

    protected $ignoredRelations = [
        'entity',
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

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID));
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
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

    public function setCreatedAt($createdAt)
    {
        $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->setAttribute(self::UPDATED_AT, $updatedAt);
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

    public function shouldNotifyTxnViaSms(): bool
    {
        return false;
    }

    public function shouldNotifyTxnViaEmail(): bool
    {
        return $this->isBalanceTypeBanking() === true;
    }

    public function isDispute(): bool
    {
        return $this->getDescription() === Dispute\Core::DEBIT_ADJUSTMENT_DESCRIPTION;
    }
}
