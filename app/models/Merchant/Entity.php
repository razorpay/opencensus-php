<?php

namespace Models\Merchant;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';

    protected $table = \Constants\Table::MERCHANT;

    protected $fillable = array(
        self::ID);

    public $incrementing = false;

//    protected static $generators = array('id');

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

    public function balance()
    {
        return $this->hasOne(
            '\Models\Merchant\Balance');
    }
}
