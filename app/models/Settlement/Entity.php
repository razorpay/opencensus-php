<?php

namespace Models\Settlement;

use Models\Base;
use Models\Transaction;

class Entity extends Base\UniqueIdEntity
{
    const ID            = 'id';
    const MERCHANT_ID   = 'merchant_id';
    const AMOUNT        = 'amount';
    const STATUS        = 'status';
    const LEDGER_ID     = 'ledger_id';
    const TRANSACTION_AMOUNT    = 'transaction_amount';
    const TRANSACTION_FEES      = 'transaction_fees';
    const REFUND_AMOUNT         = 'refund_amount';
    const REFUND_FEES           = 'refund_fees';

    protected $table = \Constants\Table::SETTLEMENT;

    protected static $sign = 'setl';

    protected $fillable = array(
        self::AMOUNT,
        self::STATUS,
        self::MERCHANT_ID,
        self::LEDGER_ID,
        self::TRANSACTION_AMOUNT,
        self::TRANSACTION_FEES,
        self::REFUND_AMOUNT,
        self::REFUND_FEES);

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function ledger()
    {
        return $this->belongsTo('Models\Ledger\Entity');
    }
}