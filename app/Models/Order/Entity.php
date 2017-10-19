<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Offer;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID              = 'id';
    const MERCHANT_ID     = 'merchant_id';
    const OFFER_ID        = 'offer_id';

    /**
     * If set to true, partial payments are allowed on this order amount.
     */
    const PARTIAL_PAYMENT = 'partial_payment';

    /**
     * Amount:      Amount of the order
     * Amount paid: Amount paid for the order till present.
     *              Amount paid ∈ [0, Amount], if partial_payment = true
     *                          ∈ {0, Amount}, otherwise
     * Amount due:  Amount due is derived appended attribute.
     */
    const AMOUNT          = 'amount';
    const AMOUNT_PAID     = 'amount_paid';
    const AMOUNT_DUE      = 'amount_due';

    const CURRENCY        = 'currency';
    const ATTEMPTS        = 'attempts';
    const STATUS          = 'status';
    const NOTES           = 'notes';

    /**
     * Receipt provided by merchant against the order. Ideally should be
     * unique from the merchant side.
     */
    const RECEIPT         = 'receipt';

    /**
     * To Mark If a payment corresponding to this order is in authorized state.
     *
     */
    const AUTHORIZED      = 'authorized';

    const METHOD          = 'method';
    const BANK            = 'bank';
    const ACCOUNT_NUMBER  = 'account_number';
    const CUSTOMER_ID     = 'customer_id';

    /**
     * Auto capture corresponding payment(s) if this value set to true.
     */
    const PAYMENT_CAPTURE = 'payment_capture';

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT,
        self::PAYMENT_CAPTURE,
        self::NOTES,
        self::METHOD,
        self::ACCOUNT_NUMBER,
        self::BANK,
    ];

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::PARTIAL_PAYMENT => false,
        self::ATTEMPTS        => 0,
        self::STATUS          => Status::CREATED,
        self::PAYMENT_CAPTURE => 0,
        self::AMOUNT_PAID     => 0,
        self::AUTHORIZED      => 0,
        self::NOTES           => [],
        self::METHOD          => null,
        self::ACCOUNT_NUMBER  => null,
        self::BANK            => null,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::CURRENCY,
        self::RECEIPT,
        self::OFFER_ID,
        self::STATUS,
        self::ATTEMPTS,
        self::NOTES,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::PARTIAL_PAYMENT => 'bool',
        self::AMOUNT          => 'int',
        self::AMOUNT_PAID     => 'int',
        self::AMOUNT_DUE      => 'int',
        self::PAYMENT_CAPTURE => 'bool',
        self::AUTHORIZED      => 'bool',
        self::ATTEMPTS        => 'int',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
    ];

    protected $appends = [
        self::AMOUNT_DUE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::OFFER_ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected static $sign = 'order';

    protected $entity = 'order';

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

    public function offer()
    {
        return $this->belongsTo('RZP\Models\Offer\Entity');
    }

    /** End Related Models */

    /** Appends */

    public function getAmountDueAttribute(): int
    {
        return $this->getAmount() - $this->getAmountPaid();
    }

    /** End Appends */

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

    public function setAmountPaid(int $amountPaid)
    {
        $this->setAttribute(self::AMOUNT_PAID, $amountPaid);
    }

    public function setPartialPayment(bool $partialPayment)
    {
        $this->setAttribute(self::PARTIAL_PAYMENT, $partialPayment);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getAmountPaid()
    {
        return $this->getAttribute(self::AMOUNT_PAID);
    }

    public function getAmountDue()
    {
        return $this->getAttribute(self::AMOUNT_DUE);
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

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getMaskedAccountNumber()
    {
        $accountNumber = $this->getAccountNumber();

        $accountNumberLength = strlen($accountNumber);

        $last2Digits = substr($accountNumber, -2);

        $formattedNumber = str_repeat('X', $accountNumberLength - 2) . $last2Digits;

        return $formattedNumber;
    }

    /** End Setters And Getters */

    /** Other Functions */

    public function isPartialPaymentAllowed()
    {
        return $this->getAttribute(self::PARTIAL_PAYMENT);
    }

    public function allowPartialPayment()
    {
        $this->setPartialPayment(true);
    }

    public function togglePartialPayment()
    {
        $value = ($this->isPartialPaymentAllowed() === false);

        $this->setPartialPayment($value);
    }

    public function incrementAttempts()
    {
        $attempts = $this->getAttempts() + 1;

        $this->setAttempts($attempts);
    }

    /**
     * Increments amount paid by given amount.
     * Also, progresses status to paid if all amount is paid.
     *
     * @param int $amount
     */
    public function incrementAmountPaidBy(int $amount)
    {
        $amountPaid = $this->getAmountPaid() + $amount;

        $this->setAmountPaid($amountPaid);

        if ($this->getAmountPaid() === $this->getAmount())
        {
            $this->setStatus(Status::PAID);
        }
    }

    public function isAuthorized()
    {
        return (((int) $this->getAttribute(self::AUTHORIZED)) === 1);
    }

    public function isPaid()
    {
        return ($this->getAttribute(self::STATUS) === Status::PAID);
    }

    public function hasOffer()
    {
        return $this->isAttributeNotNull(self::OFFER_ID);
    }

    public function getOfferIfExists()
    {
        if ($this->hasOffer() === true)
        {
            return $this->offer;
        }

        return null;
    }

    protected function setPublicOfferIdAttribute(array & $array)
    {
        $offerId = $this->getAttribute(self::OFFER_ID);

        $array[self::OFFER_ID] = Offer\Entity::getSignedIdOrNull($offerId);
    }
}
