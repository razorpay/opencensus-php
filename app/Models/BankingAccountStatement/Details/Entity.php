<?php

namespace RZP\Models\BankingAccountStatement\Details;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;
use RZP\Models\Merchant\Balance;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::BANKING_ACCOUNT_STATEMENT_DETAILS;

    protected $table = Table::BANKING_ACCOUNT_STATEMENT_DETAILS;

    use AsvGetAttribute;

    const ID                                  = 'id';
    const MERCHANT_ID                         = 'merchant_id';
    const CHANNEL                             = 'channel';
    const ACCOUNT_NUMBER                      = 'account_number';
    const ACCOUNT_TYPE                        = 'account_type';
    const STATUS                              = 'status';
    const STATEMENT_CLOSING_BALANCE           = 'statement_closing_balance';
    const BALANCE_ID                          = 'balance_id';
    const GATEWAY_BALANCE                     = 'gateway_balance';
    const GATEWAY_BALANCE_CHANGE_AT           = 'gateway_balance_change_at';
    const STATEMENT_CLOSING_BALANCE_CHANGE_AT = 'statement_closing_balance_change_at';
    const LAST_STATEMENT_ATTEMPT_AT           = 'last_statement_attempt_at';
    const BALANCE_LAST_FETCHED_AT             = 'balance_last_fetched_at';
    const LAST_RECONCILED_AT                  = 'last_reconciled_at';
    const PAGINATION_KEY                      = 'pagination_key';

    const ACCOUNT_NUMBER_LENGTH = 40;

    protected $generateIdOnCreate = true;

    protected static $sign = 'basd';

    // generators
    protected static $generators = [
        self::ID,
    ];

    protected $fillable = [
        self::ID,
        self::CHANNEL,
        self::BALANCE_ID,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::ACCOUNT_TYPE,
        self::STATUS,
        self::STATEMENT_CLOSING_BALANCE,
        self::GATEWAY_BALANCE,
        self::GATEWAY_BALANCE_CHANGE_AT,
        self::STATEMENT_CLOSING_BALANCE_CHANGE_AT,
        self::LAST_STATEMENT_ATTEMPT_AT,
        self::BALANCE_LAST_FETCHED_AT,
        self::PAGINATION_KEY,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::BALANCE_ID,
        self::CHANNEL,
        self::ACCOUNT_TYPE,
        self::STATUS,
        self::STATEMENT_CLOSING_BALANCE,
        self::STATEMENT_CLOSING_BALANCE_CHANGE_AT,
        self::GATEWAY_BALANCE,
        self::GATEWAY_BALANCE_CHANGE_AT,
        self::LAST_STATEMENT_ATTEMPT_AT,
        self::BALANCE_LAST_FETCHED_AT,
        self::PAGINATION_KEY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LAST_RECONCILED_AT,
    ];

    protected $defaults = [
        self::STATUS                    => Status::ACTIVE,
        self::STATEMENT_CLOSING_BALANCE => 0,
        self::GATEWAY_BALANCE           => 0,
        self::ACCOUNT_TYPE              => AccountType::DIRECT,
    ];

    protected $casts = [
        self::ACCOUNT_NUMBER    => 'string',
    ];

    // ============================= MUTATORS =============================

    protected function setGatewayBalanceAttribute($gatewayBalance)
    {
        $this->attributes[self::GATEWAY_BALANCE] = $gatewayBalance;

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->setAttribute(self::GATEWAY_BALANCE_CHANGE_AT, $currentTime);
    }

    protected function setStatementClosingBalanceAttribute($statementClosingBalance)
    {
        $this->attributes[self::STATEMENT_CLOSING_BALANCE] = $statementClosingBalance;

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->setAttribute(self::STATEMENT_CLOSING_BALANCE_CHANGE_AT, $currentTime);
    }

    // ============================= END MUTATORS =============================

    // ============================= GETTERS ===============================

    public function getGatewayBalance()
    {
        return $this->getAttribute(self::GATEWAY_BALANCE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getStatementClosingBalance()
    {
        return $this->getAttribute(self::STATEMENT_CLOSING_BALANCE);
    }

    public function getGatewayBalanceChangeAt()
    {
        return $this->getAttribute(self::GATEWAY_BALANCE_CHANGE_AT);
    }

    public function getStatementClosingBalanceChangeAt()
    {
        return $this->getAttribute(self::STATEMENT_CLOSING_BALANCE_CHANGE_AT);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getLastStatementAttemptAt()
    {
        return $this->getAttribute(self::LAST_STATEMENT_ATTEMPT_AT);
    }

    public function getBalanceLastFetchedAt()
    {
        return $this->getAttribute(self::BALANCE_LAST_FETCHED_AT);
    }

    public function getLastReconciledAt()
    {
        return $this->getAttribute(self::LAST_RECONCILED_AT);
    }

    public function getPaginationKey()
    {
        return $this->getAttribute(self::PAGINATION_KEY);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    // ============================= END GETTERS ===========================

    // ============================= SETTERS ===========================

    public function setBalanceId(string $string): void
    {
        $this->setAttribute(self::BALANCE_ID, $string);
    }

    public function setMerchantId(string $string): void
    {
        $this->setAttribute(self::MERCHANT_ID, $string);
    }

    public function setChannel(string $string): void
    {
        $this->setAttribute(self::CHANNEL, $string);
    }

    public function setGatewayBalance(int $gatewayBalance)
    {
        $this->setAttribute(self::GATEWAY_BALANCE, $gatewayBalance);
    }

    public function setStatementClosingBalance(int $statementClosingBalance)
    {
        $this->setAttribute(self::STATEMENT_CLOSING_BALANCE, $statementClosingBalance);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setLastStatementAttemptAt()
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $this->setAttribute(self::LAST_STATEMENT_ATTEMPT_AT, $currentTime);
    }

    public function updateLastStatementAttemptAt(int $lastAttemptAt)
    {
        $this->setAttribute(self::LAST_STATEMENT_ATTEMPT_AT, $lastAttemptAt);
    }

    public function setBalanceLastFetchedAt(int $currentTime)
    {
        $this->setAttribute(self::BALANCE_LAST_FETCHED_AT, $currentTime);
    }

    public function setLastReconciledAt(int $currentTime)
    {
        $this->setAttribute(self::LAST_RECONCILED_AT, $currentTime);
    }

    public function setPaginationKey($paginationKey)
    {
        $this->setAttribute(self::PAGINATION_KEY, $paginationKey);
    }

    public function setAccountType(string $accountType)
    {
        $this->setAttribute(self::ACCOUNT_TYPE, $accountType);
    }

    public function setGatewayBalanceLastChangedAt($newTimeStamp)
    {

        $this->setAttribute(self::GATEWAY_BALANCE_CHANGE_AT, $newTimeStamp);
    }

    // ============================= END SETTERS ===========================

    // ============================= RELATIONS ===========================

    public function balance()
    {
        return $this->belongsTo(Balance\Entity::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    // ============================= END RELATIONS ===========================

    public function isGatewayBalanceFetchCronMoreUpdated(): bool
    {
        $balance = $this->balance;

        $gatewayBalanceLastFetchedAt = $this->getBalanceLastFetchedAt();

        $accStatementLastFetchedAt = $balance->getLastFetchedAtAttribute();

        return ($gatewayBalanceLastFetchedAt > $accStatementLastFetchedAt);
    }
}
