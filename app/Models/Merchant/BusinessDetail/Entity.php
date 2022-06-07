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
    const APP_URLS                      = 'app_urls';
    const BLACKLISTED_PRODUCTS_CATEGORY = 'blacklisted_products_category';
    const BUSINESS_PARENT_CATEGORY      = 'business_parent_category';
    const CREATED_AT                    = 'created_at';
    const UPDATED_AT                    = 'updated_at';
    const AUDIT_ID                      = 'audit_id';

    protected $entity     = 'merchant_business_detail';

    protected $generateIdOnCreate = true;

    protected $public             = [
        self::MERCHANT_ID,
        self::WEBSITE_DETAILS,
        self::APP_URLS,
        self::BLACKLISTED_PRODUCTS_CATEGORY,
        self::BUSINESS_PARENT_CATEGORY,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $fillable           = [
        self::MERCHANT_ID,
        self::WEBSITE_DETAILS,
        self::APP_URLS,
        self::BLACKLISTED_PRODUCTS_CATEGORY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::BUSINESS_PARENT_CATEGORY,
        self::AUDIT_ID
    ];

    protected $casts              = [
        self::WEBSITE_DETAILS => 'array',
        self::APP_URLS        => 'array',
    ];

    protected $defaults           = [
        self::WEBSITE_DETAILS                => [],
        self::APP_URLS                       => null,
        self::BLACKLISTED_PRODUCTS_CATEGORY  => null,
    ];

    public function getId()
    {
        return $this->getMerchantId();
    }

    public function getAppUrls()
    {
        return $this->getAttribute(self::APP_URLS);
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
        return $this->getAppUrls()[Constants::PLAYSTORE_URL] ?? null;
    }

    public function getAppstoreUrl()
    {
        return $this->getAppUrls()[Constants::APPSTORE_URL] ?? null;
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

    public function setBlacklistedProductsCategory($value)
    {
        $this->setAttribute(self::BLACKLISTED_PRODUCTS_CATEGORY, $value);
    }

    public function getBlacklistedProductsCategory()
    {
        return $this->getAttribute(self::BLACKLISTED_PRODUCTS_CATEGORY);
    }
}
