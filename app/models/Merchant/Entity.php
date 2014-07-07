<?php

namespace Models\Merchant;

use Models\Base;

class Entity extends Base\Entity
{
    const ID = 'id';

    const BALANCE = 'balance';

    protected $table = \Constants\Table::MERCHANT;

    public $incrementing = false;

    protected $hidden = array(
        self::ID,
        self::BALANCE);

    protected $fillable = array(
        self::ID
    );

    public function keys()
    {
        return $this->hasMany(
            '\Models\Key\Entity');
    }

    public function transactions()
    {
        return $this->hasMany(
            '\Models\Transaction\Entity');
    }
}
