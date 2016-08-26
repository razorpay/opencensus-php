<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Exception;

class Entity extends Base\PublicEntity
{
    const ID = 'id';
    const BALANCE = 'balance';
    const ON_HOLD = 'on_hold';
    const CREDITS = 'credits';

    protected $table = \RZP\Constants\Table::BALANCE;

    protected $fillable = array(
        self::ID);

    protected $visible = array(
        self::ID,
        self::BALANCE,
        self::CREDITS);

    protected $entity = 'balance';

    protected $generateIdOnCreate = false;

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
                Unsigned integer required. Supplied: '.$amount);
        }
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function getCredits()
    {
        return $this->getAttribute(self::CREDITS);
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
     * @param  Transaction\Entity $txn
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

    public function subtractCredits($amount)
    {
        $credits = $this->getCredits();

        $credits -= $amount;

        if ($credits < 0)
        {
            $credits = 0;
        }

        $this->setAttribute(self::CREDITS, $credits);
    }

    public function setFreeCredits($freeCredits)
    {
        return $this->setCredits($freeCredits);
    }

    public function setCredits($credits)
    {
        assert ($credits >= 0);

        $this->setAttribute(self::CREDITS, $credits);
    }

    protected function getBalanceAttribute()
    {
        return (int) $this->attributes[self::BALANCE];
    }

    protected function getCreditsAttribute()
    {
        return (int) $this->attributes[self::CREDITS];
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
