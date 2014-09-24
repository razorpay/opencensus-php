<?php

namespace Models\Transaction;

use Models\Base;
use Models\Payment;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const ENTITY_ID         = 'entity_id';
    const ENTITY_TYPE       = 'entity_type';
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

    protected $table = \Constants\Table::TRANSACTION;

    protected static $sign = 'txn';

    protected $fillable = array(
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::MERCHANT_ID,
        self::DEBIT,
        self::CREDIT,
        self::AMOUNT,
        self::CURRENCY,
        self::FEE,
        self::API_FEE,
        self::GATEWAY_FEE,
        self::BALANCE,
        self::PRICING_RULE_ID,
        self::SETTLED_AT);

    protected $public = array(
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::DEBIT,
        self::CREDIT,
        self::FEE,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        );

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function entity()
    {
        $type = $this->getAttribute(self::ENTITY_TYPE);

        switch($type)
        {
            case 'payment':
                return $this->hasOne('Models\Payment\Entity');
                break;
            case 'refund':
                return $this->hasOne('Models\Payment\Entity');
                break;
            default:
                throw new Exception\InvalidArgumentException(
                    'only payment and refund supported currently');
        }
    }

    public function fillPartiallyFromPayment($payment)
    {
        $txnData = array(
            self::MERCHANT_ID   => $payment->getMerchantId(),
            self::AMOUNT        => $payment->getAmount(),
            self::ENTITY_ID     => $payment->getKey(),
            self::ENTITY_TYPE   => 'payment');

        $this->fill($txnData);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function setReconciledAt($timestamp)
    {
        $this->setAttribute(self::RECONCILED_AT, $timestamp);
    }
}