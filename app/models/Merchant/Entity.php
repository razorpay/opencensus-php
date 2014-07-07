<?php

namespace Models\Merchant;

use \Constants\Field;

class Merchant extends DAL
{
    protected $table = \Constants\Table::MERCHANT;

    public $incrementing = false;

    protected $hidden = array(
        Field\Merchant::ID,
        Field\Merchant::BALANCE);

    protected $fillable = array(
        Field\Merchant::ID
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
