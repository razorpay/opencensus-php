<?php

namespace Models\DAL;

class Transaction extends DAL
{
    protected $table = 'transactions';

    protected $hidden = array('id','merchant_id','updated_at','type');

    protected $fillable = array(
        'merchant_id',
        'type',
        'amount',
        'count',
        'created_at',
        'mode'
    );

    public function merchant()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Merchant'
        );
    }

    public static function retrieveLastByType($merchant_id, $type, $mode)
    {
        $data = self::where('merchant_id','=',$merchant_id)
                        ->where('type','=',$type)
                        ->where('mode', '=', $mode)
                        ->orderBy('updated_at','desc')
                        ->first();
        return $data;
    }

    public function updateAmount($amount)
    {
        $this->amount = (int)$this->amount + $amount;
    }

    public function updateCount($count = 1)
    {
        $this->count = (int)$this->count + $count;
    }
}
