<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Batch;
use RZP\Base\BuilderEx;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Workflow;
use RZP\Models\Admin\Org;
use RZP\Models\PayoutLink;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\BankingAccount;
use RZP\Base\RepositoryManager;
use RZP\Models\Admin\Permission;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Feature\Constants as Features;

/**
 * @property Customer\Entity        $customer
 * @property Merchant\Entity        $merchant
 * @property User\Entity            $user
 * @property FundAccount\Entity     $fundAccount
 * @property Transaction\Entity     $transaction
 * @property BankingAccount\Entity  $bankingAccount
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
    const PENDING_AT             = 'pending_at';
    const PROCESSED_AT           = 'processed_at';
    const REVERSED_AT            = 'reversed_at';
    const FAILED_AT              = 'failed_at';
    const REJECTED_AT            = 'rejected_at';
    const QUEUED_AT              = 'queued_at';
    const CANCELLED_AT           = 'cancelled_at';
    const SETTLED_ON             = 'settled_on';
    const TYPE                   = 'type';
    const MODE                   = 'mode';
    const REFERENCE_ID           = 'reference_id';
    const NARRATION              = 'narration';
    const FTS_TRANSFER_ID        = 'fts_transfer_id';
    const BATCH_ID               = 'batch_id';
    const IDEMPOTENCY_KEY        = 'idempotency_key';
    const INITIATED_AT           = 'initiated_at';
    const PAYOUT_LINK_ID         = 'payout_link_id';

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
    const REVERSED_FROM = 'reversed_from';
    const REVERSED_TO   = 'reversed_to';
    const PRODUCT       = 'product';

    const PENDING_ON_ME    = 'pending_on_me';
    const PENDING_ON_ROLES = 'pending_on_roles';
    const PENDING_ON_USER  = 'pending_on_user';

    // Input keys
    const ACCOUNT_NUMBER       = 'account_number';
    const QUEUE_IF_LOW_BALANCE = 'queue_if_low_balance';
    const PAYOUT_IDS           = 'payout_ids';

    // Output keys
    const WORKFLOW_HISTORY   = 'workflow_history';
    const BANKING_ACCOUNT_ID = 'banking_account_id';

    // Used only for `visible` array
    const INTERNAL_STATUS = 'internal_status';

    const PAYOUT_MODE     = 'payout_mode';

    // Relations
    const USER            = 'user';
    const CUSTOMER        = 'customer';
    const FUND_ACCOUNT    = 'fund_account';
    const TRANSACTION     = 'transaction';
    const REVERSAL        = 'reversal';
    const WORKFLOW_ACTION = 'workflow_action';

    const MAX_PAYOUT_LIMIT = '10000000000';

    protected $queueFlag = false;

    /**
     * In case of direct banking, we get the transactions directly from the bank. We don't create transactions
     * from our system. Sometimes, we are not able to map a transaction to one of the payouts in our system.
     * In these cases, we create the transaction against `external` entity. Later when we are able to map
     * the transaction to the payout entity, we create a dummy transaction to replace the original transaction's
     * attributes with the right payout transaction attributes. In this flow, we don't want to do any balance
     * related stuff since that would have already been taken care of when the original transaction was created.
     * This also ensures balance validations are not done, since they could fail because of double deductions - one
     * via external and now another via payout.
     *
     * @var bool
     */
    protected $shouldValidateAndUpdateBalancesFlag = true;

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
        self::NOTES,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
        self::REFERENCE_ID,
        self::NARRATION,
        self::IDEMPOTENCY_KEY,
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
        self::RETURN_UTR,
        self::FAILURE_REASON,
        self::REMARKS,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
        self::WORKFLOW_HISTORY,
        self::REFERENCE_ID,
        self::NARRATION,
        self::BATCH_ID,
        self::INTERNAL_STATUS,
        self::BANKING_ACCOUNT_ID,
        self::INITIATED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::IDEMPOTENCY_KEY,
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
        self::PENDING_ON_USER,
        self::WORKFLOW_HISTORY,
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
        self::BATCH_ID,
        self::REVERSAL,
        self::CANCELLED_AT,
        self::QUEUED_AT,
        self::BANKING_ACCOUNT_ID,
        self::INITIATED_AT,
        self::PENDING_AT,
        self::PROCESSED_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::FAILURE_REASON,
        self::CREATED_AT,
    ];

    protected $webhook = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::FUND_ACCOUNT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::FEES,
        self::TAX,
        self::STATUS,
        self::PURPOSE,
        self::UTR,
        self::MODE,
        self::REFERENCE_ID,
        self::NARRATION,
        self::BATCH_ID,
        self::FAILURE_REASON,
        self::CREATED_AT,
    ];

    protected static $modifiers = [
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
        self::PENDING_ON_USER,
        self::WORKFLOW_HISTORY,
        self::BANKING_ACCOUNT_ID,
        self::REVERSAL,
        // We want to show the failure reason only if the status is reversed.
        // This is because we might have intermittent failure reasons even
        // when the payout is not completely processed (succeeded/failed)
        self::FAILURE_REASON,
        self::INITIATED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::PROCESSED_AT,
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::TRANSACTION_ID,
        self::BATCH_ID,
        self::TRANSACTION,
    ];

    protected $defaults = [
        self::USER_ID           => null,
        self::PURPOSE           => Purpose::REFUND,
        self::FUND_ACCOUNT_ID   => null,
        self::BATCH_ID          => null,
        self::NOTES             => [],
        self::ATTEMPTS          => 1,
        self::TYPE              => self::DEFAULT,
        self::MODE              => null,
        self::UTR               => null,
        self::RETURN_UTR        => null,
        self::FAILURE_REASON    => null,
        self::REFERENCE_ID      => null,
        self::NARRATION         => null,
        self::FEES              => 0,
        self::TAX               => 0,
        self::IDEMPOTENCY_KEY   => null,
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
        self::PENDING_AT,
        self::REVERSED_AT,
        self::FAILED_AT,
        self::REJECTED_AT,
        self::QUEUED_AT,
        self::CANCELLED_AT,
        self::INITIATED_AT,
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

    public function payoutLink()
    {
        return $this->belongsTo(PayoutLink\Entity::class);
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

    public function bankingAccount()
    {
        //
        // This defines payout's relation to banking_account
        // via balance's relation to banking_account.
        //
        return $this->balance->bankingAccount();
    }

    public function workflowActions()
    {
        return $this->morphMany(Workflow\Action\Entity::class, 'entity', 'entity_name');
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

    /**
     * The batch which created this payout entity.
     *
     * @return null|\Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch()
    {
        return $this->belongsTo(Batch\Entity::class);
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

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
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

    public function getBatchId()
    {
        return $this->getAttribute(self::BATCH_ID);
    }

    public function hasBatch(): bool
    {
        return $this->isAttributeNotNull(self::BATCH_ID);
    }

    public function isOfMerchantTransaction(): bool
    {
        return ($this->getAttribute(self::TRANSACTION_TYPE) === Constants\Entity::TRANSACTION);
    }

    public function isCustomerPayout(): bool
    {
        return ($this->getAttribute(self::TRANSACTION_TYPE) === Constants\Entity::CUSTOMER_TRANSACTION);
    }

    public function toBeQueued(): bool
    {
        return ($this->queueFlag === true);
    }

    public function shouldValidateAndUpdateBalances(): bool
    {
        return ($this->shouldValidateAndUpdateBalancesFlag === true);
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

    public function getReturnUtr()
    {
        return $this->getAttribute(self::RETURN_UTR);
    }

    public function getInitiatedAt()
    {
        return $this->getAttribute(self::INITIATED_AT);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
    }

    public function getPendingAt()
    {
        return $this->getAttribute(self::PENDING_AT);
    }

    public function getReversedAt()
    {
        return $this->getAttribute(self::REVERSED_AT);
    }

    public function getFailedAt()
    {
        return $this->getAttribute(self::FAILED_AT);
    }

    public function getRejectedAt()
    {
        return $this->getAttribute(self::REJECTED_AT);
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

    public function hasBeenProcessed()
    {
        return ($this->isAttributeNotNull(self::PROCESSED_AT) === true);
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

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement either `isStatusFailed` or `isStatusReversedOrFailed`
     * function, to send out summary emails and stuff in bulkRecon.
     *
     * @return bool
     */
    public function isStatusReversedOrFailed()
    {
        return ($this->isStatusReversed() or $this->isStatusFailed());
    }

    public function isStatusQueued()
    {
        return ($this->getStatus() === Status::QUEUED);
    }

    public function isStatusCancelled()
    {
        return ($this->getStatus() === Status::CANCELLED);
    }

    public function isStatusBeforeCreate()
    {
        return (in_array($this->getStatus(), Status::$preCreateStatuses, true) === true);
    }

    /**
     * This is required for the FTA module.
     * FTA requires the sources to implement either `isStatusFailed` or `isStatusReversedOrFailed`
     * function, to send out summary emails and stuff in bulkRecon.
     *
     * @return bool
     */
    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function isStatusProcessedOrReversed(): bool
    {
        return ($this->isStatusProcessed() or $this->isStatusReversed());
    }

    public function isStatusInitiated()
    {
        return ($this->getStatus() === Status::INITIATED);
    }

    public function isStatusPending()
    {
        return ($this->getStatus() === Status::PENDING);
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

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID) === true);
    }

    public function getIdempotencyKey()
    {
        return $this->getAttribute(self::IDEMPOTENCY_KEY);
    }

    public function setQueueFlag($flag)
    {
        $this->queueFlag = $flag;
    }

    public function setShouldValidateAndUpdateBalancesFlag($flag)
    {
        $this->shouldValidateAndUpdateBalancesFlag = $flag;
    }

    public function setChannel($channel)
    {
        Channel::validate($channel);

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

        // pushing a message in the queue to update the source for payout
        $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;

        SourceUpdater::dispatchToQueue($mode, $this, $currentStatus);
    }

    protected function setStatusAttribute($status)
    {
        $previousStatus = $this->getStatus();

        $this->attributes[self::STATUS] = $status;

        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';

            //
            // In case of queued, the payout moves from queued -> created.
            // created_at is set when payout entity is created.
            // But we want to know when payout moves to `created` state.
            // We keep a track of this using `initiated_at`.
            //
            if ($status === Status::CREATED)
            {
                $timestampKey = self::INITIATED_AT;
            }

            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }

        Metric::pushStatusChangeMetrics($this, $previousStatus);
    }

    public function setInitiatedAt()
    {
        $currentTime = Carbon::now()->getTimestamp();

        $this->setAttribute(self::INITIATED_AT, $currentTime);
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

    // TODO: check how to handle this
    // JIRA: https://razorpay.atlassian.net/browse/RX-696
    public function setReturnUtr(string $returnUtr = null)
    {
        $this->setAttribute(self::RETURN_UTR, $returnUtr);
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

    public function setPendingAt($date)
    {
        $this->setAttribute(self::PENDING_AT, $date);
    }

    public function setReversedAt($date)
    {
        $this->setAttribute(self::REVERSED_AT, $date);
    }

    public function setFailedAt($date)
    {
        $this->setAttribute(self::FAILED_AT, $date);
    }

    public function setRejectedAt(int $date = null)
    {
        $this->setAttribute(self::REJECTED_AT, $date);
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

    public function setPublicPendingOnUserAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::PENDING_ON_USER]);

            return;
        }

        if (($basicAuth->getUser() === null) or
            ($this->merchant === null) or
            ($this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS) === false))
        {
            return;
        }

        /** @var RepositoryManager $repo */
        $repo = app('repo');

        $user = $basicAuth->getUser();

        $userRoleIds = $user->roles()->allRelatedIds()->toArray();

        $permissionId = $repo->permission
                             ->retrieveIdsByNamesAndOrg(Permission\Name::CREATE_PAYOUT, Org\Entity::RAZORPAY_ORG_ID)
                             ->first();

        $pendingActions = $repo->workflow_action
                               ->getPendingActionsOnRoleIds($user->getId(), $this, $permissionId, $userRoleIds);

        $attributes[self::PENDING_ON_USER] = ($pendingActions->count() > 0);
    }

    public function setPublicWorkflowHistoryAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::WORKFLOW_HISTORY]);

            return;
        }

        $attributes[self::WORKFLOW_HISTORY] = $this->getWorkflowHistoryData();
    }

    public function setPublicBankingAccountIdAttribute(array & $attributes)
    {
        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset ($attributes[self::BANKING_ACCOUNT_ID]);

            return;
        }

        $attributes[self::BANKING_ACCOUNT_ID] = optional($this->bankingAccount)->getPublicId();
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

    public function setPublicBatchIdAttribute(array & $attributes)
    {
        $batchId = $this->getAttribute(self::BATCH_ID);

        $attributes[self::BATCH_ID] = Batch\Entity::getSignedIdOrNull($batchId);
    }

    public function setBatchId(string $batchId)
    {
        $this->setAttribute(self::BATCH_ID,$batchId);
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
        if ($this->isStatusReversedOrFailed() === false)
        {
            $attributes[self::FAILURE_REASON] = null;
        }
    }

    public function setPublicTransactionIdAttribute(array & $attributes)
    {
        if (app('basicauth')->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::TRANSACTION_ID]);

            return;
        }

        $attributes[self::TRANSACTION_ID] = Transaction\Entity::getSignedIdOrNull($this->getTransactionId());
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

    public function setPublicInitiatedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::INITIATED_AT]);
        }
    }

    public function setPublicQueuedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::QUEUED_AT]);
        }
    }

    public function setPublicCancelledAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::CANCELLED_AT]);
        }
    }

    public function setPublicProcessedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::PROCESSED_AT]);
        }
    }

    public function setPublicPendingAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //
        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::PENDING_AT]);
        }
    }

    public function setPublicReversedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::REVERSED_AT]);
        }
    }

    public function setPublicFailedAtAttribute(array & $attributes)
    {
        //
        // We are currently exposing this timestamp only for dashboard.
        // Going forward, we will have a proper auditing stuff for
        // payouts, which will be exposed via API as well.
        //

        // TODO: Move to serializer

        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::FAILED_AT]);
        }
    }

    public function setPublicRejectedAtAttribute(array & $attributes)
    {
        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($attributes[self::REJECTED_AT]);
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

    public function getSourceFtsFundAccountId()
    {
        $bankingAccount = $this->balance->bankingAccount;

        return optional($bankingAccount)->getFtsFundAccountId();
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

    public function scopeStatus(BuilderEx $query, string $status)
    {
        $query->where(Entity::STATUS, $status);
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

    // Workflow history helper functions

    protected function getWorkflowHistoryData(): array
    {
        /** @var RepositoryManager $repo */
        $repo = app('repo');

        // TODO: Only get actions for the create_payout permission
        $workflowActions = $this->workflowActions()
                                ->with(['workflow', 'workflow.steps', 'workflow.steps.role'])
                                ->get();

        if ($workflowActions->count() > 1)
        {
            // Should not exist for the create_payout permission.
            // Trace for debug and fail
        }

        $workflowAction = $workflowActions->first();

        if ($workflowAction === null)
        {
            return [];
        }

        $workflowAction = $repo->workflow_action->getActionDetailsPublic($workflowAction->getId(), Org\Entity::RAZORPAY_ORG_ID);

        $workflowAction = $workflowAction->first()->toArray();

        $steps = $workflowAction['workflow']['steps'] ?? [];

        $data = [
            'current_level' => $workflowAction['current_level'],
            'steps'         => self::serializeWorkflowSteps($steps),
        ];

        return $data;
    }

    public static function serializeWorkflowSteps(array $steps): array
    {
        $data = [];

        foreach ($steps as $step)
        {
            $level = $step['level'];

            $roleData = self::serializeWorkflowStepRoles($step);

            $step = array_only($step, ['id', 'level', 'op_type']);

            if (empty($data[$level - 1]) === true)
            {
                $data[$level - 1] = $step;
            }

            $totalReviewersForStep = $data[$level - 1]['total_reviewer_count'] ?? 0;

            $data[$level - 1]['total_reviewer_count'] = $totalReviewersForStep + $roleData['reviewer_count'];
            $data[$level - 1]['roles'][] = $roleData;
        }

        return $data;
    }

    protected static function serializeWorkflowStepRoles(array $step): array
    {
        $stepRole = $step['role'];

        $role = [
            'id'             => $stepRole['id'],
            'name'           => $stepRole['name'],
            'reviewer_count' => $step['reviewer_count'],
        ];

        $checkersData = [];

        $checkers = $step['checkers'] ?? [];

        foreach ($checkers as $checker)
        {
            $userData = $checker['checker'] ?? [];

            if (empty($userData) === true)
            {
                continue;
            }

            $checkersData[] = [
                'id'       => $checker['id'],
                'user_id'  => $userData['id'],
                'name'     => $userData['name'] ?? '',
                'email'    => $userData['email'] ?? '',
                'approved' => $checker['approved'],
            ];
        }

        $role['checkers'] = $checkersData;

        return $role;
    }
}
