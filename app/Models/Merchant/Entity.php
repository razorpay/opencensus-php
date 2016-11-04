<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\Category;
use RZP\Models\Pricing\Service as PricingService;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const NAME                      = 'name';
    const EMAIL                     = 'email';
    const ACTIVATED                 = 'activated';
    const ACTIVATED_AT              = 'activated_at';
    const LIVE                      = 'live';
    const HOLD_FUNDS                = 'hold_funds';
    const PRICING_PLAN_ID           = 'pricing_plan_id';
    const INTERNATIONAL             = 'international';
    const BILLING_LABEL             = 'billing_label';
    const TRANSACTION_REPORT_EMAIL  = 'transaction_report_email';
    const RECEIPT_EMAIL_ENABLED     = 'receipt_email_enabled';
    const SETTLEMENT_SCHEDULE       = 'settlement_schedule';
    const SETTLEMENT_SCHEDULE_ID    = 'settlement_schedule_id';
    const WEBSITE                   = 'website';
    const CATEGORY                  = 'category';
    const FEATURES                  = 'features';
    const SCOPE                     = 'scope';
    const FEE_BEARER                = 'fee_bearer';
    const BRAND_COLOR               = 'brand_color';
    const RISK_RATING               = 'risk_rating';
    const LOGO_URL                  = 'logo_url';
    const AWS_LOGO_URL              = 'aws_logo_url';
    const MAX_PAYMENT_AMOUNT        = 'max_payment_amount';

    /**
     * Category for particular methods or gateways
     */
    const CATEGORY2                 = 'category2';

    /**
     * Refers to methods relation and not a property;
     */
    const METHODS                   = 'methods';
    const ORIGINAL_SIZE             = 'original';

    protected $entity = 'merchant';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(
        self::TRANSACTION_REPORT_EMAIL);

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::SCOPE,
        self::WEBSITE,
        self::CATEGORY,
        self::CATEGORY2,
        self::FEATURES,
        self::LOGO_URL,
        self::FEE_BEARER,
        self::HOLD_FUNDS,
        self::RISK_RATING,
        self::BRAND_COLOR,
        self::INTERNATIONAL,
        self::BILLING_LABEL,
        self::MAX_PAYMENT_AMOUNT,
        self::SETTLEMENT_SCHEDULE,
        self::SETTLEMENT_SCHEDULE_ID,
        self::RECEIPT_EMAIL_ENABLED,
        self::TRANSACTION_REPORT_EMAIL,
    );

    // Requires PHP 5.6
    const CONFIG_LIST = array(
        self::ID,
        self::BRAND_COLOR,
        self::TRANSACTION_REPORT_EMAIL,
        self::LOGO_URL,
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED,
        self::ACTIVATED_AT,
        self::LIVE,
        self::HOLD_FUNDS,
        self::PRICING_PLAN_ID,
        self::WEBSITE,
        self::CATEGORY,
        self::CATEGORY2,
        self::INTERNATIONAL,
        self::FEE_BEARER,
        self::BILLING_LABEL,
        self::RECEIPT_EMAIL_ENABLED,
        self::TRANSACTION_REPORT_EMAIL,
        self::SETTLEMENT_SCHEDULE,
        self::SETTLEMENT_SCHEDULE_ID,
        self::METHODS,
        self::BRAND_COLOR,
        self::RISK_RATING,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::LOGO_URL,
     );

    protected $defaults = array(
        self::CATEGORY2              => null,
        self::LIVE                   => false,
        self::ACTIVATED              => false,
        self::ACTIVATED_AT           => null,
        self::RECEIPT_EMAIL_ENABLED  => true,
        self::HOLD_FUNDS             => false,
        self::SETTLEMENT_SCHEDULE    => 3,
        self::SETTLEMENT_SCHEDULE_ID => null,
        self::FEATURES               => Features::CARD_SAVING,
        self::FEE_BEARER             => FeeBearer::PLATFORM,
        self::BRAND_COLOR            => null,
        self::RISK_RATING            => 3,
        self::LOGO_URL               => null,
        self::MAX_PAYMENT_AMOUNT     => null,
    );

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::LOGO_URL
    );

    const MAX_PAYMENT_AMOUNT_DEFAULT = 50000000;

    protected function generateTransactionReportEmail($input)
    {
        $email = array($input[self::EMAIL]);

        $this->setAttribute(self::TRANSACTION_REPORT_EMAIL, $email);
    }

    public function isActivated()
    {
        return $this->getAttribute(self::ACTIVATED);
    }

    public function isInternational()
    {
        return (bool) $this->getAttribute(self::INTERNATIONAL);
    }

    public function isFeeBearerCustomer()
    {
        return $this->getAttribute(self::FEE_BEARER) === FeeBearer::CUSTOMER;
    }

    public function isLive()
    {
        return $this->getAttribute(self::LIVE);
    }

    public function isEducationCategory()
    {
        $eduCategories = array(
            '8211',
            '8220',
            '8241',
            '8244',
            '8249',
            '8299');

        return in_array($this->getAttribute(self::CATEGORY), $eduCategories);
    }

    public function isFeatureEnabled($feature)
    {
        return in_array($feature, $this->getFeatures());
    }

    public function activate()
    {
        $this->setAttribute(self::ACTIVATED, true);
        $this->setAttribute(self::LIVE, true);
        $this->setAttribute(self::ACTIVATED_AT, time());
    }

    public function liveEnable()
    {
        $this->setAttribute(self::LIVE, true);
    }

    public function liveDisable()
    {
        $this->setAttribute(self::LIVE, false);
    }

    public function hasSchedule()
    {
        return ($this->getSettlementScheduleId() !== null);
    }

    public function keys()
    {
        return $this->hasMany('RZP\Models\Key\Entity');
    }

    public function pricing()
    {
        return $this->belongsTo('RZP\Models\Pricing\Entity', self::PRICING_PLAN_ID, 'plan_id');
    }

    public function schedule()
    {
        return $this->belongsTo(
            'RZP\Models\Schedule\Entity', self::SETTLEMENT_SCHEDULE_ID);
    }

    public function payments()
    {
        return $this->hasMany('RZP\Models\Payment\Entity');
    }

    public function invoices()
    {
        return $this->hasMany('RZP\Models\Invoice\Entity');
    }

    public function items()
    {
        return $this->hasMany('RZP\Models\Item\Entity');
    }

    public function customers()
    {
        return $this->hasMany('RZP\Models\Customer\Entity');
    }

    public function balance()
    {
        return $this->hasOne(
            'RZP\Models\Merchant\Balance\Entity', self::ID, 'id');
    }

    public function bankAccount()
    {
        return $this->hasOne(
            'RZP\Models\BankAccount\Entity', 'entity_id', self::ID);
    }

    public function methods()
    {
        return $this->hasOne(
            'RZP\Models\Merchant\Methods\Entity');
    }

    public function terminals()
    {
        return $this->hasMany(
            'RZP\Models\Terminal\Entity');
    }

    public function transactions()
    {
        return $this->hasMany(
            'RZP\Models\Transaction\Entity');
    }

    public function webhook()
    {
        return $this->hasOne(
            'RZP\Models\Merchant\Webhook\Entity');
    }

    public function setPricingPlan($planId)
    {
        $this->setAttribute(self::PRICING_PLAN_ID, $planId);
    }

    protected function setBrandColorAttribute($brandColor)
    {
        $this->attributes[self::BRAND_COLOR] = $brandColor ? strtoupper($brandColor) : null;
    }

    protected function setLogoUrlAttribute($logoUrl)
    {
        $this->attributes[self::LOGO_URL] = $logoUrl ? $logoUrl : null;
    }

    public function setLogoUrl($logoUrl)
    {
        $this->setAttribute(self::LOGO_URL, $logoUrl);
    }

    public function setCategory2($category)
    {
        return $this->setAttribute(self::CATEGORY2, $category);
    }

    public function getCategory2()
    {
        return $this->getAttribute(self::CATEGORY2);
    }

    public function getBillingLabelElseName()
    {
        $label = $this->getBillingLabel();

        if (empty($label))
        {
            $label = $this->getName();
        }

        return $label;
    }

    public function getPricingPlanId()
    {
        return $this->getAttribute(self::PRICING_PLAN_ID);
    }

    protected function getActivatedAttribute()
    {
        return (bool) $this->attributes[self::ACTIVATED];
    }

    protected function getLiveAttribute()
    {
        return (bool) $this->attributes[self::LIVE];
    }

    protected function getMaxPaymentAmountAttribute()
    {
        $amount = $this->attributes[self::MAX_PAYMENT_AMOUNT];

        if (($amount === null) or
            ($amount === '0'))
        {
            $amount = self::MAX_PAYMENT_AMOUNT_DEFAULT;
        }

        return (int) $amount;
    }

    protected function getFeeBearerAttribute()
    {
        return  FeeBearer::getBearerStringForValue($this->attributes[self::FEE_BEARER]);
    }

    protected function getInternationalAttribute()
    {
        return (bool) $this->attributes[self::INTERNATIONAL];
    }

    protected function getReceiptEmailEnabledAttribute()
    {
        return (bool) $this->attributes[self::RECEIPT_EMAIL_ENABLED];
    }

    protected function getHoldFundsAttribute()
    {
        return (bool) $this->attributes[self::HOLD_FUNDS];
    }

    protected function getCategoryAttribute()
    {
        return (int) $this->attributes[self::CATEGORY];
    }

    protected function getSettlementScheduleAttribute()
    {
        return (int) $this->attributes[self::SETTLEMENT_SCHEDULE];
    }

    public function getWebsite()
    {
        return $this->attributes[self::WEBSITE];
    }

    public function getBillingLabel()
    {
        return $this->attributes[self::BILLING_LABEL];
    }

    public function getEmail()
    {
        return $this->attributes[self::EMAIL];
    }

    public function getName()
    {
        return $this->attributes[self::NAME];
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getMaxPaymentAmount()
    {
        return $this->getAttribute(self::MAX_PAYMENT_AMOUNT);
    }

    /**
     * Returns all transaction emails associated with the merchant
     * @return array array of email addresses
     */
    public function getTransactionReportEmail()
    {
        return $this->getAttribute(self::TRANSACTION_REPORT_EMAIL);
    }

    public function getFeatures()
    {
        return $this->getAttribute(self::FEATURES);
    }

    public function getBrandColor()
    {
        return $this->getAttribute(self::BRAND_COLOR);
    }

    protected function getBrandColorAttribute()
    {
        $storedBrandColor = $this->attributes[self::BRAND_COLOR];

        if ($storedBrandColor === null)
        {
            return null;
        }

        return '#' . $storedBrandColor;
    }

    public function getLogoUrl()
    {
        return $this->getAttribute(self::LOGO_URL);
    }

    public function getFullLogoUrlWithSize($size = self::ORIGINAL_SIZE)
    {
        $relativeLogoUrl = $this->getLogoUrl();

        if ($relativeLogoUrl === null)
        {
            return null;
        }

        // Different cdn urls for different contexts.
        $context = Config::get('app.context');
        $cdnUrl = Config::get('url.cdn')[$context];

        // Sample base URL : 'https://cdn.razorpay.com' + '/logos/a.png'
        // Sample actual URL : 'https://cdn.razorpay.com' + 'logos/' + 'a_medium.png'
        $baseLogoUrl = $cdnUrl . $relativeLogoUrl;

        // In DB, we are storing the base URL. The actual URL has the
        // respective size appended to it.
        $logoUrl = $this->getLogoUrlBasedOnSize($baseLogoUrl, $size);

        return $logoUrl;
    }

    public function getAwsLogoUrl($size = self::ORIGINAL_SIZE)
    {
        $awsConfig = Config::get('aws');

        $publicLogoRelativeUrl = $this->attributes[self::LOGO_URL];
        $bucketName = $awsConfig['logo_bucket'];
        $regionName = $awsConfig['region'];

        $baseAwsLogoUrl = $bucketName . '.' . 's3-website-' . $regionName . '.amazonaws.com' . $publicLogoRelativeUrl;

        // In DB, we are storing the base URL. The actual URL
        // has the respective size appended to it.
        $awsLogoUrl = $this->getLogoUrlBasedOnSize($baseAwsLogoUrl, $size);

        return $awsLogoUrl;
    }

    protected function getLogoUrlBasedOnSize($logoUrl, $size)
    {
        // Gets the position of last dot.
        // Gets the substring until before the last dot.
        // Appends '_size' to the substring.
        // Appends the substring from the last dot to the end of url.

        $extension_pos = strrpos($logoUrl, '.');
        $logoUrlBasedOnSize = substr($logoUrl, 0, $extension_pos)
                                .'_'
                                .$size
                                .substr($logoUrl, $extension_pos);

        return $logoUrlBasedOnSize;
    }

    protected function getTransactionReportEmailAttribute()
    {
        $emails = explode(',', $this->attributes[self::TRANSACTION_REPORT_EMAIL]);

        // Just so there is no whitespace before or after the email
        return array_map('trim', $emails);
    }

    protected function getFeaturesAttribute()
    {
        $features = $this->attributes[self::FEATURES];

        if (empty($features))
        {
            return [];
        }
        else
        {
            $features = explode(Features::DELIMITER, $features);
            return array_map('trim', $features);
        }
    }

    protected function setFeaturesAttribute($features)
    {
        if (is_array($features))
        {
            $this->attributes[self::FEATURES] =
                implode(Features::DELIMITER, $features);
        }
        else
        {
            $this->attributes[self::FEATURES] = $features;
        }
    }

    protected function setEmailAttribute($email)
    {
        $this->attributes[self::EMAIL] = mb_strtolower($email);
    }

    protected function setTransactionReportEmailAttribute($emails)
    {
        if (is_array($emails) === false)
        {
            //
            // This is only called for the factory instances
            // of the merchant entity because laracasts testdummy
            // does not support array in factory values yet.
            //
            $emails = [$emails];
        }

        $emails = array_unique(array_map('mb_strtolower', array_map('trim', $emails)));

        $this->attributes[self::TRANSACTION_REPORT_EMAIL] = implode(',', $emails);
    }

    protected function setFeeBearerAttribute($bearer)
    {
        $this->attributes[self::FEE_BEARER] = FeeBearer::getValueForBearerString($bearer);
    }

    protected function setPublicLogoUrlAttribute(array & $array)
    {
        if (empty($array[self::LOGO_URL]) === false)
        {
            $array[self::LOGO_URL] = $this->getFullLogoUrlWithSize(self::ORIGINAL_SIZE);
        }
    }

    public function getSettlementSchedule()
    {
        return $this->getAttribute(self::SETTLEMENT_SCHEDULE);
    }

    public function getSettlementScheduleId()
    {
        return $this->getAttribute(self::SETTLEMENT_SCHEDULE_ID);
    }

    public function holdFunds()
    {
        return (bool) $this->attributes[self::HOLD_FUNDS];
    }

    public function isReceiptEmailsEnabled()
    {
        return $this->getReceiptEmailEnabledAttribute();
    }

    public function getRiskRating()
    {
        return $this->getAttribute(self::RISK_RATING);
    }

    public function getSubventionType()
    {
        // Move to subvention type if ever.
        if ($this->isFeeBearerCustomer())
        {
            return FeeBearer::CUSTOMER;
        }

        return FeeBearer::PLATFORM;
    }

    public function getRedactedAccountNumber()
    {
        $bankAccount = $this->bankAccount()->first();

        if ($bankAccount !== null)
        {
            $ac = $bankAccount->getAccountNumber();
            //
            // How many times should we repeat the redacted portion
            // This does not give a precise result,
            // but it looks good in groups of 4
            //
            // (strlen($ac) - 4) = Length of the segment we want to convert to X
            // divide by 4 to get number of such segments
            // and take ceil so we have a whole number of these

            $repeat = ceil((strlen($ac) - 4)/4);

            // repeat this section $repeat times
            // and then just append the original last 4 digits
            return str_repeat('XXXX-', $repeat) . substr($ac, -4);
        }
        else
        {
            return 'XXXX-XXXX-XXXX';
        }
    }

    public function enableReceiptEmails()
    {
        $this->setAttribute(self::RECEIPT_EMAIL_ENABLED, true);
    }

    /** Overridden from the PublicEntity */
    public function getDashboardEntityLink()
    {
        $id = $this->getId();

        return "https://dashboard.razorpay.com/admin#/app/merchants/$id/detail";
    }

    /**
     * TPV -> Third Party Validation
     * For securities, mutual funds etc. category of merchants,
     * only netbanking is required. And for netbanking also,
     * we need to verify the bank account number of customer during payment
     * which is not required for a normal payment flow.
     *
     * @return boolean
     */
    public function isTPVRequired()
    {
        // 9999 - Test MCC requiring TPV
        // 6211 - Live MCC requiring TPV
        $tpvCategories = $this->getTPVCategories();

        $category = $this->getCategory();

        if (isset($tpvCategories[$category]))
        {
            return true;
        }

        return false;
    }

    public function getTPVCategories()
    {
        return array(9999 => 9999, 6211 => 6211);
    }

    public function isShared()
    {
        return ($this->getId() === Account::SHARED_ACCOUNT);
    }

    public function toArrayConfig()
    {
        return array_only($this->toArrayPublic(), self::CONFIG_LIST);
    }
}
