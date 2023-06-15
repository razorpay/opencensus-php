<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Models\Base\PublicEntity;
use RZP\Models\QrCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Constants\Entity as ConstantsEntity;

class Entity extends QrCode\Entity
{
    use NotesTrait;

    const NAME                         = 'name';
    const USAGE_TYPE                   = 'usage_type';
    const STATUS                       = 'status';
    const DESCRIPTION                  = 'description';
    const FIXED_AMOUNT                 = 'fixed_amount';
    const PAYMENTS_AMOUNT_RECEIVED     = 'payments_amount_received';
    const PAYMENTS_RECEIVED_COUNT      = 'payments_received_count';
    const NOTES                        = 'notes';
    const CUSTOMER_ID                  = 'customer_id';
    const CLOSE_BY                     = 'close_by';
    const CLOSED_AT                    = 'closed_at';
    const CLOSE_REASON                 = 'close_reason';
    const REQ_PROVIDER                 = 'type';
    const REQ_AMOUNT                   = 'payment_amount';
    const REQ_USAGE_TYPE               = 'usage';
    const REQ_IMAGE_URL                = 'image_url';
    const RESP_PAYMENTS_COUNT_RECEIVED = 'payments_count_received';
    const RESP_IMAGE_CONTENT           = 'image_content';
    const TAX_INVOICE                  = 'tax_invoice';
    const REQUEST_SOURCE               = 'request_source';

    const SHARED_ID = 'FallbackQrCode';

    protected $fillable = [
        self::PROVIDER,
        self::REFERENCE,
        self::QR_STRING,
        self::NAME,
        self::USAGE_TYPE,
        self::FIXED_AMOUNT,
        self::AMOUNT,
        self::STATUS,
        self::DESCRIPTION,
        self::PAYMENTS_AMOUNT_RECEIVED,
        self::PAYMENTS_RECEIVED_COUNT,
        self::NOTES,
        self::CUSTOMER_ID,
        self::CLOSE_BY,
        self::CLOSED_AT,
        self::CLOSE_REASON,
        self::MPANS_TOKENIZED,
        self::TAX_INVOICE,
        self::REQUEST_SOURCE,
    ];

    protected $visible = [
        self::ID,
        self::REFERENCE,
        self::QR_STRING,
        self::AMOUNT,
        self::PROVIDER,
        self::SHORT_URL,
        self::CREATED_AT,
        self::FIXED_AMOUNT,
        self::NAME,
        self::USAGE_TYPE,
        self::STATUS,
        self::DESCRIPTION,
        self::PAYMENTS_AMOUNT_RECEIVED,
        self::PAYMENTS_RECEIVED_COUNT,
        self::NOTES,
        self::CUSTOMER_ID,
        self::CLOSE_BY,
        self::CLOSED_AT,
        self::CLOSE_REASON,
        self::TAX_INVOICE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CREATED_AT,
        self::NAME,
        self::REQ_USAGE_TYPE,
        self::REQ_PROVIDER,
        self::REQ_IMAGE_URL,
        self::REQ_AMOUNT,
        self::STATUS,
        self::DESCRIPTION,
        self::FIXED_AMOUNT,
        self::PAYMENTS_AMOUNT_RECEIVED,
        self::RESP_PAYMENTS_COUNT_RECEIVED,
        self::NOTES,
        self::CUSTOMER_ID,
        self::CLOSE_BY,
        self::CLOSED_AT,
        self::CLOSE_REASON,
        self::RESP_IMAGE_CONTENT,
        self::TAX_INVOICE,
        self::REQUEST_SOURCE,
    ];

    protected $casts = [
        self::AMOUNT       => 'int',
        self::FIXED_AMOUNT => 'bool',
        self::CLOSE_BY     => 'int',
        self::TAX_INVOICE  => 'json',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::REQ_USAGE_TYPE,
        self::REQ_PROVIDER,
        self::REQ_IMAGE_URL,
        self::REQ_AMOUNT,
        self::RESP_PAYMENTS_COUNT_RECEIVED,
        self::RESP_IMAGE_CONTENT,
        self::REQUEST_SOURCE,
    ];

    protected $defaults = [
        self::PAYMENTS_AMOUNT_RECEIVED => 0,
        self::PAYMENTS_RECEIVED_COUNT  => 0,
        self::STATUS                   => Status::ACTIVE,
        self::NOTES                    => [],
        self::NAME                     => null,
        self::DESCRIPTION              => null,
        self::CLOSE_BY                 => null,
        self::TAX_INVOICE              => [],
        self::REQUEST_SOURCE           => null,
    ];

    protected static $generators = [
        self::ID,
        self::AMOUNT,
        self::USAGE_TYPE,
        self::PROVIDER,
        self::REFERENCE,
    ];

    public function generateReference($input)
    {
        if (isset($input[self::REFERENCE]) === false)
        {
            $this->setReference($this->getId());
        }
    }

    public function generateAmount($input)
    {
        if (isset($input[self::REQ_AMOUNT]) === true)
        {
            $this->setAttribute(self::AMOUNT, $input[self::REQ_AMOUNT]);
        }
    }

    public function generateUsageType($input)
    {
        UsageType::checkUsageType($input[self::REQ_USAGE_TYPE]);

        $this->setAttribute(self::USAGE_TYPE, $input[self::REQ_USAGE_TYPE]);
    }

    public function generateProvider($input)
    {
        $this->setAttribute(self::PROVIDER, $input[self::REQ_PROVIDER]);
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setRequestSource(string $requestSource)
    {
        RequestSource::checkRequestSource($requestSource);

        $this->setAttribute(self::REQUEST_SOURCE, $requestSource);
    }

    public function setClosedAt(int $closedAt)
    {
        $this->setAttribute(self::CLOSED_AT, $closedAt);
    }

    public function setCloseReason(string $closeReason)
    {
        CloseReason::checkCloseReason($closeReason);

        $this->setAttribute(self::CLOSE_REASON, $closeReason);
    }

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    protected function setPublicTypeAttribute(array & $array)
    {
        $array[self::REQ_PROVIDER] = $this->getAttribute(self::PROVIDER);
    }

    protected function setPublicTaxInvoiceAttribute(array & $array)
    {
        $array[self::TAX_INVOICE] = $this->getAttribute(self::TAX_INVOICE);
    }

    protected function setPublicImageUrlAttribute(array & $array)
    {
        $array[self::REQ_IMAGE_URL] = $this->getAttribute(self::SHORT_URL);
    }

    protected function setPublicUsageAttribute(array & $array)
    {
        $array[self::REQ_USAGE_TYPE] = $this->getAttribute(self::USAGE_TYPE);
    }

    protected function setPublicPaymentAmountAttribute(array & $array)
    {
        $array[self::REQ_AMOUNT] = $this->getAttribute(self::AMOUNT);
    }

    protected function setPublicPaymentsCountReceivedAttribute(array & $array)
    {
        $array[self::RESP_PAYMENTS_COUNT_RECEIVED] = $this->getAttribute(self::PAYMENTS_RECEIVED_COUNT);
    }

    protected function setPublicImageContentAttribute(array &$array)
    {
        if (($this->merchant->isFeatureEnabled(Feature\Constants::QR_IMAGE_CONTENT) === true) or
            ($this->getRequestSource() === RequestSource::CHECKOUT) or
            ($this->isSubmQRImageContentFeatureEnabledForPartner() === true))
        {
            $array[self::RESP_IMAGE_CONTENT] = $this->getAttribute(self::QR_STRING);
        }
    }

    public function isSubmQRImageContentFeatureEnabledForPartner(): bool
    {
        $featureName = Feature\Constants::SUBM_QR_IMAGE_CONTENT;

        $partners = (new Merchant\Core())->fetchAffiliatedPartners($this->merchant->getId());

        $partnerService = (new \RZP\Models\Partner\Service());

        $partner = $partners->filter(function(Merchant\Entity $partner) use ($featureName, $partnerService) {
            return ($partnerService->isFeatureEnabledForPartner($featureName, $partner) === true);
        })->first();

        return (empty($partner) === false);
    }

    protected function setPublicRequestSourceAttribute(array &$array)
    {
        if ($this->getRequestSource() === RequestSource::EZETAP)
        {
            $array[self::REQUEST_SOURCE] = $this->getAttribute(self::REQUEST_SOURCE);
        }
    }

    public function isCheckoutQrCode(): bool
    {
        return $this->getRequestSource() === RequestSource::CHECKOUT;
    }

    public function hasFixedAmount()
    {
        return $this->getAttribute(self::FIXED_AMOUNT);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getUsageType()
    {
        return $this->getAttribute(self::USAGE_TYPE);
    }

    public function getRequestSource()
    {
        return $this->getAttribute(self::REQUEST_SOURCE);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function generateQrString()
    {
        if ($this->getRequestSource() === RequestSource::FALLBACK)
        {
            return $this;
        }

        $qrString = (new Generator)->generateQrString($this);

        $this->setQrString($qrString);

        return $this;
    }

    public function hasCustomer()
    {
        return ($this->isAttributeNotNull(self::CUSTOMER_ID));
    }

    public function incrementTotalPaymentCount()
    {
        $this->increment(self::PAYMENTS_RECEIVED_COUNT);
    }

    public function incrementPaymentAmountReceived(int $amount)
    {
        $this->increment(self::PAYMENTS_AMOUNT_RECEIVED, $amount);
    }

    public function hasOrder()
    {
        return $this->getAttribute(self::ENTITY_TYPE) === ConstantsEntity::ORDER;
    }

    public function hasCheckoutOrder(): bool
    {
        return $this->getAttribute(self::ENTITY_TYPE) === ConstantsEntity::CHECKOUT_ORDER;
    }

    public function isClosed()
    {
        // TODO: remove check on close by once QR expiry goes live
        return ($this->getAttribute(self::STATUS) === Status::CLOSED) or
               (($this->getAttribute(self::CLOSE_BY) !== null) and
                (Carbon::now()->getTimestamp() >= $this->getAttribute(self::CLOSE_BY)));
    }

    public function getCloseBy()
    {
        return ($this->getAttribute(self::CLOSE_BY));
    }

    public function getTaxInvoice()
    {
        return $this->getAttribute(self::TAX_INVOICE);
    }

    public function toArrayPublic()
    {
        return PublicEntity::toArrayPublic();
    }

    public function bankAccount()
    {
        return $this->hasOne('RZP\Models\BankAccount\Entity', BankAccount\Entity::ENTITY_ID, Entity::ID);
    }

    public function getPaymentsCountReceived()
    {
        return $this->getAttribute(self::PAYMENTS_RECEIVED_COUNT);
    }

    public function getClosedAt()
    {
        return $this->getAttribute(self::CLOSED_AT);
    }
}
