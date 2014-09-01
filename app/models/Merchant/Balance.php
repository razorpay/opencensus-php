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
        if (is_numeric($amount) === false)
        {
            throw new Exception\InvalidArgumentException('Unsigned integer required. Supplied: '.$amount);
        }

        $this->attributes[self::BALANCE] += (int) $amount;
    }

    public function subtractAmount($amount)
    {
        if (is_numeric($amount) === false)
        {
            throw new Exception\InvalidArgumentException('Unsigned integer required. Supplied: '.$amount);
        }

        $this->attributes[self::BALANCE] -= (int) $amount;
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }
}
