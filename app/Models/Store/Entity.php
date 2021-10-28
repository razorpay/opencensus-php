<?php

namespace RZP\Models\Store;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const MERCHANT_ID          = 'merchant_id';
    const AMOUNT               = 'amount';
    const CURRENCY             = 'currency';
    const CURRENCY_SYMBOL      = 'currency_symbol';
    const STATUS               = 'status';
    const STORE_URL            = 'short_url';
    const USER_ID              = 'user_id';
    const TITLE                = 'title';
    const DESCRIPTION          = 'description';
    const NOTES                = 'notes';
    const USER                 = 'user';
    const SLUG                 = 'slug';
    const VIEW_TYPE            = 'view_type';
    const VIEW_TYPE_STORE      = 'store';
    const STORE_URL_ATTRIBUTE  = 'store_url';

    const CAPTURED_PAYMENTS_COUNT = 'captured_payments_count';

    const SETTINGS = 'settings';

    //Pixel Tracking
    const PP_FB_PIXEL_TRACKING_ID              = 'pp_fb_pixel_tracking_id';
    const PP_GA_PIXEL_TRACKING_ID              = 'pp_ga_pixel_tracking_id';
    const PP_FB_EVENT_ADD_TO_CART_ENABLED      = 'pp_fb_event_add_to_cart_enabled';
    const PP_FB_EVENT_INITIATE_PAYMENT_ENABLED = 'pp_fb_event_initiate_payment_enabled';
    const PP_FB_EVENT_PAYMENT_COMPLETE         = 'pp_fb_event_payment_complete_enabled';

    const SHIPPING_FEES = 'shipping_fees';
    const SHIPPING_DAYS = 'shipping_days';

    // Product Fields

    const PRODUCT_NAME             = 'name';
    const PRODUCT_DESCRIPTION      = 'description';
    const PRODUCT_IMAGES           = 'images';
    const PRODUCT_STOCK            = 'stock';
    const PRODUCT_SELLING_PRICE    = 'selling_price';
    const PRODUCT_DISCOUNTED_PRICE = 'discounted_price';
    const PRODUCT_STOCK_AVAILABLE  = 'stock_available';
    const PRODUCT_STOCK_SOLD       = 'stock_sold';
    const STORE_ID                 = 'store_id';

    const PRODUCT_STATUS_ACTIVE   = 'active';
    const PRODUCT_STATUS_INACTIVE = 'inactive';

    const MERCHANT                = 'merchant';
    const LINE_ITEMS              = 'line_items';
    const ORDER                   = 'order';
    const PAYMENT_PAGE_ITEM_ID    = 'payment_page_item_id';

    const SETTINGS_KEYS = [
        self::PP_FB_PIXEL_TRACKING_ID,
        self::PP_GA_PIXEL_TRACKING_ID,
        self::PP_FB_EVENT_ADD_TO_CART_ENABLED,
        self::PP_FB_EVENT_INITIATE_PAYMENT_ENABLED,
        self::PP_FB_EVENT_PAYMENT_COMPLETE,
        self::SHIPPING_FEES,
        self::SHIPPING_DAYS,
    ];

    protected static $sign        = 'store';

    protected $entity             = 'payment_link';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::CURRENCY,
        self::TITLE,
        self::DESCRIPTION,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CURRENCY,
        self::TITLE,
        self::DESCRIPTION,
        self::STORE_URL_ATTRIBUTE,
        self::SLUG,
        self::STATUS,
        self::USER_ID,
        self::NOTES,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $appends = [
        self::CURRENCY_SYMBOL,
    ];

    protected $public = [
        self::ID,
        self::CURRENCY,
        self::TITLE,
        self::DESCRIPTION,
        self::SLUG,
        self::STATUS,
        self::STORE_URL_ATTRIBUTE,
        self::NOTES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $hosted = [
        self::ID,
        self::MERCHANT_ID,
        self::CURRENCY,
        self::TITLE,
        self::DESCRIPTION,
        self::STORE_URL_ATTRIBUTE,
        self::SLUG,
        self::STATUS,
        self::NOTES,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];


    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults = [
        self::STATUS             => Status::ACTIVE,
        self::DESCRIPTION        => null,
        self::CURRENCY           => 'INR',
        self::NOTES              => [],
        self::VIEW_TYPE          => self::VIEW_TYPE_STORE,
    ];

    protected $publicSetters = [
        self::ID,
        self::SLUG,
        self::STORE_URL_ATTRIBUTE,
    ];

    public function toArrayPublic(): array
    {
        $publicArray = parent::toArrayPublic();

        $publicArray['settings'] = $this->getSettings()->toArray();

        return $publicArray;
    }


    /**
     * Sets currency symbol as per the currency.
     */
    protected function getCurrencySymbolAttribute()
    {
        $currency = $this->getCurrency();

        $currencySymbol = null;

        if (empty($currency) === false)
        {
            $currencySymbol = Currency::getSymbol($currency);
        }

        return $currencySymbol;
    }


    // -------------------------------------- Relations -------------------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment\Entity::class);
    }

    // -------------------------------------- End Relations ---------------------------

    // ----------------------------------------- Getters ------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        if ($this->getAttribute(self::CURRENCY) === null)
        {
            return Currency::INR;
        }

        return $this->getAttribute(self::CURRENCY);
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }


    public function getStoreUrl()
    {
        return $this->getAttribute(self::STORE_URL);
    }


    public function getProductType(): string
    {
        return Order\ProductType::PAYMENT_STORE;
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }


    public function getTitle()
    {
        return $this->getAttribute(self::TITLE);
    }

    public function isActive(): bool
    {
        return ($this->getStatus() === Status::ACTIVE);
    }

    public function isInactive(): bool
    {
        return ($this->getStatus() === Status::INACTIVE);
    }

    public function setPublicSlugAttribute(array & $array)
    {
        $array[Entity::SLUG] = $this->getSlugFromStoreUrl();
    }

    public function setPublicStoreUrlAttribute(array & $array)
    {
        $array[Entity::STORE_URL_ATTRIBUTE] = $this->getStoreUrl();
    }

    /**
     * Checks if link in it's current state is payable or not.
     * @return boolean
     */
    public function isPayable(): bool
    {
        return ($this->isActive() === true);
    }

    public function getHostedViewUrl(string $storeHostedPageUrl, string $slug): string
    {
        return $storeHostedPageUrl . '/' .$slug;
    }

    public function getSlugFromStoreUrl()
    {
        $parts = explode('/', $this->getStoreUrl());

        return end($parts) ?: null;
    }

    public function getSettings(string $key = null)
    {
        $accessor = $this->getSettingsAccessor();

        return $key === null ? $accessor->all() : $accessor->get($key);
    }

    public function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::PAYMENT_STORE);
    }

    public function getMerchantSupportDetails(): array
    {
        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $merchantName = $this->merchant->getBillingLabel();

        $supportEmail = '';

        $supportMobile = '';

        $supportDetails = $repo->merchant_email->getEmailByType(Merchant\Email\Type::SUPPORT, $this->merchant->getId());

        if ($supportDetails !== null)
        {
            $supportDetails = $supportDetails->toArrayPublic();

            $supportEmail  = $supportDetails[Merchant\Email\Entity::EMAIL];

            $supportMobile = $supportDetails[Merchant\Email\Entity::PHONE];
        }

        return ['support_email' => $supportEmail, 'support_mobile' => $supportMobile, 'name' => $merchantName];
    }

    public function getMerchantOrgBrandingDetails(): array
    {
        $brandingLogo = 'https://cdn.razorpay.com/logo.svg';

        if($this->merchant->shouldShowCustomOrgBranding() === true)
        {
            $org = $this->merchant->org;

            $brandingLogo = $org->getCheckoutLogo();
        }

        return ['branding_logo' => $brandingLogo];
    }

    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setStoreUrl(string $url)
    {
        $this->setAttribute(self::STORE_URL, $url);
    }

    public function setViewType(string $viewType)
    {
        return $this->setAttribute(self::VIEW_TYPE, $viewType);
    }
    // -------------------------------------- End Setters -----------------------------
}
