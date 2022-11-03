<?php

namespace RZP\Models\Merchant\Balance\BalanceConfig;

use App;
use Config;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Balance\Type;

/**
 * Class Entity
 *
 * @property Entity $balanceConfig
 */
class Entity extends Base\PublicEntity
{
    //5k
    const DEFAULT_MAX_NEGATIVE = 500000;
    //5 lakhs
    const CUSTOM_MAX_NEGATIVE  = 50000000;

    // For current account on X for RBL , while processing of account statement , closing balance may be negative
    // because of some charges levied by bank on merchants account. We want to allow -ve balance for all merchants on
    // current account(RBL) for X. We can't decide on max allowed for each merchant. Thereby setting a high value of
    // 40 Cr common for all merchants. Earlier limit was 30 lakhs and then it was set to 90 lakhs. Since that limit is
    // exceeded, increased it to 400 Cr. Slack link for ref: https://razorpay.slack.com/archives/CR3K6S6C8/p1650284863967039
    // for more ref https://docs.google.com/document/d/1b_CsSdwC4n-Sld46g7i2TxhtCZQ6Kdeh8VK39HGyk2s/edit
    // TODO : Change negative limit post confirmation from bank
    const BANKING_MAX_NEGATIVE_FOR_RBL = 400000000000;
    const BANKING_MAX_NEGATIVE_FOR_ICICI = 400000000000;

    const ID                                   = 'id';
    const BALANCE_ID                           = 'balance_id';
    const TYPE                                 = 'type';
    const NEGATIVE_LIMIT_AUTO                  = 'negative_limit_auto';
    const NEGATIVE_LIMIT_MANUAL                = 'negative_limit_manual';
    const NEGATIVE_TRANSACTION_FLOWS           = 'negative_transaction_flows';

    protected $fillable = [
        self::ID,
        self::TYPE,
        self::NEGATIVE_LIMIT_AUTO,
        self::NEGATIVE_LIMIT_MANUAL,
        self::NEGATIVE_TRANSACTION_FLOWS,
    ];

    protected $defaults = [
        self::TYPE                     => Type::PRIMARY,
        self::NEGATIVE_LIMIT_AUTO     => self::DEFAULT_MAX_NEGATIVE,
        self::NEGATIVE_LIMIT_MANUAL   => 0,
    ];

    protected $visible = [
        self::ID,
        self::BALANCE_ID,
        self::TYPE,
        self::NEGATIVE_LIMIT_AUTO,
        self::NEGATIVE_LIMIT_MANUAL,
        self::NEGATIVE_TRANSACTION_FLOWS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::BALANCE_ID,
        self::TYPE,
        self::NEGATIVE_LIMIT_AUTO,
        self::NEGATIVE_LIMIT_MANUAL,
        self::NEGATIVE_TRANSACTION_FLOWS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $entity = 'balance_config';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $casts = [
        self::NEGATIVE_LIMIT_AUTO                 => 'integer',
        self::NEGATIVE_LIMIT_MANUAL               => 'integer',
        self::NEGATIVE_TRANSACTION_FLOWS          => 'array',
    ];

    /**
     * Build BalanceConfig Entity from $balance Entity
     *
     * @param $balance
     * @return Entity BalanceConfig
     */
    public static function buildFromBalance($balance)
    {
        $balanceConfig = new static;

        $balanceConfig->balance()->associate($balance);
        $balanceConfig->setAttribute(self::TYPE, Type::PRIMARY);
        $balanceConfig->setAttribute(self::NEGATIVE_LIMIT_AUTO, self::DEFAULT_MAX_NEGATIVE);
        $balanceConfig->setAttribute(self::NEGATIVE_LIMIT_MANUAL, 0);
        $balanceConfig->setAttribute(self::NEGATIVE_TRANSACTION_FLOWS, ['payment']);

        return $balanceConfig;
    }

    /**
     * BalanceConfig Entity belongs to a Balance Entity
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function balance()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Balance\Entity::class);
    }

    /**
     * Returns the Max Negative Limit for Auto mode, set for this BalanceConfig Entity
     *
     * @return int negative_limit
     */
    public function getMaxNegativeLimitAuto() : int
    {
        return $this->getAttribute(self::NEGATIVE_LIMIT_AUTO);
    }

    /**
     * Returns the Max Negative Limit for Manual mode, set for this BalanceConfig Entity
     *
     * @return int negative_limit
     */
    public function getMaxNegativeLimitManual() : int
    {
        return $this->getAttribute(self::NEGATIVE_LIMIT_MANUAL);
    }

    /**
     * Returns the array of allowed Negative Transaction
     * flows set in this BalanceConfig Entity
     *
     * @return array negative_transaction_flows
     */
    public function getNegativeTransactionFlows(): array
    {
        return $this->getAttribute(self::NEGATIVE_TRANSACTION_FLOWS);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    /**
     * Returns the balance type for this BalanceConfig Entity
     *
     * @return string balance_type
     */
    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    /**
     * Set negative limit for auto mode, for this BalanceConfig Entity
     *
     * @param $negativeLimit
     * @throws Exception\InvalidArgumentException
     */
    public function setNegativeLimitAuto($negativeLimitAuto)
    {
        $this->checkNumeric($negativeLimitAuto);

        $this->setAttribute(self::NEGATIVE_LIMIT_AUTO, $negativeLimitAuto);
    }

    /**
     * Set negative limit for manual mode, for this BalanceConfig Entity
     *
     * @param $negativeLimit
     * @throws Exception\InvalidArgumentException
     */
    public function setNegativeLimitManual($negativeLimitManual)
    {
        $this->checkNumeric($negativeLimitManual);

        $this->setAttribute(self::NEGATIVE_LIMIT_MANUAL, $negativeLimitManual);
    }

    /**
     * Set the allowed negative transaction flows
     * for this BalanceConfig Entity
     *
     * @param array $flows
     */
    public function setNegativeTransactionFlows(array $flows)
    {
        $this->setAttribute(self::NEGATIVE_TRANSACTION_FLOWS, $flows);
    }

    /**
     * Set balance type for this BalanceConfig Entity
     *
     * @param string $balanceType
     */
    public function setBalanceType(string $balanceType)
    {
        $this->setAttribute(self::TYPE, $balanceType);
    }

    /**
     * Check if the provided $arg is numeric
     *
     * @param $arg
     * @throws Exception\InvalidArgumentException
     */
    protected function checkNumeric($arg)
    {
        if (is_int($arg) === false)
        {
            throw new Exception\InvalidArgumentException('
                Unsigned integer required. Supplied: ' . $arg);
        }
    }
}
