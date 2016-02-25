<?php

namespace Models\Order;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const MERCHANT_ID   = 'merchant_id';
    const AMOUNT        = 'amount';
    const CURRENCY      = 'currency';
    const ATTEMPTS      = 'attempts';
    const STATUS        = 'status';

    // Ideally should be a unique from the merchant side as well
    const RECEIPT       = 'receipt';

    // To Mark If a payment corresponding to
    // this order is in authorized state
    const AUTHORIZED    = 'authorized';

    // const METHOD      = 'method';
    // const ACCOUNT_ID  = 'account_id';
    // const CREATED_AT  = 'created_at';
    // const VALIDITY    = 'validity';
    // const VALID_TILL  = 'valid_till';

    // Auto capture if set
    // const CAPTURE     = 'capture';

    protected $fillable = array(
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT);

    protected $table = \Constants\Table::ORDER;

    protected $genereateIdOnCreate = true;

    protected $defaults = array(
        self::ATTEMPTS  => 0,
        self::STATUS    => Status::CREATED);

    protected $public = array(
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT,
        self::STATUS,
        self::ATTEMPTS,
        self::CREATED_AT);

    protected static $sign = 'order';

    protected static $delimiter = '_';

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function payment()
    {
        return $this->hasMany('Models\Payment\Entity');
    }

    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    public function setAttempts($attempts)
    {
        return $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setAuthorized($authorized)
    {
        return $this->setAttribute(self::AUTHORIZED, $authorized);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAmountAttribute();
    }

    protected function getAmountAttribute()
    {
        return (int) $this->attributes[self::AMOUNT];
    }

    protected function getAuthorizedAttribute()
    {
        return (bool) $this->attributes[self::AUTHORIZED];
    }

    protected function getAttemptsAttribute()
    {
        return (int) $this->attributes[self::ATTEMPTS];
    }

    public function getAttempts()
    {
        return $this->getAttemptsAttribute();
    }

    public function incrementAttempts()
    {
        $attempts = $this->getAttempts() + 1;

        $this->setAttempts($attempts);
    }

    public function isAuthorized()
    {
        return (((int) $this->getAttribute(self::AUTHORIZED)) === 1);
    }
}
