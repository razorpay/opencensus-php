<?php

namespace Models\Merchant;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';
    const NAME = 'name';
    const EMAIL = 'email';
    const LIVE = 'live';

    const PRICING_PLAN = 'pricing_plan';

    protected $table = \Constants\Table::MERCHANT;

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::LIVE);

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
