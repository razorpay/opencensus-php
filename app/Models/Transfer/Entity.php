<?php

namespace RZP\Models\Transfer;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Reversal;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Merchant\Entity as Merchant;

class Entity extends Base\PublicEntity
{
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

    // Report fields
    const SETTLEMENT_INITIATED_ON = 'settlement_initiated_on';
    const SETTLEMENT_UTR          = 'settlement_utr';
    const SETTLEMENT_STATUS       = 'settlement_status';

    // Public attribute keys for SOURCE_ID and TO_ID
    const SOURCE    = 'source';
    const RECIPIENT = 'recipient';

    // Expanded relation keys
    const TO                   = 'to';

    // Append attributes
    const RECIPIENT_DETAILS = 'recipient_details';

    protected static $sign = 'trf';

    protected $entity = 'transfer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
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
        self::TAX,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
        self::RECIPIENT,
        self::RECIPIENT_DETAILS,
        self::AMOUNT,
        self::CURRENCY,
        self::AMOUNT_REVERSED,
        self::NOTES,
        self::FEES,
        self::TAX,
        self::ON_HOLD,
        self::ON_HOLD_UNTIL,
        self::RECIPIENT_SETTLEMENT_ID,
        self::RECIPIENT_SETTLEMENT,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::SOURCE,
        self::RECIPIENT,
        self::RECIPIENT_DETAILS,
        self::RECIPIENT_SETTLEMENT_ID,
        self::TRANSACTION_ID,
        self::ENTITY,
    ];

    protected $appends = [
        self::RECIPIENT_DETAILS,
    ];

    protected $casts = [
        self::AMOUNT                 => 'int',
        self::AMOUNT_REVERSED        => 'int',
        self::FEES                   => 'int',
        self::TAX                    => 'int',
        self::ON_HOLD                => 'bool',
        self::ON_HOLD_UNTIL          => 'int',
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
        self::RECIPIENT_SETTLEMENT_ID => null
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::ON_HOLD_UNTIL,
    ];

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

    // -------------------- End Setters ---------------------------

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
            Merchant::NAME,
            Merchant::EMAIL,
        ];

        $details = $account->setVisible($accountAttributes)->toArray();

        return $details;
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
            $entity = 'RZP\Models\Merchant\AccountEntity';
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
            $entity = 'RZP\Models\Merchant\AccountEntity';
        }

        $attributes[self::SOURCE] = $entity::getSignedId($sourceId);
    }

    public function setPublicRecipientSettlementIdAttribute(array & $attributes)
    {
        $setld = $this->getAttribute(self::RECIPIENT_SETTLEMENT_ID);

        $attributes[self::RECIPIENT_SETTLEMENT_ID] = Settlement\Entity::getSignedIdOrNull($setld);
    }

    public function toArrayReport()
    {
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

        $data[self::ON_HOLD] = $this->getOnHold() ? "true" : "false";

        return $data;
    }
}
