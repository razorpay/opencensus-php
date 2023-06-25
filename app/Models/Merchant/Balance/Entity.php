<?php

namespace RZP\Models\Merchant\Balance;

use App;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Base\BuilderEx;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use Razorpay\Spine\DataTypes\Dictionary;
use RZP\Models\Merchant\Credits\Constants;
use RZP\Models\Payout\Metric as PayoutMetric;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Models\Merchant\Balance\Ledger\Core as LedgerCore;

/**
 * Class Entity
 *
 * @property BankingAccount\Entity $bankingAccount
 */
class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const TYPE                  = 'type';
    const CURRENCY              = 'currency';
    const NAME                  = 'name';
    const BALANCE               = 'balance';
    const LOCKED_BALANCE        = 'locked_balance';
    const ON_HOLD               = 'on_hold';
    const AMOUNT_CREDITS        = 'credits';
    const FEE_CREDITS           = 'fee_credits';
    const REWARD_FEE_CREDITS    = 'reward_fee_credits';
    const REFUND_CREDITS        = 'refund_credits';

    //
    // This is bank_accounts.account_number for bank_account's virtual_account
    // related to this balance. At present this is the use case. It must be
    // empty for balance of type != banking for now.
    //
    const ACCOUNT_NUMBER = 'account_number';

    const ACCOUNT_NUMBER_SUFFIX = 'account_number_suffix';

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

    // Corporate Card Details are fetched from capital-cards service for account_type corp_card
    const CORP_CARD_DETAILS = 'corp_card_details';

    // ueed to decide on making sync call for balance fetch
    const CACHED = 'cached';

    // will be null if successful sync call is made
    const ERROR_INFO = 'error_info';

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
        self::LOCKED_BALANCE,
        self::AMOUNT_CREDITS,
        self::FEE_CREDITS,
        self::REFUND_CREDITS,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::CHANNEL,
        self::UPDATED_AT,
        self::LAST_FETCHED_AT,
        self::CORP_CARD_DETAILS
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
        self::LOCKED_BALANCE,
        self::CHANNEL,
        self::UPDATED_AT,
        self::LAST_FETCHED_AT,
        self::CORP_CARD_DETAILS,
        self::ERROR_INFO,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::LOCKED_BALANCE,
        self::BALANCE,
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

    protected function setPublicBalanceAttribute(array & $attributes)
    {
        $accountType = $attributes[self::ACCOUNT_TYPE];

        if ($accountType === AccountType::DIRECT)
        {
            /** @var Details\Entity $basDetails */
            $basDetails = $this->bankingAccountStatementDetails;

            $app = App::getFacadeRoot();

            $variant = $app->razorx->getTreatment(
                $basDetails->getId(),
                RazorxTreatment::USE_GATEWAY_BALANCE,
                $app['rzp.mode'] ?? 'live'
            );

            if ($variant === 'on')
            {
                // Consuming gateway balance from basDetails for RBL too, since banking_account is going to be migrated
                // to banking_account service
                $attributes[self::BALANCE] = $basDetails->getGatewayBalance();
            }
            else
            {
                // in normal scenario we are sending balance table's balance but if the gateway balance is
                // more updated, then we use that instead
                if ($basDetails->isGatewayBalanceFetchCronMoreUpdated() === true)
                {
                    $attributes[self::BALANCE] = $basDetails->getGatewayBalance();
                }
            }
        }

        if ($accountType === AccountType::SHARED)
        {
            if($this->merchant->isFeatureEnabled(Feature\Constants::MERCHANT_ROUTE_WA_INFRA))
            {
               $whatsappParentBalance = (new Repository)->getBalanceByIdFromWhatsappDB($attributes[self::ID]);

               $attributes[self::BALANCE] = $whatsappParentBalance->getBalance();
            }

            $availableSubBalances = (new SubBalanceMap\Core)->getSubBalancesForParentBalance($attributes[self::ID]);

            if (count($availableSubBalances) > 0)
            {
                $subBalancesBalance = (new Repository)->getBalanceSumFromSubBalances($availableSubBalances,$this->merchant);

                $attributes[self::BALANCE] += $subBalancesBalance;
            }
        }
    }

    protected function setPublicLastFetchedAtAttribute(array & $attributes)
    {
        $accountType = $attributes[self::ACCOUNT_TYPE];

        $app = App::getFacadeRoot();

        if ($accountType === AccountType::DIRECT)
        {
            /** @var Details\Entity $basDetails */
            $basDetails = $this->bankingAccountStatementDetails;

            $variant = $app->razorx->getTreatment(
                $basDetails->getId(),
                RazorxTreatment::USE_GATEWAY_BALANCE,
                $app['rzp.mode'] ?? 'live'
            );

            if ($variant === 'on')
            {
                // Consuming balanceLastFetchedAt from basDetails for RBL too, since banking_account is going to be migrated
                // to banking_account service
                $attributes[self::LAST_FETCHED_AT] = $basDetails->getBalanceLastFetchedAt();
            }
            else
            {
                // in normal scenario we are sending balance table's balance but if the gateway balance is
                // more updated, then we use that instead
                if ($basDetails->isGatewayBalanceFetchCronMoreUpdated() === true)
                {
                    $attributes[self::LAST_FETCHED_AT] = $basDetails->getBalanceLastFetchedAt();
                }
            }
        }

        if ($accountType === AccountType::SHARED)
        {
            $exp = $app->razorx->getTreatment(
                $this->getMerchantId(),
                RazorxTreatment::SYNC_CALL_FOR_FRESH_BALANCE,
                $app['rzp.mode'] ?? 'live'
            );

            if ($exp === 'on')
            {
                $attributes[self::LAST_FETCHED_AT] = Carbon::now()->getTimestamp();
            }

        }
    }

    public function setBalance($balance)
    {
        $this->checkNumeric($balance);

        $this->setAttribute(self::BALANCE, $balance);
    }

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

    public function getLockedBalance()
    {
        return $this->getAttribute(self::LOCKED_BALANCE);
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

    public function getErrorInfo()
    {
        return $this->getAttribute(self::ERROR_INFO);
    }

    public function isTypePrimary(): bool
    {
        return ($this->getType() === Type::PRIMARY);
    }

    public function isTypeBanking(): bool
    {
        return ($this->getType() === Type::BANKING);
    }

    public function isAccountTypeShared(): bool
    {
        return ($this->getAccountType() === AccountType::SHARED);
    }

    public function isAccountTypeDirect(): bool
    {
        return ($this->getAccountType() === AccountType::DIRECT);
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

    public function bankingAccountStatementDetails()
    {
        return $this->hasOne(Details\Entity::class);
    }

    public static function buildFromMerchant($merchant)
    {
        // TODO need to build it via input
        $balance = new static;

        $balance->merchant()->associate($merchant);
        $balance->setAttribute(self::BALANCE, 0);
        $balance->setAttribute(self::CURRENCY, $merchant->getCurrency());
        $balance->setAttribute(self::TYPE, Type::PRIMARY);

        return $balance;
    }

    /**
     * Only this method should be public
     * for updating balance.
     * We need to check for balance going less than $negativeLimit
     * whenever we update balance
     *
     * @param null $txn
     * @param int  $negativeLimit
     * @param null $amount
     *
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function updateBalance($txn = null, int $negativeLimit = 0, $amount = null)
    {
        $amount = $amount ?? $txn->getNetAmount();

        $oldBalance = $this->getBalance();

        $this->addAmount($amount);

        $newBalance = $this->getBalance();

        // if the balance after is update is greater than the previous balance,
        // even if it is still negative, we should update the balance.

        // When new balance is equal to old balance, this case can serve for
        // direct settlement with fees as 0 where net amount is 0.
        if ($newBalance >= $oldBalance)
        {
            return;
        }

        if ($txn && $txn->shouldNegativeBalanceCheckSkipped() === true)
        {
            $dimensions = (new Metric)->getBalanceAllowedNegativeDimensions($txn->getMerchantId(), $this->getType(), $this->getBalance(), $txn->getType(), $txn->getId());

            $app = App::getFacadeRoot();

            $app['trace']->count(Metric::BALANCE_ALLOWED_NEGATIVE, $dimensions);

            $app['trace']->info(TraceCode::NEGATIVE_BALANCE_ALLOWED_TXN, [
                'merchant_id'       => $txn->getMerchantId(),
                'transaction'       => $txn->getId(),
                'transaction_type'  => $txn->getType(),
                'old_balance'       => $oldBalance,
                'new_balance'       => $newBalance,
            ]);

            return;
        }

        $data = [
            'balance'     => $this->toArray(),
            'amount'      => $amount,
            'transaction' => $txn ? $txn->getId() : '',
        ];

        //
        // We don't want to do the locked balance check when money is getting added.
        // i.e., (new balance > old balance).
        // If (new balance < old balance), we want to check if there's any locked balance
        // first and see if it's going below the thresholds. If it is, we fail it.
        // It's equivalent to the merchant not having enough balance to make the txn.
        //
        $newBalanceWithLockedBalance = $this->getBalanceWithLockedBalance();

        if (($negativeLimit === 0) and
            ($newBalance < 0))
        {
            throw new Exception\LogicException(
                'Something very wrong is happening! Balance is going negative',
                null,
                $data);
        }
        else if (($negativeLimit === 0) and
                 ($newBalanceWithLockedBalance < 0))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE_LOCKED,
                Entity::BALANCE,
                [
                    'balance_id'                    => $this->getId(),
                    'amount'                        => $this->getBalance(),
                    'locked_balance'                => $this->getLockedBalance(),
                    'balance_with_locked_balance'   => $newBalanceWithLockedBalance,
                    'negative_limit'                => $negativeLimit,
                ]);
        }
        else if ($newBalanceWithLockedBalance < $negativeLimit)
        {
            $data['message'] = TraceCode::getMessage(TraceCode::NEGATIVE_BALANCE_BREACHED);

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED,
                Entity::BALANCE,
                [
                    'balance_id'                    => $this->getId(),
                    'amount'                        => $this->getBalance(),
                    'locked_balance'                => $this->getLockedBalance(),
                    'balance_with_locked_balance'   => $newBalanceWithLockedBalance,
                    'negative_limit'                => $negativeLimit,
                ],
                $data['message']
            );
        }
    }

    // TODO Locked balance is not there on Ledger yet and needs to be handled later.
    public function getBalanceWithLockedBalanceFromLedger()
    {
        $balance = $this->getBalance();

        //
        // We are currently checking locked balance only for banking type balance. For other
        // type of balances, things will need to be handled in the code accordingly before
        // making a change here. Check for `getBalance` usages specifically, among others.
        //
        if (($this->isTypeBanking() === true) and
            ($this->isAccountTypeShared() === true))
        {
            $balance = $balance - $this->getLockedBalance();

            // call ledger when "ledger_journal_reads" is enabled on the merchant.
            if ($this->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true)
            {
                $accountNumber = $this->getAccountNumber();

                $bankingAccount = (new BankingAccount\Repository)
                    ->findByMerchantAndAccountNumberPublic($this->merchant, $accountNumber);

                $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($this->merchant->getId(), $bankingAccount->getPublicId());
                if ((empty($ledgerResponse) === false) &&
                    (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE]) === false) &&
                    (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE]) === false))
                {
                    $balance = (int) $ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE];
                }
            }
        }

        return $balance;
    }

    public function getSharedBankingBalanceFromLedgerWithoutFallbackOnApi()
    {
        $balance = null;

        // call ledger when "ledger_journal_reads" is enabled on the merchant.
        if ($this->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true)
        {
            $accountNumber = $this->getAccountNumber();

            $bankingAccount = (new BankingAccount\Repository)
                ->findByMerchantAndAccountNumberPublic($this->merchant, $accountNumber);

            $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($this->merchant->getId(), $bankingAccount->getPublicId());
            if ((empty($ledgerResponse) === false) &&
                (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE]) === false) &&
                (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE]) === false))
            {
                $balance = (int) $ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE];
            }
        }
        else
        {
            // Fetch Balance from API if not enabled on ledger reverse shadow
            $balance = $this->getBalance() - $this->getLockedBalance();
        }

        if (isset($balance) === false)
        {
            app('trace')->count(PayoutMetric::LEDGER_LITE_BALANCE_FETCH_ERROR_COUNT);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_BALANCE_DOES_NOT_EXIST, null, [
                'id'           => $this->getId(),
                'merchant_id'  => $this->getMerchantId(),
                'account_type' => $this->getAccountType(),
                'type'         => $this->getType()
            ]);
        }

        return $balance;
    }

    public function getBalanceWithLockedBalance()
    {
        $balance = $this->getBalance();

        //
        // We are currently checking locked balance only for banking type balance. For other
        // type of balances, things will need to be handled in the code accordingly before
        // making a change here. Check for `getBalance` usages specifically, among others.
        //

        if (($this->isTypeBanking() === true) and
            ($this->isAccountTypeShared() === true))
        {
            $balance = $balance - $this->getLockedBalance();
        }

        return $balance;
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
        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Constants::MERCHANT_CREDIT_TYPE_MUTEX_PREFIX . $this->merchant->getId() . '_' . \RZP\Models\Merchant\Credits\Type::REFUND;

        $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($amount, $negativeLimit)
            {
                $credits = $this->getRefundCredits();

                $credits -= $amount;

                assertTrue($credits >= $negativeLimit);

                $this->setAttribute(self::REFUND_CREDITS, $credits);
            },
            Constants::MERCHANT_CREDIT_TYPE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_CREDITS_OPERATION_IN_PROGRESS,
            Constants::MERCHANT_CREDIT_TYPE_MUTEX_ACQUIRE_RETRY_LIMIT
        );
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

    public function setLockedBalance(int $lockedBalance)
    {
        assertTrue ($lockedBalance >= 0);

        if (($this->isTypeBanking() === false) or
            ($this->isAccountTypeShared() === false))
        {
            throw new Exception\LogicException(
                'Locked balance being set for non-banking or/and non-shared type',
                ErrorCode::SERVER_ERROR_LOCKED_BALANCE_SET_FOR_NON_BANKING,
                [
                    'balance_id'            => $this->getId(),
                    'locked_balance'        => $lockedBalance,
                    'balance_type'          => $this->getType(),
                    'balance_account_type'  => $this->getAccountType(),
                ]);
        }

        $this->setAttribute(self::LOCKED_BALANCE, $lockedBalance);
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

    public function setPublicLockedBalanceAttribute(array & $attributes)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($attributes[self::LOCKED_BALANCE]);
        }
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
            ($this->isAccountTypeDirect() === true))
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
