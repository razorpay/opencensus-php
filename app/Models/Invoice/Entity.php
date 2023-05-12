<?php

namespace RZP\Models\Invoice;

use App;
use Lib\Gstin;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Item;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Address;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\PaymentLink;
use RZP\Models\Currency\Currency;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Plan\Subscription;
use RZP\Exception\LogicException;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\SubscriptionRegistration;

/**
 * @property Subscription\Entity             $subscription
 * @property Order\Entity                    $order
 * @property Merchant\Entity                 $merchant
 * @property SubscriptionRegistration\Entity $tokenRegistration
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    /**
     * Prefix for pdf file name
     */
    const PDF_PREFIX               = 'pdfs/';

    // ------------------ Entity Keys --------------------------------

    const ORDER_ID                  = 'order_id';
    const RECEIPT                   = 'receipt';
    const INVOICE_NUMBER            = 'invoice_number';
    const MERCHANT_ID               = 'merchant_id';
    const SUBSCRIPTION_ID           = 'subscription_id';
    const BATCH_ID                  = 'batch_id';
    const IDEMPOTENCY_KEY           = 'idempotency_key';
    const CUSTOMER_ID               = 'customer_id';
    const CUSTOMER_NAME             = 'customer_name';
    const CUSTOMER_EMAIL            = 'customer_email';
    const CUSTOMER_CONTACT          = 'customer_contact';
    const CUSTOMER_GSTIN            = 'customer_gstin';
    const CUSTOMER_BILLING_ADDR_ID  = 'customer_billing_addr_id';
    const CUSTOMER_SHIPPING_ADDR_ID = 'customer_shipping_addr_id';
    const STATUS                    = 'status';
    const SUBSCRIPTION_STATUS       = 'subscription_status';
    const DATE                      = 'date';
    const DUE_BY                    = 'due_by';
    const SCHEDULED_AT              = 'scheduled_at';
    const ISSUED_AT                 = 'issued_at';
    const PAID_AT                   = 'paid_at';
    const CANCELLED_AT              = 'cancelled_at';
    const EXPIRED_AT                = 'expired_at';

    const EXPIRE_BY                 = 'expire_by';
    const BIG_EXPIRE_BY             = 'big_expire_by';

    const EMAIL_STATUS              = 'email_status';
    const SMS_STATUS                = 'sms_status';
    const DESCRIPTION               = 'description';
    const MERCHANT_GSTIN            = 'merchant_gstin';
    const MERCHANT_LABEL            = 'merchant_label';
    const ENTITY_TYPE               = 'entity_type';
    const ENTITY_ID                 = 'entity_id';
    const STATUSES                  = 'statuses';
    const INTERNATIONAL             = 'international';
    const SUBSCRIPTIONS             = 'subscriptions';
    const REMINDER_STATUS           = 'reminder_status';
    const BATCH_OFFSET              = 'batch_offset';
    const OFFER_AMOUNT              = 'offer_amount';
    const REF_NUM                   = 'ref_num';

    /**
     * Captures the Place of Supply GSTIN code for the invoice. (Ex: '05', '31', '35' etc.)
     * Value of this field would be valid GSTIN (For India 2 digit numeric number). We can't use state code(such as. BR,
     * KA etc) because those are not standard yet. For example for Bihar there is 2 state code used by different govt.
     * departments but both of them point to same GSTIN number.
     *
     * Ref: Lib\GSTIN::$gstinToStateCodeMap
     *
     */
    const SUPPLY_STATE_CODE         = 'supply_state_code';
    const TERMS                     = 'terms';
    const NOTES                     = 'notes';
    const COMMENT                   = 'comment';
    const SHORT_URL                 = 'short_url';
    const VIEW_LESS                 = 'view_less';

    /**
     * If set to true, partial payments would be accepted
     * against this invoice and invoice status would move
     * to PARTIALLY_PAID in those cases.
     * Once there is no due amount left, it goes to PAID.
     */
    const PARTIAL_PAYMENT          = 'partial_payment';

    const GROSS_AMOUNT             = 'gross_amount';
    const TAX_AMOUNT               = 'tax_amount';
    const TAXABLE_AMOUNT           = 'taxable_amount';
    const AMOUNT                   = 'amount';

    const FIRST_PAYMENT_MIN_AMOUNT = 'first_payment_min_amount';

    /**
     * Following two attributes are looked up from corresponding
     * order entity. Order maintains 'amount_paid'. If no order
     * has been created for invoice till now, followings will be
     * null.
     */
    const AMOUNT_PAID              = 'amount_paid';
    const AMOUNT_DUE               = 'amount_due';
    const CURRENCY                 = 'currency';
    const CURRENCY_SYMBOL          = 'currency_symbol';
    const USER_ID                  = 'user_id';
    const SOURCE                   = 'source';
    const BILLING_START            = 'billing_start';
    const BILLING_END              = 'billing_end';
    const TYPE                     = 'type';

    /**
     * This is consumed by clients, if set to true they would show
     * taxes and discounts of all line items grouped an once at
     * the bottom of invoice.
     */
    const GROUP_TAXES_DISCOUNTS    = 'group_taxes_discounts';


    /**
     * Post payment hosted page sends back control to following
     * callback URL via specified method (currently only GET).
     */
    const CALLBACK_URL             = 'callback_url';
    const CALLBACK_METHOD          = 'callback_method';

    const INTERNAL_REF             = 'internal_ref';

    const NACH_FORM_URL            = 'nach_form_url';

    const DELETED_AT               = 'deleted_at';

    // ---------------------- Input Keys -----------------------------

    const LINE_ITEMS               = 'line_items';
    const CUSTOMER                 = 'customer';
    const EMAIL_NOTIFY             = 'email_notify';
    const SMS_NOTIFY               = 'sms_notify';
    const DRAFT                    = 'draft';
    const BATCH_IDS                = 'batch_ids';
    const TYPES                    = 'types';
    const REMINDER_ENABLE          = 'reminder_enable';

    const OPTIONS_KEY              = 'options';

    // ---------------------- Input Keys End -------------------------

    // ------------------------- Output Keys -------------------------

    const CUSTOMER_DETAILS         = 'customer_details';
    const PAYMENT_ID               = 'payment_id';
    const URL                      = 'url';
    // Boolean holding 'has address or supply state name' value to be used in view
    const HAS_ADDRESS_OR_POS       = 'has_address_or_pos';

    // ------------------------ Output Keys End ----------------------


    const EMAIL                          = 'email';
    const NAME                           = 'name';
    const CONTACT                        = 'contact';
    const SMS                            = 'sms';
    const ITEMS                          = 'items';
    const IS_PAID                        = 'is_paid';
    const SUPPLY_STATE_NAME              = 'supply_state_name';
    const BILLING_ADDRESS_TEXT           = 'billing_address_text';
    const SHIPPING_ADDRESS_TEXT          = 'shipping_address_text';
    const AUTH_LINK_STATUS               = 'auth_link_status';
    const IS_CONTACT_OR_EMAIL_PRESENT    = 'is_contact_or_email_present';

    const DEFAULT_DUE_DAYS         = 60;

    //
    // For now the default value is same across merchants,
    // later it can be configurable at merchant's level.
    //
    const DEFAULT_EXPIRY_DAYS      = 60;

    // ------------------------ Relation Keys ------------------------

    const ORDER                    = 'order';
    const PAYMENTS                 = 'payments';
    const USER                     = 'user';

    // -------------------------- Stats Keys -------------------------

    const TOTAL_COUNT              = 'batch_total';
    const ISSUED_COUNT             = 'issued_count';
    const CREATED_COUNT            = 'created_count';
    const PAID_COUNT               = 'paid_count';
    const EXPIRED_COUNT            = 'expired_count';

    const SWITCH_TO                = 'switch_to';

    const SIGNED_PDF_URL           = 'signed_pdf_url';
    // ------------------------ Other constants ----------------------

    const ALLOWED_LINE_ITEM_TYPES_INVOICE = [
        Item\Type::INVOICE,
    ];

    const ALLOWED_LINE_ITEM_TYPES_SUBSCRIPTION_INVOICE = [
        Item\Type::PLAN,
        Item\Type::ADDON,
    ];

    protected static $sign         = 'inv';

    protected static $v2Sign       = 'plink';

    protected $entity              = 'invoice';

    protected $generateIdOnCreate  = true;

    protected $embeddedRelations   = [
        self::LINE_ITEMS,
    ];

    protected $ignoredRelations = [
        self::ORDER
    ];

    protected $validOperations = [
        // Core's actions
        'create',
        'update',
        'delete',
        'issue',
        'cancelInvoice',
        'expireInvoice',
        'sendNotification',
        'sendSubscriptionNotification',
        'sendPPReceiptNotification',
        'addLineItems',
        'addManyLineItems',
        'updateLineItem',
        'removeLineItem',
        'removeManyLineItems',
        'deleteInvoice',

        // Notifier's actions
        'notifyInvoiceIssued',
        'notifyInvoiceExpired',
    ];

    protected $defaults = [
        self::ORDER_ID                  => null,
        self::STATUS                    => Status::ISSUED,
        self::SUBSCRIPTION_STATUS       => null,
        self::SUBSCRIPTION_ID           => null,
        self::DATE                      => null,
        self::ISSUED_AT                 => null,
        self::PAID_AT                   => null,
        self::CANCELLED_AT              => null,
        self::EXPIRED_AT                => null,
        self::EXPIRE_BY                 => null,
        self::BIG_EXPIRE_BY             => null,
        self::RECEIPT                   => null,
        self::REF_NUM                   => null,
        self::MERCHANT_GSTIN            => null,
        self::MERCHANT_LABEL            => null,
        self::SUPPLY_STATE_CODE         => null,
        self::DESCRIPTION               => null,
        self::NOTES                     => [],
        self::COMMENT                   => null,
        self::TERMS                     => null,
        self::SHORT_URL                 => null,
        self::VIEW_LESS                 => 1,
        self::TYPE                      => Type::INVOICE,
        self::USER_ID                   => null,
        self::PARTIAL_PAYMENT           => false,
        self::FIRST_PAYMENT_MIN_AMOUNT  => null,
        self::GROSS_AMOUNT              => null,
        self::TAX_AMOUNT                => null,
        self::AMOUNT                    => null,
        self::CURRENCY                  => 'INR',
        self::BILLING_START             => null,
        self::BILLING_END               => null,
        self::CUSTOMER_NAME             => null,
        self::CUSTOMER_EMAIL            => null,
        self::CUSTOMER_CONTACT          => null,
        self::CUSTOMER_GSTIN            => null,
        self::CUSTOMER_BILLING_ADDR_ID  => null,
        self::CUSTOMER_SHIPPING_ADDR_ID => null,
        self::GROUP_TAXES_DISCOUNTS     => false,
        self::CALLBACK_URL              => null,
        self::CALLBACK_METHOD           => null,
    ];

    protected static $generators = [
        self::DATE,
        self::DUE_BY,
        self::SCHEDULED_AT,
        self::EMAIL_STATUS,
        self::SMS_STATUS,
        self::STATUS,
    ];

    protected $fillable = [
        self::EMAIL_STATUS,
        self::SMS_STATUS,
        self::DATE,
        self::TERMS,
        self::PARTIAL_PAYMENT,
        self::FIRST_PAYMENT_MIN_AMOUNT,
        self::AMOUNT,
        self::DESCRIPTION,
        self::NOTES,
        self::COMMENT,
        self::RECEIPT,
        self::VIEW_LESS,
        self::CURRENCY,
        self::SOURCE,
        self::TYPE,
        self::BILLING_START,
        self::BILLING_END,
        self::EXPIRE_BY,
        self::SUPPLY_STATE_CODE,
        self::CALLBACK_URL,
        self::CALLBACK_METHOD,
        self::INTERNAL_REF,
        self::IDEMPOTENCY_KEY,
        self::OFFER_AMOUNT,
        self::REF_NUM,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::RECEIPT,
        self::INVOICE_NUMBER,
        self::STATUS,
        self::SUBSCRIPTION_STATUS,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::SUBSCRIPTION_ID,
        self::ORDER_ID,
        self::ORDER,
        self::PAYMENT_ID,
        self::DUE_BY,
        self::EXPIRED_AT,
        self::EXPIRE_BY,
        self::SCHEDULED_AT,
        self::ISSUED_AT,
        self::PAID_AT,
        self::CANCELLED_AT,
        self::CUSTOMER_DETAILS,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::MERCHANT_ID,
        self::DATE,
        self::REF_NUM,
        self::MERCHANT_GSTIN,
        self::MERCHANT_LABEL,
        self::SUPPLY_STATE_CODE,
        self::DESCRIPTION,
        self::TERMS,
        self::NOTES,
        self::COMMENT,
        self::CURRENCY,
        self::CURRENCY_SYMBOL,
        self::SHORT_URL,
        self::VIEW_LESS,
        self::SOURCE,
        self::TYPE,
        self::PARTIAL_PAYMENT,
        self::GROUP_TAXES_DISCOUNTS,
        self::CALLBACK_URL,
        self::CALLBACK_METHOD,
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::FIRST_PAYMENT_MIN_AMOUNT,
        self::BILLING_START,
        self::BILLING_END,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::TAXABLE_AMOUNT,
        self::USER_ID,
        self::INTERNAL_REF,
        self::IDEMPOTENCY_KEY,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::RECEIPT,
        self::INVOICE_NUMBER,
        self::CUSTOMER_ID,
        self::CUSTOMER_DETAILS,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
        self::LINE_ITEMS,
        self::PAYMENT_ID,
        self::PAYMENTS,
        self::STATUS,
        self::EXPIRE_BY,
        self::ISSUED_AT,
        self::PAID_AT,
        self::CANCELLED_AT,
        self::EXPIRED_AT,
        self::SMS_STATUS,
        self::EMAIL_STATUS,
        self::DATE,
        self::TERMS,
        self::PARTIAL_PAYMENT,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::TAXABLE_AMOUNT,
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::FIRST_PAYMENT_MIN_AMOUNT,
        self::CURRENCY,
        self::CURRENCY_SYMBOL,
        self::DESCRIPTION,
        self::NOTES,
        self::COMMENT,
        self::SHORT_URL,
        self::VIEW_LESS,
        self::BILLING_START,
        self::BILLING_END,
        self::TYPE,
        self::GROUP_TAXES_DISCOUNTS,
        self::SUPPLY_STATE_CODE,
        self::SUBSCRIPTION_STATUS,
        self::USER_ID,
        self::USER,
        self::CREATED_AT,
        self::IDEMPOTENCY_KEY,
        self::REMINDER_STATUS,
        self::REF_NUM,
    ];

    /**
     * {@inheritDoc}
     */
    protected $hosted = [
        self::ID,
        self::ENTITY,
        self::RECEIPT,
        self::INVOICE_NUMBER,
        self::CUSTOMER_ID,
        self::CUSTOMER_DETAILS,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
        self::LINE_ITEMS,
        self::PAYMENT_ID,
        self::STATUS,
        self::EXPIRE_BY,
        self::ISSUED_AT,
        self::PAID_AT,
        self::CANCELLED_AT,
        self::EXPIRED_AT,
        self::DATE,
        self::TERMS,
        self::PARTIAL_PAYMENT,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::TAXABLE_AMOUNT,
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::FIRST_PAYMENT_MIN_AMOUNT,
        self::CURRENCY,
        self::CURRENCY_SYMBOL,
        self::DESCRIPTION,
        self::COMMENT,
        self::SHORT_URL,
        self::TYPE,
        self::GROUP_TAXES_DISCOUNTS,
        self::SUPPLY_STATE_CODE,
        self::SUBSCRIPTION_STATUS,
        self::NACH_FORM_URL,
        self::CREATED_AT,
        self::OFFER_AMOUNT,
        self::REF_NUM,
    ];

    protected $appends = [
        self::PUBLIC_ID,
        self::ENTITY,
        self::CUSTOMER_DETAILS,
        self::PAYMENT_ID,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::INVOICE_NUMBER,
        self::TAXABLE_AMOUNT,
        self::CURRENCY_SYMBOL,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
        self::SUBSCRIPTION_STATUS,
        self::SUPPLY_STATE_CODE,
        self::USER_ID,
        self::FIRST_PAYMENT_MIN_AMOUNT,
        self::REMINDER_STATUS,
    ];

    protected $casts = [
        self::VIEW_LESS                => 'bool',
        self::PARTIAL_PAYMENT          => 'bool',
        self::GROSS_AMOUNT             => 'int',
        self::TAX_AMOUNT               => 'int',
        self::TAXABLE_AMOUNT           => 'int',
        self::AMOUNT                   => 'int',
        self::AMOUNT_PAID              => 'int',
        self::AMOUNT_DUE               => 'int',
        self::FIRST_PAYMENT_MIN_AMOUNT => 'int',
        self::BILLING_START            => 'int',
        self::BILLING_END              => 'int',
        self::GROUP_TAXES_DISCOUNTS    => 'bool',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_PAID,
        self::AMOUNT_DUE,
        self::FIRST_PAYMENT_MIN_AMOUNT,
    ];

    /**
     * Note: Reports currently works for type:link only.
     * @var array
     */
    protected $hiddenInReport = [
        self::INVOICE_NUMBER,
        self::CUSTOMER_DETAILS,
        self::ORDER_ID,
        self::SUBSCRIPTION_ID,
        self::LINE_ITEMS,
        self::PAYMENT_ID,
        self::GROSS_AMOUNT,
        self::TAX_AMOUNT,
        self::TAXABLE_AMOUNT,
        self::COMMENT,
        self::VIEW_LESS,
        self::BILLING_START,
        self::BILLING_END,
        self::TYPE,
        self::GROUP_TAXES_DISCOUNTS,
        self::FIRST_PAYMENT_MIN_AMOUNT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DATE,
        self::EXPIRE_BY,
        self::BIG_EXPIRE_BY,
        self::ISSUED_AT,
        self::PAID_AT,
        self::EXPIRED_AT,
        self::CANCELLED_AT,
    ];

    const DELETE_ALLOWED_STATUSES = [
        Status::EXPIRED,
        Status::CANCELLED,
    ];

    const UPDATE_BLOCKED_END_STATES = [
        Status::EXPIRED,
        Status::CANCELLED,
    ];

    // -------------------------------------- Mutators ---------------

    // Following 2 mutators are for converting '' (empty strings)
    // input to null.

    public function setDateAttribute($date)
    {
        if (empty($date) === true)
        {
            $date = null;
        }

        $this->attributes[self::DATE] = $date;
    }

    public function setExpireByAttribute($expireBy)
    {
        if (empty($expireBy) === true)
        {
            $expireBy = null;
        }

        // This hardcoded here until Invoice table is not partitioned,
        // once it is partitioned, modify EXPIRE_BY to BIG_INT and
        // move the BIG_EXPIRE_BY to EXPIRE_BY
        if ($expireBy > 2147483647) {
            $this->attributes[self::BIG_EXPIRE_BY] = $expireBy;
        } else {
            $this->attributes[self::EXPIRE_BY] = $expireBy;
        }
    }

    // -------------------------------------- End Mutators -----------

    // -------------------------------------- Getters ----------------

    public static function getV2Sign()
    {
        return self::$v2Sign;
    }

    public function getEmailStatus()
    {
        return $this->getAttribute(self::EMAIL_STATUS);
    }

    public function getSmsStatus()
    {
        return $this->getAttribute(self::SMS_STATUS);
    }


    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function toArrayPublic()
    {
        $publicArray = parent::toArrayPublic();

        // Until BIG_EXPIRE_BY and EXPIRE_BY columns are merged
        if ( array_key_exists(self::EXPIRE_BY, $publicArray) === true ) {
            $publicArray[self::EXPIRE_BY] = $this->getExpireBy();
        }

        $order = $this->order;

        if ($order !== null)
        {
            $tokenRegistration = $order->getTokenRegistration();

            if ($tokenRegistration !== null)
            {
                $publicArray[self::AUTH_LINK_STATUS] = $tokenRegistration->getAuthLinkStatus($this, $order);

                if (($order->products !== null) and
                    (is_array($order->products) === false))
                {
                    $productsArray = $order->products->toArrayPublic()['items'] ?? [];

                    if (sizeof($productsArray) > 0)
                    {
                        $publicArray['products'] = $productsArray;
                    }
                }
            }
        }

        if (($order !== null) and
            ($order->getMethod() === Payment\Method::NACH))
        {
            $token = $order->toArrayPublic()[Order\Entity::TOKEN] ?? [];

            $publicArray[Order\Entity::TOKEN] = $token;

            $nachFormUrl = $token[SubscriptionRegistration\Entity::NACH][SubscriptionRegistration\Entity::PREFILLED_FORM_TRANSIENT] ?? null;

            $publicArray[self::NACH_FORM_URL] = $nachFormUrl;
        }

        if (app('basicauth')->isProxyAuth() === true)
        {
            if ($this->getOfferAmount() !== null and
                ($this->getOfferAmount() > 0) === true)
            {
                $publicArray[Entity::OFFER_AMOUNT] = $this->getOfferAmount();

                if ($this->getComment() !== null)
                {
                    $comment = explode(';#$', $this->getComment());

                    $publicArray['offer_name'] = $comment[0] ?? '';

                    $publicArray['offer_display_text'] = $comment[1] ?? '';
                }
            }
        }

        return $publicArray;
    }

    public function getPublicCustomerId()
    {
        return Customer\Entity::getSignedIdOrNull($this->getCustomerId());
    }

    public function getCustomerName()
    {
        return $this->getAttribute(self::CUSTOMER_NAME);
    }

    public function getCustomerEmail()
    {
        return $this->getAttribute(self::CUSTOMER_EMAIL);
    }

    public function getCustomerContact()
    {
        return $this->getAttribute(self::CUSTOMER_CONTACT);
    }

    public function getCustomerGstin()
    {
        return $this->getAttribute(self::CUSTOMER_GSTIN);
    }

    public function getScheduledAt()
    {
        return $this->getAttribute(self::SCHEDULED_AT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getBillingStart()
    {
        return $this->getAttribute(self::BILLING_START);
    }

    public function getBillingEnd()
    {
        return $this->getAttribute(self::BILLING_END);
    }

    public function getShortUrl()
    {
        return $this->getAttribute(self::SHORT_URL);
    }

    public function getOrderId()
    {
        return $this->getAttribute(self::ORDER_ID);
    }

    public function isPartialPaymentAllowed()
    {
        return $this->getAttribute(self::PARTIAL_PAYMENT);
    }

    public function getGrossAmount()
    {
        return $this->getAttribute(self::GROSS_AMOUNT);
    }

    public function getOfferAmount()
    {
        return $this->getAttribute(self::OFFER_AMOUNT);
    }

    public function getAmountPaid()
    {
        return $this->getAttribute(self::AMOUNT_PAID);
    }

    public function getAmountDue()
    {
        return $this->getAttribute(self::AMOUNT_DUE);
    }

    public function getFormattedAmount()
    {
        return number_format($this->getAmount() / 100, 2);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getFirstPaymentMinAmount()
    {
        return $this->getAttribute(self::FIRST_PAYMENT_MIN_AMOUNT);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getMerchantGstin()
    {
        return $this->getAttribute(self::MERCHANT_GSTIN);
    }

    public function getRefNum()
    {
        return $this->getAttribute(self::REF_NUM);
    }

    public function getMerchantLabel()
    {
        return $this->getAttribute(self::MERCHANT_LABEL);
    }

    public function getSupplyStateCode()
    {
        return $this->getAttribute(self::SUPPLY_STATE_CODE);
    }

    public function getSupplyStateName()
    {
        $code = $this->getSupplyStateCode();

        return $code !== null ? Gstin::getStateNameByCode($code) : null;
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getViewLess()
    {
        return $this->getAttribute(self::VIEW_LESS);
    }

    public function getSubscriptionStatus()
    {
        return $this->getAttribute(self::SUBSCRIPTION_STATUS);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getSubscriptionId()
    {
        return $this->getAttribute(self::SUBSCRIPTION_ID);
    }

    public function isOfSubscription()
    {
        return ($this->getAttribute(self::SUBSCRIPTION_ID) !== null);
    }

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getReceiptElsePublicId()
    {
        $receipt = $this->getReceipt();

        if ($receipt !== null)
        {
            return $receipt;
        }

        return $this->getPublicId();
    }

    public function getPaidAt()
    {
        return $this->getAttribute(self::PAID_AT);
    }

    public function getIssuedAt()
    {
        return $this->getAttribute(self::ISSUED_AT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getTypeLabel()
    {
        return Type::getLabel($this->getType());
    }

    public function getCallbackUrl()
    {
        return $this->getAttribute(self::CALLBACK_URL);
    }

    public function getCallbackMethod()
    {
        return $this->getAttribute(self::CALLBACK_METHOD);
    }

    public function hasBeenPaid()
    {
        return ($this->getStatus() === Status::PAID);
    }

    public function getDueBy()
    {
        return $this->getAttribute(self::DUE_BY);
    }

    public function getExpireBy()
    {
        $expireBy = $this->getAttribute(self::BIG_EXPIRE_BY) ?? $this->getAttribute(self::EXPIRE_BY) ;
        //$expireBy = $this->getAttribute(self::EXPIRE_BY);

        return $expireBy === null ? $expireBy: intval( $expireBy );
    }

    public function isDraft(): bool
    {
        return ($this->getStatus() === Status::DRAFT);
    }

    public function isIssued(): bool
    {
        return ($this->getStatus() === Status::ISSUED);
    }

    public function isPaid(): bool
    {
        return ($this->getStatus() === Status::PAID);
    }

    public function isPartiallyPaid(): bool
    {
        return ($this->getStatus() === Status::PARTIALLY_PAID);
    }

    public function isCancelled(): bool
    {
        return ($this->getStatus() === Status::CANCELLED);
    }

    public function isExpired(): bool
    {
        return ($this->getStatus() === Status::EXPIRED);
    }

    /**
     * Checks if expiry date has passed irrespective of the status
     *
     * @return bool
     */
    public function isPastExpireBy(): bool
    {
        $expireBy = $this->getExpireBy();

        if (empty($expireBy) === true)
        {
            return false;
        }

        return (Carbon::now()->timestamp > $expireBy);
    }

    public function hasCustomer(): bool
    {
        return $this->isAttributeNotNull(self::CUSTOMER_ID);
    }

    public function hasCustomerBillingAddress(): bool
    {
        return $this->isAttributeNotNull(self::CUSTOMER_BILLING_ADDR_ID);
    }

    public function hasCustomerShippingAddress(): bool
    {
        return $this->isAttributeNotNull(self::CUSTOMER_SHIPPING_ADDR_ID);
    }

    public function hasBatch(): bool
    {
        return $this->isAttributeNotNull(self::BATCH_ID);
    }

    public function hasSubscription(): bool
    {
        return $this->isAttributeNotNull(self::SUBSCRIPTION_ID);
    }

    public function isTypeLink(): bool
    {
        return ($this->getType() === Type::LINK);
    }

    public function isNotTypeInvoice(): bool
    {
        return ($this->isTypeInvoice() === false);
    }

    public function isTypeInvoice(): bool
    {
        return ($this->getType() === Type::INVOICE);
    }

    public function isTypeDCCEInvoice(): bool
    {
        return in_array($this->getType(), Type::getDCCEInvoiceTypes(), true);
    }

    public function isTypeOPGSPInvoice(): bool
    {
        return in_array($this->getType(), Type::getOPGSPInvoiceTypes(), true);
    }

    public function isFullyPaid(Payment\Entity $payment)
    {
        $trace = App::getFacadeRoot()['trace'];

        if($payment->discount !== null)
        {
            $trace->info(TraceCode::PAYMENT_CAPTURE_INVOICE_UPDATE_WITH_DISCOUNT,
                                      [
                                          'discount_amount' => $payment->discount->getAmount(),
                                      ]);

            $discount = $payment->discount->getAmount();

            // logic : paid amount + offer discount amount = PL amount
            if (($payment->getAmount() + $discount) === $this->getAmount())
            {
                return true;
            }
        }

        return $this->getAmount() == $this->getAmountPaid();
    }

    public function getAllowedLineItemTypes()
    {
        if ($this->isOfSubscription() === true)
        {
            return self::ALLOWED_LINE_ITEM_TYPES_SUBSCRIPTION_INVOICE;
        }
        else
        {
            return self::ALLOWED_LINE_ITEM_TYPES_INVOICE;
        }
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function isTypeOfSubscriptionRegistration(): bool
    {

        if ($this->getEntityType() === null)
        {
            return false;
        }

        try
        {
            $relation = $this->getRelation('entity');

            if($relation === null) {

                $trace = App::getFacadeRoot()['trace'];

                if ($this->getEntityType() === 'subscription_registration')
                {
                    $trace->info(TraceCode::INVOICE_ENTITY_TYPE,
                                ["Entity_type" => $this->getEntityType()]
                    );

                    return true;
                }
            }
        }
        catch (\Exception $e)
        {
            if ($this->getEntityType() === 'subscription_registration')
            {
                return true;
            }

            return false;
        }

        return ($this->getRelation('entity') instanceof SubscriptionRegistration\Entity);
    }

    public function isAuthlinkInvoice(): bool
    {
        return (($this->getEntityType() !== null) and
            ($this->entity instanceof SubscriptionRegistration\Entity));
    }

    public function isPaymentPageInvoice(): bool
    {
        if ($this->getEntityType() === null)
        {
            return false;
        }

        try
        {
            $relation = $this->getRelation('entity');
        }
        catch (\Exception $e)
        {
           if ($this->getEntityType() === 'payment_page')
           {
               return true;
           }

           return false;
        }

        return (($this->getEntityType() !== null)) and
            ($this->getRelation('entity') instanceof PaymentLink\Entity);
    }

    /**
     * Returns the path component of Dashboard view url.
     *
     * For invoices (New):   #/app/invoices/{public-id}
     * Otherwise (Existing): #/app/invoices/{public-id}/details
     *
     * @return string
     */
    public function getDashboardPath(): string
    {
        $path = '#/app/invoices/' . $this->getPublicId();

        if ($this->isTypeInvoice() === false)
        {
            $path .= '/details';
        }

        return $path;
    }

    /**
     * Returns string to be used a pdf file path in s3/local store.
     * Format: pdfs/{invoiceId}_{epoch}
     *
     * @return string
     */
    public function getPdfFilename(): string
    {
        return self::PDF_PREFIX . $this->getId() . '_' . time();
    }

    public function getPdfDisplayName(): string
    {
        // Expected format:
        // Invoice <Reciept/Invoice ID> from <Company> (<Paid/Unpaid>).pdf

        $receipt = $this->getReceiptElsePublicId();
        $from    = $this->merchant->getBillingLabel();
        $status  = $this->hasBeenPaid() ? 'Paid' : 'Unpaid';
        $ext     = FileStore\Format::PDF;

        $displayName = $this->isPaymentPageInvoice() ? "Receipt " : "Invoice ";

        $displayName = $displayName."$receipt from $from ($status).$ext";

        return sanitizeFilename($displayName);
    }

    /**
     * Gets dimensions for metrics around invoice module
     * @param  array $extra Additional key, value pair of dimensions
     * @return array
     */
    public function getMetricDimensions(array $extra = []): array
    {
        $extra =  $extra + [
            'type'             => (string) $this->getType(),
            'has_batch'        => (int) $this->hasBatch(),
            'has_subscription' => (int) $this->hasSubscription(),
            ];
        return  $extra;
    }

    public function getInternalRef()
    {
        return $this->getAttribute(self::INTERNAL_REF);
    }

    // -------------------------------------- End Getters ------------


    // -------------------------------------- Setters ----------------

    public function setCustomerDetails(Customer\Entity $customer)
    {
        // Sets basic attributes
        $this->setCustomerName($customer->getName());
        $this->setCustomerContact($customer->getContact());
        $this->setCustomerEmail($customer->getEmail());
        $this->setCustomerGstin($customer->getGstin(), $customer->merchant);

        // Retrieves primary billing and shipping addresses and associates the same with invoice
        $repo = App::getFacadeRoot()['repo'];

        $billingAddress = $repo->address->fetchPrimaryAddressOfEntityOfType($customer, Address\Type::BILLING_ADDRESS);
        $this->customerBillingAddress()->associate($billingAddress);

        $shippingAddr = $repo->address->fetchPrimaryAddressOfEntityOfType($customer, Address\Type::SHIPPING_ADDRESS);
        $this->customerShippingAddress()->associate($shippingAddr);
    }

    public function associateAndSetCustomerDetails(Customer\Entity $customer)
    {
        $this->customer()->associate($customer);

        $this->setCustomerDetails($customer);
    }

    public function unsetCustomerDetails()
    {
        $this->customer()->dissociate();
        $this->customerBillingAddress()->dissociate();
        $this->customerShippingAddress()->dissociate();

        $this->setCustomerName(null);
        $this->setCustomerContact(null);
        $this->setCustomerEmail(null);
        $this->setCustomerGstin(null, $this->merchant);
    }

    public function setCustomerName($customerName)
    {
        $this->setAttribute(self::CUSTOMER_NAME, $customerName);
    }

    public function setCustomerEmail($customerEmail)
    {
        $this->setAttribute(self::CUSTOMER_EMAIL, $customerEmail);
    }

    public function setCustomerContact($customerContact)
    {
        $this->setAttribute(self::CUSTOMER_CONTACT, $customerContact);
    }

    public function setCustomerGstin($customerGstin, $merchant)
    {
        if ($this->isGSTTaxationApplicable($merchant))
        {
            $this->setAttribute(self::CUSTOMER_GSTIN, $customerGstin);
        }
    }

    public function setSmsStatus($status)
    {
        if ($status !== null)
        {
            NotifyStatus::checkStatus($status);
        }

        $this->setAttribute(self::SMS_STATUS, $status);
    }

    public function setEmailStatus($status)
    {
        if ($status !== null)
        {
            NotifyStatus::checkStatus($status);
        }

        $this->setAttribute(self::EMAIL_STATUS, $status);
    }

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);

        // Sets corresponding timestamps as per new status
        if (in_array($status, Status::$timestampedStatuses, true) === true)
        {
            $timestampKey = $status . '_at';
            $currentTime = Carbon::now()->getTimestamp();

            $this->setAttribute($timestampKey, $currentTime);
        }
    }

    public function setSubscriptionStatus(string $subscriptionStatus)
    {
        Status::checkSubscriptionStatus($subscriptionStatus);

        $this->setAttribute(self::SUBSCRIPTION_STATUS, $subscriptionStatus);
    }

    public function setSubscriptionId(string $subscriptionId)
    {
        $this->setAttribute(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    public function setComment($comment)
    {
        if ($this->hasSubscription() === true or $this->isTypeDCCEInvoice() === true)
        {
            $this->setAttribute(self::COMMENT, $comment);
        }
    }

    public function setNotes(array $notes)
    {
        if ($this->hasSubscription() === true or $this->isTypeDCCEInvoice() === true)
        {
            $this->setAttribute(self::NOTES, $notes);
        }
    }

    public function setBatchId(string $batchId)
    {
        $this->setAttribute(self::BATCH_ID,$batchId);
    }

    public function setShortUrl(string $shortUrl)
    {
        $this->setAttribute(self::SHORT_URL, $shortUrl);
    }

    public function setBillingStart(int $billingStart)
    {
        $this->setAttribute(self::BILLING_START, $billingStart);
    }

    public function setBillingEnd(int $billingEnd)
    {
        $this->setAttribute(self::BILLING_END, $billingEnd);
    }

    public function setBillingPeriod(array $billingPeriod)
    {
        $this->setBillingStart($billingPeriod['start']);
        $this->setBillingEnd($billingPeriod['end']);
    }

    public function setGrossAmount(int $amount)
    {
        $this->setAttribute(self::GROSS_AMOUNT, $amount);
    }

    public function setOfferAmount(int $amount)
    {
        $this->setAttribute(self::OFFER_AMOUNT, $amount);
    }

    public function setTaxAmount(int $amount)
    {
        $this->setAttribute(self::TAX_AMOUNT, $amount);
    }

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setMerchantGstin(string $gstin = null)
    {
        $this->setAttribute(self::MERCHANT_GSTIN, $gstin);
    }

    public function setRefNum(string $refNum = null)
    {
        $this->setAttribute(self::REF_NUM, $refNum);
    }

    public function setReceipt(string $receipt = null)
    {
        $this->setAttribute(self::RECEIPT, $receipt);
    }

    public function setMerchantLabel(string $merchantLabel)
    {
        $this->setAttribute(self::MERCHANT_LABEL, $merchantLabel);
    }

    /**
     * Sets all amounts field to null.
     * Used when all line items of draft invoice are removed.
     *
     * 'null' represents 'not set', 0 can be at some later time a valid value.
     * We use the same during validations also.
     */
    public function setAmountsToNull()
    {
        $this->setAttribute(self::GROSS_AMOUNT, null);
        $this->setAttribute(self::TAX_AMOUNT, null);
        $this->setAttribute(self::AMOUNT, null);
    }

    /**
     * Updates invoice status post capture.
     * If all amount has been paid, move to PAID else PARTIALLY_PAID.
     */
    public function updateStatusPostCapture(Payment\Entity $payment)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::PAYMENT_CAPTURE_INVOICE_UPDATE_NOT_FULLY_PAID,
                                  [
                                      'payment_id'                    => $payment->getId(),
                                      'invoice_id'                    => $this->getId(),
                                      'payment_amount'                => $payment->getAmount(),
                                      'invoice_amount_paid_attribute' => $this->getAmountPaidAttribute(),
                                      'invoice_amount_paid'           => $this->getAmountPaid(),
                                      'invoice_amount'                => $this->getAmount(),
                                      'invoice_status'                => $this->getStatus(),
                                  ]);

        $newStatus = ($this->isFullyPaid($payment) === true) ?
                        Status::PAID : Status::PARTIALLY_PAID;

        $trace->info(TraceCode::PAYMENT_CAPTURE_INVOICE_UPDATE_NEW_STATUS,
                                  [
                                      'payment_id'                    => $payment->getId(),
                                      'invoice_id'                    => $this->getId(),
                                      'payment_amount'                => $payment->getAmount(),
                                      'invoice_amount_paid_attribute' => $this->getAmountPaidAttribute(),
                                      'invoice_amount_paid'           => $this->getAmountPaid(),
                                      'invoice_amount'                => $this->getAmount(),
                                      'invoice_status'                => $this->getStatus(),
                                  ]);

        $this->setStatus($newStatus);
    }

    public function setFirstPaymentMinAmount(int $amount = null)
    {
        return $this->setAttribute(self::FIRST_PAYMENT_MIN_AMOUNT, $amount);
    }

    // -------------------------------------- End Setters ------------

    // -------------------------------------- Accessors --------------

    /**
     * Gets customer_details attribute of invoice entity.
     *
     * Invoice has association with customer, customer_billing_addr_id, and
     * customer_shipping_addr_id. A customer's detail and it's addresses can
     * be edited anytime. We keep customer's basic attribute in invoice entity
     * as a snapshot. This attribute returns those.
     *
     * @return array
     */
    protected function getCustomerDetailsAttribute(): array
    {
        $customerId      = $this->getPublicCustomerId();
        $customerName    = $this->getCustomerName();
        $customerEmail   = $this->getCustomerEmail();
        $customerContact = $this->getCustomerContact();
        $customerGstin   = $this->getCustomerGstin();

        $details = [
            Customer\Entity::ID               => $customerId,
            Customer\Entity::NAME             => $customerName,
            Customer\Entity::EMAIL            => $customerEmail,
            Customer\Entity::CONTACT          => $customerContact,
            Customer\Entity::GSTIN            => $customerGstin,
            Customer\Entity::BILLING_ADDRESS  => null,
            Customer\Entity::SHIPPING_ADDRESS => null,

            // For backward compatibility.
            self::CUSTOMER_NAME               => $customerName,
            self::CUSTOMER_EMAIL              => $customerEmail,
            self::CUSTOMER_CONTACT            => $customerContact,
        ];

        if ($this->hasCustomerBillingAddress() === true)
        {
            $billingAddress = $this->customerBillingAddress->toArrayPublic();

            $details[Customer\Entity::BILLING_ADDRESS] = $billingAddress;
        }

        if ($this->hasCustomerShippingAddress() === true)
        {
            $shippingAddress = $this->customerShippingAddress->toArrayPublic();

            $details[Customer\Entity::SHIPPING_ADDRESS] = $shippingAddress;
        }

        return $details;
    }

    public function isInternational(Merchant\Entity $merchant): bool
    {
        return ($this->getCurrency() !== $merchant->getCurrency());
    }

    public function isGSTTaxationApplicable(Merchant\Entity $merchant): bool
    {
        $isCurrencyInr = $this->getCurrency() == Currency::INR;

        if( $merchant == null){
            return $isCurrencyInr;
        }

        return $isCurrencyInr && $merchant->getCountry() == "IN";
    }

    /**
     * Sets currency symbol as per the currency.
     */
    protected function getCurrencySymbolAttribute()
    {
        $currency = $this->getCurrency();

        return Currency::getSymbol($currency);
    }

    protected function getPaymentIdAttribute()
    {
        $orderId = $this->getOrderId();

        //
        // Order gets created when invoice moves in ISSUED state.
        // Order Id will be null for invoices in draft status.
        //
        if ($orderId === null)
        {
            return null;
        }

        $repo = App::getFacadeRoot()['repo'];

        $payment = $repo->payment->getCapturedPaymentForOrder($orderId);

        if ($payment !== null)
        {
            return $payment->getPublicId();
        }

        return null;
    }

    /**
     * Looks up amount_paid attribute from corresponding
     * order entity.
     *
     * @return null|int
     */
    public function getAmountPaidAttribute()
    {
        if (empty($this->order) === true)
        {
            return null;
        }

        return $this->order->getAmountPaid();
    }

    public function getOrderAttribute()
    {
        $order = null;

        if ($this->relationLoaded('order') === true)
        {
            $order = $this->getRelation('order');
        }

        if ($order !== null)
        {
            return $order;
        }

        $order = $this->order()->with('offers')->first();

        if (empty($order) === false)
        {
            return $order;
        }

        if (empty($this[self::ORDER_ID]) === true)
        {
            return null;
        }

        $order = (new Order\Repository)->findOrFailPublic('order_'.$this[self::ORDER_ID]);

        $this->order()->associate($order);

        return $order;
    }

    /**
     * Looks up amount_due attribute from corresponding
     * order entity.
     *
     * @return null|int
     */
    public function getAmountDueAttribute()
    {
        if (empty($this->order) === true)
        {
            return null;
        }

        if ($this->order->getProductType() === Order\ProductType::SUBSCRIPTION and
            $this->isPaid() === true)
        {
            return 0;
        }

        return $this->order->getAmountDue();
    }

    public function getInvoiceNumberAttribute()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getMerchantLabelAttribute($label)
    {
        return $label ?: $this->merchant->getLabelForInvoice();
    }

    public function getTaxableAmountAttribute(): int
    {
        $taxableAmount = $this->lineItems()->get()->pluck(LineItem\Entity::TAXABLE_AMOUNT)->sum();

        return $taxableAmount;
    }

    public function getComment()
    {
        return $this->getAttribute(self::COMMENT);
    }

    // -------------------------------------- End Accessors ----------

    // -------------------------------------- Public Setters ---------

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    protected function setPublicOrderIdAttribute(array & $array)
    {
        $orderId = $this->getAttribute(self::ORDER_ID);

        $array[self::ORDER_ID] = Order\Entity::getSignedIdOrNull($orderId);
    }

    protected function setPublicReminderStatusAttribute(array & $array)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isProxyOrPrivilegeAuth() === true)
        {
            $array[self::REMINDER_STATUS] = (empty($array[self::REMINDER_STATUS]) === false) ?
                                            $array[self::REMINDER_STATUS][self::REMINDER_STATUS] : null;
        }
        else
        {
            unset($array[self::REMINDER_STATUS]);
        }
    }


    protected function setPublicSubscriptionIdAttribute(array & $array)
    {
        $subscriptionId = $this->getAttribute(self::SUBSCRIPTION_ID);

        if ($subscriptionId !== null)
        {
            $array[self::SUBSCRIPTION_ID] = Subscription\Entity::getSignedIdOrNull($subscriptionId);
        }
        else
        {
            unset($array[Entity::SUBSCRIPTION_ID]);
        }
    }

    /**
     * TODO: Move to entity serializer
     * @param array $array
     */
    public function setPublicSubscriptionStatusAttribute(array & $array)
    {
        $app = App::getFacadeRoot();

        /** @var BasicAuth $basicAuth */
        $basicAuth = $app['basicauth'];

        if ($basicAuth->isProxyOrPrivilegeAuth() === false)
        {
            unset($array[self::SUBSCRIPTION_STATUS]);
        }
    }

    /**
     * TODO: Move to entity serializer
     * @param array $array
     */
    public function setPublicFirstPaymentMinAmountAttribute(array & $array)
    {
        $app = App::getFacadeRoot();

        /** @var BasicAuth $basicAuth */
        $basicAuth = $app['basicauth'];

        // Unset the attribute, only on strictly private auth
        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($array[self::FIRST_PAYMENT_MIN_AMOUNT]);
        }
    }

    /**
     * TODO: Move to entity serializer
     * @param array $array
     */
    public function setPublicSupplyStateCodeAttribute(array & $array)
    {
        $app = App::getFacadeRoot();

        /** @var BasicAuth $basicAuth */
        $basicAuth = $app['basicauth'];

        // Unset the attribute, only on strictly private auth
        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($array[self::SUPPLY_STATE_CODE]);
        }
    }

    public function setPublicUserIdAttribute(array & $array)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isStrictPrivateAuth() === true)
        {
            unset($array[self::USER_ID]);
        }
    }

    // -------------------------------------- End Public Setters -----

    // -------------------------------------- Generators -------------

    public function generateDate(array $input)
    {
        // If DATE is not sent in input, set it to now
        // If DATE is sent, even as null use that only(so not using isset)
        if (array_key_exists(Entity::DATE, $input) === false)
        {
            $now = Carbon::now()->getTimestamp();

            $this->setAttribute(self::DATE, $now);
        }
    }

    public function generateEmailStatus(array $input)
    {
        $this->setAttribute(self::EMAIL_STATUS, NotifyStatus::PENDING);

        if ((isset($input[self::EMAIL_NOTIFY]) === true) and
            (boolval($input[self::EMAIL_NOTIFY]) === false))
        {
            $this->setAttribute(self::EMAIL_STATUS, null);
        }
    }

    public function generateSmsStatus(array $input)
    {
        $this->setAttribute(self::SMS_STATUS, NotifyStatus::PENDING);

        if ((isset($input[self::SMS_NOTIFY]) === true) and
            (boolval($input[self::SMS_NOTIFY]) === false))
        {
            $this->setAttribute(self::SMS_STATUS, null);
        }
    }

    public function generateDueBy(array $input)
    {
        if (empty($input[self::DUE_BY]) === false)
        {
            $dueBy = $input[self::DUE_BY];
        }
        else
        {
            $dueBy = Carbon::now(Timezone::IST)
                           ->addDays(self::DEFAULT_DUE_DAYS)
                           ->getTimestamp();
        }

        $this->setAttribute(self::DUE_BY, $dueBy);
    }

    public function generateScheduledAt(array $input)
    {
        if (empty($input[self::SCHEDULED_AT]) === false)
        {
            $scheduledAt = $input[self::SCHEDULED_AT];
        }
        else
        {
            $scheduledAt = Carbon::now()->getTimestamp();
        }

        $this->setAttribute(self::SCHEDULED_AT, $scheduledAt);
    }

    /**
     * Generates status based on draft key's value sent in request param.
     * If sent to 0, means created/stays in DRAFT status, otherwise if sent to 1,
     * means will be moved to ISSUED state.
     *
     * @param array $input
     *
     * @return null
     */
    public function generateStatus(array $input)
    {
        if (isset($input[self::DRAFT]) and (boolval($input[self::DRAFT]) === true))
        {
            $this->setStatus(Status::DRAFT);
        }
        else
        {
            $this->setStatus(Status::ISSUED);
        }
    }

    // -------------------------------------- End Generators ---------

    // -------------------------------------- Relations --------------

    public function order()
    {
        return $this->belongsTo('RZP\Models\Order\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function lineItems()
    {
        return $this->morphMany('RZP\Models\LineItem\Entity', 'entity');
    }

    public function subscription()
    {
        return $this->belongsTo('RZP\Models\Plan\Subscription\Entity');
    }

    /**
     * The batch which created this invoice entity.
     *
     * @return null|\Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch()
    {
        return $this->belongsTo('RZP\Models\Batch\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customerBillingAddress()
    {
        return $this->belongsTo(Address\Entity::class, self::CUSTOMER_BILLING_ADDR_ID);
    }

    public function customerShippingAddress()
    {
        return $this->belongsTo(Address\Entity::class, self::CUSTOMER_SHIPPING_ADDR_ID);
    }

    public function payments()
    {
        return $this->hasMany(Payment\Entity::class)
                    ->orderBy(Payment\Entity::CREATED_AT, 'desc');
    }

    public function files()
    {
        return $this->morphMany('RZP\Models\FileStore\Entity', 'entity');
    }

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function reminderStatus()
    {
        return $this->belongsTo(Reminder\Entity::class, 'id', 'invoice_id');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function tokenRegistration()
    {
        return $this->morphTo('entity');
    }

    /**
     * Gets the most recent invoice pdf file, or null
     *
     * @return array
     */
    public function pdf()
    {
        $response = (new FileUploadUfh())->getFiles($this);

        if($response['count'] == 0)
        {
            return null;
        }

        //didnt find any need to convert the ufh response to an local file instance, will change once I find it.
        return $response;
    }

    // -------------------------------------- End Relations ----------

    public function getValidOperations(): array
    {
        return $this->validOperations;
    }

    // -------------------------------------- Serializations ---------

    public function toArrayReport()
    {
        if ($this->isTypeLink() === false)
        {
            throw new LogicException('Report not available for types other than link');
        }

        $report = parent::toArrayReport();

        // Add flattened customer details in report

        $report[self::CUSTOMER_NAME]    = $this->getCustomerName();
        $report[self::CUSTOMER_EMAIL]   = $this->getCustomerEmail();
        $report[self::CUSTOMER_CONTACT] = $this->getCustomerContact();

        return $report;
    }
}
