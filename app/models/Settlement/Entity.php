<?php

namespace Models\Settlement;

use Models\Base;
use Models\Payment;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const AMOUNT                = 'amount';
    const STATUS                = 'status';
    const LEDGER_ID             = 'ledger_id';

    protected $table = \Constants\Table::SETTLEMENT;

    protected static $sign = 'setl';

    protected $fillable = array(
        self::AMOUNT,
        self::STATUS,
        self::MERCHANT_ID,
        self::LEDGER_ID);

    protected $public = array(
        self::ID,
        self::AMOUNT,
        self::STATUS);

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function ledger()
    {
        return $this->belongsTo('Models\Ledger\Entity');
    }
}