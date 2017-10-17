<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Adjustment;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Models\Merchant;
use RZP\Models\Dispute;

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
    const TAX                 = 'tax';
    const PRICING_RULE_ID     = 'pricing_rule_id';
    const BALANCE             = 'balance';
    const GATEWAY_AMOUNT      = 'gateway_amount';
    const GATEWAY_FEE         = 'gateway_fee';
    const GATEWAY_SERVICE_TAX = 'gateway_service_tax';
    const GATEWAY_SETTLED_AT  = 'gateway_settled_at';
    const API_FEE             = 'api_fee';
    const GRATIS              = 'gratis';
    const FEE_CREDITS         = 'fee_credits';
    const ESCROW_BALANCE      = 'escrow_balance';
    const RECONCILED_AT       = 'reconciled_at';
    const CHANNEL             = 'channel';
    const FEE_MODEL           = 'fee_model';
    const FEE_BEARER          = 'fee_bearer';
    const CREDIT_TYPE         = 'credit_type';
    const ON_HOLD             = 'on_hold';
    const SETTLED             = 'settled';
    const SETTLED_AT          = 'settled_at';
    const SETTLEMENT_ID       = 'settlement_id';

    const PAYMENT_ID        = 'payment_id';

    const RECONCILED        = 'reconciled';

    protected static $sign = 'txn';

    protected $entity = 'transaction';

    protected $fillable = [
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
        self::TAX,
        self::GRATIS,
        self::FEE_CREDITS,
        self::BALANCE,
        self::ESCROW_BALANCE,
        self::PRICING_RULE_ID,
        self::RECONCILED_AT,
        self::CHANNEL,
        self::FEE_MODEL,
        self::FEE_BEARER,
        self::CREDIT_TYPE,
        self::ON_HOLD,
        self::SETTLED_AT
    ];

    protected $public = [
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
        self::ON_HOLD,
        self::SETTLED,
        self::CREATED_AT,
        self::SETTLED_AT,
        self::SETTLEMENT_ID,
        self::TAX,
    ];

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::ENTITY_ID,
        self::SETTLEMENT_ID);

    protected $dates = array(
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SETTLED_AT,
    );

    protected $defaults = array(
        self::GRATIS                => false,
        self::GATEWAY_SETTLED_AT    => null,
        self::GATEWAY_AMOUNT        => null,
        self::GATEWAY_FEE           => null,
        self::GATEWAY_SERVICE_TAX   => null,
        self::BALANCE               => null,
        self::API_FEE               => null,
        self::FEE_CREDITS           => 0,
        self::ESCROW_BALANCE        => null,
        self::SETTLED_AT            => null,
        self::SETTLEMENT_ID         => null,
        self::RECONCILED_AT         => null,
        self::ON_HOLD               => 0,
        self::SETTLED               => 0,
        self::PRICING_RULE_ID       => null,
        self::SERVICE_TAX           => null,
        self::TAX                   => null,
        self::FEE_MODEL             => Merchant\FeeModel::NA,
        self::FEE_BEARER            => Merchant\FeeBearer::NA,
        self::CREDIT_TYPE           => CreditType::DEFAULT,
    );

    protected $amounts = array(
        self::AMOUNT,
        self::DEBIT,
        self::CREDIT,
        self::FEE,
        self::SERVICE_TAX,
        self::TAX,
    );

    protected $casts = [
        self::CREDIT              => 'int',
        self::DEBIT               => 'int',
        self::AMOUNT              => 'int',
        self::FEE                 => 'int',
        self::SERVICE_TAX         => 'int',
        self::TAX                 => 'int',
        self::BALANCE             => 'int',
        self::GATEWAY_AMOUNT      => 'int',
        self::GATEWAY_FEE         => 'int',
        self::GATEWAY_SERVICE_TAX => 'int',
        self::GRATIS              => 'bool',
        self::FEE_CREDITS         => 'int',
        self::FEE_MODEL           => 'int',
        self::FEE_BEARER          => 'int',
        self::ON_HOLD             => 'bool',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function source()
    {
        return $this->morphTo('source', 'type', 'entity_id');
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

    public function feesBreakup()
    {
        return $this->hasMany('RZP\Models\Transaction\FeeBreakup\Entity', 'transaction_id');
    }

    public function getCredit()
    {
        return $this->getAttribute(self::CREDIT);
    }

    public function getDebit()
    {
        return $this->getAttribute(self::DEBIT);
    }

    public function getNetAmount()
    {
        return $this->getCredit() - $this->getDebit();
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getBalance()
    {
        return (int) $this->getAttribute(self::BALANCE);
    }

    public function getOnHold()
    {
        return $this->getAttribute(self::ON_HOLD);
    }

    public function getSettledAt()
    {
        return $this->getAttribute(self::SETTLED_AT);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getGatewayAmount()
    {
        return $this->getAttribute(self::GATEWAY_AMOUNT);
    }

    public function getGatewayFee()
    {
        return $this->getAttribute(self::GATEWAY_FEE);
    }

    public function getGatewayServiceTax()
    {
        return $this->getAttribute(self::GATEWAY_SERVICE_TAX);
    }

    public function getFeeBearer()
    {
        return $this->getAttribute(self::FEE_BEARER);
    }

    public function getFeeModel()
    {
        return $this->getAttribute(self::FEE_MODEL);
    }

    public function getCreditType()
    {
        return $this->getAttribute(self::CREDIT_TYPE);
    }

    public function getSettlementId()
    {
        return $this->getAttribute(self::SETTLEMENT_ID);
    }

    public function getReconciledAt()
    {
        return $this->getAttribute(self::RECONCILED_AT);
    }

/* ----------------------------- Accessors -----------------------------------*/

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

    protected function getServiceTaxAttribute()
    {
        return (int) $this->attributes[self::SERVICE_TAX];
    }

    protected function getTaxAttribute()
    {
        return (int) $this->attributes[self::TAX];
    }

    protected function setFeeBearerAttribute($bearer)
    {
        $this->attributes[self::FEE_BEARER] = Merchant\FeeBearer::getValueForBearerString($bearer);
    }

    protected function getFeeBearerAttribute()
    {
        return Merchant\FeeBearer::getBearerStringForValue($this->attributes[self::FEE_BEARER]);
    }

    protected function getFeeModelAttribute()
    {
        return Merchant\FeeModel::getFeeModelStringForValue($this->attributes[self::FEE_MODEL]);
    }

    protected function setFeeModelAttribute($feeModel)
    {
        $this->attributes[self::FEE_MODEL] = Merchant\FeeModel::getValueForFeeModelString($feeModel);
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

    public function getFeeCredits()
    {
        return $this->getAttribute(self::FEE_CREDITS);
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

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getPricingRule()
    {
        return $this->getAttribute(self::PRICING_RULE_ID);
    }

    public function getGatewaySettledAt()
    {
        return $this->getAttribute(self::GATEWAY_SETTLED_AT);
    }

    public function setReconciledAt($timestamp)
    {
        $this->setAttribute(self::RECONCILED_AT, $timestamp);
    }

    public function setGatewaySettledAt($timestamp)
    {
        $this->setAttribute(self::GATEWAY_SETTLED_AT, $timestamp);
    }

    public function setGatewayAmount($gatewayAmount)
    {
        $this->setAttribute(self::GATEWAY_AMOUNT, $gatewayAmount);
    }

    public function setGatewayFee($gatewayFee)
    {
        $this->setAttribute(self::GATEWAY_FEE, $gatewayFee);
    }

    public function setGatewayServiceTax($gatewayServiceTax)
    {
        $this->setAttribute(self::GATEWAY_SERVICE_TAX, $gatewayServiceTax);
    }

    public function setSettledAt($settledAt)
    {
        $this->setAttribute(self::SETTLED_AT, $settledAt);
    }

    public function setOnHold(bool $onHold)
    {
        $this->setAttribute(self::ON_HOLD, $onHold);
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

    public function setFeeCredits(int $credits)
    {
        $this->setAttribute(self::FEE_CREDITS, $credits);
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

    public function setTax($tax)
    {
        assertTrue($tax >= 0);

        $this->setAttribute(self::TAX, $tax);
    }

    public function setFeeBearer($bearer)
    {
        $this->setAttribute(self::FEE_BEARER, $bearer);
    }

    public function setFeeModel($feeModel)
    {
        $this->setAttribute(self::FEE_MODEL, $feeModel);
    }

    public function setCreditType($creditType)
    {
        $this->setAttribute(self::CREDIT_TYPE, $creditType);
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

    public function isTypeSettlement()
    {
        return ($this->getType() === Type::SETTLEMENT);
    }

    public function isTypeAdjustment()
    {
        return ($this->getType() === Type::ADJUSTMENT);
    }

    public function isTypeTransfer()
    {
        return ($this->getType() === Type::TRANSFER);
    }

    public function isTypeDispute()
    {
        return ($this->getType() === Type::DISPUTE);
    }

    public function isGratis()
    {
        return $this->getAttribute(self::GRATIS);
    }

    public function isFeeCredits()
    {
        return $this->getAttribute(self::FEE_CREDITS);
    }

    public function isOnHold()
    {
        return $this->getOnHold();
    }

    public function isSettled()
    {
        return $this->getSettledAttribute();
    }

    public function isFeeBearerCustomer()
    {
        return $this->getAttribute(self::FEE_BEARER) === Merchant\FeeBearer::CUSTOMER;
    }

    public function isPostpaid()
    {
        return ($this->getAttribute(self::FEE_MODEL) === Merchant\FeeModel::POSTPAID);
    }

    public function toArrayReport()
    {
        $reportTxn = parent::toArrayReport();

        unset($reportTxn[self::ID]);

        $tax = $reportTxn[self::TAX];

         // Add tax key at the end to maintain order of columns in the report
        unset($reportTxn[self::TAX]);

        $reportTxn[Payment\Entity::DESCRIPTION] = null;
        $reportTxn[Payment\Entity::NOTES] = null;
        $reportTxn[Refund\Entity::PAYMENT_ID] = null;
        $reportTxn['settlement_utr'] = null;
        $reportTxn[Payment\Entity::ORDER_ID] = null;
        $reportTxn['order_receipt'] = null;
        $reportTxn[Payment\Entity::METHOD] = null;
        $reportTxn['card_network'] = null;
        $reportTxn['card_issuer'] = null;
        $reportTxn['card_type'] = null;
        $reportTxn[Adjustment\Entity::DISPUTE_ID] = null;

        // settled_at will by default have date and time (d/m/y h:m:s) in it
        // while we only want to provide date.
        $reportTxn[self::SETTLED_AT] = $this->getDateInFormatDMY(self::SETTLED_AT);

        if ($this->isTypePayment() === true)
        {
            $payment = $this->source;

            if ($payment->hasBeenCaptured() === false)
            {
                // Skip if the payment was not captured.
                return null;
            }

            $reportTxn[Payment\Entity::DESCRIPTION] = $payment->getDescription();
            $reportTxn[Payment\Entity::NOTES] = $payment->getNotesJson();

            $this->fillPaymentDetails($payment, $reportTxn);
        }
        else if ($this->isTypeRefund() === true)
        {
            $refund = $this->source;

            $payment = $refund->payment;

            // Skip if the payment was not captured.
            if ($payment->hasBeenCaptured() === false)
            {
                return null;
            }

            $reportTxn[Refund\Entity::NOTES] = $refund->getNotesJson();
            $reportTxn[Refund\Entity::PAYMENT_ID] = $payment->getPublicId();

            $this->fillPaymentDetails($payment, $reportTxn);
        }
        else if ($this->isTypeSettlement() === true)
        {
            $settlement = $this->source;

            $reportTxn['settlement_utr'] = $settlement->getUtr();
            $reportTxn[self::SETTLED] = null;
        }
        else if ($this->isTypeAdjustment() === true)
        {
            $adjustment = $this->source;

            $reportTxn[Adjustment\Entity::DESCRIPTION] = $adjustment->getDescription();

            if ($adjustment->getEntityType() === Constants\Entity::DISPUTE)
            {
                $dispute = $adjustment->entity;

                $reportTxn[Adjustment\Entity::DISPUTE_ID] = $dispute->getPublicId();

                $payment = $dispute->payment;

                $reportTxn[Dispute\Entity::PAYMENT_ID] = $payment->getPublicId();

                $this->fillPaymentDetails($payment, $reportTxn);
            }
        }
        else if ($this->isTypeDispute() === true)
        {
            $dispute = $this->source;

            $payment = $dispute->payment;

            $reportTxn[Dispute\Entity::PAYMENT_ID] = $payment->getPublicId();

            $this->fillPaymentDetails($payment, $reportTxn);
        }

        $reportTxn[self::TAX] = $tax;

        return $reportTxn;
    }

    protected function fillPaymentDetails(Payment\Entity $payment, & $reportTxn)
    {
        $reportTxn[Payment\Entity::METHOD] = $payment->getMethod();

        if ($payment->hasOrder() === true)
        {
            $order = $payment->order;

            $reportTxn[Payment\Entity::ORDER_ID] = $order->getPublicId();
            $reportTxn['order_receipt'] = $order->getReceipt();
        }

        if ($payment->isMethodCardOrEmi())
        {
            $card = $payment->card;

            $reportTxn['card_network'] = $card->getNetwork();
            $reportTxn['card_issuer'] = $card->getIssuer();
            $reportTxn['card_type'] = $card->getType();
        }
    }

    public function validateEntityIdUnique()
    {
        $entityId = [self::ENTITY_ID => $this->getEntityId()];

        $this->getValidator()->validateInput('unique_entity_id', $entityId);
    }
}
