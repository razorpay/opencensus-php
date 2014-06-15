<?php 

namespace Models\DAL;

use \Constants\Field;

class Ledger extends UuidDAL
{
    protected $table = \Constants\Table::LEDGER;

    protected $fillable = array(
        'ref',
        Field\Ledger::ACTION,
        Field\Common::MERCHANT_ID,
        Field\Ledger::AMOUNT,
        Field\Ledger::PENDING,
        Field\Ledger::FEE,
        Field\Ledger::BALANCE);
    
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
                Field\Common::MERCHANT_ID   => $merchant->id,
                Field\Ledger::ACTION        => 'capture',
                Field\Ledger::FEE           => $fee,
                Field\Ledger::BALANCE       => $merchant->amount);

            $ledger = static::createOrFail($data);
        });

        return $ledger;
    }


}