<?php

namespace Models\Transaction;

use Models\Base;
use Models\Payment;
use Models\Transaction;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const ENTITY_ID         = 'entity_id';
    const TYPE              = 'type';
    const MERCHANT_ID       = 'merchant_id';
    const AMOUNT            = 'amount';
    const DEBIT             = 'debit';
    const CREDIT            = 'credit';
    const CURRENCY          = 'currency';
    const FEE               = 'fee';
    const PRICING_RULE_ID   = 'pricing_rule_id';
    const BALANCE           = 'balance';
    const GATEWAY_FEE       = 'gateway_fee';
    const API_FEE           = 'api_fee';
    const ESCROW_BALANCE    = 'escrow_balance';
    const RECONCILED_AT     = 'reconciled_at';
    const SETTLED           = 'settled';
    const SETTLED_AT        = 'settled_at';
    const SETTLEMENT_ID     = 'settlement_id';

    protected $table = \Constants\Table::TRANSACTION;

    protected static $sign = 'txn';

    protected $entity = 'transaction';

    protected $fillable = array(
        self::ENTITY_ID,
        self::TYPE,
        self::MERCHANT_ID,
        self::DEBIT,
        self::CREDIT,
        self::AMOUNT,
        self::CURRENCY,
        self::FEE,
        self::API_FEE,
        self::GATEWAY_FEE,
        self::BALANCE,
        self::ESCROW_BALANCE,
        self::PRICING_RULE_ID,
        self::SETTLED_AT);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::DEBIT,
        self::CREDIT,
        self::FEE,
        self::ENTITY_ID,
        self::TYPE);

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function entity()
    {
        $type = $this->getAttribute(self::TYPE);

        Transaction\Type::validateType($type);

        $class = 'Models\\';

        if ($type === Transaction\Type::REFUND)
            $class .= 'Payment\\';

        $class .= ucfirst($type).'\\'.'Entity';

        return $this->belongsTo($class, self::ENTITY_ID);
    }

    public function settlement()
    {
        return $this->belongsTo('Models\Settlement\Entity');
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getCredit()
    {
        return (int) $this->getAttribute(self::CREDIT);
    }

    public function getDebit()
    {
        return (int) $this->getAttribute(self::DEBIT);
    }

    public function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getFeeAttribute()
    {
        return (int) $this->attributes[self::FEE];
    }

    public function getApiFeeAttribute()
    {
        return (int) $this->attributes[self::API_FEE];
    }

    public function getBalanceAttribute()
    {
        return (int) $this->attributes[self::BALANCE];
    }

    public function getSettledAttribute()
    {
        return (bool) $this->attributes[self::SETTLED];
    }

    public function getGateway()
    {
        if ($this->isTypePayment())
        {
            return $this->getRelation('entity')->getGateway();
        }
        else if ($this->getType() === Type::REFUND)
        {
            return $this->getRelation('entity')->payment->getGateway();
        }
    }

    public function setReconciledAt($timestamp)
    {
        $this->setAttribute(self::RECONCILED_AT, $timestamp);
    }

    public function setPublicEntityIdAttribute(array & $array)
    {
        $entity = 'Models\\'.ucfirst($array[self::TYPE]) . '\Entity';
        $sign = $entity::getIdPrefix();

        $array[self::ENTITY_ID] = $sign . $array[self::ENTITY_ID];
    }

    public function isReconciled()
    {
        return ($this->getAttribute(self::RECONCILED_AT) !== null);
    }

    public function isTypePayment()
    {
        return ($this->getType() === Type::PAYMENT);
    }
}