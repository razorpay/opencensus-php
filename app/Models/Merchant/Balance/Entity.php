<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const BALANCE        = 'balance';
    const ON_HOLD        = 'on_hold';
    const AMOUNT_CREDITS = 'credits';
    const FEE_CREDITS    = 'fee_credits';

    protected $fillable = array(
        self::ID);

    protected $visible = array(
        self::ID,
        self::BALANCE,
        self::AMOUNT_CREDITS,
        self::FEE_CREDITS);

    protected $entity = 'balance';

    protected $generateIdOnCreate = false;

    protected $casts = [
        self::AMOUNT_CREDITS => 'integer',
        self::FEE_CREDITS    => 'integer',
        self::BALANCE        => 'integer',
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

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', 'id');
    }

    public static function buildFromMerchant($merchant)
    {
        $balance = new static;

        $balance->merchant()->associate($merchant);
        $balance->setAttribute(self::BALANCE, 0);

        return $balance;
    }

    /**
     * Only this method should be public
     * for updating balance.
     * We need to check for balance going negative
     * whenever we update balance
     *
     * @param  \RZP\Models\Transaction\Entity $txn
     * @throws Exception\LogicException
     */
    public function updateBalance($txn)
    {
        $amount = $txn->getNetAmount();

        $this->addAmount($amount);

        if ($this->getBalance() < 0)
        {
            $data = [
                'balance' => $this->toArray(),
                'transaction' => $txn->toArray(),
                'amount' => $amount
            ];

            throw new Exception\LogicException(
                'Something very wrong is happening! Balance is going negative',
                null,
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

    public function setAmountCredits($credits)
    {
        assert ($credits >= 0);

        $this->setAttribute(self::AMOUNT_CREDITS, $credits);
    }

    public function setFeeCredits(int $credits)
    {
        assert ($credits >= 0);

        $this->setAttribute(self::FEE_CREDITS, $credits);
    }

    public function save(array $options = array())
    {
        $this->validateBalance();

        return parent::save($options);
    }

    protected function validateBalance()
    {
        if ($this->getBalance() < 0)
        {
            throw new Exception\LogicException(
                'Something very wrong is happening! Balance is going negative',
                null,
                $this->toArray());
        }
    }
}
