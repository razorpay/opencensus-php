<?php

namespace Models\Merchant;

use Models\Base;

class Balance extends Base\UniqueIdEntity
{
    const ID = 'id';
    const BALANCE = 'balance';

    protected $table = \Constants\Table::BALANCE;

    protected $fillable = array(
        self::ID);

    public function addAmount($amount)
    {
        $this->checkNumeric($amount);

        $this->attributes[self::BALANCE] += (int) $amount;
    }

    public function subtractAmount($amount)
    {
        $this->checkNumeric($amount);

        $this->attributes[self::BALANCE] -= (int) $amount;
    }

    protected function checkNumeric($arg)
    {
        if (is_numeric($arg) === false)
        {
            throw new Exception\InvalidArgumentException('Unsigned integer required. Supplied: '.$amount);
        }
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }
}
