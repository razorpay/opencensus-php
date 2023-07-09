<?php

namespace RZP\Models\SubscriptionRegistration;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Feature;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\PaperMandate;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * @property PaperMandate\Entity   $paperMandate
 * @property Customer\Token\Entity $token
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    use SoftDeletes;

    const CUSTOMER_ID       = 'customer_id';

    //
    // Method can be of card or emandate or nach_mandate
    //
    const METHOD            = 'method';
    const ENTITY_TYPE       = 'entity_type';
    const BANK              = 'bank';
    const ENTITY_ID         = 'entity_id';
    const RECURRING_STATUS  = 'recurring_status';
    const FAILURE_REASON    = 'failure_reason';
    const MAX_AMOUNT        = 'max_amount';
    const TOKEN_ID          = 'token_id';
    const TOKEN             = 'token';
    const NOTES             = 'notes';
    const AMOUNT            = 'amount';
    const STATUS            = 'status';
    const ATTEMPTS          = 'attempts';
    const CURRENCY          = 'currency';
    const FREQUENCY         = 'frequency';
    const RECURRING_TYPE    = "recurring_type";
    const RECURRING_VALUE   = "recurring_value";

    // Allowed frequencies for card recurring
    const AS_PRESENTED          = 'as_presented';
    const WEEKLY                = 'weekly';
    const MONTHLY               = 'monthly';
    const YEARLY                = 'yearly';

    const FIRST_PAYMENT_AMOUNT = 'first_payment_amount';

    const BANK_ACCOUNT      = 'bank_account';
    const PAPER_MANDATE     = 'paper_mandate';
    //
    // Auth Type is aadhaar or netbanking
    //
    const AUTH_TYPE         = 'auth_type';
    const EXPIRE_AT         = 'expire_at';
    const DELETED_AT        = 'deleted_at';

    const METHOD_TYPE_CARD      = 'card';
    const METHOD_TYPE_EMANDATE  = 'emandate';

    const ORDER_ID     = 'order_id';

    // Internally it is Invoice id
    const AUTH_LINK_ID = 'auth_link_id';

    const CREATE_FORM              = 'create_form';
    const FORM_REFERENCE1          = 'form_reference1';
    const FORM_REFERENCE2          = 'form_reference2';
    const PREFILLED_FORM           = 'prefilled_form';
    const PREFILLED_FORM_TRANSIENT = 'prefilled_form_transient';
    const PREFILLED_FORM_DOWNLOAD  = 'prefilled_form_download';
    const UPLOAD_FORM_URL          = 'upload_form_url';
    const NACH                     = 'nach';
    const SUCCEED                  = 'succeed';
    const SIGNED_FORM              = 'signed_form';

    const PAYMENT_ID       = 'payment_id';
    const CUSTOMER_CONTACT = 'customer_contact';
    const CUSTOMER_EMAIL   = 'customer_email';

    const DEFAULT_MAX_AMOUNT              = 9999900;
    const CARD_MANDATE_DEFAULT_MAX_AMOUNT = 1500000;

    protected static $sign = 'subr';

    protected $entity = 'subscription_registration';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::AUTH_TYPE                 => null,
        self::MAX_AMOUNT                => null,
        self::EXPIRE_AT                 => null,
        self::NOTES                     => [],
        self::STATUS                    => Status::CREATED,
        self::AMOUNT                    => 0,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::MERCHANT_ID,
        self::METHOD,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::NOTES,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::TOKEN_ID,
        self::EXPIRE_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::FREQUENCY,
    ];

    protected $tokenFields = [
        self::METHOD,
        self::EXPIRE_AT,
        self::BANK_ACCOUNT,
        self::PAPER_MANDATE,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::NOTES,
        self::FIRST_PAYMENT_AMOUNT,
        self::CURRENCY,
        self::STATUS
    ];
    protected $public = [
        self::ID,
        self::METHOD,
        self::ENTITY,
        self::NOTES,
        self::FIRST_PAYMENT_AMOUNT,
        self::RECURRING_STATUS,
        self::FAILURE_REASON,
        self::CURRENCY,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::EXPIRE_AT,
    ];

    protected $fillable = [
        self::METHOD,
        self::CURRENCY,
        self::AMOUNT,
        self::FIRST_PAYMENT_AMOUNT,
        self::MAX_AMOUNT,
        self::AUTH_TYPE,
        self::EXPIRE_AT,
        self::NOTES,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::EXPIRE_AT,
        self::DELETED_AT,
    ];

    public function toArrayTokenFields(Invoice\Entity $invoice = null)
    {
        $flippedArrayTokenFields = array_flip($this->tokenFields);

        $tokenArray = array_intersect_key($this->toArrayPublic(), $flippedArrayTokenFields);

        if ($this->getEntityType() === self::BANK_ACCOUNT)
        {

            $bankAccount = $this->bankAccount;

            $publicArrayBankAccount = $bankAccount->toArrayHosted();

            unset($publicArrayBankAccount[self::ID]);

            unset($publicArrayBankAccount['entity']);

            $tokenArray[self::BANK_ACCOUNT] = $publicArrayBankAccount;
        }
        else if ($this->getEntityType() === self::PAPER_MANDATE)
        {
            $paperMandate = $this->paperMandate;

            $bankAccount = $paperMandate->bankAccount;

            $publicArrayBankAccount = $bankAccount->toArrayHosted();

            unset($publicArrayBankAccount[self::ID]);

            unset($publicArrayBankAccount['entity']);

            $paperMandate->reload();

            $nachArray[Entity::CREATE_FORM] = empty($paperMandate->getGeneratedFileID()) === true ? false : true;

            $nachArray[Entity::FORM_REFERENCE1] = $paperMandate->getReference1();

            $nachArray[Entity::FORM_REFERENCE2] = $paperMandate->getReference2();

            $nachArray[Entity::PREFILLED_FORM] = $paperMandate->getGeneratedFormUrl($invoice);

            $nachArray[Entity::PREFILLED_FORM_TRANSIENT] = $paperMandate->getGeneratedFormUrlTransient();

            if ($this->shouldSendPrefilledFormDownload() === true)
            {
                $nachArray[Entity::PREFILLED_FORM_DOWNLOAD] = $paperMandate->getGeneratedFormUrlTransient();
            }

            $uploadFormUrl = $invoice === null ? null : $invoice->getShortUrl();

            $nachArray[Entity::UPLOAD_FORM_URL] = $uploadFormUrl;

            $nachArray[Invoice\Entity::DESCRIPTION] = $invoice->getDescription();

            $tokenArray[Entity::NACH] = $nachArray;

            $tokenArray[self::BANK_ACCOUNT] = $publicArrayBankAccount;
        }

        $tokenArray[self::FIRST_PAYMENT_AMOUNT] = $this->getAmount();

        return $tokenArray;
    }

    public function toArrayTokenFieldsNach(Invoice\Entity $invoice = null, $tokenArray = null)
    {
        if ($this->getEntityType() === self::PAPER_MANDATE)
        {
            $paperMandate = $this->paperMandate;

            $nachArray[Entity::CREATE_FORM] = empty($paperMandate->getGeneratedFileID()) === true ? false : true;
            $nachArray[Entity::FORM_REFERENCE1] = $paperMandate->getReference1();
            $nachArray[Entity::FORM_REFERENCE2] = $paperMandate->getReference2();
            $nachArray[Entity::PREFILLED_FORM] = $paperMandate->getGeneratedFormUrl($invoice);
            $nachArray[Entity::PREFILLED_FORM_TRANSIENT] = $paperMandate->getGeneratedFormUrlTransient();

            if ($this->shouldSendPrefilledFormDownload() === true)
            {
                $nachArray[Entity::PREFILLED_FORM_DOWNLOAD] = $paperMandate->getGeneratedFormUrlTransient();
            }

            $uploadFormUrl = $invoice === null ? null : $invoice->getShortUrl();
            $nachArray[Entity::UPLOAD_FORM_URL] = $uploadFormUrl;
            $nachArray[Invoice\Entity::DESCRIPTION] = $invoice->getDescription();
            $tokenArray[Entity::NACH] = $nachArray;
            $tokenArray[self::FIRST_PAYMENT_AMOUNT] = $this->getAmount();

            return $tokenArray;
        }
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFrequency()
    {
        return $this->getAttribute(self::FREQUENCY);
    }

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }

    public function getFirstPaymentAmountAttribute()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    public function getExpireAt()
    {
        return $this->getAttribute(self::EXPIRE_AT);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public static function getDefaultMaxAmountForMethod($method)
    {
        switch ($method)
        {
            case Method::NACH:
                return PaperMandate\Entity::DEFAULT_AMOUNT;

            case Method::CARD:
            case null:
                return self::CARD_MANDATE_DEFAULT_MAX_AMOUNT;

            default:
                return self::DEFAULT_MAX_AMOUNT;
        }
    }

    public function getAuthLinkStatus(Invoice\Entity $invoice, Order\Entity $order)
    {
        $invoiceStatus = $invoice->getStatus();

        if ($invoiceStatus === Invoice\Status::ISSUED)
        {
            //
            // if any payment created for auth link
            // is still not in terminal status then
            // auth link status should be pending
            //
            $payments = $order->payments;

            foreach ($payments as $payment)
            {
                if (($payment->getStatus() === Payment\Status::CREATED) or
                    ($payment->getStatus() === Payment\Status::AUTHORIZED))
                {
                    return SubscriptionRegistrationConstants::PENDING;
                }
            }
        }

        return $invoiceStatus;
    }

    public function getAttempts()
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function isMethodCard(): bool
    {
        return ($this->getMethod() === self::METHOD_TYPE_CARD);
    }

    public function isMethodEmandate(): bool
    {
        return ($this->getMethod() === self::METHOD_TYPE_EMANDATE);
    }

    public function isMethodNach(): bool
    {
        return ($this->getMethod() === Method::NACH);
    }

    public function hasAutoPayment()
    {
        return ($this->getAmount() > 0);
    }


        /**
     * Gets dimensions for metrics around invoice module
     * @param  array $extra Additional key, value pair of dimensions
     * @return array
     */
    public function getMetricDimensions(array $extra = []): array
    {
        return $extra + [
            'method'           => (string) $this->getMethod(),
            'has_auto_payment' => (int) $this->hasAutoPayment()
        ];
    }

    public function incrementAttempts()
    {
        $this->increment(self::ATTEMPTS);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setFailureReason(string $reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    public function setFirstPaymentAmountAttribute($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }
    // Relations

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function token()
    {
        return $this->belongsTo(Customer\Token\Entity::class);
    }

    //
    // Entity type currently supports bank_account or card or paper_mandate.
    //
    public function entity()
    {
        return $this->morphTo();
    }

    public function bankAccount()
    {
        return $this->morphTo('entity');
    }

    public function paperMandate()
    {
        return $this->morphTo('entity');
    }

    public function setBank(string $bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setMaxAmount(string $maxAmount)
    {
        $this->setAttribute(self::MAX_AMOUNT, $maxAmount);
    }

    public function setFrequency(string $frequency)
    {
        $this->setAttribute(self::FREQUENCY, $frequency);
    }

    public function build(array $input = array())
    {
        $subscriptionRegistration = parent::build($input);

        if (empty($subscriptionRegistration->getMaxAmount()) === true)
        {
            $maxAmount = Entity::getDefaultMaxAmountForMethod($subscriptionRegistration->getMethod());

            if (empty($maxAmount) === false)
            {
                $subscriptionRegistration->setMaxAmount($maxAmount);
            }
        }

        return $subscriptionRegistration;
    }

    private function shouldSendPrefilledFormDownload(): bool
    {
        return $this->merchant->isFeatureEnabled(Feature\Constants::NACH_FORM_DIRECT_DOWNLOAD) === true;
    }
}
