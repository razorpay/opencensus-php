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

    public function merchant()
    {
        return $this->belongsTo('App\Merchant\Entity');
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

    public static function getAllTransactionsGrouped($mode, $type, $from, $to)
    {
        // $selectClause = \DB::raw('transactions.merchant_id, SUM(transactions.amount) as amount_sum, SUM(transactions.count) as count_sum');

        $data = self::with('merchant')
            ->select('merchant_id')
            ->with('merchant')
            ->where('mode', '=', $mode)
            // ->where('type', '=', 0)
            // ->where('created_at', '>=', $from)
            // ->where('created_at', '<=', $to)
            ->get();

        return $data;
    }
}
