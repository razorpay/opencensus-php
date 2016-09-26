<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID              = 'id';
    const MERCHANT_ID     = 'merchant_id';
    const AMOUNT          = 'amount';
    const CURRENCY        = 'currency';
    const ATTEMPTS        = 'attempts';
    const STATUS          = 'status';
    const NOTES           = 'notes';

    // Ideally should be a unique from the merchant side as well
    const RECEIPT         = 'receipt';

    // To Mark If a payment corresponding to
    // this order is in authorized state
    const AUTHORIZED      = 'authorized';
    const METHOD          = 'method';
    const BANK            = 'bank';
    const ACCOUNT_NUMBER  = 'account_number';

    const CUSTOMER_ID     = 'customer_id';


    // const VALIDITY     = 'validity';
    // const VALID_TILL   = 'valid_till';

    // Auto capture if set
    const PAYMENT_CAPTURE = 'payment_capture';

    protected $fillable = array(
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT,
        self::PAYMENT_CAPTURE,
        self::NOTES,
        self::METHOD,
        self::ACCOUNT_NUMBER,
        self::BANK,
    );

    protected $table = Table::ORDER;

    protected $generateIdOnCreate = true;

    protected $defaults = array(
        self::ATTEMPTS          => 0,
        self::STATUS            => Status::CREATED,
        self::PAYMENT_CAPTURE   => 0,
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

    protected $casts = array(
        self::AMOUNT          => 'int',
        self::PAYMENT_CAPTURE => 'bool',
        self::AUTHORIZED      => 'bool',
        self::ATTEMPTS        => 'int'
    );

    protected $amounts = array(
        self::AMOUNT
    );

    protected static $sign = 'order';

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

    public function invoice()
    {
        return $this->hasOne('RZP\Models\Invoice\Entity');
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
        return $this->getAttribute(self::AMOUNT);
    }

    public function getPaymentCapture()
    {
        return $this->getAttribute(self::PAYMENT_CAPTURE);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
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
