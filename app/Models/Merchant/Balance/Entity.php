<?php

namespace RZP\Models\Merchant\Balance;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use Razorpay\Spine\DataTypes\Dictionary;

/**
 * Class Entity
 *
 * @property BankingAccount\Entity $bankingAccount
 */
class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const MERCHANT_ID    = 'merchant_id';
    const TYPE           = 'type';
    const CURRENCY       = 'currency';
    const NAME           = 'name';
    const BALANCE        = 'balance';
    const ON_HOLD        = 'on_hold';
    const AMOUNT_CREDITS = 'credits';
    const FEE_CREDITS    = 'fee_credits';
    const REFUND_CREDITS = 'refund_credits';

    //
    // This is bank_accounts.account_number for bank_account's virtual_account
    // related to this balance. At present this is the use case. It must be
    // empty for balance of type != banking for now.
    //
    const ACCOUNT_NUMBER = 'account_number';

    //
    // account_type can be shared (for Virtual Accounts) or direct (for Current Accounts)
    //
    const ACCOUNT_TYPE         = 'account_type';
    //
    // channel which provides the account, eg: rbl, yesbank
    // would be null for account_type=shared and for primary balance accounts
    //
    const CHANNEL              = 'channel';

    // Additional input keys
    const BALANCE_ID     = 'balance_id';

    // Used by RazorpayX Current Accounts to store when was the Banking Account Statement last fetched at
    const LAST_FETCHED_AT = 'last_fetched_at';

    protected $fillable = [
        self::ID,
        self::TYPE,
        self::CURRENCY,
        self::ACCOUNT_TYPE,
        self::CHANNEL,
        self::ACCOUNT_NUMBER,
    ];

    protected $defaults = [
        self::TYPE     => Type::PRIMARY,
        self::CURRENCY => null,
        self::BALANCE  => 0,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TYPE,
        self::CURRENCY,
        self::NAME,
        self::BALANCE,
        self::AMOUNT_CREDITS,
        self::FEE_CREDITS,
        self::REFUND_CREDITS,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::CHANNEL,
        self::UPDATED_AT,
        self::LAST_FETCHED_AT,
    ];

    protected $public = [
        self::ID,
        self::TYPE,
        self::CURRENCY,
        self::NAME,
        self::BALANCE,
        self::AMOUNT_CREDITS,
        self::FEE_CREDITS,
        self::REFUND_CREDITS,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::CHANNEL,
        self::UPDATED_AT,
        self::LAST_FETCHED_AT,
    ];

    protected $appends = [
        self::LAST_FETCHED_AT
    ];

    protected $entity = 'balance';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $casts = [
        self::AMOUNT_CREDITS => 'integer',
        self::FEE_CREDITS    => 'integer',
        self::REFUND_CREDITS => 'integer',
        self::BALANCE        => 'integer',
    ];

    protected $dates = [
        self::LAST_FETCHED_AT
    ];

    protected function addAmount($amount)
    {
        $this->checkNumeric($amount);

        $balance = $this->getBalance();

        $balance += $amount;

        $this->setAttribute(self::BALANCE, $balance);
    }

    protected function subAmount($amount)
    {
        $this->checkNumeric($amount);

        $this->attributes[self::BALANCE] -= (int) $amount;
    }

    protected function checkNumeric($arg)
    {
        if (is_int($arg) === false)
        {
            throw new Exception\InvalidArgumentException('
                Unsigned integer required. Supplied: ' . $arg);
        }
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function getAmountCredits()
    {
        return $this->getAttribute(self::AMOUNT_CREDITS);
    }

    public function getFeeCredits()
    {
        return $this->getAttribute(self::FEE_CREDITS);
    }

    public function getRefundCredits()
    {
        return $this->getAttribute(self::REFUND_CREDITS);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function isTypePrimary(): bool
    {
        return ($this->getType() === Type::PRIMARY);
    }

    public function isTypeBanking(): bool
    {
        return ($this->getType() === Type::BANKING);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function invoices()
    {
        return $this->hasMany(\RZP\Models\Merchant\Invoice\Entity::class);
    }

    public function bankingAccount()
    {
        return $this->hasOne(BankingAccount\Entity::class);
    }

    public static function buildFromMerchant($merchant)
    {
        // TODO need to build it via input
        $balance = new static;

        $balance->merchant()->associate($merchant);
        $balance->setAttribute(self::BALANCE, 0);
        $balance->setAttribute(self::CURRENCY, Currency::INR);
        $balance->setAttribute(self::TYPE, Type::PRIMARY);

        return $balance;
    }

    /**
     * Only this method should be public
     * for updating balance.
     * We need to check for balance going less than $negativeLimit
     * whenever we update balance
     *
     * @param \RZP\Models\Transaction\Entity $txn
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function updateBalance($txn, int $negativeLimit = 0)
    {
        $amount = $txn->getNetAmount();

        $oldBalance = $this->getBalance();

        $this->addAmount($amount);

        $newBalance = $this->getBalance();

        // if the balance after is update is greater than the previous balance,
        // even if it is still negative, we should update the balance.

        if ($newBalance > $oldBalance)
        {
            return;
        }

        $data = [
            'balance'     => $this->toArray(),
            'amount'      => $amount,
            'transaction' => $txn->getId(),
        ];

        if (($negativeLimit === 0) and
            ($this->getBalance() < 0))
        {
            throw new Exception\LogicException(
                'Something very wrong is happening! Balance is going negative',
                null,
                $data);
        }
        else if ($this->getBalance() < $negativeLimit)
        {
            $data['message'] = TraceCode::getMessage(TraceCode::NEGATIVE_BALANCE_BREACHED);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED, abs($amount),
                $data);
        }
    }

    public function subtractAmountCredits($amount)
    {
        $credits = $this->getAmountCredits();

        $credits -= $amount;

        if ($credits < 0)
        {
            $credits = 0;
        }

        $this->setAttribute(self::AMOUNT_CREDITS, $credits);
    }

    public function subtractFeeCredits($amount)
    {
        $credits = $this->getFeeCredits();

        $credits -= $amount;

        if ($credits < 0)
        {
            $credits = 0;
        }

        $this->setAttribute(self::FEE_CREDITS, $credits);
    }

    public function subtractRefundCredits($amount, int $negativeLimit = 0)
    {
        $credits = $this->getRefundCredits();

        $credits -= $amount;

        assertTrue($credits >= $negativeLimit);

        $this->setAttribute(self::REFUND_CREDITS, $credits);
    }

    public function setAmountCredits($credits)
    {
        assertTrue ($credits >= 0);

        $this->setAttribute(self::AMOUNT_CREDITS, $credits);
    }

    public function setFeeCredits(int $credits)
    {
        assertTrue ($credits >= 0);

        $this->setAttribute(self::FEE_CREDITS, $credits);
    }

    public function setRefundCredits(int $credits)
    {
        assertTrue ($credits >= 0);

        $this->setAttribute(self::REFUND_CREDITS, $credits);
    }

    public function setAccountNumber(string $accountNumber)
    {
        $this->setAttribute(self::ACCOUNT_NUMBER, $accountNumber);
    }

    public function setAccountType(string $type)
    {
        $this->setAttribute(self::ACCOUNT_TYPE, $type);
    }

    public function setChannel(string $channel = null)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function save(array $options = array())
    {
        return parent::save($options);
    }

    /**
     * Applies a WHERE clause on merchant_id and type. type defaults to 'primary'
     *
     * @param BuilderEx $query
     * @param string    $merchantId
     * @param string    $type
     */
    public function scopeMerchantIdAndType(BuilderEx $query, string $merchantId, string $type = Type::PRIMARY)
    {
        $query->where($this->dbColumn(Entity::MERCHANT_ID), $merchantId)
              ->where($this->dbColumn(Entity::TYPE), $type);
    }

    public function balanceConfigs()
    {
        return $this->hasMany(BalanceConfig\Entity::class);
    }

    public function getLastFetchedAtAttribute()
    {
        if (($this->getType() === Type::BANKING) and
            ($this->getAccountType() === AccountType::DIRECT))
        {
            // getLastFetchedAt() returns a sting in case there is a corresponding entry in the DB. It returns an empty
            // dictionary in case RBL BAS fetch cron hasn't run and there is no corresponding entry, in that case we
            // return the balance entity's updatedAt as the last_fetched_at.

            if (($this->getLastFetchedAt() instanceof Dictionary) and
                (empty($this->getLastFetchedAt()->key()) === true))
            {
                return $this->getUpdatedAt();
            }

            return $this->getLastFetchedAt();
        }
    }

    public function updateLastFetchedAt()
    {
        $this->getSettingsAccessor()
            ->upsert(self::LAST_FETCHED_AT, Carbon::now(Timezone::IST)->getTimestamp())
            ->save();
    }

    protected function getLastFetchedAt()
    {
        return $this->getSettingsAccessor()
                    ->get(self::LAST_FETCHED_AT);
    }

    protected function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::BALANCE);
    }
}
