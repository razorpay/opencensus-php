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
use RZP\Models\FundAccount;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\FundTransfer\Attempt\Purpose;

/**
 * @property Customer\Entity    $customer
 * @property Merchant\Entity    $merchant
 * @property User\Entity        $user
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
    const SETTLED_ON             = 'settled_on';
    const TYPE                   = 'type';
    const MODE                   = 'mode';

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

    // Input keys
    const ACCOUNT_NUMBER = 'account_number';

    // Relations
    const USER     = 'user';
    const CUSTOMER = 'customer';

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
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
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
        self::SETTLED_ON,
        self::TYPE,
        self::MODE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::FUND_ACCOUNT_ID,
        self::DESTINATION,
        self::METHOD,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::FEES,
        self::TAX,
        self::STATUS,
        self::UTR,
        self::USER_ID,
        self::USER,
        self::SETTLED_ON,
        self::MODE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::BALANCE_ID,
        self::DESTINATION,
        self::CUSTOMER_ID,
        self::USER_ID,
        self::FUND_ACCOUNT_ID,
    ];

    protected $defaults = [
        self::USER_ID           => null,
        self::STATUS            => Status::CREATED,
        self::PURPOSE           => Purpose::REFUND,
        self::FUND_ACCOUNT_ID   => null,
        self::NOTES             => [],
        self::ATTEMPTS          => 1,
        self::TYPE              => self::DEFAULT
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
        self::SETTLED_ON,
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

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getFundAccountId()
    {
        return $this->getAttribute(self::FUND_ACCOUNT_ID);
    }

    public function hasFundAccount()
    {
        return ($this->isAttributeNotNull(self::FUND_ACCOUNT_ID) === true);
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

    public function isStatusCreated()
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
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

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
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
        $this->setAttribute(self::STATUS, $status);
    }

    public function setUtr(string $utr = null)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    public function setRemarks(string $remarks = null)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setProcessedAt($date)
    {
        $this->setAttribute(self::PROCESSED_AT, $date);
    }

    public function setSettledOn($date)
    {
        $this->setAttribute(self::SETTLED_ON, $date);
    }

    public function setType($onDemand)
    {
        $this->setAttribute(self::TYPE, $onDemand);
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

    public function getPricingFeatures()
    {
        return [];
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function shouldNotifyTxnViaSms(): bool
    {
        return false;
    }

    public function shouldNotifyTxnViaEmail(): bool
    {
        return true;
    }
}
