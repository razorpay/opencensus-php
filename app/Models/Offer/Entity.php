<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const NAME                = 'name';
    const MERCHANT_ID         = 'merchant_id';
    const PAYMENT_METHOD      = 'payment_method';
    const PAYMENT_METHOD_TYPE = 'payment_method_type';
    const IINS                = 'iins';
    const PAYMENT_NETWORK     = 'payment_network';
    const ISSUER              = 'issuer';
    const ACTIVE              = 'active';
    const TYPE                = 'type';
    const BLOCK               = 'block';

    /**
     * Flag to denote if offer needs to be displayed on checkout always or
     * conditionally when associated with order
     * 0 - Conditional display
     * 1 - Display always
    */
    const CHECKOUT_DISPLAY    = 'checkout_display';
    const PERCENT_RATE        = 'percent_rate';
    const MIN_AMOUNT          = 'min_amount';
    const MAX_CASHBACK        = 'max_cashback';
    const FLAT_CASHBACK       = 'flat_cashback';

    /**
     * For card payments, this indicates the maximum number of payments
     * allowed on a card for the offer
     */
    const MAX_PAYMENT_COUNT   = 'max_payment_count';

    /**
     * Additional set of offer ids to check if the card for payment has also
     * bee used against these offer ids.
     * @todo check for better name
     */
    const LINKED_OFFER_IDS    = 'linked_offer_ids';

    /**
     * Processing time denotes the number of seconds required for offer cashback to be
     * settled to customer's account. Not being used now, may be used later
     */
    const PROCESSING_TIME     = 'processing_time';
    const STARTS_AT           = 'starts_at';
    const ENDS_AT             = 'ends_at';
    const DISPLAY_TEXT        = 'display_text';
    const ERROR_MESSAGE       = 'error_message';
    const TERMS               = 'terms';

    // Offer types
    const INSTANT  = 'instant';
    const DEFERRED = 'deferred';

    //Attribute lengths
    const NAME_LENGTH               = 50;
    const PAYMENT_METHOD_LENGTH     = 10;
    const PAYMENT_METHOD_TYPE_LENTH = 10;
    const PAYMENT_NETWORK_LENGTH    = 20;
    const ISSUER_LENGTH             = 20;
    const DISPLAY_TEXT_LENGTH       = 255;

    const DEFAULT_ERROR_MESSAGE = 'Payment method used is not eligible for offer. Please try with a different payment method.';

    /**
     * Attributes on the basis of which we determine an offer satisfies the same
     * payment criteria as another offer
     */
    const COMPARISON_ATTRIBUTES = [
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::ISSUER,
        self::PAYMENT_NETWORK
    ];

    protected $entity      = 'offer';

    protected static $sign = 'offer';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::TYPE,
        self::PERCENT_RATE,
        self::MIN_AMOUNT,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::MAX_PAYMENT_COUNT,
        self::LINKED_OFFER_IDS,
        self::PROCESSING_TIME,
        self::ACTIVE,
        self::BLOCK,
        self::CHECKOUT_DISPLAY,
        self::STARTS_AT,
        self::ENDS_AT,
        self::DISPLAY_TEXT,
        self::ERROR_MESSAGE,
        self::TERMS,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::TYPE,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::MIN_AMOUNT,
        self::MAX_PAYMENT_COUNT,
        self::LINKED_OFFER_IDS,
        self::PROCESSING_TIME,
        self::CHECKOUT_DISPLAY,
        self::ACTIVE,
        self::BLOCK,
        self::STARTS_AT,
        self::ENDS_AT,
        self::DISPLAY_TEXT,
        self::ERROR_MESSAGE,
        self::TERMS,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::MERCHANT_ID,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::TYPE,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::MIN_AMOUNT,
        self::MAX_PAYMENT_COUNT,
        self::LINKED_OFFER_IDS,
        self::PROCESSING_TIME,
        self::STARTS_AT,
        self::ENDS_AT,
        self::DISPLAY_TEXT,
        self::ERROR_MESSAGE,
        self::ACTIVE,
        self::BLOCK,
        self::CHECKOUT_DISPLAY,
        self::TERMS,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::ACTIVE           => 1,
        self::BLOCK            => 1,
        self::CHECKOUT_DISPLAY => 0,
        self::TYPE             => self::DEFERRED,
        self::ERROR_MESSAGE    => self::DEFAULT_ERROR_MESSAGE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::LINKED_OFFER_IDS,
    ];

    protected static $generators = [
        self::STARTS_AT,
    ];

    protected $casts = [
        self::IINS               => 'array',
        self::ACTIVE             => 'boolean',
        self::BLOCK              => 'boolean',
        self::CHECKOUT_DISPLAY   => 'boolean',
        self::PROCESSING_TIME    => 'int',
        self::PERCENT_RATE       => 'int',
        self::MAX_CASHBACK       => 'int',
        self::FLAT_CASHBACK      => 'int',
        self::MIN_AMOUNT         => 'int',
        self::STARTS_AT          => 'int',
        self::ENDS_AT            => 'int',
        self::MAX_PAYMENT_COUNT  => 'int',
        self::LINKED_OFFER_IDS   => 'array',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function orders()
    {
        return $this->hasMany('RZP\Models\Order\Entity');
    }

    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function getPercentRate()
    {
        return $this->getAttribute(self::PERCENT_RATE);
    }

    public function getFlatCashback()
    {
        return $this->getAttribute(self::FLAT_CASHBACK);
    }

    public function getMaxCashback()
    {
        return $this->getAttribute(self::MAX_CASHBACK);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getPaymentNetwork()
    {
        return $this->getAttribute(self::PAYMENT_NETWORK);
    }

    public function getPaymentMethodType()
    {
        return $this->getAttribute(self::PAYMENT_METHOD_TYPE);
    }

    public function getPaymentMethod()
    {
        return $this->getAttribute(self::PAYMENT_METHOD);
    }

    public function getMaxPaymentCount()
    {
        return $this->getAttribute(self::MAX_PAYMENT_COUNT);
    }

    public function getLinkedOfferIds()
    {
        return (array) $this->getAttribute(self::LINKED_OFFER_IDS);
    }

    public function getMinAmount()
    {
        return $this->getAttribute(self::MIN_AMOUNT);
    }

    public function getProcessingTime()
    {
        return $this->getAttribute(self::PROCESSING_TIME);
    }

    public function getIins()
    {
        return $this->getAttribute(self::IINS);
    }

    public function getStartsAt()
    {
        return $this->getAttribute(self::STARTS_AT);
    }

    public function getEndsAt()
    {
        return $this->getAttribute(self::ENDS_AT);
    }

    public function shouldBlockPayment()
    {
        return $this->getAttribute(self::BLOCK);
    }

    public function getDisplayText()
    {
        return $this->getAttribute(self::DISPLAY_TEXT);
    }

    public function getErrorMessage()
    {
        return $this->getAttribute(self::ERROR_MESSAGE);
    }

    public function getTerms()
    {
        return $this->getAttribute(self::TERMS);
    }

// ----------------------- Setters ---------------------------------------------

    public function deactivate()
    {
        $this->setAttribute(self::ACTIVE, 0);
    }

// ------------------------Public Setters--------------------------------------------

    public function setPublicLinkedOfferIdsAttribute(array & $array)
    {
        $linkedOfferIds = $this->getAttribute(self::LINKED_OFFER_IDS);

        if (empty($linkedOfferIds) === false)
        {
            self::getSignedIdMultiple($linkedOfferIds);

            $array[self::LINKED_OFFER_IDS] = $linkedOfferIds;
        }
    }

// ----------------------- Mutators --------------------------------------------

    protected function setIinsAttribute(array $iins)
    {
        $existingIins = $this->getAttribute(self::IINS);

        if ($existingIins !== null)
        {
            $iins = array_unique(array_merge($existingIins, $iins));
        }

        $this->attributes[self::IINS] = json_encode(array_values($iins));
    }

    protected function setLinkedOfferIdsAttribute(array $linkedOfferIds)
    {
        $existingLinkedOfferIds = $this->getAttribute(self::LINKED_OFFER_IDS);

        if ($existingLinkedOfferIds !== null)
        {
            $linkedOfferIds = array_unique(array_merge($existingLinkedOfferIds, $linkedOfferIds));
        }

        $this->attributes[self::LINKED_OFFER_IDS] = json_encode(array_values($linkedOfferIds));
    }

    protected function generateStartsAt(array $input)
    {
        $startsAt = $input[self::STARTS_AT] ?? Carbon::now()->getTimestamp();

        $this->setAttribute(self::STARTS_AT, $startsAt);
    }

    public function toArrayCheckout()
    {
        $data = [
            self::NAME            => $this->getAttribute(self::NAME),
            self::PAYMENT_METHOD  => $this->getAttribute(self::PAYMENT_METHOD),
            self::PAYMENT_NETWORK => $this->getAttribute(self::PAYMENT_NETWORK),
            self::ISSUER          => $this->getAttribute(self::ISSUER),
            self::DISPLAY_TEXT    => $this->getAttribute(self::DISPLAY_TEXT),
        ];

        return array_filter($data);
    }

    /**
     * Determines if two offers are for the same payment criteria,
     * as defined by the COMPARISON_ATTRIBUTES
     *
     * @param  Entity $offer
     *
     * @return bool
     */
    public function matches(Entity $offer): bool
    {
        foreach (self::COMPARISON_ATTRIBUTES as $attr)
        {
            if (($this->isAttributeNotNull($attr) === true) and
                ($this->getAttribute($attr) !== $offer->getAttribute($attr)))
            {
                return false;
            }
        }

        return true;
    }
}
