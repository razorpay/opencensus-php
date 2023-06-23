<?php

namespace RZP\Models\Order;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Offer;
use RZP\Trace\TraceCode;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Transfer;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\UpiMandate;
use RZP\Models\BankAccount;
use RZP\Models\Payment\Config;
use RZP\Constants\Entity as E;
use RZP\Models\Feature\Constants;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Order\OrderMeta\Type;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Base\Traits\ExternalOwner;
use RZP\Models\Base\Traits\ExternalEntity;
use RZP\Tests\Functional\Order\OrderMeta\OrderMetaTest;

/**
 * @property Offer\Entity    $offer
 * @property Merchant\Entity $merchant
 * @property Invoice\Entity  $invoice
 * @property Transfer\Entity $transfer
 * @property UpiMandate\Entity $upiMandate
 * @property Product\Entity $products
 * @property OrderMeta\Entity $orderMetas
 *
 * @property-read Base\PublicCollection $offers
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait, ExternalOwner, ExternalEntity;

    /**
     *
     */
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
    const AMOUNT            = 'amount';
    const AMOUNT_PAID       = 'amount_paid';
    const AMOUNT_DUE        = 'amount_due';

    const CURRENCY          = 'currency';
    const ATTEMPTS          = 'attempts';
    const STATUS            = 'status';
    const NOTES             = 'notes';

    /**
     * Can be set along with partial_payment true.
     * If set, defines the minimum amount that can be made for the first payment
     * on the order.
     */
    const FIRST_PAYMENT_MIN_AMOUNT = 'first_payment_min_amount';
    /**
     * Receipt provided by merchant against the order. Ideally should be
     * unique from the merchant side.
     */
    const RECEIPT           = 'receipt';


    /**
     * This is not the customer account token, this is merchant facing entity token.registration
     * currently called subscription_registration. Merchant facing key will be token
     */
    const TOKEN             = 'token';

    /**
     * To Mark If a payment corresponding to this order is in authorized state.
     *
     */
    const AUTHORIZED        = 'authorized';

    const REFERENCE1        = 'reference1';
    const REFERENCE2        = 'reference2';
    const REFERENCE3        = 'reference3';
    const REFERENCE4        = 'reference4';
    const REFERENCE5        = 'reference5';
    const REFERENCE6        = 'reference6';
    // Using reference7 column to store config_id for convenience fee config
    const FEE_CONFIG_ID     = 'reference7';
    // Using reference8 column to store "source" of orders
    const REFERENCE8        = 'reference8';
    const PUBLIC_KEY        = 'public_key';

    const METHOD            = 'method';
    const BANK              = 'bank';
    const ACCOUNT_NUMBER    = 'account_number';
    const CUSTOMER_ID       = 'customer_id';

    /**
     * Auto capture corresponding payment(s) if this value set to true.
     */
    const PAYMENT_CAPTURE   = 'payment_capture';

    /**
     * Used in creation request to link multiple offers
     */
    const OFFERS            = 'offers';

    /**
    * Used for expand order payments
    */
    const PAYMENTS          = 'payments';

    /**
     * Used in creation request to create and link bank account
     */
    const BANK_ACCOUNT      = 'bank_account';

    /**
     * Enforce usage of an offer for payment of this order
     */
    const FORCE_OFFER       = 'force_offer';

    const PAYER_NAME        = 'payer_name';

    const TRANSFERS         = 'transfers';

    const VIRTUAL_ACCOUNT   = 'virtual_account';

    const AUTH_TYPE = 'auth_type';

    const LATE_AUTH_CONFIG_ID= 'late_auth_config_id';

    const MAX_AMOUNT = 'max_amount';
    /**
     * This contains the capture settings whcih hets applied to late auth payments
     */
    const PAYMENT            = 'payment';

    const CHECKOUT_CONFIG_ID = 'checkout_config_id';

    const PROVIDER_CONTEXT = 'provider_context';

    const PRODUCT_TYPE = 'product_type';

    const PRODUCT_ID = 'product_id';

    const PHONEPE_SWITCH_CONTEXT = 'phonepe_switch_context';

    const APP_OFFER = 'app_offer';

    const PRODUCTS = 'products';

    const PG_ROUTER_SYNCED = 'pg_router_synced';

    const TAX_INVOICE = 'tax_invoice';

    const ORDER_META_1CC = 'order_meta_1cc';

    const CONVENIENCE_FEE_CONFIG = 'convenience_fee_config';

    const CUSTOMER_ADDITIONAL_INFO = 'customer_additional_info';

    const OLD_BANK_FORMAT = 'old_bank_format';

    //paginated fetch params
    const FROM         = 'from';
    const TO           = 'to';
    const COUNT        = 'count';
    const SKIP         = 'skip';

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
        self::PARTIAL_PAYMENT,
        self::FIRST_PAYMENT_MIN_AMOUNT,
        self::PAYER_NAME,
        self::CHECKOUT_CONFIG_ID,
        self::PRODUCT_ID,
        self::PRODUCT_TYPE,
        self::APP_OFFER,
        self::PG_ROUTER_SYNCED,
        self::STATUS,
        self::AMOUNT_PAID,
        self::AUTHORIZED,
        self::ATTEMPTS
    ];

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::DISCOUNT              => false,
        self::PARTIAL_PAYMENT       => false,
        self::RECEIPT               => null,
        self::ATTEMPTS              => 0,
        self::STATUS                => Status::CREATED,
        self::PAYMENT_CAPTURE       => 0,
        self::AMOUNT_PAID           => 0,
        self::AUTHORIZED            => 0,
        self::NOTES                 => [],
        self::METHOD                => null,
        self::ACCOUNT_NUMBER        => null,
        self::BANK                  => null,
        self::FORCE_OFFER           => null,
        self::LATE_AUTH_CONFIG_ID   => null,
        self::CHECKOUT_CONFIG_ID    => null,
        self::PROVIDER_CONTEXT      => null,
        self::APP_OFFER             => false,
        self::PG_ROUTER_SYNCED      => false,
        self::FEE_CONFIG_ID         => null,
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
        self::PAYMENTS,
        self::OFFER_ID,
        self::OFFERS,
        self::STATUS,
        self::ATTEMPTS,
        self::PRODUCTS,
        self::NOTES,
        self::VIRTUAL_ACCOUNT,
        self::CREATED_AT,
        self::TRANSFERS,
        self::CHECKOUT_CONFIG_ID,
        self::TAX_INVOICE,
        OrderMeta\Order1cc\Fields::PROMOTIONS,
        OrderMeta\Order1cc\Fields::COD_FEE,
        OrderMeta\Order1cc\Fields::SHIPPING_FEE,
        OrderMeta\Order1cc\Fields::CUSTOMER_DETAILS,
        OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL,
    ];

    protected $casts = [
        self::DISCOUNT                 => 'bool',
        self::PARTIAL_PAYMENT          => 'bool',
        self::AMOUNT                   => 'int',
        self::AMOUNT_PAID              => 'int',
        self::AMOUNT_DUE               => 'int',
        self::FIRST_PAYMENT_MIN_AMOUNT => 'int',
        self::PAYMENT_CAPTURE          => 'bool',
        self::AUTHORIZED               => 'bool',
        self::ATTEMPTS                 => 'int',
        self::FORCE_OFFER              => 'bool',
        self::APP_OFFER                => 'bool',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::FIRST_PAYMENT_MIN_AMOUNT,
    ];

    protected $appends = [
        self::AMOUNT_DUE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::OFFERS,
        self::CHECKOUT_CONFIG_ID,
        self::PRODUCTS,
        // This is likely needed for the merchant,
        // but still needs to be discussed.
        // self::DISCOUNT,
        self::TAX_INVOICE,
        self::ORDER_META_1CC,
        self::TRANSFERS,
    ];

    protected $internalSetters = [
        self::ID,
        self::ENTITY,
        self::OFFERS,
        self::CHECKOUT_CONFIG_ID,
        self::PRODUCTS,
        // This is likely needed for the merchant,
        // but still needs to be discussed.
        // self::DISCOUNT,
        self::TAX_INVOICE,
        self::ORDER_META_1CC,
        self::TRANSFERS,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected static $modifiers = [
        self::CHECKOUT_CONFIG_ID,
    ];

    protected static $sign = 'order';

    protected $entity = 'order';

    protected static $generators = [
        self::FORCE_OFFER,
        self::PROVIDER_CONTEXT,
    ];

    const ALLOWED_LINE_ITEM_TYPES = [
        Item\Type::PAYMENT_PAGE,
    ];

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

    public function lineItems()
    {
        return $this->morphMany('RZP\Models\LineItem\Entity', 'entity');
    }

    public function checkoutConfig()
    {
        return $this->hasOne('RZP\Models\Payment\Config\Entity');
    }

    public function offers()
    {
        return $this->morphToMany(
                        Offer\Entity::class,
                        'entity',
                        Table::ENTITY_OFFER)
                    ->withTimestamps();
    }

    public function virtualAccount()
    {
        return $this->morphOne('RZP\Models\VirtualAccount\Entity', 'entity');
    }

    public function upiMandate()
    {
        return $this->hasOne('RZP\Models\UpiMandate\Entity');
    }

    public function associateOffer(Offer\Entity $offer)
    {
        // Creates row in entity_offers table
        $this->offers()->attach($offer);
    }

    public function bankAccount()
    {
        return $this->hasOne(
            'RZP\Models\BankAccount\Entity', 'entity_id', self::ID);
    }

    public function transfers()
    {
        return $this->morphMany(Transfer\Entity::class, 'source');
    }

    public function products()
    {
        return $this->hasMany(Product\Entity::class);
    }

    public function orderMetas()
    {
        return $this->hasMany(OrderMeta\Entity::class);
    }

/** End Related Models */

    /*
     * We need to add the token if token is non-null
     * */
    public function toArrayPublic()
    {
        $arrayPublic = parent::toArrayPublic();

        $token = $this->getTokenRegistration();

        if ($token !== null)
        {
            $invoice = $this->getMethod() === Payment\Method::NACH ? $this->invoice : null;

            $arrayPublic[self::TOKEN] = $token->toArrayTokenFields($invoice);

            // Doing this as per the requirement for the orders api response for CAW Card methods.
            if (($this->getMethod() === null) or
                ($this->getMethod() === Payment\Method::CARD))
            {
                $arrayPublic[self::METHOD] = $this->getMethod();
                unset($arrayPublic[self::PAYMENTS]);
                unset($arrayPublic[self::TOKEN][SubscriptionRegistration\Entity::NOTES]);
                unset($arrayPublic[self::TOKEN][SubscriptionRegistration\Entity::METHOD]);
                unset($arrayPublic[self::TOKEN][SubscriptionRegistration\Entity::CURRENCY]);
                unset($arrayPublic[self::TOKEN][SubscriptionRegistration\Entity::AUTH_TYPE]);
                unset($arrayPublic[self::TOKEN][SubscriptionRegistration\Entity::FAILURE_REASON]);
                unset($arrayPublic[self::TOKEN][SubscriptionRegistration\Entity::RECURRING_STATUS]);
                unset($arrayPublic[self::TOKEN][SubscriptionRegistration\Entity::FIRST_PAYMENT_AMOUNT]);
            }
        }

        return $arrayPublic;
    }

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

    /**
     * Save phonepe_switch_context value to provider_context if set.
     *
     * This is done because provider_context needs to be standardized later on
     *
     * @param  array $input
     * @return null
     */
    protected function generateProviderContext($input)
    {
        if (isset($input[Entity::PHONEPE_SWITCH_CONTEXT]) === true)
        {
            $this->setAttribute(self::PROVIDER_CONTEXT, $input[self::PHONEPE_SWITCH_CONTEXT]);
        }
    }

    /** End Generators */

    /** Setters And Getters */

    public function setAmount($amount)
    {
        return $this->setAttribute(self::AMOUNT, $amount);
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

    public function setAppOffer($appOffer)
    {
        return $this->setAttribute(self::APP_OFFER, $appOffer);
    }

    public function setAmountPaid(int $amountPaid)
    {
        $this->setAttribute(self::AMOUNT_PAID, $amountPaid);
    }

    public function setPartialPayment(bool $partialPayment)
    {
        $this->setAttribute(self::PARTIAL_PAYMENT, $partialPayment);
    }

    public function setAccountNumber(string $bank)
    {
        return $this->setAttribute(self::ACCOUNT_NUMBER, $bank);
    }

    public function setPayerName(string $bank)
    {
        return $this->setAttribute(self::PAYER_NAME, $bank);
    }

    public function setBank(string $bank)
    {
        return $this->setAttribute(self::BANK, $bank);
    }

    public function setMethod(string $method)
    {
        return $this->setAttribute(self::METHOD, $method);
    }

    public function setFirstPaymentMinAmount(int $amount = null)
    {
        return $this->setAttribute(self::FIRST_PAYMENT_MIN_AMOUNT, $amount);
    }

    public function setDiscount(bool $discount )
    {
        return $this->setAttribute(self::DISCOUNT, $discount);
    }

    public function setLateAuthConfigId(string $lateAuthConfigId)
    {
        return $this->setAttribute(self::LATE_AUTH_CONFIG_ID, $lateAuthConfigId);
    }

    public function setFeeConfigId(string $feeConfigId)
    {
        return $this->setAttribute(self::FEE_CONFIG_ID, $feeConfigId);
    }

    public function setPublicKey($publicKey)
    {
        $this->setAttribute(self::PUBLIC_KEY, $publicKey);
    }

    public function setReference8($reference8)
    {
        $this->setAttribute(self::REFERENCE8, $reference8);
    }

    public function setReceipt($receipt)
    {
        $this->setAttribute(self::RECEIPT, $receipt);
    }

    public function getLateAuthConfigId()
    {
        return $this->getAttribute(self::LATE_AUTH_CONFIG_ID);
    }

    public function getFeeConfigId()
    {
        return $this->getAttribute(self::FEE_CONFIG_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getPayerName()
    {
        return $this->getAttribute(self::PAYER_NAME);
    }

    public function getAmountPaid()
    {
        return $this->getAttribute(self::AMOUNT_PAID);
    }

    public function getAmountDue($payment = null)
    {
        return $this->getAttribute(self::AMOUNT_DUE) +  $this->getCodFeeIfApplicable($payment);
    }

    public function getFirstPaymentMinAmount()
    {
        return $this->getAttribute(self::FIRST_PAYMENT_MIN_AMOUNT);
    }

    public function getPaymentCapture()
    {
        return $this->getAttribute(self::PAYMENT_CAPTURE);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
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

    public function getAppOffer()
    {
        return (bool) $this->getAttribute(self::APP_OFFER);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getBankForNachMethod()
    {
        $tokenRegistration = $this->getTokenRegistration();

        if (($tokenRegistration === null) or
            ($tokenRegistration->paperMandate === null) or
            ($tokenRegistration->paperMandate->bankAccount === null))
        {
            return null;
        }

        $bank = $tokenRegistration->paperMandate->bankAccount->getBankCode();

        return $bank;
    }

    public function getTokenRegistration()
    {
        $invoice = $this->invoice;

        if ( $invoice !== null)
        {
            $externalEntity = $invoice->entity;

            if (($externalEntity instanceof SubscriptionRegistration\Entity) === true)
            {
                return $externalEntity;
            }
        }

        return null;
    }

    public function getAllowedLineItemTypes()
    {
        return self::ALLOWED_LINE_ITEM_TYPES;
    }

    public function getProductType()
    {
        return $this->getAttribute(self::PRODUCT_TYPE);
    }

    public function getProductId()
    {
        return $this->getAttribute(self::PRODUCT_ID);
    }

    public function getBankAccountAttribute()
    {
        $app = \App::getFacadeRoot();

        if ($this->relationLoaded('bankAccount') === true)
        {
            return $this->getRelation('bankAccount');
        }

        $apiBankAccount = $this->bankAccount()->first();

        if (isset($apiBankAccount) === true)
        {
            return $apiBankAccount;
        }

        try
        {
            $pgRouterBankAccountArray = $this->getAttribute('bank_account_data');
            if (empty($pgRouterBankAccountArray) === false)
            {
                $pgRouterBankAccount = (new BankAccount\Entity())->forceFill($pgRouterBankAccountArray);

                $pgRouterBankAccount->merchant()->associate($this->merchant);
                $pgRouterBankAccount->setAttribute(BankAccount\Entity::TYPE, E::ORDER);
                $pgRouterBankAccount->setAttribute(BankAccount\Entity::ENTITY_ID, $this->getId());

                $this->setRelation('bankAccount', $pgRouterBankAccount);

                return $pgRouterBankAccount;
            }
            else
            {

                return $apiBankAccount;
            }
        }
        catch (\Exception $ex)
        {
            $app['trace']->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PG_ROUTER_BANK_ACCOUNT_MATCH_ERROR,
                [
                    'data' => $ex->getMessage()
                ]);
        }

        return $apiBankAccount;
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

        // If partial payment is set to false, unset first_payment_min_amount
        if ($value === false)
        {
            $this->setAttribute(self::FIRST_PAYMENT_MIN_AMOUNT, null);
        }
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

    public function getPublicKey()
    {
        return $this->getAttribute(self::PUBLIC_KEY);
    }

    public function getReference8()
    {
        return $this->getAttribute(self::REFERENCE8);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function hasOffers(): bool
    {
        return (($this->offers !== null) and (is_array($this->offers) === false) and ($this->offers->isNotEmpty() === true));
    }

    public function hasOrderMeta(): bool
    {
        return ($this->orderMetas->isNotEmpty() === true);
    }

    public function isInternational(): bool
    {
        return ($this->getCurrency() !== Currency::INR);
    }

    protected function setPublicOffersAttribute(array & $array)
    {
        if ($this->hasOffers() === true)
        {
            $offers = $this->offers;

            //
            // For backward compatibility
            //
            if ($offers->count() === 1)
            {
                $array[self::OFFER_ID] = $offers->first()->getPublicId();
            }

            $array[self::OFFERS] = $offers->getPublicIds();
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

    protected function setInternalOffersAttribute(array & $array)
    {
        $this->setPublicOffersAttribute($array);
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

    protected function setInternalDiscountAttribute(array & $array)
    {
        $this->setPublicDiscountAttribute($array);
    }

    public function setPublicCheckoutConfigIdAttribute(array & $array)
        {
            if ((isset($array[self::CHECKOUT_CONFIG_ID]) === true) and
                 ($this->merchant->isFeatureEnabled(Constants::SEND_PAYMENT_CONFIG_ID) === true))
            {
                $array[self::CHECKOUT_CONFIG_ID] = Config\Entity::getSignedId($array[self::CHECKOUT_CONFIG_ID]);
            }
            else
            {
                unset($array[self::CHECKOUT_CONFIG_ID]);
            }
        }

    public function setInternalCheckoutConfigIdAttribute(array & $array)
    {
        $this->setPublicCheckoutConfigIdAttribute($array);
    }

    public function setPublicProductsAttribute(array & $array)
    {
        if (($this->products !== null) and (is_array($this->products) === false) and
            (count($this->products) > 0))
        {
            $array[self::PRODUCTS] = $this->products->toArrayPublic()['items'];
        }
        else
        {
            unset($array[self::PRODUCTS]);
        }
    }

    public function setInternalProductsAttribute(array & $array)
    {
        $this->setPublicProductsAttribute($array);
    }

    public function setPublicTaxInvoiceAttribute(array & $array)
    {
        $orderMetaArray = $this->orderMetas;

        if (($orderMetaArray !== null) and
            (count($orderMetaArray) > 0))
        {
            foreach ($orderMetaArray as $orderMeta)
            {
                if ($orderMeta->getType() === Type::TAX_INVOICE)
                {
                    $array[Type::TAX_INVOICE] = $orderMeta->getValue();

                    return;
                }
            }
        }

        unset($array[Type::TAX_INVOICE]);
    }

    public function setInternalTaxInvoiceAttribute(array & $array)
    {
        $this->setPublicTaxInvoiceAttribute($array);
    }

    public function setPublicTransfersAttribute(array & $array)
    {
        if (isset($array[self::TRANSFERS]) === false)
        {
            unset($array[self::TRANSFERS]);
        }
    }

    public function setInternalTransfersAttribute(array & $array)
    {
        $this->setPublicTransfersAttribute($array);
    }

    public function isMagicCheckoutOrder() : bool
    {
        if(!$this->hasOrderMeta())
        {
            return false;
        }

        $orderMetaArray = $this->orderMetas;

        if (($orderMetaArray !== null) and (count($orderMetaArray) > 0))
        {
            foreach ($orderMetaArray as $orderMeta) {
                if ($orderMeta->getType() === Type::ONE_CLICK_CHECKOUT)
                {
                    $value = $orderMeta->getValue();

                    if (empty($value) === false)
                    {
                        $lineItemstotal = $value['line_items_total'];

                        return empty($lineItemstotal) === false;
                    }
                }
            }
        }

        return false;
    }

    public function setPublicOrderMeta1ccAttribute(array & $array)
    {
        $orderMetaArray = $this->orderMetas;

        if (($orderMetaArray !== null) and
            (count($orderMetaArray) > 0))
        {
            foreach ($orderMetaArray as $orderMeta)
            {
                if ($orderMeta->getType() === Type::ONE_CLICK_CHECKOUT)
                {
                    $value = $orderMeta->getValue();

                    foreach ($value as $key => $val)
                    {
                        if($key === Fields::CUSTOMER_DETAILS)
                        {
                            foreach ($val as $customerDetailsKey => $customerDetailsVal)
                            {
                                if($customerDetailsKey !== Fields::CUSTOMER_DETAILS_DEVICE)
                                {
                                    $array[$key][$customerDetailsKey] = $customerDetailsVal;
                                }
                            }
                        }
                        else
                        {
                            $array[$key] = $val;
                        }
                    }
                    return;
                }
            }
        }
    }

    public function setInternalOrderMeta1ccAttribute(array & $array)
    {
        $orderMetaArray = $this->orderMetas;

        if (($orderMetaArray !== null) and
            (count($orderMetaArray) > 0))
        {
            foreach ($orderMetaArray as $orderMeta)
            {
                if ($orderMeta->getType() === Type::ONE_CLICK_CHECKOUT)
                {
                    $value = $orderMeta->getValue();

                    foreach ($value as $key => $val)
                    {
                        $array[$key] = $val;
                    }
                    return;
                }
            }
        }
    }

    protected function modifyCheckoutConfigId(& $input)
    {
        if (empty($input[self::CHECKOUT_CONFIG_ID]) === false)
        {
           $input[self::CHECKOUT_CONFIG_ID] =   Config\Entity::verifyIdAndStripSign($input[self::CHECKOUT_CONFIG_ID]);
        }
    }


    protected function getCodFeeIfApplicable($payment)
    {
        $fee = 0;

        if ($payment === null)
        {
            return $fee;
        }

        if ($payment->isCod() === false)
        {
            return $fee;
        }

        if ($this->orderMetas === null)
        {
            return $fee;
        }
        foreach ($this->orderMetas as $orderMeta)
        {
            if ($orderMeta->getType() !== OrderMeta\Type::ONE_CLICK_CHECKOUT)
            {
                continue;
            }

            $value = $orderMeta->getValue();

            if (isset($value[OrderMeta\Order1cc\Fields::COD_FEE]) === false)
            {
                continue;
            }

            $pricingResponse = (new Merchant\OneClickCheckout\Core())->get1CcPricingObject($this->getPublicId());

            $fee += $pricingResponse[Merchant\OneClickCheckout\Constants::FINAL_ADJUSTED_COD_VALUE];
        }

        return $fee;
    }

    /**
     * checks if an order contains the 1cc meta key
     * @param void
     * @return bool
     */
    public function is1ccOrder(): bool
    {
        $is1ccOrder = false;

        foreach ($this->orderMetas as $meta)
        {
            if ($meta->getType() === OrderMeta\Type::ONE_CLICK_CHECKOUT)
            {
                $is1ccOrder = true;
                break;
            }
        }

        return $is1ccOrder;
    }

    /**
     * checks if the 1cc order was made by a shopify
     * merchant by checking mandatory notes attribute
     * @param void
     * @return bool
     */
    public function is1ccShopifyOrder(): bool
    {
        $is1ccOrder = $this->is1ccOrder();

        return $is1ccOrder === true and isset($this->getNotes()['storefront_id']) === true;
    }

    /*
     * We need to add the token if token is non-null
     * */
    public function toCodOrderArray()
    {
        $app = App::getFacadeRoot();

        $arrayPublic = $this->toArrayPublic();

        $orderMetaArray = $this->orderMetas;

        foreach ($orderMetaArray as $orderMeta)
        {
            $value = $orderMeta->getValue();

            $arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RISK_TIER] = $value[OrderMeta\Order1cc\Fields::COD_INTELLIGENCE][OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RISK_TIER] ?? Null;

            $arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_REASONS] = $value[OrderMeta\Order1cc\Fields::COD_INTELLIGENCE][OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_REASONS] ?? Null;

            $arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_CATEGORY] = $value[OrderMeta\Order1cc\Fields::COD_INTELLIGENCE][OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_CATEGORY] ?? Null;

            $arrayPublic[OrderMeta\Order1cc\Fields::REVIEW_STATUS] = $value[OrderMeta\Order1cc\Fields::REVIEW_STATUS] ?? Null;

            $arrayPublic[OrderMeta\Order1cc\Fields::REVIEWED_BY] = $value[OrderMeta\Order1cc\Fields::REVIEWED_BY] ?? Null;

            $arrayPublic[OrderMeta\Order1cc\Fields::REVIEWED_AT] = $value[OrderMeta\Order1cc\Fields::REVIEWED_AT] ?? Null;

            $arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] = $value[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] ?? Null;

            if ($arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] != Null) {

                $magicPaymentLinkData = $arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK];
                $magicPaymentLinkData[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK_STATUS] =
                    OrderMeta\Order1cc\Constants::MAGIC_PAYMENT_LINK_REVERSE_STATUS_MAPPING[
                        $magicPaymentLinkData[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK_STATUS]
                    ];

                $arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] = $magicPaymentLinkData;
            }

            if (isset($arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_REASONS]))
            {
                $reasons = $app['rto_feature_reason_provider_service']->getRTOReasons($arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_REASONS]);

                $arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_REASONS] = $reasons;
            }

            if (empty($arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_CATEGORY]))
            {
                $arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RTO_CATEGORY] = null;
            }

        }

        return $arrayPublic;
    }

    public function toPrepayOrderArray()
    {
        $arrayPublic = $this->toArrayPublic();

        $orderMetaArray = $this->orderMetas;

        foreach ($orderMetaArray as $orderMeta)
        {
            $value = $orderMeta->getValue();

            unset($arrayPublic[OrderMeta\Order1cc\Fields::CUSTOMER_DETAILS],
                $arrayPublic[OrderMeta\Order1cc\Fields::SHIPPING_FEE],
                $arrayPublic[OrderMeta\Order1cc\Fields::COD_FEE],
                $arrayPublic[OrderMeta\Order1cc\Fields::PROMOTIONS]);

            $arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] = $value[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] ?? Null;

            if ($arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] != null) {

                $magicPaymentLinkData = $arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK];
                $magicPaymentLinkData[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK_STATUS] =
                    OrderMeta\Order1cc\Constants::MAGIC_PAYMENT_LINK_REVERSE_STATUS_MAPPING[
                        $magicPaymentLinkData[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK_STATUS]
                    ];

                $arrayPublic[OrderMeta\Order1cc\Fields::MAGIC_PAYMENT_LINK] = $magicPaymentLinkData;
            }

            $arrayPublic[OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RISK_TIER] = $value[OrderMeta\Order1cc\Fields::COD_INTELLIGENCE][OrderMeta\Order1cc\Fields::COD_ELIGIBILITY_RISK_TIER] ?? Null;

        }

        return $arrayPublic;
    }
}
