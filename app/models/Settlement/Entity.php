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
    const TRANSACTION_ID        = 'transaction_id';

    protected $table = \Constants\Table::SETTLEMENT;

    protected static $sign = 'setl';

    protected $entity = 'settlement';

    protected $fillable = array(
        self::AMOUNT,
        self::STATUS,
        self::MERCHANT_ID,
        self::TRANSACTION_ID);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::STATUS);

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('Models\Transaction\Entity');
    }
}