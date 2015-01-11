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
    const UTR                   = 'utr';
    const FAILURE_REASON        = 'failure_reason';
    const RETURN_UTR            = 'return_utr';

    protected $table = \Constants\Table::SETTLEMENT;

    protected static $sign = 'setl';

    protected $entity = 'settlement';

    protected $fillable = array(
        self::AMOUNT,
        self::STATUS,
        self::MERCHANT_ID,
        self::TRANSACTION_ID);

    protected $visible = array(
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

    public function getAmount()
    {
        $this->getAttribute(self::AMOUNT);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setStatus($status = Status::CREATED)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setReturnUtr($utr)
    {
        $this->setAttribute(self::RETURN_UTR, $utr);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }
}