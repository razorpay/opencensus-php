<?php


namespace RZP\Models\Merchant\BusinessDetail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstant;

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
    const ID              = 'id';
    const MERCHANT_ID     = 'merchant_id';
    const WEBSITE_DETAILS = 'website_details';
    const APP_URLS        = 'app_urls';
    const CREATED_AT      = 'created_at';
    const UPDATED_AT      = 'updated_at';

    protected $entity     = 'merchant_business_detail';

    protected $generateIdOnCreate = true;

    protected $fillable           = [
        self::MERCHANT_ID,
        self::WEBSITE_DETAILS,
        self::APP_URLS
    ];

    protected $public             = [
        self::MERCHANT_ID,
        self::WEBSITE_DETAILS,
        self::APP_URLS,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $casts              = [
        self::WEBSITE_DETAILS => 'array',
        self::APP_URLS        => 'array',
    ];

    protected $defaults           = [
        self::WEBSITE_DETAILS => [],
        self::APP_URLS        => null,
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
            BusinessDetailConstant::PLAYSTORE_URL    => null,
            BusinessDetailConstant::APPSTORE_URL     => null,
        ];
    }

    public function getPlaystoreUrl(){
        return $this->getAppUrls()[BusinessDetailConstant::PLAYSTORE_URL] ?? null;
    }

    public function getAppstoreUrl(){
        return $this->getAppUrls()[BusinessDetailConstant::APPSTORE_URL] ?? null;
    }

    public function getWebsiteDetails()
    {
        return $this->getAttribute(self::WEBSITE_DETAILS);
    }

    public static function getDefaultWebsiteDetails()
    {
        return [
            BusinessDetailConstant::ABOUT          => null,
            BusinessDetailConstant::CONTACT        => null,
            BusinessDetailConstant::CANCELLATION   => null,
            BusinessDetailConstant::PRICING        => null,
            BusinessDetailConstant::PRIVACY        => null,
            BusinessDetailConstant::REFUND         => null,
            BusinessDetailConstant::TERMS          => null,
            BusinessDetailConstant::LOGIN          => null,
            BusinessDetailConstant::PHYSICAL_STORE => false,
            BusinessDetailConstant::SOCIAL_MEDIA   => false,
            BusinessDetailConstant::WEBSITE_OR_APP => false
        ];
    }

    public function getWebsiteAbout()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::ABOUT]) ? $this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::ABOUT] : null;
    }

    public function getWebsiteContact()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::CONTACT]) ? $this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::CONTACT] : null;
    }

    public function getWebsitePricing()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::PRICING]) ? $this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::PRICING] : null;
    }

    public function getWebsitePrivacy()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::PRIVACY]) ? $this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::PRIVACY] : null;
    }

    public function getWebsiteRefund()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::REFUND]) ? $this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::REFUND] : null;
    }

    public function getWebsiteTerms()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::TERMS]) ? $this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::TERMS] : null;
    }

    public function getWebsiteCancellation()
    {
        return isset($this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::CANCELLATION]) ? $this->getAttribute(self::WEBSITE_DETAILS)[BusinessDetailConstant::CANCELLATION] : null;
    }
}
