<?php

namespace Models\Ledger;

use Models\Base;
use Models\Transaction;

class Entity extends Base\UniqueIdEntity
{
    const ID            = 'id';
    const ENTITY_ID     = 'entity_id';
    const ENTITY_TYPE   = 'entity_type';
    const MERCHANT_ID   = 'merchant_id';
    const AMOUNT        = 'amount';
    const DEBIT         = 'debit';
    const CREDIT        = 'credit';
    const FEE           = 'fee';
    const BALANCE       = 'balance';

    protected $table = \Constants\Table::LEDGER;

    protected static $sign = 'lgr';

    protected $fillable = array(
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::MERCHANT_ID,
        self::DEBIT,
        self::CREDIT,
        self::AMOUNT,
        self::FEE,
        self::BALANCE);

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function entity()
    {
        $type = $this->getAttribute(self::ENTITY_TYPE);

        switch($type)
        {
            case 'transaction':
                return $this->hasOne('Models\Transaction\Entity');
                break;
            case 'refund':
                return $this->hasOne('Models\Transaction\Entity');
                break;
            default:
                throw new Exception\InvalidArgumentException(
                    'only transaction and refund supported currently');
        }
    }

}