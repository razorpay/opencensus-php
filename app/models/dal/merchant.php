<?php 

namespace Models\DAL;

class Merchant extends DAL
{
    protected $table = 'merchants';

    protected $hidden = array(
        'id',
        'balance');

    protected $fillable = array(
        'id'
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
}

?>