<?php

namespace RZP\Models\Transfer;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Order\Repository as OrderRepository;
use RZP\Models\Transfer\Traits\LinkedAccountNotesTrait;

/**
 * @property Merchant $merchant
 */
class Entity extends Base\PublicEntity
{
    use LinkedAccountNotesTrait;
    use NotesTrait;

    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const TO_ID                     = 'to_id';
    const TO_TYPE                   = 'to_type';
    const SOURCE_ID                 = 'source_id';
    const SOURCE_TYPE               = 'source_type';
    const AMOUNT                    = 'amount';
    const CURRENCY                  = 'currency';
    const REVERSAL_STATUS           = 'reversal_status';
    const AMOUNT_REVERSED           = 'amount_reversed';
    const NOTES                     = 'notes';
    const FEES                      = 'fees';
    const TAX                       = 'tax';
    const ON_HOLD                   = 'on_hold';
    const ON_HOLD_UNTIL             = 'on_hold_until';
    const TRANSACTION_ID            = 'transaction_id';
    const RECIPIENT_SETTLEMENT_ID   = 'recipient_settlement_id';
    const RECIPIENT_SETTLEMENT      = 'recipient_settlement';
    const LINKED_ACCOUNT_NOTES      = 'linked_account_notes';
    const STATUS                    = 'status';
    const MESSAGE                   = 'message';
    const ORIGIN                    = 'origin';
    const PROCESSED_AT              = 'processed_at';
    const ATTEMPTS                  = 'attempts';
    const ACCOUNT_CODE              = 'account_code';
    const ACCOUNT_CODE_USED         = 'account_code_used';
    const ERROR_CODE                = 'error_code';

    const ERROR = 'error';

    // Report fields
    const SETTLEMENT_INITIATED_ON = 'settlement_initiated_on';
    const SETTLEMENT_UTR          = 'settlement_utr';
    const SETTLEMENT_STATUS       = 'settlement_status';

    // Public attribute keys for SOURCE_ID and TO_ID
    const SOURCE    = 'source';
    const RECIPIENT = 'recipient';

    // Expanded relation keys
    const TO                   = 'to';

    // Relation
    const TRANSACTION          = 'transaction';

    // Append attributes
    const RECIPIENT_DETAILS = 'recipient_details';
    const PARENT_PAYMENT_ID = 'parent_payment_id';

    const PARTNER_DETAILS = 'partner_details';

    protected static $sign = 'trf';

    protected $entity = 'transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::LINKED_ACCOUNT_NOTES,
        self::STATUS,
        self::ORIGIN,
        self::ACCOUNT_CODE,
    ];

    protected $visible = [
        self::ID,
        self::TO_TYPE,
        self::TO_ID,
        self::SOURCE_TYPE,
        self::SOURCE_ID,
        self::SOURCE,
        self::RECIPIENT,
        self::RECIPIENT_DETAILS,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::NOTES,
        self::FEES,
        self::TAX,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::TRANSACTION_ID,
        self::RECIPIENT_SETTLEMENT_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LINKED_ACCOUNT_NOTES,
        self::STATUS,
        self::SETTLEMENT_STATUS,
        self::ATTEMPTS,
        self::PROCESSED_AT,
        self::MESSAGE,
        self::ACCOUNT_CODE,
        self::ACCOUNT_CODE_USED,
        self::ERROR_CODE,
        self::PARTNER_DETAILS,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::STATUS,
        self::SOURCE,
        self::RECIPIENT,
        self::RECIPIENT_DETAILS,
        self::ACCOUNT_CODE,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::FEES,
        self::TAX,
        self::NOTES,
        self::LINKED_ACCOUNT_NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::SETTLEMENT_STATUS,
        self::RECIPIENT_SETTLEMENT_ID,
        self::RECIPIENT_SETTLEMENT,
        self::CREATED_AT,
        self::PROCESSED_AT,
        self::ERROR,
        self::PARTNER_DETAILS,
    ];

    protected $publicSetters = [
        self::ID,
        self::SOURCE,
        self::RECIPIENT,
        self::RECIPIENT_DETAILS,
        self::RECIPIENT_SETTLEMENT_ID,
        self::TRANSACTION_ID,
        self::ENTITY,
        self::LINKED_ACCOUNT_NOTES,
        self::PARENT_PAYMENT_ID,
        self::ERROR,
    ];

    protected $appends = [
        self::RECIPIENT_DETAILS,
    ];

    protected $expanded = [
        self::TRANSACTION
    ];

    protected $casts = [
        self::AMOUNT                 => 'int',
        self::AMOUNT_REVERSED        => 'int',
        self::FEES                   => 'int',
        self::TAX                    => 'int',
        self::ON_HOLD                => 'bool',
        self::ON_HOLD_UNTIL          => 'int',
        self::PROCESSED_AT           => 'int',
        self::ATTEMPTS               => 'int',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_REVERSED,
        self::FEES,
        self::TAX,
    ];

    protected $defaults = [
        self::AMOUNT_REVERSED         => 0,
        self::NOTES                   => [],
        self::ON_HOLD                 => 0,
        self::ON_HOLD_UNTIL           => null,
        self::RECIPIENT_SETTLEMENT_ID => null,
        self::MESSAGE                 => null,
        self::ORIGIN                  => Origin::API,
        self::PROCESSED_AT            => null,
        self::ATTEMPTS                => 0,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::ON_HOLD_UNTIL,
        self::PROCESSED_AT,
    ];

    protected $ignoredRelations = [
        'source',
        'to',
    ];

    protected static $generators = [
        self::ACCOUNT_CODE_USED,
    ];

    public function generateAccountCodeUsed($input)
    {
        $this->setAttribute(self::ACCOUNT_CODE_USED, isset($input[self::ACCOUNT_CODE]));
    }

    // -------------------- Relations ---------------------------

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function to()
    {
        return $this->morphTo();
    }

    public function reversals()
    {
        return $this->morphMany(Reversal\Entity::class, 'entity');
    }

    public function recipientSettlement()
    {
        return $this->belongsTo(Settlement\Entity::class);
    }

    // -------------------- End Relations -----------------------

    // -------------------- Getters -----------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getToType()
    {
        return $this->getAttribute(self::TO_TYPE);
    }

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
    }

    public function getToId()
    {
        return $this->getAttribute(self::TO_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getAmountReversed()
    {
        return $this->getAttribute(self::AMOUNT_REVERSED);
    }

    public function getAmountUnreversed()
    {
        return ($this->getAmount() - $this->getAmountReversed());
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    // FeeCalculator calls `$entity->getFee()` for all the pricing entity
    public function getFee()
    {
        return $this->getFees();
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getOnHold()
    {
        return $this->getAttribute(self::ON_HOLD);
    }

    public function getOnHoldUntil()
    {
        return $this->getAttribute(self::ON_HOLD_UNTIL);
    }

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getRecipientSettlementId()
    {
        return $this->getAttribute(self::RECIPIENT_SETTLEMENT_ID);
    }

    /**
     * Called by pricing flow to determine fee based on transfer
     * method
     *
     * @return mixed
     */
    public function getMethod()
    {
        $method = $this->getToType();

        //
        // Pricing is defined for the 'account' method, which is stored
        // internally as merchant and we convert convert it accordingly.
        //
        if ($method === 'merchant')
        {
            $method = ToType::ACCOUNT;
        }

        return $method;
    }

    /**
     * Define the pricing features for Transfers
     *
     * @return array
     */
    public function getPricingFeatures()
    {
        return [];
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getAccountCode()
    {
        return $this->getAttribute(self::ACCOUNT_CODE);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getSettlementStatus()
    {
        return $this->getAttribute(self::SETTLEMENT_STATUS);
    }

    public function getMessage()
    {
        return $this->getAttribute(self::MESSAGE);
    }

    // -------------------- End Getters ---------------------------

    // -------------------- Setters ---------------------------

    public function setAmountReversed(int $amount)
    {
        $this->setAttribute(self::AMOUNT_REVERSED, $amount);
    }

    public function setFees(int $fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function setTax(int $tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setOnHold(bool $onHold)
    {
        $this->setAttribute(self::ON_HOLD, $onHold);
    }

    public function setOnHoldUntil($holdUntil)
    {
        $this->setAttribute(self::ON_HOLD_UNTIL, $holdUntil);
    }

    public function setRecipientSettlementId(string $recipientSettlementId)
    {
        $this->setAttribute(self::RECIPIENT_SETTLEMENT_ID, $recipientSettlementId);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setProcessed()
    {
        $this->setStatus(Status::PROCESSED);

        $currentTime = Carbon::now()->getTimestamp();

        $this->setAttribute(self::PROCESSED_AT, $currentTime);
    }

    public function setFailed()
    {
        $this->setStatus(Status::FAILED);

        $currentTime = Carbon::now()->getTimestamp();

        $this->setAttribute(self::PROCESSED_AT, $currentTime);
    }

    public function setMessage(string $message)
    {
        $this->setAttribute(self::MESSAGE, $message);
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    public function setAccountCode(string $accountCode)
    {
        $this->setAttribute(self::ACCOUNT_CODE, $accountCode);
    }

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setErrorCode($errorCode)
    {
        return $this->setAttribute(self::ERROR_CODE, $errorCode);
    }

    public function setSettlementStatus(string $settlementStatus)
    {
        $this->setAttribute(self::SETTLEMENT_STATUS, $settlementStatus);
    }

    // -------------------- End Setters ---------------------------

    /**
     * Is this a direct transfer? i.e. No source payment
     *
     * @return bool
     */
    public function isDirectTransfer(): bool
    {
        return ($this->getSourceType() === E::MERCHANT);
    }

    public function isPaymentTransfer(): bool
    {
        return ($this->getSourceType() === E::PAYMENT);
    }

    public function isOrderTransfer(): bool
    {
        return ($this->getSourceType() === E::ORDER);
    }

    public function isBalanceTransfer(): bool
    {
        return ($this->getToType() === ToType::BALANCE);
    }

    public function isCreated(): bool
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isProcessed(): bool
    {
        return ($this->getStatus() === Status::PROCESSED);
    }

    public function isFailed(): bool
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function reverseAmount(int $amount)
    {
        $amountUnreversed = $this->getAmountUnreversed();

        if ($amount > $amountUnreversed)
        {
            throw new Exception\LogicException(
                'Transfer reversal amount should be less than or equal to amount not refunded yet',
                'amount_reversed',
                [
                    'amount'            => $amount,
                    'amount_unreversed' => $amountUnreversed,
                ]);
        }

        $amountReversed = $this->getAmountReversed() + $amount;

        $this->setAttribute(self::AMOUNT_REVERSED, $amountReversed);

        if ($amount < $amountUnreversed)
        {
            $this->setStatus(Status::PARTIALLY_REVERSED);
        }
        else
        {
            $this->setStatus(Status::REVERSED);
        }
    }

    /**
     * Add the `recipient_details` attribute via $appends
     *
     * @return array|null
     */
    public function getRecipientDetailsAttribute()
    {
        if ($this->getToType() !== E::MERCHANT)
        {
            return null;
        }

        $account = $this->to;

        $accountAttributes = [
            Merchant\Entity::NAME,
            Merchant\Entity::EMAIL,
        ];

        $details = $account->setVisible($accountAttributes)->toArray();

        return $details;
    }

    /** unset the ParentPaymentId attribute based on the feature flag.
     *
     * @param array $attributes
     */
    public function setPublicParentPaymentIdAttribute(array &$attributes)
    {
        $merchant = $this->merchant;

        if($merchant->isDisplayParentPaymentId() == false)
        {
            unset($attributes[self::PARENT_PAYMENT_ID]);
        }
    }

    /** GetParent PaymentId based on the sourceType
     *
     * @return string
     */
    public function getParentPaymentIdAttribute(): string
    {
        $transferSourceType = $this->getSourceType();

        $parentPaymentId = "";

        if($transferSourceType === E::ORDER)
        {
            $parentPaymentId = Payment\Entity::getSignedId($this->source->payments()->whereIn('status', ['captured','refunded'])->first()->getId());
        }
        else if($transferSourceType === E::PAYMENT)
        {
            $parentPaymentId = Payment\Entity::getSignedId($this->getSourceId());
        }

        return $parentPaymentId;
    }

    public function setPublicRecipientDetailsAttribute(array & $attributes)
    {
        //
        // The `recipient_details` attributes is only needed for
        // for dashboard and should be hidden in private API
        // requests
        //
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::RECIPIENT_DETAILS]);
        }
    }

    public function setPublicTransactionIdAttribute(array & $attributes)
    {
        $txnId = $this->getAttribute(self::TRANSACTION_ID);

        if ($txnId !== null)
        {
            $attributes[self::TRANSACTION_ID] = Transaction\Entity::getSignedId($txnId);
        }
    }

    public function setPublicRecipientAttribute(array & $attributes)
    {
        $toId = $this->getAttribute(self::TO_ID);

        $toType = $this->getAttribute(self::TO_TYPE);

        $entity = E::getEntityClass($toType);

        if ($toType === 'merchant')
        {
            $entity = 'RZP\Models\Merchant\Account\Entity';
        }

        $attributes[self::RECIPIENT] = $entity::getSignedId($toId);
    }

    public function setPublicSourceAttribute(array & $attributes)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $sourceType = $this->getAttribute(self::SOURCE_TYPE);

        $entity = E::getEntityClass($sourceType);

        if ($sourceType === 'merchant')
        {
            $entity = 'RZP\Models\Merchant\Account\Entity';
        }

        $attributes[self::SOURCE] = $entity::getSignedId($sourceId);
    }

    public function setPublicRecipientSettlementIdAttribute(array & $attributes)
    {
        $setld = $this->getAttribute(self::RECIPIENT_SETTLEMENT_ID);

        $attributes[self::RECIPIENT_SETTLEMENT_ID] = Settlement\Entity::getSignedIdOrNull($setld);
    }

    public function setPublicErrorAttribute(array & $attributes)
    {
        $attributes[self::ERROR] = ErrorCodeMapping::getPublicErrorAttribute($this);
    }

    public function getSourceAttribute()
    {
        $source = null;

        if ($this->relationLoaded('source') === true)
        {
            $source = $this->getRelation('source');
        }

        if ($source !== null)
        {
            return $source;
        }

        if ($this->getSourceType() === Constant::ORDER)
        {
            $source = $this->source()->with('offers')->first();
        }
        else if ($this->getSourceId() !== null)
        {
            $source = $this->source()->first();
        }

        if (empty($source) === false)
        {
            return $source;
        }

        if ($this->getSourceType() === Constant::ORDER)
        {
            $order = (new OrderRepository())->findOrFailPublic($this->getSourceId());

            $this->source()->associate($order);

            return $order;
        }

        if ($this->getSourceType() === Constant::PAYMENT)
        {
            $payment = (new Payment\Repository)->findOrFailPublic($this->getSourceId());

            $this->source()->associate($payment);

            return $payment;
        }

        return null;
    }

    public function toArrayReport()
    {
        app('trace')->info(
            TraceCode::GENERATING_TRANSFERS_REPORT,
            [
                'id'            => $this->getId() ?? null,
                'merchant_id'   => $this->getMerchantId() ?? null,
            ]
        );

        $data = parent::toArrayReport();

        $settlementId          = null;
        $settlementInitiatedOn = null;
        $utr                   = null;
        $settlementStatus      = null;

        if (isset($data[self::RECIPIENT_SETTLEMENT]) === true)
        {
            $recipientSettlement   = $data[self::RECIPIENT_SETTLEMENT];
            $settlementId          = $recipientSettlement[Settlement\Entity::ID];
            $settlementCreatedAt   = $recipientSettlement[Settlement\Entity::CREATED_AT];
            $settlementInitiatedOn = Carbon::createFromTimestamp($settlementCreatedAt, Timezone::IST)->format('d/m/y');
            $utr                   = $recipientSettlement[Settlement\Entity::UTR];
            $settlementStatus      = $recipientSettlement[Settlement\Entity::STATUS];

            unset($data[self::RECIPIENT_SETTLEMENT]);
        }

        $tax = $data[self::TAX];

        // Unset the keys here and set it at the end to maintain order of columns in the report
        unset($data[self::RECIPIENT_SETTLEMENT_ID]);
        unset($data[self::TAX]);

        $data[self::ON_HOLD]                 = $this->getOnHold() ? "true" : "false";
        $data[self::RECIPIENT_SETTLEMENT_ID] = $settlementId;
        $data[self::SETTLEMENT_INITIATED_ON] = $settlementInitiatedOn;
        $data[self::SETTLEMENT_UTR]          = $utr;
        $data[self::SETTLEMENT_STATUS]       = $settlementStatus;
        $data[self::TAX]                     = $tax;

        return $data;
    }

    public function toArrayPublic()
    {
        $data = parent::toArrayPublic();

        if (empty($data[Entity::ACCOUNT_CODE]) === true)
        {
            unset($data[Entity::ACCOUNT_CODE]);
        }

        return $data;
    }

    public function toArrayPublicWithExpand()
    {
        $data = parent::toArrayPublicWithExpand();

        if (empty($data[Entity::ACCOUNT_CODE]) === true)
        {
            unset($data[Entity::ACCOUNT_CODE]);
        }

        return $data;
    }

    public function build(array $input = array())
    {
        if ((isset($input[self::NOTES]) === true) and
            (isset($input[self::LINKED_ACCOUNT_NOTES]) === true))
        {
            $linkedAccountNotes = $input[self::LINKED_ACCOUNT_NOTES];

            unset($input[self::LINKED_ACCOUNT_NOTES]);

            $input[self::LINKED_ACCOUNT_NOTES] = $linkedAccountNotes;
        }

        return parent::build($input);
    }
}
