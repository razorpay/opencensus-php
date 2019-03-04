<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\FundTransfer\Yesbank\NodalAccount;

/**
 * @property Customer\Entity    $customer
 * @property Merchant\Entity    $merchant
 * @property User\Entity        $user
 * @property FundAccount\Entity $fundAccount
 * @property Transaction\Entity $transaction
 */
class Entity extends Base\PublicEntity
{
    use HasBalance;
    use NotesTrait;

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const CUSTOMER_ID            = 'customer_id';
    const FUND_ACCOUNT_ID        = 'fund_account_id';
    const METHOD                 = 'method';
    const BALANCE_ID             = 'balance_id';
    const DESTINATION_ID         = 'destination_id';
    const DESTINATION_TYPE       = 'destination_type';
    const USER_ID                = 'user_id';
    const PURPOSE                = 'purpose';
    const PURPOSE_TYPE           = 'purpose_type';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const NOTES                  = 'notes';
    const FEES                   = 'fees';
    const TAX                    = 'tax';
    const PAYMENT_ID             = 'payment_id';
    const TRANSACTION_ID         = 'transaction_id';
    const TRANSACTION_TYPE       = 'transaction_type';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const STATUS                 = 'status';
    const CHANNEL                = 'channel';
    const ATTEMPTS               = 'attempts';
    const UTR                    = 'utr';
    const FAILURE_REASON         = 'failure_reason';
    const RETURN_UTR             = 'return_utr';
    const REMARKS                = 'remarks';
    const PROCESSED_AT           = 'processed_at';
    const REVERSED_AT            = 'reversed_at';
    const QUEUED_AT              = 'queued_at';
    const CANCELLED_AT           = 'cancelled_at';
    const SETTLED_ON             = 'settled_on';
    const TYPE                   = 'type';
    const MODE                   = 'mode';
    const REFERENCE_ID           = 'reference_id';
    const NARRATION              = 'narration';
    const FTS_TRANSFER_ID        = 'fts_transfer_id';

    // Public attribute
    const DESTINATION            = 'destination';


    // These are used while creating merchant payouts.
    // Min amount refers to the minimum amount payout has to be
    // Modulo refers to the multiples in which amount should be
    // Buffer Amount specifies the remaining merchant balance (buffer balance) after the payout
    const MIN_AMOUNT             = 'min_amount';
    const MODULO                 = 'modulo';
    const BUFFER_AMOUNT          = 'buffer_amount';

    // Constants for payout types
    const DEFAULT   = 'default';
    const ON_DEMAND = 'on_demand';

    // Additional input/output attributes
    const CONTACT_NAME  = 'contact_name';
    const CONTACT_PHONE = 'contact_phone';
    const CONTACT_ID    = 'contact_id';
    const CONTACT_EMAIL = 'contact_email';
    const CONTACT_TYPE  = 'contact_type';

    // Input keys
    const ACCOUNT_NUMBER    = 'account_number';
    const QUEUED            = 'queued';

    // Used only for `visible` array
    const INTERNAL_STATUS = 'internal_status';

    // Relations
    const USER          = 'user';
    const CUSTOMER      = 'customer';
    const FUND_ACCOUNT  = 'fund_account';
    const TRANSACTION   = 'transaction';
    const REVERSAL      = 'reversal';

    protected $queueFlag = false;

    protected $entity = 'payout';

    protected $table  = Table::PAYOUT;

    protected $generateIdOnCreate = true;

    protected static $sign = 'pout';

    protected static $generators = [
        self::ID
    ];

    protected $fillable = [
        self::ID,
        self::METHOD,
        self::PURPOSE,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::NOTES,
        self::PROCESSED_AT,
        self::REVERSED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
        self::REFERENCE_ID,
        self::NARRATION,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::FUND_ACCOUNT_ID,
        self::DESTINATION,
        self::USER_ID,
        self::AMOUNT,
        self::BALANCE_ID,
        self::CURRENCY,
        self::NOTES,
        self::REVERSAL,
        self::PURPOSE,
        self::PURPOSE_TYPE,
        self::METHOD,
        self::FEES,
        self::TAX,
        self::PAYMENT_ID,
        self::TRANSACTION_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::STATUS,
        self::CHANNEL,
        self::ATTEMPTS,
        self::UTR,
        self::FAILURE_REASON,
        self::REMARKS,
        self::PROCESSED_AT,
        self::REVERSED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
        self::REFERENCE_ID,
        self::NARRATION,
        self::INTERNAL_STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::FUND_ACCOUNT_ID,
        self::FUND_ACCOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
        self::TRANSACTION,
        self::NOTES,
        self::FEES,
        self::TAX,
        self::STATUS,
        self::PURPOSE,
        self::UTR,
        self::USER_ID,
        self::USER,
        self::MODE,
        self::REFERENCE_ID,
        self::NARRATION,
        self::REVERSAL,
        self::FAILURE_REASON,
        self::CREATED_AT,
    ];

    protected static $modifiers = [
        self::MODE,
        self::NARRATION,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::STATUS,
        self::BALANCE_ID,
        self::DESTINATION,
        self::CUSTOMER_ID,
        self::USER_ID,
        self::FUND_ACCOUNT_ID,
        self::FUND_ACCOUNT,
        self::REVERSAL,
        // We want to show the failure reason only if the status is reversed.
        // This is because we might have intermittent failure reasons even
        // when the payout is not completely processed (succeeded/failed)
        self::FAILURE_REASON,
        // Sometimes, we get the UTR even if the payout has not been processed.
        // This might cause confusions and hence we show UTR only when either
        // the payout is in processed or reversed state.
        self::UTR,
        self::TRANSACTION_ID,
        self::TRANSACTION,
    ];

    protected $defaults = [
        self::USER_ID           => null,
        self::STATUS            => Status::CREATED,
        self::PURPOSE           => Purpose::REFUND,
        self::FUND_ACCOUNT_ID   => null,
        self::NOTES             => [],
        self::ATTEMPTS          => 1,
        self::TYPE              => self::DEFAULT,
        self::MODE              => null,
        self::UTR               => null,
        self::FAILURE_REASON    => null,
        self::REFERENCE_ID      => null,
        self::NARRATION         => null,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEES,
        self::TAX,
    ];

    protected $casts = [
        self::AMOUNT      => 'int',
        self::FEES        => 'int',
        self::TAX         => 'int',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PROCESSED_AT,
        self::REVERSED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::SETTLED_ON,
    ];

    protected $appends = [
        self::INTERNAL_STATUS,
    ];

    protected $ignoredRelations = [
        'destination',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function destination()
    {
        return $this->morphTo();
    }

    public function fundTransferAttempts()
    {
        return $this->morphMany('RZP\Models\FundTransfer\Attempt\Entity', 'source');
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function fundAccount()
    {
        return $this->belongsTo(FundAccount\Entity::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }

    public function reversal()
    {
        return $this->belongsTo(Reversal\Entity::class, self::ID, Reversal\Entity::ENTITY_ID);
    }

    /**
     * Can be customer_transaction (used for customer wallets) or just transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function transaction()
    {
        return $this->morphTo();
    }

    public function batchFundTransfer()
    {
        return $this->belongsTo(FundTransfer\Batch\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function getPurpose()
    {
        return $this->getAttribute(self::PURPOSE);
    }

    public function getPurposeType()
    {
        return $this->getAttribute(self::PURPOSE_TYPE);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getReferenceId()
    {
        return $this->getAttribute(self::REFERENCE_ID);
    }

    public function getNarration()
    {
        return $this->getAttribute(self::NARRATION);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function hasCustomer()
    {
        return ($this->isAttributeNotNull(self::CUSTOMER_ID) === true);
    }

    public function getFundAccountId()
    {
        return $this->getAttribute(self::FUND_ACCOUNT_ID);
    }

    public function hasFundAccount()
    {
        return ($this->isAttributeNotNull(self::FUND_ACCOUNT_ID) === true);
    }

    public function isOfMerchantTransaction(): bool
    {
        return $this->getAttribute(self::TRANSACTION_TYPE) === Constants\Entity::TRANSACTION;
    }

    public function toBeQueued(): bool
    {
        return ($this->queueFlag === true);
    }

    /**
     * FeeCalculator calls `$entity->getFee()` for all the pricing entity
     *
     * @return mixed
     */
    public function getFee()
    {
        return $this->getFees();
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getBatchFundTransferId()
    {
        return $this->getAttribute(self::BATCH_FUND_TRANSFER_ID);
    }

    public function getRemarks()
    {
        return $this->getAttribute(self::REMARKS);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function getReversedAt()
    {
        return $this->getAttribute(self::REVERSED_AT);
    }

    public function getQueuedAt()
    {
        return $this->getAttribute(self::QUEUED_AT);
    }

    public function getCancelledAt()
    {
        return $this->getAttribute(self::CANCELLED_AT);
    }

    public function hasBeenQueued()
    {
        return ($this->isAttributeNotNull(self::QUEUED_AT) === true);
    }

    public function isStatusCreated(): bool
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isStatusProcessed(): bool
    {
        return ($this->getStatus() === Status::PROCESSED);
    }

    public function isStatusReversed()
    {
        return ($this->getStatus() === Status::REVERSED);
    }

    public function isStatusQueued()
    {
        return ($this->getStatus() === Status::QUEUED);
    }

    public function isStatusCancelled()
    {
        return ($this->getStatus() === Status::CANCELLED);
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement `isStatusFailed`
     * function, to send out summary emails and stuff in bulkRecon.
     *
     * @return bool
     */
    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::REVERSED);
    }

    public function isStatusProcessedOrReversed(): bool
    {
        return ($this->isStatusProcessed() or $this->isStatusReversed());
    }

    public function isStatusInitiated()
    {
        return ($this->getStatus() === Status::INITIATED);
    }

    public function isPendingReconciliation()
    {
        return $this->isStatusInitiated();
    }

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getDestinationId()
    {
        return $this->getAttribute(self::DESTINATION_ID);
    }

    public function getDestinationType()
    {
        return $this->getAttribute(self::DESTINATION_TYPE);
    }

    public function getPayoutType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function hasPayment()
    {
        return ($this->isAttributeNotNull(self::PAYMENT_ID) === true);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getFTSTransferId()
    {
        return $this->getAttribute(self::FTS_TRANSFER_ID);
    }

    public function setQueueFlag($flag)
    {
        $this->queueFlag = $flag;
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setTax($tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function setMethod($method)
    {
        $this->setAttribute(self::METHOD, $method);
    }

    public function setMode($mode)
    {
        if ($mode !== null)
        {
            FundTransfer\Mode::validateMode($mode);
        }

        $this->setAttribute(self::MODE, $mode);
    }

    public function setStatus($status)
    {
        Status::validate($status);

        $currentStatus = $this->getStatus();

        //
        // In code, we could call it multiple times for the same status update.
        // We do not want to update the status timestamp with the new value
        // and hence return it back from here itself if we are updating with same status.
        //
        if ($currentStatus === $status)
        {
            return;
        }

        $this->setAttribute(self::STATUS, $status);

        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';

            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement `setUtr`
     * function, to set the utr.
     *
     * @param string|null $utr
     */
    public function setUtr(string $utr = null)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement `setRemarks`
     * function, to set the bank remarks.
     *
     * @param string|null $remarks
     */
    public function setRemarks(string $remarks = null)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setProcessedAt($date)
    {
        $this->setAttribute(self::PROCESSED_AT, $date);
    }

    public function setReversedAt($date)
    {
        $this->setAttribute(self::REVERSED_AT, $date);
    }

    public function setQueuedAt($date)
    {
        $this->setAttribute(self::QUEUED_AT, $date);
    }

    public function setCancelledAt($date)
    {
        $this->setAttribute(self::CANCELLED_AT, $date);
    }

    public function setPurpose(string $purpose)
    {
        $this->setAttribute(self::PURPOSE, $purpose);
    }

    public function setPurposeType(string $purposeType)
    {
        $this->setAttribute(self::PURPOSE_TYPE, $purposeType);
    }

    public function setSettledOn($date)
    {
        $this->setAttribute(self::SETTLED_ON, $date);
    }

    public function setType($onDemand)
    {
        $this->setAttribute(self::TYPE, $onDemand);
    }

    public function setFTSTransferId($ftsTransferId)
    {
        $this->setAttribute(self::FTS_TRANSFER_ID, $ftsTransferId);
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    protected function getSettledOnAttribute()
    {
        $timestamp = $this->attributes[self::SETTLED_ON];

        if ($timestamp !== null)
        {
            return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
        }

        return null;
    }

    public function getInternalStatusAttribute()
    {
        return $this->getStatus();
    }

    public function setPublicDestinationAttribute(array & $attributes)
    {
        $type = $this->getDestinationType();

        // Type (destination) will be null in case of Business Banking payouts.
        // We will be deprecating this soon, in favor of fund accounts.
        if ($type === null)
        {
            return;
        }

        $entity = Constants\Entity::getEntityClass($type);

        $id = $this->getDestinationId();

        $attributes[self::DESTINATION] = $entity::getSignedId($id);
    }

    public function setPublicCustomerIdAttribute(array & $attributes)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        //
        // customer_id is used only in the openwallet payout flow. We do not want
        // to expose this field in general
        //
        if ($customerId === null)
        {
            unset($attributes[self::CUSTOMER_ID]);

            return;
        }

        $attributes[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    public function setPublicUserIdAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::USER_ID]);
        }
    }

    public function setPublicFundAccountIdAttribute(array & $attributes)
    {
        $fundAccountId = $this->getAttribute(self::FUND_ACCOUNT_ID);

        $attributes[self::FUND_ACCOUNT_ID] = FundAccount\Entity::getSignedIdOrNull($fundAccountId);
    }

    public function setPublicFundAccountAttribute(array & $attributes)
    {
        //
        // We never want to expose fund_account on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the fund_account is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            array_forget($attributes, self::FUND_ACCOUNT);

            return;
        }
    }

    public function setPublicReversalAttribute(array & $attributes)
    {
        //
        // We never want to expose reversal on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the reversal is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            array_forget($attributes, self::REVERSAL);

            return;
        }
    }

    public function setPublicStatusAttribute(array & $attributes)
    {
        $internalStatus = $this->getAttribute(self::STATUS);

        $externalStatus = Status::getPublicStatusFromInternalStatus($internalStatus);

        $attributes[self::STATUS] = $externalStatus;
    }

    public function setPublicFailureReasonAttribute(array & $attributes)
    {
        if ($this->isStatusReversed() === false)
        {
            $attributes[self::FAILURE_REASON] = null;
        }
    }

    public function setPublicUtrAttribute(array & $attributes)
    {
        if ($this->isStatusProcessedOrReversed() === false)
        {
            $attributes[self::UTR] = null;
        }
    }

    public function setPublicTransactionIdAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::TRANSACTION_ID]);

            return;
        }

        $attributes[self::TRANSACTION_ID] = Transaction\Entity::getSignedId($attributes[self::TRANSACTION_ID]);
    }

    public function setPublicTransactionAttribute(array & $attributes)
    {
        //
        // We never want to expose transactions on private.
        // The correct way to do this would be to not add it in $public array.
        // But, we want to expose it in proxy auth (via expands). Hence, we
        // cannot remove it from $public array.
        // It's possible that the transactions is loaded in some flow. This check
        // ensures that it's always removed before sending out the response.
        //
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            array_forget($attributes, self::TRANSACTION);

            return;
        }

        $transaction = array_pull($attributes, self::TRANSACTION);

        //
        // We don't want to expose customer_transactions as of now.
        //
        if ((empty($transaction) === false) and
            (($this->transaction instanceof Transaction\Entity)))
        {
            $attributes[self::TRANSACTION] = $this->transaction->toStatement()->toArrayPublic();
        }
    }

    public function getPricingFeatures()
    {
        return [];
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    protected function modifyMode(& $input)
    {
        $fundAccount = $this->fundAccount;

        //
        // In case of merchant payouts, we don't use fund account entity.
        // We use destination directly. We have to move them to FA soon.
        //
        if (empty($fundAccount) === true)
        {
            return;
        }

        $accountType = $fundAccount->getAccountType();

        if ($accountType === FundAccount\Type::VPA)
        {
            $input[self::MODE] = Mode::UPI;
        }
        else if ($accountType === FundAccount\Type::BANK_ACCOUNT)
        {
            /** @var BankAccount\Entity $ba */
            $ba = $fundAccount->account;

            $ifsc = $ba->getIfscCode();

            $ifscFirstFour = substr($ifsc, 0, 4);

            if (starts_with($ifscFirstFour, NodalAccount::IFSC_IDENTIFIER) === true)
            {
                $input[self::MODE] = Mode::IFT;
            }
        }
    }

    protected function modifyNarration(& $input)
    {
        $narration = $input[self::NARRATION] ?? null;

        if (empty($narration) === false)
        {
            return;
        }

        $merchant = $this->merchant;

        $merchantBillingLabel = $merchant->getBillingLabel();

        // Remove all characters other than a-z, A-Z, 0-9 and space
        $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel);

        // If formattedLabel is non-empty, pick the first 30 chars, else fallback to 'Razorpay'
        $formattedLabel = ($formattedLabel ? $formattedLabel : 'Razorpay');

        $narration = $formattedLabel . ' Fund Transfer';

        $narration = str_limit($narration, 30, '');

        $input[self::NARRATION] = $narration;
    }

    public function shouldNotifyTxnViaSms(): bool
    {
        return false;
    }

    public function shouldNotifyTxnViaEmail(): bool
    {
        return (($this->isBalanceTypeBanking() === true) and
                // We only send transaction mail when we have UTR available, post reconciliation.
                ($this->isAttributeNotNull(Entity::UTR) === true));
    }

    /**
     * {@inheritDoc}
     */
    public function toArrayPublic()
    {
        $this->removeRecursiveRelation();

        return parent::toArrayPublic();
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $this->removeRecursiveRelation();

        return parent::toArray();
    }

    /**
     * This removes the recursive relations caused by using the same entity to associate.
     * Relations' mind is blown when this happens.
     * This happens in POST /payouts. In that, we create a transaction and associate the
     * payout created to the newly created transaction and then associate this newly created
     * transaction to the same payout. Since here the payout has transaction loaded and
     * transaction has the same payout loaded, recursion is spawned.
     */
    protected function removeRecursiveRelation()
    {
        if ($this->hasRelation(Entity::TRANSACTION) === true)
        {
            $txn = $this->transaction;
            $relations = array_except($txn->getRelations(), Transaction\Entity::SOURCE);
            $txn->setRelations($relations);
        }
    }
}
