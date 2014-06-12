<?php 

namespace Models\DAL;

class Merchant extends DAL
{

    protected $hidden = array(
        'id',
        'pwd',
        'hash',
        'amount');

    public function keys()
    {
        return $this->hasMany(
            __NAMESPACE__.'\Keys');
    }

    public function transactions()
    {
        return $this->hasMany(
            __NAMESPACE__.'\Transaction');
    }
}

?>