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
    const NOTES         = 'notes';

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
        self::RECEIPT,
        self::NOTES
    );

    protected $table = \Constants\Table::ORDER;

    protected $genereateIdOnCreate = true;

    protected $defaults = array(
        self::ATTEMPTS   => 0,
        self::STATUS     => Status::CREATED,
        self::AUTHORIZED => 0,
        self::NOTES      => []
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT,
        self::STATUS,
        self::ATTEMPTS,
        self::NOTES,
        self::CREATED_AT
    );

    protected $amounts = array(
        self::AMOUNT
    );

    protected static $sign = 'order';

    protected static $delimiter = '_';

    protected $entity           = 'order';

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function payment()
    {
        return $this->hasMany('Models\Payment\Entity');
    }

    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
    }

    public function getNotesJson()
    {
        return $this->attributes[self::NOTES];
    }

    public function setNotesAttribute($notes)
    {
        $this->attributes[self::NOTES] = json_encode($notes);
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

    public function getNotesAttribute($notes)
    {
        $notesArray = json_decode($notes, true);

        if ($notesArray === '')
        {
            return [];
        }

        return $notesArray;
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
