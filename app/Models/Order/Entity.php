<?php

namespace RZP\Models\Order;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * @property Offer\Entity $offer
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID              = 'id';
    const MERCHANT_ID     = 'merchant_id';
    const OFFER_ID        = 'offer_id';

    /**
     * If set to true, we discount the amount for the payment
     */
    const DISCOUNT        = 'discount';

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

    const REFERENCE1      = 'reference1';
    const REFERENCE2      = 'reference2';
    const REFERENCE3      = 'reference3';
    const REFERENCE4      = 'reference4';
    const REFERENCE5      = 'reference5';
    const REFERENCE6      = 'reference6';
    const REFERENCE7      = 'reference7';
    const REFERENCE8      = 'reference8';
    const REFERENCE9      = 'reference9';
    const REFERENCE10     = 'reference10';

    const METHOD          = 'method';
    const BANK            = 'bank';
    const ACCOUNT_NUMBER  = 'account_number';
    const CUSTOMER_ID     = 'customer_id';

    /**
     * Auto capture corresponding payment(s) if this value set to true.
     */
    const PAYMENT_CAPTURE = 'payment_capture';

    /**
     * Used in creation request to link multiple offers
     */
    const OFFERS          = 'offers';

    /**
     * Enforce usage of an offer for payment of this order
     */
    const FORCE_OFFER     = 'force_offer';

    protected $fillable = [
        self::DISCOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::RECEIPT,
        self::PAYMENT_CAPTURE,
        self::NOTES,
        self::METHOD,
        self::ACCOUNT_NUMBER,
        self::BANK,
        self::FORCE_OFFER,
    ];

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::DISCOUNT        => false,
        self::PARTIAL_PAYMENT => false,
        self::RECEIPT         => null,
        self::ATTEMPTS        => 0,
        self::STATUS          => Status::CREATED,
        self::PAYMENT_CAPTURE => 0,
        self::AMOUNT_PAID     => 0,
        self::AUTHORIZED      => 0,
        self::NOTES           => [],
        self::METHOD          => null,
        self::ACCOUNT_NUMBER  => null,
        self::BANK            => null,
        self::FORCE_OFFER     => null,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::CURRENCY,
        self::RECEIPT,
        // This is likely needed for the merchant,
        // but still needs to be discussed.
        // See setPublicDiscountAttribute
        // self::DISCOUNT,
        self::OFFER_ID,
        self::OFFERS,
        self::STATUS,
        self::ATTEMPTS,
        self::NOTES,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::DISCOUNT        => 'bool',
        self::PARTIAL_PAYMENT => 'bool',
        self::AMOUNT          => 'int',
        self::AMOUNT_PAID     => 'int',
        self::AMOUNT_DUE      => 'int',
        self::PAYMENT_CAPTURE => 'bool',
        self::AUTHORIZED      => 'bool',
        self::ATTEMPTS        => 'int',
        self::FORCE_OFFER     => 'bool',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
    ];

    protected $appends = [
        self::AMOUNT_DUE,
    ];

    protected static $generators = [
        self::FORCE_OFFER,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::OFFERS,
        // This is likely needed for the merchant,
        // but still needs to be discussed.
        // self::DISCOUNT,
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

    public function offers()
    {
        return $this->morphToMany(
                        Offer\Entity::class,
                        'entity',
                        Table::ENTITY_OFFER)
                    ->withTimestamps();
    }

    /** End Related Models */

    /** Appends */

    public function getAmountDueAttribute(): int
    {
        return $this->getAmount() - $this->getAmountPaid();
    }

    /** End Appends */

    /** Generators */

    /**
     * Enforces a default value for offer-related orders.
     *
     * If offers are being used, and no value is set for
     * force_offer, force_offer is set to false by default.
     *
     * @param  array $input
     * @return null
     */
    protected function generateForceOffer($input)
    {
        if ((isset($input[Entity::OFFERS]) === true) and
            (isset($input[Entity::FORCE_OFFER]) === false))
        {
            $this->setAttribute(self::FORCE_OFFER, false);
        }
    }

    /** End Generators */

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
    }

    public function isAuthorized()
    {
        return (((int) $this->getAttribute(self::AUTHORIZED)) === 1);
    }

    public function isPaid()
    {
        return ($this->getAttribute(self::STATUS) === Status::PAID);
    }

    public function isDiscountApplicable()
    {
        return $this->getAttribute(self::DISCOUNT);
    }

    public function isOfferForced()
    {
        return $this->getAttribute(self::FORCE_OFFER);
    }

    public function getOfferId()
    {
        return $this->getAttribute(self::OFFER_ID);
    }

    public function hasOffers(): bool
    {
        return ($this->offers->isNotEmpty() === true);
    }

    /**
     * Temporary. Serves to fetch the only offer available via pivot table.
     * Includes validations to ensure there isn't more than one.
     * TODO: Remove this when multiple offers are expected.
     *
     * @return Offer\Entity
     */
    public function getOffer()
    {
        $offers = $this->offers;

        return $offers->first();
    }

    protected function setPublicOffersAttribute(array & $array)
    {
        if ($this->hasOffers() === true)
        {
            //
            // For backward compatibility
            //
            if ($this->offers->count() === 1)
            {
                $array[self::OFFER_ID] = $this->getOffer()->getPublicId();
            }

            $array[self::OFFERS] = $this->offers->getPublicIds();
        }
        else
        {
            //
            // We are already sending offer_id=null for all order responses
            // (even when no offer is associated), so this cannot be removed for now.
            //
            $array[self::OFFER_ID] = null;
        }
    }

    protected function setPublicDiscountAttribute(array & $array)
    {
        if ($this->getAttribute(self::DISCOUNT) === true)
        {
            $array[self::DISCOUNT] = true;
        }
        else
        {
            unset($array[self::DISCOUNT]);
        }
    }
}
