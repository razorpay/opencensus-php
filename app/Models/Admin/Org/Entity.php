<?php

namespace RZP\Models\Admin\Org;

use Illuminate\Database\Eloquent\SoftDeletes;

use App;
use RZP\Models\Feature;
use RZP\Constants\Table;
use RZP\Models\Admin\Base;
use RZP\Models\Base\Traits\RevisionableTrait;
use RZP\Trace\TraceCode;

class Entity extends Base\Entity
{
    use SoftDeletes;
    use RevisionableTrait;

    const AUTH_TYPE                       = 'auth_type';
    const BUSINESS_NAME                   = 'business_name';
    const DISPLAY_NAME                    = 'display_name';
    const EMAIL                           = 'email';
    const EMAIL_DOMAINS                   = 'email_domains';
    const ALLOW_SIGN_UP                   = 'allow_sign_up';
    const LOGIN_LOGO_URL                  = 'login_logo_url';
    const MAIN_LOGO_URL                   = 'main_logo_url';
    const INVOICE_LOGO_URL                = 'invoice_logo_url';
    const CHECKOUT_LOGO_URL               = 'checkout_logo_url';
    const EMAIL_LOGO_URL                  = 'email_logo_url';
    const DELETED_AT                      = 'deleted_at';
    const CUSTOM_CODE                     = 'custom_code';
    const ADMIN                           = 'admin';
    const FROM_EMAIL                      = 'from_email';
    const SIGNATURE_EMAIL                 = 'signature_email';
    const CROSS_ORG_ACCESS                = 'cross_org_access';
    const DEFAULT_PRICING_PLAN_ID         = 'default_pricing_plan_id';
    const BACKGROUND_IMAGE_URL            = 'background_image_url';
    const MERCHANT_STYLES                 = 'merchant_styles';
    const MERCHANT_SECOND_FACTOR_AUTH     = 'merchant_second_factor_auth';
    const MERCHANT_MAX_WRONG_2FA_ATTEMPTS = 'merchant_max_wrong_2fa_attempts';
    const ADMIN_SECOND_FACTOR_AUTH        = 'admin_second_factor_auth';
    const ADMIN_MAX_WRONG_2FA_ATTEMPTS    = 'admin_max_wrong_2fa_attempts';
    const SECOND_FACTOR_AUTH_MODE         = 'second_factor_auth_mode';
    const PAYMENT_APPS_LOGO_URL           = 'payment_apps_logo_url';
    const PAYMENT_BTN_LOGO_URL            = 'payment_btn_logo_url';
    const EXTERNAL_REDIRECT_URL           = 'external_redirect_url';
    const EXTERNAL_REDIRECT_URL_TEXT      = 'external_redirect_url_text';
    const MERCHANT_SESSION_TIMEOUT_IN_SECONDS = 'merchant_session_timeout_in_seconds';

    /**
     * Org level features
     */
    const FEATURES                 = 'features';
    /**
     * Added to distinguish between regular heimdall orgs and restricted ones like SBI
     * which need a custom admin view of transaction entities and some file upload
     * functionality like disputes and emi files
     */
    const TYPE                    = 'type';

    const WORKFLOW_PERMISSIONS = 'workflow_permissions';

    /**
     * Holds all the permissions as relation key.
     */
    const PERMISSIONS       = 'permissions';

    const RAZORPAY_ORG_ID       = '100000razorpay';
    const HDFC_ORG_ID           = '6dLbNSpv5XbCOG';
    const BOB_ORG_ID            = '7ia1ttoyqIL8sw';
    const AXIS_ORG_ID           = 'CLTnQqDj9Si8bx';
    const ICICI_ORG_ID          = 'EKUZMBUtgInwi0';
    const SIB_ORG_ID            = 'HrgeWjbnzZefSN';
    const AXIS_EASYPAY_ORG_ID   = 'ISCkwbk39MdTk5';
    const KOTAK_ORG_ID          = 'IUXvshap3HbzOs';
    const BAJAJ_ORG_ID          = 'CerI5wCZlnyN1Q';
    const BAJAJ_ORG_SIGNED_ID   = 'org_CerI5wCZlnyN1Q';
    const CURLEC_ORG_ID         = 'KjWRtYXwpK6VfK';
    const HDFC_COLLECT_ORG_ID   = 'ISCBolfdHQnhj4';

    /**
     * Org Id list on which Merchant on boarding escalation has to be triggered.
     */
    const ORG_ID_LIST = [
        self::RAZORPAY_ORG_ID,
        self::ICICI_ORG_ID,
    ];

    /**
     * Key used to send hostname of org for other services
     */
    const PRIMARY_HOST_NAME = 'primary_host_name';

    /**
     * One of the types
     */
    const RESTRICTED      = 'restricted';

    const DEFAULT_MERCHANT_SESSION_TIMEOUT_IN_SECONDS = 43200;

    /**
     * Org IDs whose merchants should allow dynamic wallet flow.
     * Dynamic wallet flow enabled means, for power wallets, front end will not hardcode otp flow,
     * instead it will decide otp/redirect flow based on payment create response.
     * This is done as power wallet flow is not supported in some gateways like payu,ccavenue. And hence,
     * merchants who route their payments via payu,ccavenue should have a dynamic wallet flow.
     *
     * Merchants belonging to these org ids, will have dynamic wallet flow.
     *
     * @var string[]
     */
    protected static $dynamicWalletFlowOrgs = array(
        self::AXIS_ORG_ID,
    );

    /**
     * Org features, saved to this variable once fetched to avoid
     * repeated DB calls.
     *
     * @var null
     */
    protected $loadedFeatures = null;

    protected static $sign = 'org';

    protected $entity = 'org';

    protected $generateIdOnCreate = false;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $embeddedRelations = [
        self::PERMISSIONS,
        self::WORKFLOW_PERMISSIONS,
    ];

    protected $fillable = [
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::EMAIL_DOMAINS,
        self::ALLOW_SIGN_UP,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::INVOICE_LOGO_URL,
        self::CHECKOUT_LOGO_URL,
        self::EMAIL_LOGO_URL,
        self::CROSS_ORG_ACCESS,
        self::CUSTOM_CODE,
        self::FROM_EMAIL,
        self::SIGNATURE_EMAIL,
        self::DEFAULT_PRICING_PLAN_ID,
        self::TYPE,
        self::FEATURES,
        self::BACKGROUND_IMAGE_URL,
        self::MERCHANT_STYLES,
        self::MERCHANT_SESSION_TIMEOUT_IN_SECONDS,
        self::MERCHANT_SECOND_FACTOR_AUTH,
        self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS,
        self::ADMIN_SECOND_FACTOR_AUTH,
        self::ADMIN_MAX_WRONG_2FA_ATTEMPTS,
        self::SECOND_FACTOR_AUTH_MODE,
        self::PAYMENT_APPS_LOGO_URL,
        self::PAYMENT_BTN_LOGO_URL,
        self::EXTERNAL_REDIRECT_URL,
        self::EXTERNAL_REDIRECT_URL_TEXT,
    ];

    protected $visible = [
        self::ID,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::AUTH_TYPE,
        self::EMAIL_DOMAINS,
        self::ALLOW_SIGN_UP,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::INVOICE_LOGO_URL,
        self::CHECKOUT_LOGO_URL,
        self::EMAIL_LOGO_URL,
        self::DELETED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::CUSTOM_CODE,
        self::FROM_EMAIL,
        self::SIGNATURE_EMAIL,
        self::PERMISSIONS,
        self::WORKFLOW_PERMISSIONS,
        self::DEFAULT_PRICING_PLAN_ID,
        self::TYPE,
        self::BACKGROUND_IMAGE_URL,
        self::MERCHANT_STYLES,
        self::MERCHANT_SESSION_TIMEOUT_IN_SECONDS,
        self::MERCHANT_SECOND_FACTOR_AUTH,
        self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS,
        self::ADMIN_SECOND_FACTOR_AUTH,
        self::ADMIN_MAX_WRONG_2FA_ATTEMPTS,
        self::SECOND_FACTOR_AUTH_MODE,
        self::PAYMENT_APPS_LOGO_URL,
        self::PAYMENT_BTN_LOGO_URL,
        self::EXTERNAL_REDIRECT_URL,
        self::EXTERNAL_REDIRECT_URL_TEXT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::DISPLAY_NAME,
        self::BUSINESS_NAME,
        self::EMAIL,
        self::EMAIL_DOMAINS,
        self::ALLOW_SIGN_UP,
        self::LOGIN_LOGO_URL,
        self::MAIN_LOGO_URL,
        self::INVOICE_LOGO_URL,
        self::CHECKOUT_LOGO_URL,
        self::EMAIL_LOGO_URL,
        self::AUTH_TYPE,
        self::CREATED_AT,
        self::CUSTOM_CODE,
        self::FROM_EMAIL,
        self::SIGNATURE_EMAIL,
        self::PERMISSIONS,
        self::WORKFLOW_PERMISSIONS,
        self::DEFAULT_PRICING_PLAN_ID,
        self::BACKGROUND_IMAGE_URL,
        self::MERCHANT_STYLES,
        self::MERCHANT_SESSION_TIMEOUT_IN_SECONDS,
        self::MERCHANT_SECOND_FACTOR_AUTH,
        self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS,
        self::ADMIN_SECOND_FACTOR_AUTH,
        self::ADMIN_MAX_WRONG_2FA_ATTEMPTS,
        self::SECOND_FACTOR_AUTH_MODE,
        self::PAYMENT_APPS_LOGO_URL,
        self::PAYMENT_BTN_LOGO_URL,
        self::EXTERNAL_REDIRECT_URL,
        self::EXTERNAL_REDIRECT_URL_TEXT,
    ];

    protected $guarded = [
        self::ID
    ];

    protected $casts = [
        self::ALLOW_SIGN_UP    => 'bool',
        self::CROSS_ORG_ACCESS => 'bool',
        self::MERCHANT_STYLES => 'array',
        self::DELETED_AT      => 'int',
    ];

    protected $defaults = [
        self::CROSS_ORG_ACCESS                => false,
        self::DEFAULT_PRICING_PLAN_ID         => null,
        self::TYPE                            => null,
        self::MERCHANT_SECOND_FACTOR_AUTH     => false,
        self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS => Constants::DEFAULT_MAX_WRONG_2FA_ATTEMPTS,
        self::ADMIN_SECOND_FACTOR_AUTH        => false,
        self::ADMIN_MAX_WRONG_2FA_ATTEMPTS    => Constants::DEFAULT_MAX_WRONG_2FA_ATTEMPTS,
        self::SECOND_FACTOR_AUTH_MODE         => Constants::SMS,
        self::EXTERNAL_REDIRECT_URL          => null,
        self::EXTERNAL_REDIRECT_URL_TEXT     => null,
        self::MERCHANT_SESSION_TIMEOUT_IN_SECONDS => self::DEFAULT_MERCHANT_SESSION_TIMEOUT_IN_SECONDS,
    ];

    protected $publicSetters = [
        self::ID,
        self::MERCHANT_SECOND_FACTOR_AUTH,
        self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS,
        self::ADMIN_SECOND_FACTOR_AUTH,
        self::ADMIN_MAX_WRONG_2FA_ATTEMPTS,
        self::SECOND_FACTOR_AUTH_MODE,
    ];

    protected $diff = [
        self::BUSINESS_NAME,
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($org)
        {
            $org->hostnames()->delete();
            $org->roles()->delete();
            $org->admins()->delete();
            $org->groups()->delete();
            $org->permissions()->delete();
        });

        static::restored(function ($org)
        {
            $org->hostnames()->withTrashed()->restore();
            $org->roles()->withTrashed()->restore();
            $org->admins()->withTrashed()->restore();
            $org->groups()->withTrashed()->restore();
            $org->permissions()->withTrashed()->restore();
        });
    }

    public function hostnames()
    {
        return $this->hasMany('RZP\Models\Admin\Org\Hostname\Entity');
    }

    public function policy()
    {
        return $this->hasOne('RZP\Models\Admin\Org\AuthPolicy\Entity');
    }

    public function admins()
    {
        return $this->hasMany('RZP\Models\Admin\Admin\Entity');
    }

    public function groups()
    {
        return $this->hasMany('RZP\Models\Admin\Group\Entity');
    }

    public function roles()
    {
        return $this->hasMany('RZP\Models\Admin\Admin\Entity');
    }

    public function permissions()
    {
        return $this->morphToMany('RZP\Models\Admin\Permission\Entity', 'entity', Table::PERMISSION_MAP);
    }

    public function features()
    {
        return $this->morphMany('RZP\Models\Feature\Entity', 'entity');
    }

    public function workflow_permissions()
    {
        return $this->permissions()->where('enable_workflow', '=', 1);
    }

    public function getAllowSignUp()
    {
        return $this->getAttribute(self::ALLOW_SIGN_UP);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getFromEmail()
    {
        return $this->getAttribute(self::FROM_EMAIL) ?? "support@razorpay.com";
    }

    public function getSignatureEmail()
    {
        return $this->getAttribute(self::SIGNATURE_EMAIL);
    }

    public function getEmailDomains()
    {
        return $this->getAttribute(self::EMAIL_DOMAINS);
    }

    public function getDisplayName()
    {
        return $this->getAttribute(self::DISPLAY_NAME);
    }

    public function getBusinessName()
    {
        return $this->getAttribute(self::BUSINESS_NAME);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    public function getCustomCode()
    {
        return $this->getAttribute(self::CUSTOM_CODE);
    }

    public function getDefaultPricingPlanId()
    {
        return $this->getAttribute(self::DEFAULT_PRICING_PLAN_ID);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function isCrossOrgAccessEnabled()
    {
        return $this->getAttribute(self::CROSS_ORG_ACCESS);
    }

    protected function getEmailDomainsAttribute()
    {
        $emailDomains = $this->attributes[self::EMAIL_DOMAINS];

        return explode(',', $emailDomains);
    }

    protected function setEmailDomainsAttribute($emailDomains)
    {
        if (is_array($emailDomains) === true)
        {
            $emailDomains = implode(',', $emailDomains);
        }

        $this->attributes[self::EMAIL_DOMAINS] = $emailDomains;
    }

    public function getInputFields()
    {
        return $this->fillable;
    }

    public function getPrimaryHostName()
    {
        return $this->hostnames()->first()->getHostName();
    }

    public function isFeatureEnabled(string $featureName): bool
    {
        $assignedFeatures = $this->getEnabledFeatures();

        return (in_array($featureName, $assignedFeatures, true) === true);
    }

    public function getMainLogo()
    {
        return $this->getAttribute(self::MAIN_LOGO_URL);
    }

    public function getInvoiceLogo()
    {
        return $this->getAttribute(self::INVOICE_LOGO_URL);
    }

    public function getLoginLogo()
    {
        return $this->getAttribute(self::LOGIN_LOGO_URL);
    }

    public function getCheckoutLogo()
    {
        return $this->getAttribute(self::CHECKOUT_LOGO_URL);
    }

    /**
     * Return an array of features enabled for the merchant entity
     *
     * @return array
     */
    public function getEnabledFeatures(): array
    {
        // If we've already loaded features for the merchant object, return that
        if ($this->loadedFeatures !== null)
        {
            return $this->loadedFeatures;
        }
        $cacheTtl = app('repo')->feature->getCacheTtl(Feature\Entity::FEATURE);

        $cacheTags = Feature\Entity::getCacheTagsForNames($this->entity, $this->getId());

        $apiResponse = $this->features()
                            ->remember($cacheTtl)
                            ->cacheTags($cacheTags)
                            ->pluck(Feature\Entity::NAME)
                            ->toArray();

        $dcs = App::getFacadeRoot()['dcs'];
        $dcsResponse = $dcs->getDcsEnabledFeatures(Feature\Constants::ORG, $this->getId())
                           ->pluck(Feature\Entity::NAME)
                           ->toArray();

        $this->loadedFeatures = $this->mergeUniqueArrays($apiResponse, $dcsResponse);

        return $this->loadedFeatures;
    }

    // Custom function to merge and pick unique items from two arrays
    private function mergeUniqueArrays($arr1, $arr2)
    {
        foreach ($arr2 as $element)
        {
            if (!in_array($element, $arr1))
            {
                $arr1[] = $element;
            }
        }

        return $arr1;
    }

    public function setLoadedFeaturesNull()
    {
        $this->loadedFeatures = null;
    }

    public function getEmailLogo()
    {
        return $this->attributes[self::EMAIL_LOGO_URL];
    }

    public function getPaymentAppLogo()
    {
        return $this->attributes[self::PAYMENT_APPS_LOGO_URL];
    }

    public function getBackgroundImage()
    {
        return $this->attributes[self::BACKGROUND_IMAGE_URL];
    }

    public function getExternalRedirectUrl()
    {
        return $this->attributes[self::EXTERNAL_REDIRECT_URL];
    }

    public function setExternalRedirectUrl($array)
    {
        if (isset($array[self::EXTERNAL_REDIRECT_URL]) === true)
        {
            $this->attributes[self::EXTERNAL_REDIRECT_URL] = $array[self::EXTERNAL_REDIRECT_URL];
        }
    }

    public function getExternalRedirectUrlText()
    {
        return $this->attributes[self::EXTERNAL_REDIRECT_URL_TEXT];
    }

    public function setExternalRedirectUrlText($array)
    {
        if (isset($array[self::EXTERNAL_REDIRECT_URL_TEXT]) === true)
        {
            $this->attributes[self::EXTERNAL_REDIRECT_URL_TEXT] = $array[self::EXTERNAL_REDIRECT_URL_TEXT];
        }
    }

    public function isMerchant2FaEnabled(): bool
    {
        return $this->getAttribute(self::MERCHANT_SECOND_FACTOR_AUTH) === 1;
    }

    public function getMerchantMaxWrong2FaAttempts()
    {
        return $this->getAttribute(self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS);
    }

    public function isAdmin2FaEnabled(): bool
    {
        return (bool) $this->getAttribute(self::ADMIN_SECOND_FACTOR_AUTH);
    }

    public function getAdminMaxWrong2FaAttempts()
    {
        return $this->getAttribute(self::ADMIN_MAX_WRONG_2FA_ATTEMPTS);
    }

    public function get2FaAuthMode()
    {
        return $this->getAttribute(self::SECOND_FACTOR_AUTH_MODE);
    }

    public function setAdmin2FaEnabled($admin2faFlag)
    {
        $this->setAttribute(self::ADMIN_SECOND_FACTOR_AUTH,$admin2faFlag);
    }

    public function setPublicMerchantSecondFactorAuthAttribute($array)
    {
        if (isset($array[self::MERCHANT_SECOND_FACTOR_AUTH]) === true)
        {
            $merchant2Fa = (bool) $array[self::MERCHANT_SECOND_FACTOR_AUTH];

            $this->attributes[self::MERCHANT_SECOND_FACTOR_AUTH] = $merchant2Fa;
        }
    }

    public function setPublicMerchantMaxWrong2faAttemptsAttribute($array)
    {
        if (isset($array[self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS]) === true)
        {
            $merchantMaxWrong2FaAttempts = $array[self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS];

            $this->attributes[self::MERCHANT_MAX_WRONG_2FA_ATTEMPTS] = $merchantMaxWrong2FaAttempts;
        }
    }

    public function setPublicAdminSecondFactorAuthAttribute($array)
    {
        if (isset($array[self::ADMIN_SECOND_FACTOR_AUTH]) === true)
        {
            $admin2Fa = (bool) $array[self::ADMIN_SECOND_FACTOR_AUTH];

            $this->attributes[self::ADMIN_SECOND_FACTOR_AUTH] = $admin2Fa;
        }
    }

    public function setPublicAdminMaxWrong2faAttemptsAttribute($array)
    {
        if (isset($array[self::ADMIN_MAX_WRONG_2FA_ATTEMPTS]) === true)
        {
            $adminMaxWrong2FaAttempts = $array[self::ADMIN_MAX_WRONG_2FA_ATTEMPTS];

            $this->attributes[self::ADMIN_MAX_WRONG_2FA_ATTEMPTS] = $adminMaxWrong2FaAttempts;
        }
    }

    public function isDisableDefaultEmailReceipt(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::ORG_DISABLE_DEF_EMAIL_RECEIPT) === true);
    }

    public function setPublicSecondFactorAuthModeAttribute($array)
    {
        if (isset($array[self::SECOND_FACTOR_AUTH_MODE]) === true)
        {
            $secondFactorAuthMode = $array[self::SECOND_FACTOR_AUTH_MODE];

            $this->attributes[self::SECOND_FACTOR_AUTH_MODE] = $secondFactorAuthMode;
        }
    }

    public function getMerchantStyles()
    {
        return $this->attributes[self::MERCHANT_STYLES];
    }

    public function setMerchantStyles(string $styles)
    {
        $this->attributes[self::MERCHANT_STYLES] = $styles;
    }

    /*
     * Merchant features for org access.
     * Check if org has a feature set as defined in
     * Feature\Constants::$merchantFeaturesForOrgAccess
     */
    public function getFeatureAssignedToOrg($orgId)
    {
        $org = app('repo')->org->findOrFailPublic($orgId);

        foreach (Feature\Constants::$merchantFeaturesForOrgAccess as $orgFeature => $merchantFeature)
        {
            $isFeatureAssignedToOrg = $org->isFeatureEnabled($orgFeature);

            if($isFeatureAssignedToOrg === true)
            {
                return $orgFeature;
            }
        }
        return null;
    }

    /**
     * Return if an org belongs to dynamic wallet flow orgs.
     *
     *
     * @param $orgId
     * @return bool
     */
    public static function isDynamicWalletFlowOrg($orgId)
    {
        return in_array($orgId, self::$dynamicWalletFlowOrgs);
    }

    public static function isOrgRazorpay(string $orgId): bool
    {
        return $orgId === self::RAZORPAY_ORG_ID;
    }

    public static function isOrgCurlec(string $orgId): bool
    {
        return $orgId === self::CURLEC_ORG_ID;
    }
}

