<?php

namespace Models\DAL;

use \Constants\Field;

class Merchant extends DAL
{
    protected $table = \Constants\Table::MERCHANT;

    protected $hidden = array(
        Field\Merchant::ID,
        Field\Merchant::BALANCE);

    protected $fillable = array(
        Field\Merchant::ID
    );

    public function keys()
    {
        return $this->hasMany(
            __NAMESPACE__.'\Key');
    }

    public function transactions()
    {
        return $this->hasMany(
            __NAMESPACE__.'\Transaction');
    }

    public function getId()
    {
        return $this->getAttribute(Field\Merchant::ID);
    }
}

?>
