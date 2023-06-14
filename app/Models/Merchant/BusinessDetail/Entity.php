<?php


namespace RZP\Models\Merchant\BusinessDetail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 * @property Detail\Entity $merchantDetail
 *
 * @package RZP\Models\Merchant\BusinessDetail
 */
class Entity extends Base\PublicEntity
{
    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const WEBSITE_DETAILS               = 'website_details';
    const PLUGIN_DETAILS                = 'plugin_details';
    const APP_URLS                      = 'app_urls';
    const BLACKLISTED_PRODUCTS_CATEGORY = 'blacklisted_products_category';
    const BUSINESS_PARENT_CATEGORY      = 'business_parent_category';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';
    const AUDIT_ID                      = 'audit_id';
    const LEAD_SCORE_COMPONENTS         = 'lead_score_components';
    const ONBOARDING_SOURCE             = 'onboarding_source';
    const PG_USE_CASE                   = 'pg_use_case';
    const MIQ_SHARING_DATE              = 'miq_sharing_date';
    const TESTING_CREDENTIALS_DATE      = 'testing_credentials_date';
    const METADATA                      = 'metadata';

    protected $entity = 'merchant_business_detail';

    protected $generateIdOnCreate = true;

    protected $public = [
        self::MERCHANT_ID,
        self::WEBSITE_DETAILS,
        self::PLUGIN_DETAILS,
        self::APP_URLS,
        self::BLACKLISTED_PRODUCTS_CATEGORY,
        self::BUSINESS_PARENT_CATEGORY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LEAD_SCORE_COMPONENTS,
        self::ONBOARDING_SOURCE,
        self::PG_USE_CASE,
        self::MIQ_SHARING_DATE,
        self::TESTING_CREDENTIALS_DATE,
        self::METADATA,
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::WEBSITE_DETAILS,
        self::PLUGIN_DETAILS,
        self::APP_URLS,
        self::BLACKLISTED_PRODUCTS_CATEGORY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::BUSINESS_PARENT_CATEGORY,
        self::AUDIT_ID,
        self::ONBOARDING_SOURCE,
        self::PG_USE_CASE,
        self::MIQ_SHARING_DATE,
        self::TESTING_CREDENTIALS_DATE,
        self::METADATA,
    ];

    protected $casts = [
        self::WEBSITE_DETAILS           => 'array',
        self::APP_URLS                  => 'array',
        self::PLUGIN_DETAILS            => 'array',
        self::LEAD_SCORE_COMPONENTS     => 'array',
        self::METADATA                  => 'array',
    ];

    protected $defaults = [
        self::WEBSITE_DETAILS                => [],
        self::APP_URLS                       => null,
        self::BLACKLISTED_PRODUCTS_CATEGORY  => null,
        self::PLUGIN_DETAILS                 => [],
        self::LEAD_SCORE_COMPONENTS          => [],
        self::ONBOARDING_SOURCE              => null,
        self::PG_USE_CASE                    => null,
        self::MIQ_SHARING_DATE               => 0,
        self::TESTING_CREDENTIALS_DATE       => 0,
        self::METADATA                       => null,
    ];

    public function getId()
    {
        return $this->getMerchantId();
    }

    public function getAppUrls()
    {
        return $this->getAttribute(self::APP_URLS);
    }

    public function getPgUseCase()
    {
        return $this->getAttribute(self::PG_USE_CASE);
    }

    public function setPgUseCase(string $pgUseCase)
    {
        return $this->setAttribute(self::PG_USE_CASE, $pgUseCase);
    }

    public static function getDefaultAppUrls()
    {
        return [
            Constants::PLAYSTORE_URL    => null,
            Constants::APPSTORE_URL     => null,
        ];
    }

    public function getPlaystoreUrl()
    {
        return isset($this->getAttribute(self::APP_URLS)[Constants::PLAYSTORE_URL]) ?
            $this->getAttribute(self::APP_URLS)[Constants::PLAYSTORE_URL] :
            null;
    }

    public function getAppstoreUrl()
    {
        return isset($this->getAttribute(self::APP_URLS)[Constants::APPSTORE_URL]) ?
            $this->getAttribute(self::APP_URLS)[Constants::APPSTORE_URL] :
            null;
    }

    public function getWebsiteDetails()
    {
        return $this->getAttribute(self::WEBSITE_DETAILS);
    }

    public function getBusinessParentCategory()
    {
        return $this->getAttribute(self::BUSINESS_PARENT_CATEGORY);
    }

    public static function getDefaultWebsiteDetails()
    {
        return [
            Constants::ABOUT                      => null,
            Constants::CONTACT                    => null,
            Constants::CANCELLATION               => null,
            Constants::PRICING                    => null,
            Constants::PRIVACY                    => null,
            Constants::REFUND                     => null,
            Constants::TERMS                      => null,
            Constants::LOGIN                      => null,
            Constants::PHYSICAL_STORE             => false,
            Constants::SOCIAL_MEDIA               => false,
            Constants::WEBSITE_OR_APP             => false,
            Constants::OTHERS                     => '',
            Constants::WEBSITE_NOT_READY          => false,
            Constants::WEBSITE_COMPLIANCE_CONSENT => null,
            Constants::WEBSITE_PRESENT            => false,
            Constants::ANDROID_APP_PRESENT        => false,
            Constants::IOS_APP_PRESENT            => false,
            Constants::OTHERS_PRESENT             => false,
            Constants::WHATSAPP_SMS_EMAIL         => false,
            Constants::SOCIAL_MEDIA_URLS          => [],
        ];
    }

    public function getWebsiteAbout()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[Constants::ABOUT]) ? $this->getAttribute(self::WEBSITE_DETAILS)[Constants::ABOUT] : null;
    }

    public function getWebsiteContact()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[Constants::CONTACT]) ? $this->getAttribute(self::WEBSITE_DETAILS)[Constants::CONTACT] : null;
    }

    public function getWebsitePricing()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[Constants::PRICING]) ? $this->getAttribute(self::WEBSITE_DETAILS)[Constants::PRICING] : null;
    }

    public function getWebsitePrivacy()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[Constants::PRIVACY]) ? $this->getAttribute(self::WEBSITE_DETAILS)[Constants::PRIVACY] : null;
    }

    public function getWebsiteRefund()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[Constants::REFUND]) ? $this->getAttribute(self::WEBSITE_DETAILS)[Constants::REFUND] : null;
    }

    public function getWebsiteTerms()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[Constants::TERMS]) ? $this->getAttribute(self::WEBSITE_DETAILS)[Constants::TERMS] : null;
    }

    public function getWebsiteCancellation()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[Constants::CANCELLATION]) ? $this->getAttribute(self::WEBSITE_DETAILS)[Constants::CANCELLATION] : null;
    }

    public function getPluginDetails()
    {
        return $this->getAttribute(self::PLUGIN_DETAILS);
    }

    public function setPluginDetail(array $pluginDetails)
    {
        return $this->setAttribute(self::PLUGIN_DETAILS, $pluginDetails);
    }

    public function setBlacklistedProductsCategory($value)
    {
        $this->setAttribute(self::BLACKLISTED_PRODUCTS_CATEGORY, $value);
    }

    public function getBlacklistedProductsCategory()
    {
        return $this->getAttribute(self::BLACKLISTED_PRODUCTS_CATEGORY);
    }

    public function getLeadScoreComponents()
    {
        return $this->getAttribute(self::LEAD_SCORE_COMPONENTS);
    }

    public function setLeadScoreComponents($leadScoreComponents)
    {
        return $this->setAttribute(self::LEAD_SCORE_COMPONENTS, $leadScoreComponents);
    }

    public function getMetadata()
    {
        return $this->getAttribute(self::METADATA);
    }

    public function setMetadata($value)
    {
        $this->setAttribute(self::METADATA, $value);
    }

    public function getValueFromLeadScoreComponents($key)
    {
        $metaData   = $this->getAttribute(self::LEAD_SCORE_COMPONENTS);

        $value = null;

        if (empty($metaData) === false
            and array_key_exists($key, $metaData))
        {
            $value = $metaData[$key];
        }

        return $value;
    }

    public function getTotalLeadScore()
    {
        return (($this->getValueFromLeadScoreComponents(Constants::GSTIN_SCORE) ?? 0) * 0.4) +
               (($this->getValueFromLeadScoreComponents(Constants::DOMAIN_SCORE) ?? 0) * 0.6);
    }

    public function setOnboardingSource(string $onboardingSource)
    {
        return $this->setAttribute(self::ONBOARDING_SOURCE, $onboardingSource);
    }

    public function getOnboardingSource()
    {
        return $this->getAttribute(self::ONBOARDING_SOURCE);
    }

    public function getEsAttributes()
    {
        return [
            self::MIQ_SHARING_DATE => $this->getAttribute(self::MIQ_SHARING_DATE),
            self::TESTING_CREDENTIALS_DATE => $this->getAttribute(self::TESTING_CREDENTIALS_DATE),
        ];
    }
}
