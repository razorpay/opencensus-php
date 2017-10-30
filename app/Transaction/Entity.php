<?php

namespace App\Transaction;

use App\Base;

class Entity extends Base\Entity
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

    public static function retrieveLastByType($merchant_id, $type, $mode)
    {
        $data = self::where('merchant_id','=',$merchant_id)
                        ->where('type','=',$type)
                        ->where('mode', '=', $mode)
                        ->orderBy('updated_at','desc')
                        ->first();
        return $data;
    }

    public static function retrieveByTypeAndCreatedAt($merchant_id, $type, $created_at, $mode)
    {
        $data = self::where('merchant_id','=',$merchant_id)
                        ->where('type','=',$type)
                        ->where('mode', '=', $mode)
                        ->where('created_at', '=', $created_at)
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

    public function forceUpdateAmount($amount)
    {
        $this->amount = $amount;
    }

    public function forceUpdateCount($count = 1)
    {
        $this->count = $count;
    }
}
