<?php

namespace Models\Merchant;

use EE\Exception;
use Models\Base;

class Balance extends Base\UniqueIdEntity
{
    const ID = 'id';
    const BALANCE = 'balance';
    const ON_HOLD = 'on_hold';
    const CREDITS = 'credits';

    protected $table = \Constants\Table::BALANCE;

    protected $fillable = array(
        self::ID);

    protected $visible = array(
        self::ID,
        self::BALANCE);

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

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity', 'id');
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
                $data);
        }
    }

    public function getBalanceAttribute()
    {
        return (int) $this->attributes[self::BALANCE];
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
                $this->toArray());
        }
    }
}
