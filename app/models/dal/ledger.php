<?php 

namespace Models\DAL;

class Ledger extends UuidDAL
{
    protected $table = 'ledger';

    protected $fillable = array(
        'ref',
        'action',
        'merchant_id',
        'amount',
        'pending',
        'fee',
        'balance');
    
    public static function updateRecords($txn)
    {
        $merchantId = $txn->merchant_id;

        $ledger = null;

        \DB::transaction( function() use ($txn, &$ledger)
        {
            $merchantId = $txn->merchant_id;

            $merchant = Merchant::find($merchantId);

            $fee = $txn->amount * 3 / 100;

            $merchant->amount += ($txn->amount - $fee);

            $data = array(
                'ref'           => $txn->id,
                'merchant_id'   => $merchant->id,
                'action'        => 'capture',
                'fee'           => $fee,
                'balance'       => $merchant->amount);

            $ledger = static::createOrFail($data);
        });

        return $ledger;
    }


}