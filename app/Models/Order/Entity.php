<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID             = 'id';
    const MERCHANT_ID    = 'merchant_id';
    const AMOUNT         = 'amount';
    const CURRENCY       = 'currency';
    const ATTEMPTS       = 'attempts';
    const STATUS         = 'status';
    const NOTES          = 'notes';

    // Ideally should be a unique from the merchant side as well
    const RECEIPT        = 'receipt';

    // To Mark If a payment corresponding to
    // this order is in authorized state
    const AUTHORIZED     = 'authorized';
    const METHOD         = 'method';
    const BANK           = 'bank';
    const ACCOUNT_NUMBER = 'account_number';

    const CUSTOMER_ID    = 'customer_id';


    // const VALIDITY    = 'validity';
    // const VALID_TILL  = 'valid_till';

    // Auto capture if set
    // const CAPTURE     = 'capture';

    protected $fillable = array(
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT,
        self::NOTES,
        self::METHOD,
        self::ACCOUNT_NUMBER,
        self::BANK,
    );

    protected $table = \RZP\Constants\Table::ORDER;

    protected $generateIdOnCreate = true;

    protected $defaults = array(
        self::ATTEMPTS          => 0,
        self::STATUS            => Status::CREATED,
        self::AUTHORIZED        => 0,
        self::NOTES             => [],
        self::METHOD            => null,
        self::ACCOUNT_NUMBER    => null,
        self::BANK              => null,
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

    /** Related Models */
    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function payments()
    {
        return $this->hasMany('RZP\Models\Payment\Entity');
    }

    /** End Related Models */

    /** Setters And Getters */
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

    public function getAccountNumber()
    {
        return $this->attributes[self::ACCOUNT_NUMBER];
    }

    public function getMethod()
    {
        return $this->attributes[self::METHOD];
    }

    public function getAttempts()
    {
        return $this->getAttemptsAttribute();
    }

    public function getBank()
    {
        return $this->attributes[self::BANK];
    }

    public function getMaskedAccountNumber()
    {
        $accountNumber = $this->getAccountNumber();

        $accountNumberLength = strlen($accountNumber);

        $last2Digits = substr($accountNumber, -2);

        $formattedNumber = str_repeat('X',$accountNumberLength - 2).$last2Digits;

        return $formattedNumber;
    }

    /** End Setters And Getters */

    /** Mutators */
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

    /** End Mutators */

    /** Other Functions */
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
