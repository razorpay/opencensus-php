<?php

namespace RZP\Models\Merchant\InternationalEnablement\Detail;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID                         = 'merchant_id';
    const REVISION_ID                         = 'revision_id';

    // Business Details
    const GOODS_TYPE                          = 'goods_type';
    const BUSINESS_USE_CASE                   = 'business_use_case';
    const ALLOWED_CURRENCIES                  = 'allowed_currencies';
    const MONTHLY_SALES_INTL_CARDS_MIN        = 'monthly_sales_intl_cards_min';
    const MONTHLY_SALES_INTL_CARDS_MAX        = 'monthly_sales_intl_cards_max';
    const BUSINESS_TXN_SIZE_MIN               = 'business_txn_size_min';
    const BUSINESS_TXN_SIZE_MAX               = 'business_txn_size_max';
    const LOGISTIC_PARTNERS                   = 'logistic_partners';

    // Quick Links
    const ABOUT_US_LINK                       = 'about_us_link';
    const CONTACT_US_LINK                     = 'contact_us_link';
    const TERMS_AND_CONDITIONS_LINK           = 'terms_and_conditions_link';
    const PRIVACY_POLICY_LINK                 = 'privacy_policy_link';
    const REFUND_AND_CANCELLATION_POLICY_LINK = 'refund_and_cancellation_policy_link';
    const SHIPPING_POLICY_LINK                = 'shipping_policy_link';
    const SOCIAL_MEDIA_PAGE_LINK              = 'social_media_page_link';

    // Supporting details
    const EXISTING_RISK_CHECKS                = 'existing_risk_checks';
    const CUSTOMER_INFO_COLLECTED             = 'customer_info_collected';
    const PARTNER_DETAILS_PLUGINS             = 'partner_details_plugins';

    const ACCEPTS_INTL_TXNS                   = 'accepts_intl_txns';
    const IMPORT_EXPORT_CODE                  = 'import_export_code';
    const PRODUCTS                            = 'products';

    // accepted terms and conditions. sent for approval
    const SUBMIT                              = 'submit';

    const SUBMITTED_AT                        = 'submitted_at';

    const DOCUMENTS                           = 'documents';

    const IMPORT_EXPORT_CODE_LENGTH           = 10;
    const GOODS_TYPE_FIELD_MAX_LENGTH         = 50;
    const LINK_FIELD_MAX_LENGTH               = 255;

    protected $entity = 'international_enablement_detail';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::GOODS_TYPE,
        self::BUSINESS_USE_CASE,
        self::ALLOWED_CURRENCIES,
        self::MONTHLY_SALES_INTL_CARDS_MIN,
        self::MONTHLY_SALES_INTL_CARDS_MAX,
        self::BUSINESS_TXN_SIZE_MIN,
        self::BUSINESS_TXN_SIZE_MAX,
        self::LOGISTIC_PARTNERS,

        self::ABOUT_US_LINK,
        self::CONTACT_US_LINK,
        self::TERMS_AND_CONDITIONS_LINK,
        self::PRIVACY_POLICY_LINK,
        self::REFUND_AND_CANCELLATION_POLICY_LINK,
        self::SHIPPING_POLICY_LINK,
        self::SOCIAL_MEDIA_PAGE_LINK,

        self::EXISTING_RISK_CHECKS,
        self::CUSTOMER_INFO_COLLECTED,
        self::PARTNER_DETAILS_PLUGINS,

        self::ACCEPTS_INTL_TXNS,
        self::IMPORT_EXPORT_CODE,
        self::PRODUCTS,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::REVISION_ID,
        self::GOODS_TYPE,
        self::BUSINESS_USE_CASE,
        self::ALLOWED_CURRENCIES,
        self::MONTHLY_SALES_INTL_CARDS_MIN,
        self::MONTHLY_SALES_INTL_CARDS_MAX,
        self::BUSINESS_TXN_SIZE_MIN,
        self::BUSINESS_TXN_SIZE_MAX,
        self::LOGISTIC_PARTNERS,

        self::ABOUT_US_LINK,
        self::CONTACT_US_LINK,
        self::TERMS_AND_CONDITIONS_LINK,
        self::PRIVACY_POLICY_LINK,
        self::REFUND_AND_CANCELLATION_POLICY_LINK,
        self::SHIPPING_POLICY_LINK,
        self::SOCIAL_MEDIA_PAGE_LINK,

        self::EXISTING_RISK_CHECKS,
        self::CUSTOMER_INFO_COLLECTED,
        self::PARTNER_DETAILS_PLUGINS,

        self::ACCEPTS_INTL_TXNS,
        self::IMPORT_EXPORT_CODE,
        self::PRODUCTS,

        self::SUBMIT,

        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::GOODS_TYPE,
        self::BUSINESS_USE_CASE,
        self::ALLOWED_CURRENCIES,
        self::MONTHLY_SALES_INTL_CARDS_MIN,
        self::MONTHLY_SALES_INTL_CARDS_MAX,
        self::BUSINESS_TXN_SIZE_MIN,
        self::BUSINESS_TXN_SIZE_MAX,
        self::LOGISTIC_PARTNERS,

        self::ABOUT_US_LINK,
        self::CONTACT_US_LINK,
        self::TERMS_AND_CONDITIONS_LINK,
        self::PRIVACY_POLICY_LINK,
        self::REFUND_AND_CANCELLATION_POLICY_LINK,
        self::SHIPPING_POLICY_LINK,
        self::SOCIAL_MEDIA_PAGE_LINK,

        self::EXISTING_RISK_CHECKS,
        self::CUSTOMER_INFO_COLLECTED,
        self::PARTNER_DETAILS_PLUGINS,

        self::ACCEPTS_INTL_TXNS,
        self::IMPORT_EXPORT_CODE,
        self::PRODUCTS,

        self::SUBMITTED_AT,

        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::SUBMITTED_AT,
    ];

    protected $defaults = [
        self::GOODS_TYPE                          => null,
        self::BUSINESS_USE_CASE                   => null,
        self::ALLOWED_CURRENCIES                  => null,
        self::MONTHLY_SALES_INTL_CARDS_MIN        => null,
        self::MONTHLY_SALES_INTL_CARDS_MAX        => null,
        self::BUSINESS_TXN_SIZE_MIN               => null,
        self::BUSINESS_TXN_SIZE_MAX               => null,
        self::LOGISTIC_PARTNERS                   => null,

        self::ABOUT_US_LINK                       => null,
        self::CONTACT_US_LINK                     => null,
        self::TERMS_AND_CONDITIONS_LINK           => null,
        self::PRIVACY_POLICY_LINK                 => null,
        self::REFUND_AND_CANCELLATION_POLICY_LINK => null,
        self::SHIPPING_POLICY_LINK                => null,
        self::SOCIAL_MEDIA_PAGE_LINK              => null,

        self::EXISTING_RISK_CHECKS                => null,
        self::CUSTOMER_INFO_COLLECTED             => null,
        self::PARTNER_DETAILS_PLUGINS             => null,

        self::ACCEPTS_INTL_TXNS                   => false,
        self::IMPORT_EXPORT_CODE                  => null,

        self::SUBMIT                              => false,
    ];

    protected static $generators = [
        self::REVISION_ID,
    ];

    protected $casts = [
        self::ALLOWED_CURRENCIES             => 'array',
        self::EXISTING_RISK_CHECKS           => 'array',
        self::CUSTOMER_INFO_COLLECTED        => 'array',
        self::PARTNER_DETAILS_PLUGINS        => 'array',
        self::ACCEPTS_INTL_TXNS              => 'bool',
        self::PRODUCTS                       => 'array',
        self::MONTHLY_SALES_INTL_CARDS_MIN   => 'int',
        self::MONTHLY_SALES_INTL_CARDS_MAX   => 'int',
        self::BUSINESS_TXN_SIZE_MIN          => 'int',
        self::BUSINESS_TXN_SIZE_MAX          => 'int',
    ];

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function documents()
    {
        return $this->hasMany('RZP\Models\Merchant\InternationalEnablement\Document\Entity');
    }

    public function markSubmitted()
    {
        $this->setAttribute(self::SUBMIT, true);
    }

    public function isSubmitted(): bool
    {
        return $this->getAttribute(self::SUBMIT);
    }

    public function setPublicSubmittedAtAttribute(array & $attributes)
    {
        $attributes[self::SUBMITTED_AT] = null;

        if ($this->isSubmitted() === true)
        {
            $attributes[self::SUBMITTED_AT] = $this->getUpdatedAt();
        }
    }

    public function setRevisionId($revisionId): string
    {
        return $this->setAttribute(self::REVISION_ID, $revisionId);
    }

    public function getRevisionId(): string
    {
        return $this->getAttribute(self::REVISION_ID);
    }

    public function getAcceptsIntlTxns(): bool
    {
        return $this->getAttribute(self::ACCEPTS_INTL_TXNS);
    }

    public function generateRevisionId(array $input)
    {
        $revisionId = $this->getAttribute(self::REVISION_ID);

        if (empty($revisionId) === true)
        {
            $this->setAttribute(self::REVISION_ID, static::generateUniqueId());
        }
    }

    public function getMerchantId(): string
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }
}
