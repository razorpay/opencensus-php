<?php

namespace RZP\Models\PaymentLink;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use Illuminate\Support\Facades\Config;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;

    const MERCHANT_ID        = 'merchant_id';
    const AMOUNT             = 'amount';
    const CURRENCY           = 'currency';
    const CURRENCY_SYMBOL    = 'currency_symbol';
    const EXPIRE_BY          = 'expire_by';
    const TIMES_PAYABLE      = 'times_payable';
    const TIMES_PAID         = 'times_paid';
    const TOTAL_AMOUNT_PAID  = 'total_amount_paid';
    const STATUS             = 'status';
    const STATUS_REASON      = 'status_reason';
    const SHORT_URL          = 'short_url';
    const USER_ID            = 'user_id';
    const RECEIPT            = 'receipt';
    const TITLE              = 'title';
    const DESCRIPTION        = 'description';
    const NOTES              = 'notes';
    const SUPPORT_CONTACT    = 'support_contact';
    const SUPPORT_EMAIL      = 'support_email';
    const TERMS              = 'terms';
    const TYPE               = 'type';
    const URL                = 'url';
    const TEMPLATE_TYPE      = 'template_type';
    const HANDLE_URL         = 'handle_url';
    const ENCRYPTED_AMOUNT   = 'encrypted_amount';

    const PAYMENT_PAGE_ITEMS   = 'payment_page_items';
    const PAYMENT_PAGE_ITEM_ID = 'payment_page_item_id';
    const LINE_ITEMS           = 'line_items';
    const ORDER                = 'order';
    const VIEW_TYPE            = 'view_type';

    const VIEW_TYPE_BUTTON     = 'button';
    const VIEW_TYPE_PAGE       = 'page';
    const VIEW_TYPE_STORE      = 'store';
    const VIEW_TYPE_FILE_UPLOAD_PAGE = 'file_upload_page';

    const VIEW_TYPE_PAYMENT_HANDLE     = 'payment_handle';
    const NAME                         = 'name';

    const PAYMENT_HANDLE_AMOUNT = 'payment_handle_amount';

    /**
     * Optional attribute: allows a custom view template ID to be defined
     */
    const HOSTED_TEMPLATE_ID = 'hosted_template_id';

    /**
     * Optional attribute: allows a UDF JSON schema to be defined
     */
    const UDF_JSONSCHEMA_ID  = 'udf_jsonschema_id';

    //
    // Additional request input keys used in various other endpoint calls.
    // TODO: Move 'INPUT' to Base\Entity if possible.
    //
    const INPUT             = 'input';
    const CONTACTS          = 'contacts';
    const EMAILS            = 'emails';
    const CONTACT           = 'contact';
    const PHONE             = 'phone';
    const EMAIL             = 'email';
    const USER              = 'user';
    const SLUG              = 'slug';
    const DOMAIN            = 'domain';
    const VIDEO             = 'video';
    const VALUE             = 'value';
    const VIDEO_URL         = 'video_url';

    // Additional general usage input/output constants for the module
    const PAYMENT_ID         = 'payment_id';
    const FROM_STATUS        = 'from_status';
    const FROM_STATUS_REASON = 'from_status_reason';
    const TO_STATUS          = 'to_status';
    const TO_STATUS_REASON   = 'to_status_reason';
    const ERROR              = 'error';
    const REQUEST_PARAMS     = 'request_params';

    const CAPTURED_PAYMENTS_COUNT = 'captured_payments_count';

    // List of keys stored against entity's settings.
    const SETTINGS                     = 'settings';
    const THEME                        = 'theme';
    const UDF_SCHEMA                   = 'udf_schema';
    const UNITS                        = 'units';
    const ALLOW_MULTIPLE_UNITS         = 'allow_multiple_units';
    const ALLOW_SOCIAL_SHARE           = 'allow_social_share';
    const PAYMENT_SUCCESS_REDIRECT_URL = 'payment_success_redirect_url';
    const PAYMENT_SUCCESS_MESSAGE      = 'payment_success_message';
    const CHECKOUT_OPTIONS             = 'checkout_options';
    const ONE_CLICK_CHECKOUT           = 'one_click_checkout';
    const SHIPPING_FEE_RULE            = 'shipping_fee_rule';
    const PAYMENT_BUTTON_LABEL         = 'payment_button_label';
    const VERSION                      = 'version';
    const TEXT_80G_12A                 = 'text_80g_12a';
    const IMAGE_URL_80G                = 'image_url_80g';
    const RECEIPT_ENABLE               = 'enable_receipt';
    const SELECTED_INPUT_FIELD         = 'selected_udf_field';
    const CUSTOM_SERIAL_NUMBER         = 'enable_custom_serial_number';
    const ENABLE_80G_DETAILS           = 'enable_80g_details';
    const ALL_FIELDS                   = 'all_fields';

    const DEFAULT_PAYMENT_HANDLE               = 'default_payment_handle';
    const DEFAULT_PAYMENT_HANDLE_PAGE_ID       = 'default_payment_handle_page_id';

    const COUNT                                   = 'count';
    const DEFAULT_PAYMENT_HANDLE_SUGGESTION_COUNT = 4;
    const SUGGESTIONS                             = 'suggestions';
    const HANDLE                                  = 'handle';
    const MAX_SLUG_LENGTH                         = 30;
    const MIN_SLUG_LENGTH                         = 4;
    const MIN_TITLE_LENGTH                        = 3;
    const MAX_TITLE_LENGTH                        = 80;
    const RETRIES                                 = 'retries';

    //Settings applicable to Payment Button only
    const PP_BUTTON_DISABLE_BRANDING   = 'pp_button_disable_branding';
    const PP_BUTTON_THEME              = 'payment_button_theme';
    const PP_BUTTON_TEXT               = 'payment_button_text';
    const PAYMENT_BUTTON_TEMPLATE_TYPE = 'payment_button_template_type';
    const DEFAULT_THEME                = 'light';

    // settings applicable for donation goal tracker
    const GOAL_TRACKER                  = 'goal_tracker';
    const COMPUTED_GOAL_TRACKER         = 'computed_goal_tracker';
    const TRACKER_TYPE                  = 'tracker_type';
    const GOAL_IS_ACTIVE                = 'is_active';
    const META_DATA                     = 'meta_data';
    const DISPLAY_SUPPORTER_COUNT       = 'display_supporter_count';
    const SUPPORTER_COUNT               = 'supporter_count';
    const DISPLAY_DAYS_LEFT             = 'display_days_left';
    const GOAL_END_TIMESTAMP            = 'goal_end_timestamp';
    // for amount based
    const GOAL_AMOUNT                   = 'goal_amount';
    const COLLECTED_AMOUNT              = 'collected_amount';
    // for supporter based
    const AVALIABLE_UNITS               = 'available_units';
    const DISPLAY_AVAILABLE_UNITS       = 'display_available_units';
    const SOLD_UNITS                    = 'sold_units';
    const DISPLAY_SOLD_UNITS            = 'display_sold_units';

    //Pixel Tracking
    const PP_FB_PIXEL_TRACKING_ID              = 'pp_fb_pixel_tracking_id';
    const PP_GA_PIXEL_TRACKING_ID              = 'pp_ga_pixel_tracking_id';
    const PP_FB_EVENT_ADD_TO_CART_ENABLED      = 'pp_fb_event_add_to_cart_enabled';
    const PP_FB_EVENT_INITIATE_PAYMENT_ENABLED = 'pp_fb_event_initiate_payment_enabled';
    const PP_FB_EVENT_PAYMENT_COMPLETE         = 'pp_fb_event_payment_complete_enabled';

    const PARTNER_WEBHOOK_SETTINGS              = Constants::PARTNER_WEBHOOK_SETTINGS_KEY;
    const PARTNER_SHIPROCKET                    = Constants::PARTNER_SHIPROCKET;
    const COMPUTED_SETTINGS                     = 'computed_settings';

    // shiprocket specific udfs name
    const ADDRESS                               = 'address';
    const CITY                                  = 'city';
    const STATE                                 = 'state';
    const PINCODE                               = 'pincode';

    // hosted Cache key names
    const ENTITY_BASE_CACHE_KEY                         = "hosted:entity";
    const SERIALIZE_PP_CACHE_KEY                        = "paymentlink:serialized";

    const CUSTOM_DOMAIN                         = 'custom_domain';
    const CUSTOM_DOMAIN_SLUG                    = 'custom_domain_slug'; // This is only for validation key
    const SETTINGS_CUSTOM_DOMAIN_KEY            = "entity_settings_custom_domain";

    // HDFC Collect Now Mandatory UDF fields
    const PAYER_NAME = 'payer__name';

    //File upload
    const PRI_REF_ID                            = 'pri__ref__id';

    const SETTINGS_KEYS                = [
        self::THEME,
        self::UDF_SCHEMA,
        self::ALLOW_MULTIPLE_UNITS,
        self::ALLOW_SOCIAL_SHARE,
        self::PAYMENT_SUCCESS_REDIRECT_URL,
        self::PAYMENT_SUCCESS_MESSAGE,
        self::CHECKOUT_OPTIONS,
        self::PAYMENT_BUTTON_LABEL,
        self::VERSION,
        self::PP_BUTTON_DISABLE_BRANDING,
        self::PP_BUTTON_TEXT,
        self::PP_BUTTON_THEME,
        self::PAYMENT_BUTTON_TEMPLATE_TYPE,
        self::PP_FB_PIXEL_TRACKING_ID,
        self::PP_GA_PIXEL_TRACKING_ID,
        self::PP_FB_EVENT_ADD_TO_CART_ENABLED,
        self::PP_FB_EVENT_INITIATE_PAYMENT_ENABLED,
        self::PP_FB_EVENT_PAYMENT_COMPLETE,
        self::GOAL_TRACKER,
        self::PARTNER_WEBHOOK_SETTINGS,
        self::CUSTOM_DOMAIN,
        self::ONE_CLICK_CHECKOUT,
        self::SHIPPING_FEE_RULE,
    ];

    const INVOICE_DETAILS_KEYS          = [
        self::RECEIPT_ENABLE,
        self::SELECTED_INPUT_FIELD,
        self::CUSTOM_SERIAL_NUMBER,
        self::ENABLE_80G_DETAILS,
    ];

    const BUTTON_PREFERENCES_KEYS = [
        self::PP_BUTTON_DISABLE_BRANDING,
        self::PP_BUTTON_TEXT,
        self::PP_BUTTON_THEME,
    ];

    const PARTNER_WEBHOOKS = [
        Constants::PARTNER_ZAPIER     => Merchant\Webhook\Event::ZAPIER_PAYMENT_PAGE_PAID_V1,
        Constants::PARTNER_SHIPROCKET => Merchant\Webhook\Event::SHIPROCKET_PAYMENT_PAGE_PAID_V1
    ];

    /**
     * expire_by has to be atleast 15 minutes from current timestamp
     */
    const MIN_EXPIRY_SECS = 900;

    protected static $sign        = 'pl';

    protected $entity             = 'payment_link';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
        self::TYPE,
        self::VIEW_TYPE,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CURRENCY_SYMBOL,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TIMES_PAID,
        self::TOTAL_AMOUNT_PAID,
        self::STATUS,
        self::STATUS_REASON,
        self::SHORT_URL,
        self::USER_ID,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
        self::TYPE,
        self::PAYMENT_PAGE_ITEMS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::VIEW_TYPE,
    ];

    protected $appends = [
        self::CURRENCY_SYMBOL,
    ];

    protected $public = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CURRENCY_SYMBOL,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TIMES_PAID,
        self::TOTAL_AMOUNT_PAID,
        self::STATUS,
        self::STATUS_REASON,
        self::SHORT_URL,
        self::USER_ID,
        self::USER,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::NOTES,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
        self::TYPE,
        self::PAYMENT_PAGE_ITEMS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $hosted = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::CURRENCY_SYMBOL,
        self::EXPIRE_BY,
        self::TIMES_PAYABLE,
        self::TIMES_PAID,
        self::STATUS,
        self::STATUS_REASON,
        self::SHORT_URL,
        self::RECEIPT,
        self::TITLE,
        self::DESCRIPTION,
        self::SUPPORT_CONTACT,
        self::SUPPORT_EMAIL,
        self::TERMS,
        self::TYPE,
        self::PAYMENT_PAGE_ITEMS,
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::TIMES_PAYABLE     => 'int',
        self::TIMES_PAID        => 'int',
        self::TOTAL_AMOUNT_PAID => 'int',
    ];

    protected $dates = [
        self::EXPIRE_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults = [
        self::AMOUNT             => null,
        self::EXPIRE_BY          => null,
        self::TIMES_PAYABLE      => null,
        self::TIMES_PAID         => 0,
        self::TOTAL_AMOUNT_PAID  => 0,
        self::STATUS             => Status::ACTIVE,
        self::STATUS_REASON      => null,
        self::DESCRIPTION        => null,
        self::CURRENCY           => 'INR',
        self::NOTES              => [],
        self::HOSTED_TEMPLATE_ID => null,
        self::UDF_JSONSCHEMA_ID  => null,
        self::SUPPORT_CONTACT    => null,
        self::SUPPORT_EMAIL      => null,
        self::TERMS              => null,
        self::TYPE               => Type::PAYMENT,
        self::VIEW_TYPE          => self::VIEW_TYPE_PAGE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::DESCRIPTION,
    ];

    protected $embeddedRelations   = [
        self::PAYMENT_PAGE_ITEMS,
    ];

    /**
     * @param string $id
     *
     * @return string
     */
    public static function getHostedCacheKey(string $id): string
    {
        $prefix = Config::get("app.nocode.cache.prefix");

        return  $prefix . ":"
            . self::ENTITY_BASE_CACHE_KEY . ":"
            . self::SERIALIZE_PP_CACHE_KEY . ":"
            . $id;
    }

    /**
     * @param string $id
     *
     * @return void
     */
    public static function clearHostedCacheForPageId(string $id)
    {
        $cacheKey = Entity::getHostedCacheKey($id);

        Cache::forget($cacheKey);
    }

    /**
     * @return int
     */
    public static function getHostedCacheTTL(): int
    {
        return (int) Config::get("app.nocode.cache.hosted_ttl");
    }

    /**
     * Converting description to quill js object format for backward compatibility.
     *
     * @param array $array
     */
    public function setPublicDescriptionAttribute(array & $array)
    {
        $description = $array[Entity::DESCRIPTION];

        // Newer values for description attribute are json encoded quilljs meta object.
        if (($description !== null) and (json_decode($description) === null))
        {
            $array[Entity::DESCRIPTION] = Utility::convertTextToQuillFormat($description);
        }
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

    public function paymentPageItems()
    {
        return $this->hasMany(PaymentPageItem\Entity::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function nocodeCustomUrl()
    {
        return $this->hasOne(NocodeCustomUrl\Entity::class, NocodeCustomUrl\Entity::PRODUCT_ID);
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

    public function getStatusReason()
    {
        return $this->getAttribute(self::STATUS_REASON);
    }

    public function getShortUrl()
    {
        return $this->getAttribute(self::SHORT_URL);
    }

    public function getExpireBy()
    {
        return $this->getAttribute(self::EXPIRE_BY);
    }

    public function getTimesPayable()
    {
        return $this->getAttribute(self::TIMES_PAYABLE);
    }

    public function getTimesPaid(): int
    {
        return $this->getAttribute(self::TIMES_PAID);
    }

    public function getTotalAmountPaid(): int
    {
        return $this->getAttribute(self::TOTAL_AMOUNT_PAID);
    }

    public function getHostedTemplateId()
    {
        return $this->getAttribute(self::HOSTED_TEMPLATE_ID);
    }

    public function getUdfJsonschemaId()
    {
        return $this->getAttribute(self::UDF_JSONSCHEMA_ID);
    }

    public function getVersion(): string
    {
        return $this->getSettings()[Entity::VERSION] ?? Version::V1;
    }

    public function getViewType(): string
    {
        return $this->getAttribute(self::VIEW_TYPE);
    }

    public function getProductType(): string
    {
        if ($this->getViewType() === ViewType::PAGE)
        {
            return Order\ProductType::PAYMENT_PAGE;
        }
        if($this->getViewType() === ViewType::PAYMENT_HANDLE)
        {
            return Order\ProductType::PAYMENT_HANDLE;
        }
        return Order\ProductType::PAYMENT_BUTTON;
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getMetaDescription()
    {
        $description = $this->getAttribute(self::DESCRIPTION);

        if (($description !== null) and (json_decode($description) !== null))
        {
            $decodedDescription = json_decode($description, true);

            if (isset($decodedDescription['metaText']) === true)
            {
                return $decodedDescription['metaText'];
            }
        }

        return $description;
    }

    public function getTitle()
    {
        return $this->getAttribute(self::TITLE);
    }

    public function getTerms()
    {
        return $this->getAttribute(self::TERMS);
    }

    public function isActive(): bool
    {
        return ($this->getStatus() === Status::ACTIVE);
    }

    public function isInactive(): bool
    {
        return ($this->getStatus() === Status::INACTIVE);
    }

    public function isExpired(): bool
    {
        return (($this->getStatus() === Status::INACTIVE) and
                ($this->getStatusReason() === StatusReason::EXPIRED));
    }

    public function isCompleted(): bool
    {
        return (($this->getStatus() === Status::INACTIVE) and
                ($this->getStatusReason() === StatusReason::COMPLETED));
    }

    public function isDeactivated(): bool
    {
        return (($this->getStatus() === Status::INACTIVE) and
                ($this->getStatusReason() === StatusReason::DEACTIVATED));
    }

    public function isPastExpireBy(): bool
    {
        $now = Carbon::now(Timezone::IST)->timestamp;

        return (($this->getExpireBy() !== null) and
                ($now >= $this->getExpireBy()));
    }

    public function isTimesPayableExhausted(): bool
    {
        $paymentPageItems = $this->paymentPageItems()->get();

        foreach ($paymentPageItems as $paymentPageItem)
        {
            if ($paymentPageItem->isStockLeft() === true)
            {
                return false;
            }
        }

        return true;
    }

    public function isReceiptEnabled(): bool
    {
        $receiptEnable = Settings\Accessor::for($this, Settings\Module::PAYMENT_LINK)
            ->get(self::RECEIPT_ENABLE);

        if(isset($receiptEnable) === true and $receiptEnable == '1')
        {
            return true;
        }

        return false;
    }

    public function getSelectedInputField(): string
    {
        return Settings\Accessor::for($this, Settings\Module::PAYMENT_LINK)
            ->get(self::SELECTED_INPUT_FIELD);
    }

    public function isCustomSerialNumberEnabled(): bool
    {
        $customSerialNumber = Settings\Accessor::for($this, Settings\Module::PAYMENT_LINK)
            ->get(self::CUSTOM_SERIAL_NUMBER);

        if(isset($customSerialNumber) === true and $customSerialNumber == '1')
        {
            return true;
        }

        return false;
    }

    /**
     * Checks if link in it's current state is payable or not.
     * @return boolean
     */
    public function isPayable(): bool
    {
        // Must be 'active' and expire_by must not be past now(CRON might yet to be mark it as expired, in that case)
        return (($this->isActive() === true) and
                ($this->isPastExpireBy() === false));
    }

    /**
     * Payment link's hosted view long url is one of the following formats:
     * - https://pages.razorpay.in/pl_10000000000000/view
     * - https://pages.razorpay.in/AlphaNumMin4Max20Slug
     * Notice the suffix in 1nd kind of route(when there is no slug), we need this distinction for routes to work.
     *
     * @param  string      $plHostedBaseUrl
     * @param  string|null $slug
     * @return string
     */
    public function getHostedViewUrl(string $plHostedBaseUrl, string $slug = null): string
    {
        return $plHostedBaseUrl . '/' . ($slug === null ? $this->getPublicId() . '/view' : $slug);
    }

    /**
     * Gets the slug part from short URL.
     * @return string|null
     */
    public function getSlugFromShortUrl()
    {
        $parts = explode('/', $this->getShortUrl());

        return end($parts);
    }

    public function getCapturedPaymentsCount(): int
    {
        return $this->payments()->whereNotNull(Payment\Entity::CAPTURED_AT)->count();
    }

    /**
     * Get settings associated with payment link entity.
     * @param  string|null $key
     * @return \Razorpay\Spine\DataTypes\Dictionary|string
     */
    public function getSettings(string $key = null)
    {
        $accessor = $this->getSettingsAccessor();

        return $key === null ? $accessor->all() : $accessor->get($key);
    }

    public function getEnabledPartnerWebhooks()
    {
        $accessor = $this->getSettingsAccessor();

        $partnerWebhookSettings = $accessor->get(Constants::PARTNER_WEBHOOK_SETTINGS_KEY);

        // Hardcoding zapier settings because its activated by oauth and merchant does not have
        // an option to enable/disable from payment page dashboard
        $partnerWebhookSettings[Constants::PARTNER_ZAPIER] = "1";

        return $partnerWebhookSettings;
    }

    /**
     * Get computed settings associated with payment link entity.
     * @param  string|null $key
     * @return \Razorpay\Spine\DataTypes\Dictionary|string
     */
    public function getComputedSettings(string $key = null)
    {
        $accessor = $this->getComputedSettingsAccessor();

        return $key === null ? $accessor->all() : $accessor->get($key);
    }

    /**
     * Used when expecting either a scalar string value against settings key or null(instead of Dictionary).
     * In case of 'null', above call returns instance of Dictionary for some reason.
     *
     * @param  string $key
     * @return string|null
     */
    public function getSettingsScalarElseNull(string $key)
    {
        $resp = $this->getSettings($key);

        return (is_string($resp) === true) ? $resp : null;
    }

    public function getComputedSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::PAYMENT_LINK_COMPUTED);
    }

    public function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::PAYMENT_LINK);
    }

    public function getAmountToSendSmsOrEmail()
    {
        $paymentPageItems = $this->paymentPageItems;

        if (count($paymentPageItems) !== 1)
        {
            return null;
        }

        $paymentPageItem = $paymentPageItems->get(0);

        $item = $paymentPageItem->item;

        return $item->getAmount();
    }

    public function getMetricDimensions(): array
    {
        return [
            'view_type' => $this->getViewType(),
        ];
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
        $securityBrandingLogo = '';
        $isCurlecOrg = false;

        if($this->merchant->shouldShowCustomOrgBranding() === true)
        {
            $org = $this->merchant->org;
            if(ORG_ENTITY::isOrgCurlec($org->getId()) === true){
                $branding = $this->getCurlecBrandingConfig();
                $securityBrandingLogo = $branding['security_branding_logo'];
                $isCurlecOrg = true;
            }
            $brandingLogo = $org->getCheckoutLogo();
        }

        return [
            'branding_logo'          => $brandingLogo,
            'security_branding_logo' => $securityBrandingLogo,
            'is_curlec_org'          => $isCurlecOrg,
        ];
    }

    public function getCurlecBrandingConfig()
    {
        $branding = [];
        $branding['show_rzp_logo'] = true;
        $branding['security_branding_logo'] = "https://cdn.razorpay.com/static/assets/i18n/malaysia/security-branding.png";
        return $branding;
    }

    public function getHandleUrl(): string
    {
        if($this->getViewType() !== ViewType::PAYMENT_HANDLE)
        {
            throw new BadRequestException('URL exist for Payment Handle');
        }

        $phHostedViewUrl = Config::get('app.payment_handle_hosted_base_url');

        return $phHostedViewUrl . '/' . $this->getSlugFromShortUrl();
    }

    // -------------------------------------- End Getters -----------------------------

    // ----------------------------------------- Setters ------------------------------

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setStatusReason(string $statusReason = null)
    {
        if ($statusReason !== null)
        {
            StatusReason::checkStatusReason($statusReason);
        }

        $this->setAttribute(self::STATUS_REASON, $statusReason);
    }

    public function setShortUrl(string $url)
    {
        $this->setAttribute(self::SHORT_URL, $url);
    }

    public function incrementTimesPaidBy(int $incrementValue)
    {
        $this->setAttribute(self::TIMES_PAID, ($this->getTimesPaid() + $incrementValue));
    }

    public function incrementTotalAmountPaidBy(int $incrementValue)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_PAID, ($this->getTotalAmountPaid() + $incrementValue));
    }

    public function setUdfJsonschemaId(string $id)
    {
        $this->setAttribute(self::UDF_JSONSCHEMA_ID, $id);
    }

    // -------------------------------------- End Setters -----------------------------
}
