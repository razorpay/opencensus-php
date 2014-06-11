<?php 

namespace Models\DAL;

class Merchant extends DAL
{
    protected $table = 'merchants';

    protected $fillable = array(
        'id'
    );

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