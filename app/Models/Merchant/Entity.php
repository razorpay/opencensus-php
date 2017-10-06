<?php

namespace RZP\Models\Merchant;

use Config;

use RZP\Models\User;
use RZP\Models\Base;
use RZP\Models\Emi;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Invitation;
use RZP\Models\Merchant\Detail;
use Conner\Tagging\Taggable;
use RZP\Exception\LogicException;

/**
 * @property Detail\Entity $merchantDetail
 */
class Entity extends Base\PublicEntity
{
    use Taggable;

    const ID                        = 'id';
    const ORG_ID                    = 'org_id';
    const NAME                      = 'name';
    const EMAIL                     = 'email';
    const PARENT_ID                 = 'parent_id';
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
    const WEBSITE                   = 'website';
    const CATEGORY                  = 'category';
    const CATEGORY2                 = 'category2';
    const INVOICE_CODE              = 'invoice_code';
    const SCOPE                     = 'scope';
    const FEE_BEARER                = 'fee_bearer';
    const FEE_MODEL                 = 'fee_model';
    const LINKED_ACCOUNT_KYC        = 'linked_account_kyc';
    const BRAND_COLOR               = 'brand_color';
    const HANDLE                    = 'handle';
    const RISK_RATING               = 'risk_rating';
    const RISK_THRESHOLD            = 'risk_threshold';
    const LOGO_URL                  = 'logo_url';
    const AWS_LOGO_URL              = 'aws_logo_url';
    const MAX_PAYMENT_AMOUNT        = 'max_payment_amount';
    const AUTO_REFUND_DELAY         = 'auto_refund_delay';
    const AUTO_CAPTURE_LATE_AUTH    = 'auto_capture_late_auth';
    const CONVERT_CURRENCY          = 'convert_currency';
    const ARCHIVED_AT               = 'archived_at';
    const SUSPENDED_AT              = 'suspended_at';

    // Coupon Related Data for display only
    const COUPON_CODE               = 'coupon_code';

    //
    // Followings are derived data indexed in ES and goes to
    // admin dashboard as it is.
    //

    // Whether the entity is marketplace entity or not
    const IS_MARKETPLACE            = 'is_marketplace';
    // Referrer for the entity is name of first admin.
    const REFERRER                  = 'referrer';
    // List of tags this entity is tagged as.
    const TAG_LIST                  = 'tag_list';

    /**
     * Constants for merchant analytics keys
     */
    const FILTERS                   = 'filters';
    const KEY_MERCHANT_ID           = 'merchant_id';

    //
    // Configs
    //

    const AUTO_REFUND_DELAY_DEFAULT = 432000; // 5 days
    const SETTLEMENT_SCHEDULE_DEFAULT_DELAY = 3;
    // 30 minutes in seconds
    const MIN_AUTO_REFUND_DELAY = 1800;
    // 10 days in seconds
    const MAX_AUTO_REFUND_DELAY = 864000;

    /**
     * A query parameter to filter results based on
     * account status which can be one of suspended,
     * archived, activated, pending or dead.
     */
    const ACCOUNT_STATUS            = 'account_status';

    /**
     * A query parameters to get only merchants who
     * are sub accounts(if value is 1) or sub accounts
     * of specific merchant (if value is an id).
     */
    const SUB_ACCOUNTS              = 'sub_accounts';

    /**
     * Refers to methods relation and not a property;
     */
    const METHODS                   = 'methods';
    const ORIGINAL_SIZE             = 'original';
    const ACTION                    = 'action';
    const MEDIUM_SIZE               = 'medium';
    const MERCHANT_DETAIL           = 'merchant_detail';
    const GROUPS                    = 'groups';
    const ADMINS                    = 'admins';
    const FEATURES                  = 'features';

    const ROLE                      = 'role';
    const PIVOT                     = 'pivot';

    protected $entity = 'merchant';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
        self::TRANSACTION_REPORT_EMAIL,
        self::INVOICE_CODE,
    ];

    protected $embeddedRelations = [
        self::GROUPS,
        self::ADMINS,
    ];

    protected $fillable = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::SCOPE,
        self::ORG_ID,
        self::WEBSITE,
        self::CATEGORY,
        self::CATEGORY2,
        self::FEE_MODEL,
        self::LOGO_URL,
        self::FEE_BEARER,
        self::HOLD_FUNDS,
        self::RISK_RATING,
        self::RISK_THRESHOLD,
        self::BRAND_COLOR,
        self::HANDLE,
        self::INTERNATIONAL,
        self::BILLING_LABEL,
        self::CONVERT_CURRENCY,
        self::AUTO_REFUND_DELAY,
        self::MAX_PAYMENT_AMOUNT,
        self::LINKED_ACCOUNT_KYC,
        self::SETTLEMENT_SCHEDULE,
        self::RECEIPT_EMAIL_ENABLED,
        self::AUTO_CAPTURE_LATE_AUTH,
        self::TRANSACTION_REPORT_EMAIL,
    ];

    // Requires PHP 5.6
    const CONFIG_LIST = [
        self::ID,
        self::BRAND_COLOR,
        self::HANDLE,
        self::TRANSACTION_REPORT_EMAIL,
        self::LOGO_URL,
        self::AUTO_CAPTURE_LATE_AUTH,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED,
        self::ACTIVATED_AT,
        self::LIVE,
        self::HOLD_FUNDS,
        self::PRICING_PLAN_ID,
        self::PARENT_ID,
        self::WEBSITE,
        self::CATEGORY,
        self::CATEGORY2,
        self::INTERNATIONAL,
        self::LINKED_ACCOUNT_KYC,
        self::FEE_BEARER,
        self::FEE_MODEL,
        self::BILLING_LABEL,
        self::RECEIPT_EMAIL_ENABLED,
        self::TRANSACTION_REPORT_EMAIL,
        self::SETTLEMENT_SCHEDULE,
        self::METHODS,
        self::CONVERT_CURRENCY,
        self::MAX_PAYMENT_AMOUNT,
        self::AUTO_REFUND_DELAY,
        self::AUTO_CAPTURE_LATE_AUTH,
        self::BRAND_COLOR,
        self::HANDLE,
        self::RISK_RATING,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SUSPENDED_AT,
        self::ARCHIVED_AT,
        self::LOGO_URL,
        self::ORG_ID,
        self::GROUPS,
        self::ADMINS,
     ];

    protected $defaults = [
        self::PARENT_ID              => null,
        self::CATEGORY2              => null,
        self::LIVE                   => false,
        self::ACTIVATED              => false,
        self::ACTIVATED_AT           => null,
        self::RECEIPT_EMAIL_ENABLED  => true,
        self::HOLD_FUNDS             => false,
        self::SETTLEMENT_SCHEDULE    => self::SETTLEMENT_SCHEDULE_DEFAULT_DELAY,
        self::FEE_BEARER             => FeeBearer::PLATFORM,
        self::BRAND_COLOR            => null,
        self::HANDLE                 => null,
        self::RISK_RATING            => 3,
        self::LINKED_ACCOUNT_KYC     => 0,
        self::RISK_THRESHOLD         => null,
        self::LOGO_URL               => null,
        self::MAX_PAYMENT_AMOUNT     => null,
        self::ORG_ID                 => null,
        self::AUTO_REFUND_DELAY      => null,
        self::AUTO_CAPTURE_LATE_AUTH => false,
        self::FEE_MODEL              => FeeModel::PREPAID,
        self::CONVERT_CURRENCY       => null,
        self::ARCHIVED_AT            => null,
        self::SUSPENDED_AT           => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::LOGO_URL,
    ];

    protected $casts = [
        self::ACTIVATED                 => 'bool',
        self::LIVE                      => 'bool',
        self::INTERNATIONAL             => 'bool',
        self::RECEIPT_EMAIL_ENABLED     => 'bool',
        self::HOLD_FUNDS                => 'bool',
        self::LINKED_ACCOUNT_KYC        => 'bool',
        self::CATEGORY                  => 'int',
        self::SETTLEMENT_SCHEDULE       => 'int',
        self::RISK_THRESHOLD            => 'int',
        self::CONVERT_CURRENCY          => 'bool',
        self::AUTO_CAPTURE_LATE_AUTH    => 'bool',
    ];

    protected $eventFields = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::WEBSITE,
        self::CATEGORY,
        self::CATEGORY2,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::ACTIVATED_AT,
    ];

    const MAX_PAYMENT_AMOUNT_DEFAULT = 50000000;
    const RISK_THRESHOLD_DEFAULT     = 5;

    protected function generateTransactionReportEmail($input)
    {
        $email = array($input[self::EMAIL]);

        $this->setAttribute(self::TRANSACTION_REPORT_EMAIL, $email);
    }

    protected function generateInvoiceCode($input)
    {
        $id = $this->getAttribute(self::ID);

        $first8 = substr($id, 0, 8);

        $last4 = substr($id, -4);

        $invoiceCode = strtoupper($first8 . $last4);

        $this->setAttribute(self::INVOICE_CODE, $invoiceCode);
    }

    public function isActivated()
    {
        return $this->getAttribute(self::ACTIVATED);
    }

    public function isSuspended()
    {
        return ($this->getAttribute(self::SUSPENDED_AT) !== null);
    }

    public function isArchived()
    {
        return ($this->getAttribute(self::ARCHIVED_AT) !== null);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function isFeeBearerCustomer()
    {
        return $this->getAttribute(self::FEE_BEARER) === FeeBearer::CUSTOMER;
    }

    public function isPrepaid()
    {
        return $this->getAttribute(self::FEE_MODEL) === FeeModel::PREPAID;
    }

    public function isLive()
    {
        return $this->getAttribute(self::LIVE);
    }

    /**
     * Is the merchant a linked-account under Marketplace?
     */
    public function isLinkedAccount(): bool
    {
        return $this->isAttributeNotNull(self::PARENT_ID);
    }

    public function isMarketplace(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::MARKETPLACE);
    }

    public function linkedAccountsRequireKyc(): bool
    {
        return $this->getAttribute(self::LINKED_ACCOUNT_KYC);
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
        $assignedFeatures = $this->getEnabledFeatures();

        return (in_array($feature, $assignedFeatures, true) === true);
    }

    /**
     * Return an array of features enabled for the merchant entity
     *
     * @return array
     */
    public function getEnabledFeatures()
    {
        return $this->features
                    ->pluck(Feature\Entity::NAME)
                    ->toArray();
    }

    public function getEmiSubvention()
    {
        $subvention = Emi\Subvention::CUSTOMER;

        if ($this->isFeatureEnabled(Feature\Constants::EMI_MERCHANT_SUBVENTION))
        {
            $subvention = Emi\Subvention::MERCHANT;
        }

        return $subvention;
    }

    public function activate()
    {
        $this->setAttribute(self::ACTIVATED, true);
        $this->setAttribute(self::LIVE, true);
        $this->setAttribute(self::ACTIVATED_AT, time());
    }

    public function suspend()
    {
        $this->setAttribute(self::SUSPENDED_AT, time());
        $this->setAttribute(self::LIVE, false);
        $this->setAttribute(self::HOLD_FUNDS, true);
    }

    public function unsuspend()
    {
        $this->setAttribute(self::SUSPENDED_AT, null);
        $this->setAttribute(self::LIVE, true);
        $this->setAttribute(self::HOLD_FUNDS, false);
    }

    public function liveEnable()
    {
        $this->setAttribute(self::LIVE, true);
    }

    public function liveDisable()
    {
        $this->setAttribute(self::LIVE, false);
    }

    public function archive()
    {
        $this->setAttribute(self::ARCHIVED_AT, time());
    }

    public function unarchive()
    {
        $this->setAttribute(self::ARCHIVED_AT, null);
    }

    public function keys()
    {
        return $this->hasMany('RZP\Models\Key\Entity');
    }

    public function pricing()
    {
        return $this->belongsTo('RZP\Models\Pricing\Entity', self::PRICING_PLAN_ID, 'plan_id');
    }

    public function payments()
    {
        return $this->hasMany('RZP\Models\Payment\Entity');
    }

    public function items()
    {
        return $this->hasMany('RZP\Models\Item\Entity');
    }

    public function lineItems()
    {
        return $this->hasMany('RZP\Models\LineItem\Entity');
    }

    public function invoices()
    {
        return $this->hasMany('RZP\Models\Invoice\Entity');
    }

    public function customers()
    {
        return $this->hasMany('RZP\Models\Customer\Entity');
    }

    // Linked-accounts belonging to the Marketplace
    public function accounts()
    {
        return $this->hasMany('RZP\Models\Merchant\Entity', self::PARENT_ID, self::ID);
    }

    // Marketplace owner
    public function parent()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::PARENT_ID, self::ID);
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

    public function org()
    {
        return $this->belongsTo(
            'RZP\Models\Admin\Org\Entity');
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

    public function features()
    {
        return $this->morphMany('RZP\Models\Feature\Entity', 'entity');
    }

    public function transfers()
    {
        return $this->morphMany('RZP\Models\Transfer\Entity', 'to');
    }

    public function merchantDetail()
    {
        return $this->hasOne(Detail\Entity::class, self::MERCHANT_ID, self::ID);
    }

    public function setPricingPlan($planId)
    {
        $this->setAttribute(self::PRICING_PLAN_ID, $planId);
    }

    public function setSettlementSchedule($settlementSchedule)
    {
        $this->setAttribute(self::SETTLEMENT_SCHEDULE, $settlementSchedule);
    }

    public function setMaxPaymentAmount(int $maxAmount)
    {
        $this->setAttribute(self::MAX_PAYMENT_AMOUNT, $maxAmount);
    }

    protected function setBrandColorAttribute($brandColor)
    {
        $this->attributes[self::BRAND_COLOR] = $brandColor ? strtoupper($brandColor) : null;
    }

    protected function setHandleAttribute(string $handle = null)
    {
        $this->attributes[self::HANDLE] = $handle ? strtoupper($handle) : null;
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

    public function getBillingLabelNotName()
    {
        return $this->attributes[self::BILLING_LABEL];
    }

    public function getFilteredDba()
    {
        $label = $this->getBillingLabel();

        $filteredLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);

        return $filteredLabel;
    }

    public function getPricingPlanId()
    {
        return $this->getAttribute(self::PRICING_PLAN_ID);
    }

    public function offers()
    {
        return $this->hasMany('RZP\Models\Offer\Entity');
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
        return FeeBearer::getBearerStringForValue($this->attributes[self::FEE_BEARER]);
    }

    protected function getFeeModelAttribute()
    {
        return FeeModel::getFeeModelStringForValue($this->attributes[self::FEE_MODEL]);
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

    protected function getBillingLabelAttribute()
    {
        $label = $this->attributes[self::BILLING_LABEL];

        if (empty($label))
        {
            $label = $this->getName();
        }

        return $label;
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
        return $this->getBillingLabelAttribute();
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

    public function getAutoRefundDelay()
    {
        $autoRefundDelay = $this->getAttribute(self::AUTO_REFUND_DELAY);

        if ($autoRefundDelay === null)
        {
            $autoRefundDelay = self::AUTO_REFUND_DELAY_DEFAULT;
        }

        return $autoRefundDelay;
    }

    public function getAutoCaptureLateAuth()
    {
        return $this->getAttribute(self::AUTO_CAPTURE_LATE_AUTH);
    }

    /**
     * Returns all transaction emails associated with the merchant
     * @return array array of email addresses
     */
    public function getTransactionReportEmail()
    {
        return $this->getAttribute(self::TRANSACTION_REPORT_EMAIL);
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function getInvoiceCode()
    {
        return $this->getAttribute(self::INVOICE_CODE);
    }

    /**
     * check if api or gateway should do currency conversion for merchant
     *
     * @return bool
     */
    public function convertOnApi()
    {
        return $this->getAttribute(self::CONVERT_CURRENCY);
    }

    public function getBrandColor()
    {
        return $this->getAttribute(self::BRAND_COLOR);
    }

    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    public function getBrandColorElseDefault()
    {
        return $this->getAttribute(self::BRAND_COLOR) ??
            Merchant\Checkout::CHECKOUT_DEFAULT_THEME_COLOR;
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

    public function getFeeBearer()
    {
        return $this->getAttribute(self::FEE_BEARER);
    }

    public function getFeeModel()
    {
        return $this->getAttribute(self::FEE_MODEL);
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
        $regionName = $awsConfig['bucket_region'];

        $baseAwsLogoUrl = $bucketName . '.' . 's3-website-' . $regionName . '.amazonaws.com' . $publicLogoRelativeUrl;

        // In DB, we are storing the base URL. The actual URL
        // has the respective size appended to it.
        $awsLogoUrl = $this->getLogoUrlBasedOnSize($baseAwsLogoUrl, $size);

        return $awsLogoUrl;
    }

    public function getParentId()
    {
        return $this->getAttribute(self::PARENT_ID);
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
        return array_filter(array_map('trim', $emails));
    }

    protected function setEmailAttribute($email)
    {
        $this->attributes[self::EMAIL] = mb_strtolower($email);
    }

    protected function setWebsiteAttribute($website)
    {
        $this->attributes[self::WEBSITE] = mb_strtolower($website);
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

    protected function setFeeModelAttribute($feeModel)
    {
        $this->attributes[self::FEE_MODEL] = FeeModel::getValueForFeeModelString($feeModel);
    }

    protected function setAutoRefundDelayAttribute($autoRefundDelayPeriod)
    {
        if ($autoRefundDelayPeriod === null)
        {
            $this->attributes[self::AUTO_REFUND_DELAY] = null;
            return;
        }

        $autoRefundDelay = explode(' ', $autoRefundDelayPeriod);

        $time = $autoRefundDelay[0];
        $duration = $autoRefundDelay[1];

        switch ($duration)
        {
            case 'mins':
                $multiplier = 60;
                break;

            case 'hours':
                $multiplier = 3600;
                break;

            case 'days':
                $multiplier = 86400;
                break;

            default:
                throw new LogicException(
                    'Invalid duration for auto refund delay',
                    ErrorCode::SERVER_ERROR_INVALID_DURATION,
                    [
                        'auto_refund_delay_period' => $autoRefundDelayPeriod,
                        'duration' => $duration,
                        'time' => $time
                    ]);
        }

        $delay = $time * $multiplier;

        $this->attributes[self::AUTO_REFUND_DELAY] = (int) $delay;
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

    public function getHoldFunds()
    {
        return $this->getAttribute(self::HOLD_FUNDS);
    }

    public function holdFunds()
    {
        $this->setHoldFunds(true);
    }

    public function releaseFunds()
    {
        $this->setHoldFunds(false);
    }

    public function setHoldFunds($holdFunds)
    {
        $this->setAttribute(self::HOLD_FUNDS, $holdFunds);
    }

    public function isReceiptEmailsEnabled()
    {
        return $this->getAttribute(self::RECEIPT_EMAIL_ENABLED);
    }

    public function getRiskRating()
    {
        return $this->getAttribute(self::RISK_RATING);
    }

    public function getRiskThreshold()
    {
        return $this->getAttribute(self::RISK_THRESHOLD);
    }

    protected function getRiskThresholdAttribute()
    {
        $riskThreshold = $this->attributes[self::RISK_THRESHOLD];

        if ($riskThreshold === null)
        {
            $riskThreshold = self::RISK_THRESHOLD_DEFAULT;
        }

        return (int) $riskThreshold;
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
            return $bankAccount->getRedactedAccountNumber();
        }
        else
        {
            return 'XXXX-XXXX-XXXX';
        }
    }

    public function getBusinessStateCode()
    {
        $businessStateCode = null;

        $merchantDetail = $this->merchantDetail;

        if ($merchantDetail !== null)
        {
            $businessStateCode = $merchantDetail->getBusinessStateCode();
        }

        return $businessStateCode;
    }

    public function getGstin()
    {
        if ($this->merchantDetail === null)
        {
            return null;
        }

        return $this->merchantDetail->getGstin() ?? $this->merchantDetail->getPGstin();
    }

    public function enableReceiptEmails()
    {
        $this->setAttribute(self::RECEIPT_EMAIL_ENABLED, true);
    }

    public function disableReceiptEmails()
    {
        $this->setAttribute(self::RECEIPT_EMAIL_ENABLED, false);
    }

    public function enableInternational()
    {
        $this->setAttribute(self::INTERNATIONAL, true);
    }

    public function disableInternational()
    {
        $this->setAttribute(self::INTERNATIONAL, false);
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
     * This now enforced via a feature flag, because certain merchants
     * from mutual_funds do not require the
     *
     * @return boolean
     */
    public function isTPVRequired()
    {
        return ($this->isFeatureEnabled(Feature\Constants::TPV) === true);
    }

    public function isTestAccount()
    {
        return Account::isTestAccount($this->getId());
    }

    public function getTPVCategories()
    {
        return Terminal\Category::getTPVCategories();
    }

    public function isShared()
    {
        return ($this->getId() === Account::SHARED_ACCOUNT);
    }

    public function toArrayConfig()
    {
        return array_only($this->toArrayPublic(), self::CONFIG_LIST);
    }

    /**
     * Used for Marketplace, dashboard:
     * Return report data for a linked account under a marketplace merchant
     * @todo: Move this to Merchant/Account/Entity when account onboarding is merged.
     *
     * @return array
     */
    public function toArrayReport(): array
    {
        $data = parent::toArrayReport();

        // Fields that show up on the report
        $reportFields = [
            self::ID,
            self::EMAIL,
            self::NAME,
            self::CREATED_AT,
            self::ACTIVATED,
            self::ACTIVATED_AT,
        ];

        $data = array_only($data, $reportFields);

        $data[self::ID] = AccountEntity::getSignedId($this->getAttribute(self::ID));

        return $data;
    }

    public function groups()
    {
        return $this->morphedByMany('\RZP\Models\Admin\Group\Entity', 'entity', Table::MERCHANT_MAP);
    }

    public function admins()
    {
        return $this->morphedByMany('\RZP\Models\Admin\Admin\Entity', 'entity', Table::MERCHANT_MAP);
    }

    /**
     * Get the owners of the merchant.
     */
    public function owners()
    {
        return $this->users()->where('role','owner')->get();
    }

    /**
     * Get the primary owner of the merchant.
     */
    public function primaryOwner()
    {
        return $this->owners()->first();
    }

    public function users()
    {
        return $this->belongsToMany(User\Entity::class, Table::MERCHANT_USERS)
                    ->withPivot(User\Entity::ROLE)
                    ->orderBy(self::NAME);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation\Entity::class)
                    ->orderBy(Invitation\Entity::CREATED_AT, 'desc');
    }

    public function isEmailOptional()
    {
        return $this->isFeatureEnabled(Feature\Constants::EMAIL_OPTIONAL);
    }

    public function isPhoneOptional()
    {
        return $this->isFeatureEnabled(Feature\Constants::CONTACT_OPTIONAL);
    }

    public function getOptionalInputConfig()
    {
        $config = [];

        if ($this->isEmailOptional() === true)
        {
            $config[] = 'email';
        }

        if ($this->isPhoneOptional() === true)
        {
            $config[] = 'contact';
        }

        return $config;
    }

    public function toArrayUser()
    {
        $attributes = [
            self::ID           => $this->getAttribute(self::ID),
            self::NAME         => $this->getAttribute(self::NAME),
            self::EMAIL        => $this->getAttribute(self::EMAIL),
            self::ACTIVATED    => $this->getAttribute(self::ACTIVATED),
            self::ARCHIVED_AT  => $this->getAttribute(self::ARCHIVED_AT),
            self::SUSPENDED_AT => $this->getAttribute(self::SUSPENDED_AT),
            self::LOGO_URL     => $this->getFullLogoUrlWithSize(self::MEDIUM_SIZE),
            self::CREATED_AT   => $this->getAttribute(self::CREATED_AT),
            self::UPDATED_AT   => $this->getAttribute(self::UPDATED_AT),
        ];

        $attributes[self::ROLE] = $this->getAttribute(self::PIVOT)->role;

        return $attributes;
    }

    public function toArrayEvent()
    {
        $merchantAttributes = [];

        foreach ($this->eventFields as $eventField)
        {
            if ($this->hasAttribute($eventField))
            {
                $merchantAttributes[$eventField] = $this->getAttribute($eventField);
            }
        }

        if ($this->merchantDetail !== null)
        {
            $merchantDetailAttributes = $this->merchantDetail->toArrayEvent();

            $merchantAttributes = array_merge($merchantAttributes, $merchantDetailAttributes);
        }

        return $merchantAttributes;
    }
}
