<?php

namespace Models\Merchant;

use EE\Exception;
use Models\Base;

class Balance extends Base\UniqueIdEntity
{
    const ID = 'id';
    const BALANCE = 'balance';
    const ON_HOLD = 'on_hold';

    protected $table = \Constants\Table::BALANCE;

    protected $fillable = array(
        self::ID);

    public function addAmount($amount)
    {
        $this->checkNumeric($amount);

        $this->attributes[self::BALANCE] += (int) $amount;
    }

    public function subAmount($amount)
    {
        $this->checkNumeric($amount);

        $this->attributes[self::BALANCE] -= (int) $amount;
    }

    protected function checkNumeric($arg)
    {
        if (is_numeric($arg) === false)
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

        return $balance;
    }
}
