<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;

use RZP\Models\Bank\IFSC;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Offer\SubscriptionOffer\Entity as SubscriptionOfferEntity;

class Entity extends Base\PublicEntity
{
    const NAME                = 'name';
    const MERCHANT_ID         = 'merchant_id';
    const PAYMENT_METHOD      = 'payment_method';
    const PAYMENT_METHOD_TYPE = 'payment_method_type';
    const IINS                = 'iins';
    const PAYMENT_NETWORK     = 'payment_network';
    const ISSUER              = 'issuer';
    const INTERNATIONAL       = 'international';
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

    //This flag denotes if the offer is a no cost emi offer.
    const EMI_SUBVENTION      = 'emi_subvention';

    /**
     * This tells for what duration emi the offer is applicable.
     * It can have multiple emi durations. It will be stored in
     * serialized format.
     * For example if offer is applicable for 3,6 months then
     * emi_duration will be set to {3,6}
     */
    const EMI_DURATIONS       = 'emi_durations';

    /**
     * For card payments, this indicates the maximum number of payments
     * allowed on a card for the offer
     */
    const MAX_PAYMENT_COUNT   = 'max_payment_count';

    /**
     * Additional set of offer ids to check if the card for payment has also
     * been used against these offer ids.
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
    const PAYMENT_METHOD_LENGTH     = 30;
    const PAYMENT_METHOD_TYPE_LENTH = 10;
    const PAYMENT_NETWORK_LENGTH    = 20;
    const ISSUER_LENGTH             = 20;
    const DISPLAY_TEXT_LENGTH       = 255;

    const DEFAULT_ERROR_MESSAGE = 'Payment method used is not eligible for offer. ' .
                                    'Please try with a different payment method.';

    const MAX_OFFER_USAGE = 'max_offer_usage';

    const CURRENT_OFFER_USAGE = 'current_offer_usage';

    const DEFAULT_OFFER       = 'default_offer';

    const MAX_ORDER_AMOUNT    = 'max_order_amount';

    const PRODUCT_TYPE        = 'product_type';

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
        self::INTERNATIONAL,
        self::TYPE,
        self::PERCENT_RATE,
        self::MIN_AMOUNT,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::EMI_SUBVENTION,
        self::EMI_DURATIONS,
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
        self::MAX_OFFER_USAGE,
        self::DEFAULT_OFFER,
        self::MAX_ORDER_AMOUNT,
        self::PRODUCT_TYPE,
    ];

    protected $public = [
        self::ID,
    ];

    protected $proxy = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::IINS,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::INTERNATIONAL,
        self::TYPE,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::EMI_SUBVENTION,
        self::EMI_DURATIONS,
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
        self::MAX_OFFER_USAGE,
        self::CURRENT_OFFER_USAGE,
        self::CREATED_AT,
        self::DEFAULT_OFFER,
        self::MAX_ORDER_AMOUNT,
        self::PRODUCT_TYPE,

        SubscriptionOfferEntity::APPLICABLE_ON,
        SubscriptionOfferEntity::NO_OF_CYCLES,
        SubscriptionOfferEntity::REDEMPTION_TYPE,
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
        self::INTERNATIONAL,
        self::TYPE,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::EMI_SUBVENTION,
        self::EMI_DURATIONS,
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
        self::UPDATED_AT,
        self::MAX_OFFER_USAGE,
        self::CURRENT_OFFER_USAGE,
        self::DEFAULT_OFFER,
        self::MAX_ORDER_AMOUNT,
        self::PRODUCT_TYPE,

        SubscriptionOfferEntity::APPLICABLE_ON,
        SubscriptionOfferEntity::NO_OF_CYCLES,
        SubscriptionOfferEntity::REDEMPTION_TYPE,
    ];

    /** @var string[] List of columns/keys that are allowed to be exposed to the public for affordability widget. */
    protected $visibleForAffordability = [
        self::ID,
        self::NAME,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_TYPE,
        self::PAYMENT_NETWORK,
        self::ISSUER,
        self::INTERNATIONAL,
        self::TYPE,
        self::PERCENT_RATE,
        self::MAX_CASHBACK,
        self::FLAT_CASHBACK,
        self::EMI_SUBVENTION,
        self::EMI_DURATIONS,
        self::MIN_AMOUNT,
        self::STARTS_AT,
        self::ENDS_AT,
        self::DISPLAY_TEXT,
        self::TERMS,
        self::DEFAULT_OFFER,
        self::MAX_ORDER_AMOUNT,
    ];

    protected $defaults = [
        self::ACTIVE           => 1,
        self::BLOCK            => 1,
        self::CHECKOUT_DISPLAY => 0,
        self::TYPE             => self::INSTANT,
        self::ERROR_MESSAGE    => self::DEFAULT_ERROR_MESSAGE,
        self::EMI_SUBVENTION   => null,
        self::EMI_DURATIONS    => null,
        self::DEFAULT_OFFER    => 0,
        self::PRODUCT_TYPE     => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::LINKED_OFFER_IDS,
    ];

    protected static $generators = [
        self::STARTS_AT,
        self::MIN_AMOUNT,
    ];

    protected $casts = [
        self::EMI_DURATIONS      => 'array',
        self::EMI_SUBVENTION     => 'boolean',
        self::IINS               => 'array',
        self::INTERNATIONAL      => 'boolean',
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
        self::MAX_OFFER_USAGE    => 'int',
        self::CURRENT_OFFER_USAGE => 'int',
        self::CREATED_AT          => 'int',
        self::DEFAULT_OFFER       => 'boolean',
        self::MAX_ORDER_AMOUNT    => 'int',
    ];

    /**
     * Get list of columns that are allowed to be visivle
     * @return string[]
     */
    public static function getVisibleForAffordability(): array
    {
        return (new static())->visibleForAffordability;
    }

    public function build(array $input = [], string $operation = 'create')
    {
        $this->modify($input);

        if (isset($input[self::EMI_SUBVENTION]) === true)
        {
            $operation = 'emiSubvention';
        }

        $this->getValidator()->validateInput($operation, $input);

        $this->generate($input);

        $this->unsetInput($operation, $input);

        $this->fill($input);

        return $this;
    }

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

    public function isDefaultOffer()
    {
        return $this->getAttribute(self::DEFAULT_OFFER);
    }

    public function isPeriodActive()
    {
        $now = Carbon::now()->getTimestamp();

        return (($now >= $this->getStartsAt()) and
                ($now <= $this->getEndsAt()));
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
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

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
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

    public function getEmiSubvention()
    {
        return $this->getAttribute(self::EMI_SUBVENTION);
    }

    public function getEmiDurations()
    {
        return $this->getAttribute(self::EMI_DURATIONS);
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

    public function getCheckoutDisplay()
    {
        return $this->getAttribute(self::CHECKOUT_DISPLAY);
    }

    public function getMaxOfferUsage()
    {
        return $this->getAttribute(self::MAX_OFFER_USAGE);
    }

    public function getCurrentOfferUsage()
    {
        return $this->getAttribute(self::CURRENT_OFFER_USAGE);
    }

    public function getOfferType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getMaxOrderAmount()
    {
        return $this->getAttribute(self::MAX_ORDER_AMOUNT);
    }

    public function getProductType()
    {
        return $this->getAttribute(self::PRODUCT_TYPE);
    }

// --------------------- Calculator --------------------------------------------

    public function getDiscountedAmountForPayment(int $amount, $payment): int
    {
        $percentDiscount = null;

        if ($this->getEmiSubvention() === true)
        {
            $emiPlan = $payment->emiPlan;

            $percentDiscount = $emiPlan->getMerchantPayback();
        }

        return $this->getDiscountedAmount($amount, $percentDiscount);
    }

    public function getDiscountAmountForPayment(int $amount, $payment): int
    {
        $percentDiscount = null;

        if ($this->getEmiSubvention() === true)
        {
            $emiPlan = $payment->emiPlan;

            $percentDiscount = $emiPlan->getMerchantPayback();
        }

        return $this->getDiscount($amount, $percentDiscount);
    }

    public function getDiscountedAmount(int $amount, $percentDiscount = null)
    {
        $calculator = new Calculator($this);

        return $calculator->calculateDiscountedAmount($amount, $percentDiscount);
    }

    public function getDiscount(int $amount, $percentDiscount = null)
    {
        $calculator = new Calculator($this);

        return $calculator->calculateDiscount($amount, $percentDiscount);
    }

// ----------------------- Setters ---------------------------------------------

    public function deactivate()
    {
        $this->setAttribute(self::ACTIVE, 0);
    }

    public function activate()
    {
        $this->setAttribute(self::ACTIVE, 1);
    }

    public function setCurrentUsageCount(int $count)
    {
        $this->setAttribute(self::CURRENT_OFFER_USAGE, $count);
    }

    public function setErrorMessage(string $errorMessage)
    {
        $this->setAttribute(self::ERROR_MESSAGE, $errorMessage);
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

    /**
     * Set the Issuer Attribute.
     *
     * @param string|null $issuer
     */
    protected function setIssuerAttribute(?string $issuer): void
    {
        if (IFSC::isDebitCardIssuer($issuer)) {
            // _DC issuers are hacks for differentiating between debit & credit card EMI's
            $this->attributes[self::PAYMENT_METHOD_TYPE] = Emi\Type::DEBIT;
        }

        $this->attributes[self::ISSUER] = IFSC::getIssuingBank($issuer);
    }

    protected function setEmiDurationsAttribute($emiDurations)
    {
        $existingEmiDurations = $this->getAttribute(self::EMI_DURATIONS);

        $emiDurations = $emiDurations ?? [];

        if (empty($existingEmiDurations) === false)
        {
            $emiDurations = array_unique(array_merge($existingEmiDurations, $emiDurations));
        }

        $emiDurations = array_map(function ($emiDuration) {
            return (int) $emiDuration;
        }, $emiDurations);

        $this->attributes[self::EMI_DURATIONS] = json_encode(array_unique(array_values($emiDurations)));
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

    protected function generateMinAmount(array $input)
    {
        if ((isset($input[self::EMI_SUBVENTION]) === false) or
            (empty($input[self::MIN_AMOUNT]) === false))
        {
            return;
        }

        $bank = IFSC::getIssuingBank($input[self::ISSUER] ?? '');

        $network = $input[self::PAYMENT_NETWORK] ?? null;

        $emiDurations = $input[self::EMI_DURATIONS] ?? [];

        $type = $input[Entity::PAYMENT_METHOD_TYPE] ?? null;

        //in case of emi PAYMENT_METHOD_TYPE comes as null
        //as of now only credit is supported so added type credit for emi

        if (($type === null) and
             ($input[Entity::PAYMENT_METHOD] === 'emi'))
        {
            $type = 'credit';
        }

        $minAmount = (new Emi\Core)->calculateMinAmountForPlans($emiDurations, $bank, $network, $type);

        $this->setAttribute(self::MIN_AMOUNT, $minAmount);
    }

    public function toArrayCheckout(int $amount = null)
    {
        $data = [
            self::ID                  => $this->getPublicId(),
            self::NAME                => $this->getAttribute(self::NAME),
            self::PAYMENT_METHOD      => $this->getAttribute(self::PAYMENT_METHOD),
            self::PAYMENT_METHOD_TYPE => $this->getAttribute(self::PAYMENT_METHOD_TYPE),
            self::PAYMENT_NETWORK     => $this->getAttribute(self::PAYMENT_NETWORK),
            self::ISSUER              => $this->getAttribute(self::ISSUER),
            self::DISPLAY_TEXT        => $this->getAttribute(self::DISPLAY_TEXT),
            self::EMI_SUBVENTION      => $this->getAttribute(self::EMI_SUBVENTION),
            self::TYPE                => $this->getAttribute(self::TYPE),
        ];

        if ($this->getProductType() === 'subscription')
        {
            $data[self::TERMS] = $this->getTerms();
        }

        //
        // If this flag is set then amount is to be discounted by us
        // We don't calculate the discount for emi subvented offers
        // because one offer of emi subvention can corresponds to multiple
        // plans which means multiple discounts are applicable.
        //
        if (($this->getAttribute(self::TYPE) === Constants::INSTANT_OFFER) and
            ($this->getAttribute(self::EMI_SUBVENTION) !== true))
        {
            $data['original_amount'] = $amount;

            $data['amount'] = $this->getDiscountedAmount($amount);
        }

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

    public function toArrayProxy()
    {
        $array = $this->attributesToArray();

        $this->setPublicAttributes($array);

        return array_only($array, $this->proxy);
    }
}
