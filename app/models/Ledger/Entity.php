<?php

namespace Models\Ledger;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';

    const MERCHANT_ID = 'merchant_id';

    const AMOUNT = 'amount';

    const ACTION = 'action';

    const FEE = 'fee';

    const BALANCE = 'balance';

    const PENDING = 'pending';

    protected $table = \Constants\Table::LEDGER;

    protected static $sign = 'lgr';

    protected $fillable = array(
        'ref',
        self::ACTION,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::PENDING,
        self::FEE,
        self::BALANCE);

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
                self::ACTION        => 'capture',
                self::FEE           => $fee,
                self::BALANCE       => $merchant->amount);

            $ledger = static::createOrFail($data);
        });

        return $ledger;
    }


}