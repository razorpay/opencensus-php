<?php

namespace RZP\Models\Transaction;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Dispute;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Payment\Refund;
use RZP\Exception\LogicException;
use RZP\Models\Partner\Commission;
use RZP\Models\BankingAccountStatement;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction
 *
 * @property Merchant\Entity    $merchant
 */
class Entity extends Base\PublicEntity
{
    const ENTITY_ID           = 'entity_id';
    const TYPE                = 'type';
    const AMOUNT              = 'amount';
    const DEBIT               = 'debit';
    const CREDIT              = 'credit';
    const CURRENCY            = 'currency';
    const FEE                 = 'fee';
    const MDR                 = 'mdr';
    const TAX                 = 'tax';
    const PRICING_RULE_ID     = 'pricing_rule_id';
    const BALANCE             = 'balance';
    const GATEWAY_AMOUNT      = 'gateway_amount';
    const GATEWAY_FEE         = 'gateway_fee';
    const GATEWAY_SERVICE_TAX = 'gateway_service_tax';
    const GATEWAY_SETTLED_AT  = 'gateway_settled_at';
    const API_FEE             = 'api_fee';
    const GRATIS              = 'gratis';
    const CREDITS             = 'fee_credits';
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
    const RECONCILED_TYPE     = 'reconciled_type';
    const BALANCE_ID          = 'balance_id';
    const BALANCE_UPDATED     = 'balance_updated';
    const POSTED_AT           = 'posted_at';

    // dummy columns usable later
    const REFERENCE3          = 'reference3';
    const REFERENCE4          = 'reference4';

    const REFERENCE6          = 'reference6';

    //Reference7 (CUSTOMER_FEE) has been used to store customer part of fee in case merchant is in dynamic fee bearer model and in post-paid model
    const CUSTOMER_FEE          = 'reference7';

    //Reference8 (CUSTOMER_TAX) has been used to store customer part of GST, in case merchant is in dynamic fee bearer model and in post-paid model
    const CUSTOMER_TAX          = 'reference8';
    const REFERENCE9          = 'reference9';

    const PAYMENT_ID        = 'payment_id';

    const RECONCILED        = 'reconciled';

    // Relation names/attributes
    const SOURCE            = 'source';
    const ACCOUNT_BALANCE   = 'account_balance';
    const SETTLEMENT        = 'settlement';

    protected static $sign = 'txn';

    protected $entity = 'transaction';

    protected $fillable = [
        self::DEBIT,
        self::CREDIT,
        self::AMOUNT,
        self::CURRENCY,
        self::FEE,
        self::API_FEE,
        self::GATEWAY_FEE,
        self::GATEWAY_SERVICE_TAX,
        self::GATEWAY_SETTLED_AT,
        self::TAX,
        self::GRATIS,
        self::CREDITS,
        self::BALANCE,
        self::ESCROW_BALANCE,
        self::RECONCILED_AT,
        self::RECONCILED_TYPE,
        self::CHANNEL,
        self::FEE_MODEL,
        self::FEE_BEARER,
        self::CREDIT_TYPE,
        self::ON_HOLD,
        self::SETTLED_AT,
        self::POSTED_AT,
        self::CUSTOMER_FEE,
        self::CUSTOMER_TAX,
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
        self::TAX,
        self::ON_HOLD,
        self::SETTLED,
        self::CREATED_AT,
        self::SETTLED_AT,
        self::SETTLEMENT_ID,
        self::POSTED_AT,
        self::CREDIT_TYPE,
    ];

    /**
     * Relations to be returned when receiving expand[] query param in fetch
     * (eg. transaction, transaction.settlement with payment fetch)
     *
     * @var array
     */
    protected $expanded = [
        self::SETTLEMENT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ENTITY_ID,
        self::SETTLEMENT_ID
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SETTLED_AT,
        self::POSTED_AT,
    ];

    protected $defaults = [
        self::GRATIS                => false,
        self::GATEWAY_SETTLED_AT    => null,
        self::GATEWAY_AMOUNT        => null,
        self::GATEWAY_FEE           => null,
        self::GATEWAY_SERVICE_TAX   => null,
        self::BALANCE               => null,
        self::API_FEE               => null,
        self::CREDITS               => 0,
        self::ESCROW_BALANCE        => null,
        self::SETTLED_AT            => null,
        self::SETTLEMENT_ID         => null,
        self::RECONCILED_AT         => null,
        self::RECONCILED_TYPE       => null,
        self::ON_HOLD               => 0,
        self::SETTLED               => 0,
        self::PRICING_RULE_ID       => null,
        self::TAX                   => null,
        self::MDR                   => null,
        self::FEE_MODEL             => Merchant\FeeModel::NA,
        self::FEE_BEARER            => Merchant\FeeBearer::NA,
        self::CREDIT_TYPE           => CreditType::DEFAULT,
        self::BALANCE_UPDATED       => null,
        self::CUSTOMER_FEE          => null,
        self::CUSTOMER_TAX          => null,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::DEBIT,
        self::CREDIT,
        self::FEE,
        self::TAX,
        self::MDR,
    ];

    protected $casts = [
        self::CREDIT              => 'int',
        self::DEBIT               => 'int',
        self::AMOUNT              => 'int',
        self::FEE                 => 'int',
        self::GATEWAY_AMOUNT      => 'int',
        self::GRATIS              => 'bool',
        self::CREDITS             => 'int',
        self::ON_HOLD             => 'bool',
        self::SETTLED_AT          => 'int',
        self::GATEWAY_SETTLED_AT  => 'int',
        self::BALANCE_UPDATED     => 'bool',
    ];

    protected $ignoredRelations = [
        self::SOURCE,
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

        //
        // Besides transactions having source_id and source_type, most such
        // source contain transaction_id (belongsTo) or transaction (morphTo)
        // relation and hence below association is being done. But now newer
        // source entities e.g. BankTransfer do not contain later kind of columns
        // in them, is unnecessary.
        //

        if ($entity->transaction() instanceof BelongsTo)
        {
            $entity->transaction()->associate($this);
        }
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement\Entity::class);
    }

    public function feesBreakup()
    {
        return $this->hasMany(FeeBreakup\Entity::class, 'transaction_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission\Entity::class, Commission\Entity::TRANSACTION_ID, Entity::ID);
    }

    public function bankingAccountStatement()
    {
        return $this->hasOne(BankingAccountStatement\Entity::class);
    }

    public function getCredit()
    {
        return $this->getAttribute(self::CREDIT);
    }

    public function getDebit()
    {
        return $this->getAttribute(self::DEBIT);
    }

    public function isCredit()
    {
        return ($this->getCredit() > 0);
    }

    public function isDebit()
    {
        return ($this->getDebit() > 0);
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

    public function getSignedEntityId(): string
    {
        if (($this->getType() === null) or ($this->getEntityId() === null))
        {
            throw new LogicException('Unexpected method call, source entity has not been associated yet.');
        }

        $entityClass = Constants\Entity::getEntityClass($this->getType());

        return $entityClass::getSignedId($this->getEntityId());
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

    public function getReconciledType()
    {
        return $this->getAttribute(self::RECONCILED_TYPE);
    }

    public function getCustomerFee()
    {
        return $this->getAttribute(self::CUSTOMER_FEE);
    }

    public function getCustomerTax()
    {
        return $this->getAttribute(self::CUSTOMER_TAX);
    }

/* ----------------------------- Accessors -----------------------------------*/

    //
    // These accessor methods are added as we want to convert null to the desired
    // type if a value is null. null values are not handled by casts
    //

    protected function getApiFeeAttribute($apiFee)
    {
        return (int) $apiFee;
    }

    protected function getGatewayFeeAttribute($gatewayFee)
    {
        return (int) $gatewayFee;
    }

    protected function getGatewayServiceTaxAttribute($gatewayServiceTax)
    {
        return (int) $gatewayServiceTax;
    }

    protected function getBalanceAttribute($balance)
    {
        return (int) $balance;
    }

    protected function getEscrowBalanceAttribute($escrowBalance)
    {
        return (int) $escrowBalance;
    }

    protected function getTaxAttribute($tax)
    {
        return (int) $tax;
    }

    protected function getSettledAttribute($settled)
    {
        return (bool) $settled;
    }

    protected function getFeeBearerAttribute($bearer)
    {
        return Merchant\FeeBearer::getBearerStringForValue($bearer);
    }

    protected function getFeeModelAttribute($feeModel)
    {
        return Merchant\FeeModel::getFeeModelStringForValue($feeModel);
    }

    protected function getMdrAttribute($mdr)
    {
        if ($mdr === null)
        {
            return $this->getFee();
        }

        return $mdr;
    }

    public function getSourceAttribute()
    {
        if ($this->relationLoaded('source') === true)
        {
            return $this->getRelation('source');
        }

        if ($this->getType() === Constants\Entity::REFUND)
        {
            $refund = (new Refund\Repository())->findOrFailPublic($this->getEntityId());

            $this->source()->associate($refund);

            return $refund;
        }

        $source = $this->source()->first();

        if (empty($source) === false)
        {
            return $source;
        }

        if ($this->getType() === Constants\Entity::PAYMENT)
        {
            $payment = (new Payment\Repository)->findOrFailPublic($this->getEntityId());

            $this->source()->associate($payment);

            return $payment;
        }

        return null;
    }

/* --------------------------- End Accessors ---------------------------------*/

/* --------------------------- Mutators --------------------------------------*/

    protected function setFeeBearerAttribute($bearer)
    {
        $this->attributes[self::FEE_BEARER] = Merchant\FeeBearer::getValueForBearerString($bearer);
    }

    protected function setFeeModelAttribute($feeModel)
    {
        $this->attributes[self::FEE_MODEL] = Merchant\FeeModel::getValueForFeeModelString($feeModel);
    }

/* ----------------------------- Mutators end --------------------------------*/

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

    public function getCredits()
    {
        return $this->getAttribute(self::CREDITS);
    }

    public function getApiFee()
    {
        return $this->getAttribute(self::API_FEE);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
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

    public function setType(string $type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setReconciledAt($timestamp)
    {
        $this->setAttribute(self::RECONCILED_AT, $timestamp);
    }

    public function setReconciledType($reconciledType)
    {
        ReconciledType::validateReconciledType($reconciledType);

        $this->setAttribute(self::RECONCILED_TYPE, $reconciledType);
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

    public function setSettled(bool $settled)
    {
        $this->setAttribute(self::SETTLED, $settled);
    }

    public function setBalanceUpdated(bool $balanceUpdated)
    {
        $this->setAttribute(self::BALANCE_UPDATED, $balanceUpdated);
    }

    public function setOnHold(bool $onHold)
    {
        $this->setAttribute(self::ON_HOLD, $onHold);
    }

    public function setEscrowBalance($balance)
    {
        assertTrue ($balance >= 0);

        $this->setAttribute(self::ESCROW_BALANCE, $balance);
    }

    public function setBalance($balance, int $negativeLimit = 0, bool $checkNegativeLimit = true)
    {
        if ($checkNegativeLimit === true)
        {
            assertTrue($balance >= $negativeLimit);
        }

        $this->setAttribute(self::BALANCE, $balance);
    }

    public function setAmount($amount)
    {
        assertTrue ($amount >= 0);

        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setFee($fee)
    {
        $this->setAttribute(self::FEE, $fee);
    }

    public function setApiFee($apiFee)
    {
        $this->setAttribute(self::API_FEE, $apiFee);
    }

    public function setMdr(int $mdr)
    {
        $this->setAttribute(self::MDR, $mdr);
    }

    public function setCredit($credit)
    {
        assertTrue ($credit >= 0);

        $this->setAttribute(self::CREDIT, $credit);
    }

    public function setGratis($gratis)
    {
        $this->setAttribute(self::GRATIS, $gratis);
    }

    public function setCredits(int $credits)
    {
        $this->setAttribute(self::CREDITS, $credits);
    }

    public function setChannel(string $channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setDebit($amount)
    {
        assertTrue ($amount >= 0);

        $this->setAttribute(self::DEBIT, $amount);
    }

    public function setPricingRule($ruleId)
    {
        $this->setAttribute(self::PRICING_RULE_ID, $ruleId);
    }

    public function setPublicEntityIdAttribute(array & $array)
    {
        $entity = Type::getEntityClass($array[self::TYPE]);

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

    public function setTax($tax)
    {
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

    public function setEntityId($id)
    {
        $this->setAttribute(self::ENTITY_ID, $id);
    }

    public function setCustomerFee($customerFee)
    {
        $this->setAttribute(self::CUSTOMER_FEE, $customerFee);
    }

    public function setCustomerTax($customerTax)
    {
        $this->setAttribute(self::CUSTOMER_TAX, $customerTax);
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

    public function isTypeReversal()
    {
        return ($this->getType() === Type::REVERSAL);
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

    public function isTypeFundAccountValidation()
    {
        return ($this->getType() === Type::FUND_ACCOUNT_VALIDATION);
    }

    public function isTypeDispute()
    {
        return ($this->getType() === Type::DISPUTE);
    }

    public function isTypePayout(): bool
    {
        return ($this->getType() === Type::PAYOUT);
    }

    public function isTypeCreditRepayment(): bool
    {
        return ($this->getType() === Type::CREDIT_REPAYMENT);
    }

    public function isTypeCapitalTransaction(): bool
    {
        return (in_array($this->getType(), Type::CAPITAL_TYPE, true) === true);
    }

    public function isGratis()
    {
        return $this->getAttribute(self::GRATIS);
    }

    public function isFeeCredits()
    {
        return ($this->getAttribute(self::CREDIT_TYPE) === CreditType::FEE);
    }

    public function isRewardFeeCredits()
    {
        return ($this->getAttribute(self::CREDIT_TYPE) === CreditType::REWARD_FEE);
    }

    public function isRefundCredits()
    {
        return ($this->getAttribute(self::CREDIT_TYPE) === CreditType::REFUND);
    }

    public function isOnHold()
    {
        return $this->getOnHold();
    }

    public function isSettled()
    {
        return $this->getAttribute(self::SETTLED);
    }

    public function isBalanceUpdated(): bool
    {
        return ($this->getAttribute(self::BALANCE_UPDATED) === true);
    }

    public function isFeeBearerCustomer()
    {
        return $this->getAttribute(self::FEE_BEARER) === Merchant\FeeBearer::CUSTOMER;
    }

    public function isPostpaid()
    {
        return ($this->getAttribute(self::FEE_MODEL) === Merchant\FeeModel::POSTPAID);
    }

    public function hasSettlement()
    {
        return ($this->isAttributeNotNull(self::SETTLEMENT_ID));
    }

    public function toArrayPublic()
    {
        $reportTxn = parent::toArrayPublic();

        // For credit repayment & capital txns, we need id in response to store ids in entities
        if (($this->isTypeCreditRepayment() === true) or
            ($this->isTypeCapitalTransaction() === true))
        {
            return $reportTxn;
        }

        unset($reportTxn[self::ID]);
        unset($reportTxn[self::ENTITY]);

        //
        // For linked accounts alone, add the transfer_id
        // which should show up for payment and refund
        // entity types
        //
        if ($this->merchant->isLinkedAccount() === true)
        {
            $reportTxn[Payment\Entity::TRANSFER_ID] = null;
        }

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

        if ($this->isTypePayment() === true)
        {
            $payment = $this->source;

            if ($payment->hasBeenCaptured() === false)
            {
                // Skip if the payment was not captured.
                return null;
            }

            $this->addLinkedAccountTransferIdFromPayment($payment, $reportTxn);

            $reportTxn[Payment\Entity::DESCRIPTION] = $payment->getDescription();
            $reportTxn[Payment\Entity::NOTES]       = $payment->getNotesJson();

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

            $this->addLinkedAccountTransferIdFromPayment($payment, $reportTxn);

            $reportTxn[Refund\Entity::NOTES]      = $refund->getNotesJson();
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
        else if ($this->isTypeTransfer() === true)
        {
            $transfer = $this->source;

            if ($transfer->getSourceType() === Constants\Entity::PAYMENT)
            {
                $reportTxn[Refund\Entity::PAYMENT_ID] = Payment\Entity::getSignedId($transfer->getSourceId());
            }
        }

        if ($this->hasSettlement() === true)
        {
            $reportTxn['settlement_utr'] = $this->settlement->getUtr();
        }

        return $reportTxn;
    }

    public function toArrayReport()
    {
        $reportTxn = parent::toArrayReport();

        if ($reportTxn !== null)
        {
            // settled_at will by default have date and time (d/m/y h:m:s) in it
            // while we only want to provide date.
            $reportTxn[self::SETTLED_AT] = $this->getDateInFormatDMY(self::SETTLED_AT);
        }

        return $reportTxn;
    }

    protected function addLinkedAccountTransferIdFromPayment(Payment\Entity $payment, & $reportTxn)
    {
        if ($this->merchant->isLinkedAccount() === true)
        {
            $transferId = Transfer\Entity::getSignedId($payment->getTransferId());

            $reportTxn[Payment\Entity::TRANSFER_ID] = $transferId;
        }
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

    public function getReconTimeFromTransactionCreationInMinutes(): int
    {
        return intval(($this->getReconciledAt() - $this->getCreatedAt()) / 60);
    }

    /**
     *
     * Transaction entity has balance (integer) attribute. Having a relation with same name in Eloquent model has
     * multiple issues(examples below). These issues had not surfaced before this change because of very limited
     * usage around the same. Now we have exposed web-hooks, APIs etc around transaction and hence more usage.
     *
     * Few examples of issues:
     * 1. Having a $transaction object outside this class you cannot access balance relation as normal.
     *    Doing $transaction->balance will always get the integer attribute. Workarounds exist but are not
     *    expressive. I.e. $transaction->getRelation('balance') etcetera.
     * 2. For lists API, if having balance relation lazy loaded and existing balance integer attribute in $public,
     *    it'll always get overridden with balance relation because how the base serialization happens. Again,
     *    workaround for this also exists but not worth repeating.
     *
     * Also refer http://php.net/manual/en/language.oop5.traits.php and the trait HasBalance on why can't use:
     * use HasBalance { balance as accountBalance; }
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accountBalance()
    {
        return $this->belongsTo(Merchant\Balance\Entity::class, Entity::BALANCE_ID);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    public function isBalanceTypeBanking(): bool
    {
        return (optional($this->accountBalance)->getType() === Merchant\Balance\Type::BANKING);
    }

    /**
     * Constructs & returns corresponding Statement\Entity.
     * Statement entity is the publicly exposed entity on /transactions/* apis. :(
     *
     * @return Statement\Entity
     */
    public function toStatement(): Statement\Entity
    {
        $statement = new Statement\Entity;

        $this->load('source');

        $statement->exists     = $this->exists;
        $statement->connection = $this->connection;
        $statement->attributes = $this->attributes;
        $statement->relations  = $this->relations;
        $statement->original   = $this->original;

        return $statement;
    }

    /**
     * Gives the cache tag which in join of all the variable passed and prefixed with entity name
     * adding it here because queryCaching doesnt support `joinSub`.
     * todo: move this to cachable trait once `joinSub` support is added
     *
     * @return string
     */
    public static function getCacheTag(): string
    {
        return implode('_', func_get_args());
    }

    public function setPostedDate(int $posted_at)
    {
        $this->setAttribute(self::POSTED_AT, $posted_at);
    }

    public function getPostedDate()
    {
        return $this->getAttribute(self::POSTED_AT);
    }

    public function isBalanceAccountTypeDirect(): bool
    {
        if ($this->isBalanceTypeBanking() === true)
        {
            return ($this->accountBalance->isAccountTypeDirect() === true);
        }

        return false;
    }

    public function shouldNegativeBalanceCheckSkipped(): bool
    {
        if(($this->isTypeAdjustment()) and
            (method_exists($this->source, 'isDispute') === true) and
            ($this->source->isDispute() === true))
        {
            return true;
        }
        return false;
    }
}
