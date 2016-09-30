<?php

namespace RZP\Models\Transaction;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Models\Settlement;

class Entity extends Base\PublicEntity
{
    const ID                  = 'id';
    const ENTITY_ID           = 'entity_id';
    const TYPE                = 'type';
    const MERCHANT_ID         = 'merchant_id';
    const AMOUNT              = 'amount';
    const DEBIT               = 'debit';
    const CREDIT              = 'credit';
    const CURRENCY            = 'currency';
    const FEE                 = 'fee';
    const SERVICE_TAX         = 'service_tax';
    const PRICING_RULE_ID     = 'pricing_rule_id';
    const BALANCE             = 'balance';
    const GATEWAY_FEE         = 'gateway_fee';
    const GATEWAY_SERVICE_TAX = 'gateway_service_tax';
    const GATEWAY_SETTLED_AT  = 'gateway_settled_at';
    const API_FEE             = 'api_fee';
    const GRATIS              = 'gratis';
    const ESCROW_BALANCE      = 'escrow_balance';
    const RECONCILED_AT       = 'reconciled_at';
    const CHANNEL             = 'channel';
    const SETTLED             = 'settled';
    const SETTLED_AT          = 'settled_at';
    const SETTLEMENT_ID       = 'settlement_id';

    const PAYMENT_ID        = 'payment_id';

    const RECONCILED        = 'reconciled';

    protected $table = Table::TRANSACTION;

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
        self::GATEWAY_SERVICE_TAX,
        self::GATEWAY_SETTLED_AT,
        self::SERVICE_TAX,
        self::GRATIS,
        self::BALANCE,
        self::ESCROW_BALANCE,
        self::PRICING_RULE_ID,
        self::RECONCILED_AT,
        self::CHANNEL,
        self::SETTLED_AT,
        self::SERVICE_TAX);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::ENTITY_ID,
        self::TYPE,
        self::DEBIT,
        self::CREDIT,
        self::AMOUNT,
        self::CURRENCY,
        self::FEE,
        self::SERVICE_TAX,
        self::SETTLED,
        self::CREATED_AT,
        self::SETTLED_AT,
        self::SETTLEMENT_ID);

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::ENTITY_ID,
        self::SETTLEMENT_ID);

    protected $dates = array(
        self::SETTLED_AT,
    );

    protected $defaults = array(
        self::GRATIS    => false,
    );

    protected $amounts = array(
        self::AMOUNT,
        self::DEBIT,
        self::CREDIT,
        self::FEE,
        self::SERVICE_TAX,
    );

    protected $reportAttributes = array(
        self::CREATED_AT,
        self::AMOUNT,
        self::DEBIT,
        self::CREDIT,
        self::FEE,
        self::SERVICE_TAX,
        self::SETTLED_AT,
        self::SETTLEMENT_ID,
        Payment\Entity::DESCRIPTION,
        Payment\Entity::NOTES,
        self::PAYMENT_ID,
    );

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function source()
    {
        $type = $this->getAttribute(self::TYPE);

        Transaction\Type::validateType($type);

        $class = 'RZP\\Models\\';

        if ($type === Transaction\Type::REFUND)
        {
            $class .= 'Payment\\';
        }

        $class .= ucfirst($type).'\\'.'Entity';

        return $this->belongsTo($class, self::ENTITY_ID);
    }

    /**
     * Associates the entity id and validates that the entity id is unique.
     * @param $entity
     */
    public function sourceAssociate($entity)
    {
        $this->source()->associate($entity);
        $this->validateEntityIdUnique();
        $entity->transaction()->associate($this);
    }

    public function settlement()
    {
        return $this->belongsTo('RZP\Models\Settlement\Entity');
    }

    public function getCredit()
    {
        return (int) $this->getAttribute(self::CREDIT);
    }

    public function getDebit()
    {
        return (int) $this->getAttribute(self::DEBIT);
    }

    public function getNetAmount()
    {
        return $this->getCredit() - $this->getDebit();
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getBalance()
    {
        return (int) $this->getAttribute(self::BALANCE);
    }

    public function getSettledAt()
    {
        return $this->getAttribute(self::SETTLED_AT);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getGatewayFee()
    {
        return $this->getAttribute(self::GATEWAY_FEE);
    }

    public function getGatewayServiceTax()
    {
        return $this->getAttribute(self::GATEWAY_SERVICE_TAX);
    }

/* ----------------------------- Accessors -----------------------------------*/

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    protected function getFeeAttribute()
    {
        return (int) $this->attributes[self::FEE];
    }

    protected function getApiFeeAttribute()
    {
        return (int) $this->attributes[self::API_FEE];
    }

    protected function getGatewayFeeAttribute()
    {
        return (int) $this->attributes[self::GATEWAY_FEE];
    }

    protected function getGatewayServiceTaxAttribute()
    {
        return (int) $this->attributes[self::GATEWAY_SERVICE_TAX];
    }

    protected function getDebitAttribute()
    {
        return (int) $this->attributes[self::DEBIT];
    }

    protected function getCreditAttribute()
    {
        return (int) $this->attributes[self::CREDIT];
    }

    protected function getBalanceAttribute()
    {
        return (int) $this->attributes[self::BALANCE];
    }

    protected function getEscrowBalanceAttribute()
    {
        return (int) $this->attributes[self::ESCROW_BALANCE];
    }

    protected function getSettledAttribute()
    {
        return (bool) $this->attributes[self::SETTLED];
    }

    protected function getSettledAtAttribute()
    {
        $settledAt = $this->attributes[self::SETTLED_AT];

        if ($settledAt === null)
        {
            return null;
        }

        return (int) $settledAt;
    }

    protected function getGatewaySettledAtAttribute()
    {
        $gatewaySettledAt = $this->attributes[self::GATEWAY_SETTLED_AT];

        if ($gatewaySettledAt === null)
        {
            return null;
        }

        return (int) $gatewaySettledAt;
    }

    protected function getGratisAttribute()
    {
        return (bool) $this->attributes[self::GRATIS];
    }

    protected function getServiceTaxAttribute()
    {
        return (int) $this->attributes[self::SERVICE_TAX];
    }

/* --------------------------- End Accessors ---------------------------------*/

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

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getApiFee()
    {
        return $this->getAttribute(self::API_FEE);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function getPricingRule()
    {
        return $this->getAttribute(self::PRICING_RULE_ID);
    }

    public function setReconciledAt($timestamp)
    {
        $this->setAttribute(self::RECONCILED_AT, $timestamp);
    }

    public function setGatewaySettledAt($timestamp)
    {
        $this->setAttribute(self::GATEWAY_SETTLED_AT, $timestamp);
    }

    public function getGatewaySettledAt()
    {
        return $this->getAttribute(self::GATEWAY_SETTLED_AT);
    }

    public function setGatewayFee($gatewayFee)
    {
        $this->setAttribute(self::GATEWAY_FEE, $gatewayFee);
    }

    public function setGatewayServiceTax($gatewayServiceTax)
    {
        $this->setAttribute(self::GATEWAY_SERVICE_TAX, $gatewayServiceTax);
    }

    public function setEscrowBalance($balance)
    {
        assert ($balance >= 0);

        $this->setAttribute(self::ESCROW_BALANCE, $balance);
    }

    public function setBalance($balance)
    {
        assert ($balance >= 0);

        $this->setAttribute(self::BALANCE, $balance);
    }

    public function setAmount($amount)
    {
        assert ($amount > 0);

        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setFee($fee)
    {
        assert ($fee >= 0);

        $this->setAttribute(self::FEE, $fee);
    }

    public function setCredit($credit)
    {
        assert ($credit >= 0);

        $this->setAttribute(self::CREDIT, $credit);
    }

    public function setGratis($gratis)
    {
        $this->setAttribute(self::GRATIS, $gratis);
    }

    public function setDebit($amount)
    {
        assert ($amount >= 0);

        $this->setAttribute(self::DEBIT, $amount);
    }

    public function setPricingRule($ruleId)
    {
        $this->setAttribute(self::PRICING_RULE_ID, $ruleId);
    }

    public function setPublicEntityIdAttribute(array & $array)
    {
        $entity = Transaction\Type::getEntityClass($array[self::TYPE]);

        $sign = $entity::getIdPrefix();

        $array[self::ENTITY_ID] = $sign . $array[self::ENTITY_ID];
    }

    public function setPublicSettlementIdAttribute(array & $array)
    {
        if ($array[self::SETTLED] !== true)
        {
            return;
        }

        $sign = Settlement\Entity::getIdPrefix();

        $array[self::SETTLEMENT_ID] = $sign . $array[self::SETTLEMENT_ID];
    }

    public function setServiceTax($servicetax)
    {
        assertTrue($servicetax >= 0);

        $this->setAttribute(self::SERVICE_TAX, $servicetax);
    }

    public function isReconciled()
    {
        return ($this->getAttribute(self::RECONCILED_AT) !== null);
    }

    public function isTypePayment()
    {
        return ($this->getType() === Type::PAYMENT);
    }

    public function isTypeRefund()
    {
        return ($this->getType() === Type::REFUND);
    }

    public function isGratis()
    {
        return $this->getAttribute(self::GRATIS);
    }

    public function toArrayReport()
    {
        $reportTxn = parent::toArrayReport();

        unset($reportTxn[self::ID]);

        $reportTxn['description'] = null;
        $reportTxn['notes'] = null;
        $reportTxn['payment_id'] = null;

        // settled_at will by default have date and time (d/m/y h:m:s) in it
        // while we only want to provide date.
        $reportTxn[self::SETTLED_AT] = $this->getDateInFormatDMY(self::SETTLED_AT);

        if ($this->isTypePayment())
        {
            $payment = $this->source;

            $reportTxn['description'] = $payment->getDescription();
            $reportTxn['notes'] = $payment->getNotesJson();

            if ($payment->hasBeenCaptured() === false)
            {
                // Skip if the payment was not captured.
                return null;
            }
        }
        else if ($this->isTypeRefund())
        {
            $refund = $this->source;
            $payment = $refund->payment;

            // Skip if the payment was not captured.
            if ($payment->hasBeenCaptured() === false)
            {
                return null;
            }

            $reportTxn['payment_id'] = $payment->getPublicId();
        }

        return $reportTxn;
    }

    public function validateEntityIdUnique()
    {
        $entityId = [self::ENTITY_ID => $this->getEntityId()];

        $this->getValidator()->validateInput('unique_entity_id', $entityId);
    }
}
