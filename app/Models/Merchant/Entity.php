<?php

namespace RZP\Models\Merchant;

use App;
use Config;
use Carbon\Carbon;
use Conner\Tagging\Taggable;
use Razorpay\Trace\Logger;
use RZP\Models\Payment\Method;
use RZP\Services\Dcs;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Admin\Org;
use RZP\Models\Bank\IFSC;
use RZP\Models\BankAccount;
use RZP\Models\BankingAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Pricing;
use RZP\Models\Base;
use RZP\Models\Base\QueryCache\Cacheable;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Emi;
use RZP\Models\Feature;
use RZP\Models\Admin;
use RZP\Models\Invitation;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Detail;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\ProductInternational\ProductInternationalField;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;
use RZP\Models\Merchant\PurposeCode\PurposeCodeList;
use RZP\Models\Partner\Commission;
use RZP\Models\Payment\Event;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Models\Settlement;
use RZP\Models\State;
use RZP\Models\Terminal;
use RZP\Models\User;
use RZP\Models\Workflow\Action;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Methods\Core as MethodCore;
use RZP\Models\Payment\Config as PaymentConfig;
use RZP\Models\Partner\Activation as PartnerActivation;
use RZP\Models\Merchant\Account\Constants as AccountConstants;
use MVanDuijker\TransactionalModelEvents as TransactionalModelEvents;
use RZP\Models\Payment\Processor as PaymentProcessor;
/**
 * @property Org\Entity               $org
 * @property Detail\Entity            $merchantDetail
 * @property BusinessDetail\Entity    $merchantBusinessDetail
 * @property Methods\Entity           $methods
 * @property BankAccount\Entity       $bankAccount
 * @property Balance\Entity           $bankingBalance
 * @property Balance\Entity           $sharedBankingBalance
 * @property Balance\Entity           $directBankingBalances
 * @property Balance\Entity           $primaryBalance
 * @property Balance\Entity           $reservePrimaryBalance
 * @property Balance\Entity           $reserveBankingBalance
 * @property Base\Collection          $activeBankingAccounts
 * @property Balance\Entity           $commissionBalance
 * @property PaymentConfig\Entity     $dccPaymentConfig
 * @property PartnerActivation\Entity $partnerActivation
 * @property Merchant\Product\Entity  $merchantProducts
 */
class Entity extends Base\PublicEntity
{
    use Taggable;
    use NotesTrait;
    use Cacheable;
    use TransactionalModelEvents\TransactionalAwareEvents;

    const ID_LENGTH = 14;

    const ID                             = 'id';
    const ORG_ID                         = 'org_id';
    const NAME                           = 'name';
    const EMAIL                          = 'email';
    const PARENT_ID                      = 'parent_id';
    const ACTIVATED                      = 'activated';
    const ACTIVATED_AT                   = 'activated_at';
    const LIVE                           = 'live';
    const LIVE_DISABLE_REASON            = 'live_disable_reason';
    const HOLD_FUNDS                     = 'hold_funds';
    const HOLD_FUNDS_REASON              = 'hold_funds_reason';
    const PRICING_PLAN_ID                = 'pricing_plan_id';
    const INTERNATIONAL                  = 'international';
    const BILLING_LABEL                  = 'billing_label';
    const DISPLAY_NAME                   = 'display_name';
    const TRANSACTION_REPORT_EMAIL       = 'transaction_report_email';
    const RECEIPT_EMAIL_ENABLED          = 'receipt_email_enabled';
    const CHANNEL                        = 'channel';
    const WEBSITE                        = 'website';
    const EXTERNAL_ID                    = 'external_id';
    const PRODUCT_INTERNATIONAL          = 'product_international';




    // this is same as mcc in legal entity table.
    // This will be removed after migrating to legal entity
    const CATEGORY                       = 'category';

    const WHITELISTED_IPS_LIVE           = 'whitelisted_ips_live';
    const WHITELISTED_IPS_TEST           = 'whitelisted_ips_test';
    const WHITELISTED_DOMAINS            = 'whitelisted_domains';
    const CATEGORY2                      = 'category2';
    const INVOICE_CODE                   = 'invoice_code';
    const SCOPE                          = 'scope';
    const FEE_BEARER                     = 'fee_bearer';
    const FEE_MODEL                      = 'fee_model';
    const REFUND_SOURCE                  = 'refund_source';
    const LINKED_ACCOUNT_KYC             = 'linked_account_kyc';
    const HAS_KEY_ACCESS                 = 'has_key_access';
    const PARTNER_TYPE                   = 'partner_type';
    const BRAND_COLOR                    = 'brand_color';
    const HANDLE                         = 'handle';
    const RISK_RATING                    = 'risk_rating';
    const RISK_THRESHOLD                 = 'risk_threshold';
    const ICON_URL                       = 'icon_url';
    const LOGO_URL                       = 'logo_url';
    const INVOICE_LABEL_FIELD            = 'invoice_label_field';
    const AWS_LOGO_URL                   = 'aws_logo_url';
    const MAX_PAYMENT_AMOUNT             = 'max_payment_amount';
    const AUTO_REFUND_DELAY              = 'auto_refund_delay';
    const AUTO_CAPTURE_LATE_AUTH         = 'auto_capture_late_auth';
    const CONVERT_CURRENCY               = 'convert_currency';
    const ARCHIVED_AT                    = 'archived_at';
    const SUSPENDED_AT                   = 'suspended_at';
    const NOTES                          = 'notes';
    const FEE_CREDITS_THRESHOLD          = 'fee_credits_threshold';
    const AMOUNT_CREDITS_THRESHOLD       = 'amount_credits_threshold';
    const REFUND_CREDITS_THRESHOLD       = 'refund_credits_threshold';
    const BALANCE_THRESHOLD              = 'balance_threshold';
    const PRODUCT                        = 'product';
    const DEFAULT_REFUND_SPEED           = 'default_refund_speed';
    const SECOND_FACTOR_AUTH             = 'second_factor_auth';
    const RESTRICTED                     = 'restricted';
    const DASHBOARD_WHITELISTED_IPS_LIVE = 'dashboard_whitelisted_ips_live';
    const DASHBOARD_WHITELISTED_IPS_TEST = 'dashboard_whitelisted_ips_test';
    const PARTNERSHIP_URL                = 'partnership_url';
    const LEGAL_ENTITY_ID                = 'legal_entity_id';
    const CA_STATUS                      = 'ca_status';
    const VA_STATUS                      = 'va_status';
    const AUDIT_ID                       = 'audit_id';
    const CURRENCY                       = 'currency';

    // Source denotes if a merchant activation request came from PG or business banking.
    const ACTIVATION_SOURCE        = 'activation_source';
    // Signup Source is the place from where the merchant signed up from PG or banking
    const SIGNUP_SOURCE            = 'signup_source';

    const BUSINESS_BANKING         = 'business_banking';

    const ACCOUNT_CODE              = 'account_code';
    const CODE                      = 'code';
    const SIGNUP_VIA_EMAIL          = 'signup_via_email';

    // Coupon Related Data for display only
    const COUPON_CODE              = 'coupon_code';

    // Receipt email to be triggered at payment status
    const RECEIPT_EMAIL_TRIGGER_EVENT = 'receipt_email_trigger_event';

    // International transaction limit of merchant
    const MAX_INTERNATIONAL_PAYMENT_AMOUNT = 'max_international_payment_amount';

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

    // key used to pass external legal entity id when creating merchant
    const LEGAL_EXTERNAL_ID         = 'legal_external_id';

    // key used to pass purpose code when creating merchant
    const PURPOSE_CODE              = 'purpose_code';

    /**
     * Constants for merchant analytics keys
     */
    const FILTERS                   = 'filters';
    const KEY_MERCHANT_ID           = 'merchant_id';
    const DEFAULT_FILTER            = 'default';
    const POSTED_AT                 = 'posted_at';
    const REVERSED_AT               = 'reversed_at';
    const GT                        = 'gt';
    const GTE                       = 'gte';

    //
    // Configs
    //

    const AUTO_REFUND_DELAY_DEFAULT = 432000; // 5 days
    const AUTO_REFUND_DELAY_FOR_EMANDATE = 2592000; // 30 days
    const AUTO_REFUND_DELAY_FOR_NACH = 1728000; // 20 days
    const AUTO_REFUND_DELAY_FOR_COD = 3888000; // 45 days
    const AUTO_REFUND_DELAY_FOR_NETBANKING_CORPORATE = 432000; // 5 days

    const DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_DELAY = 2;
    const DOMESTIC_SETTLEMENT_SCHEDULE_DEFAULT_HOUR = 13;
    const INTERNATIONAL_SETTLEMENT_SCHEDULE_DEFAULT_DELAY = 7;
    // 30 minutes in seconds
    const MIN_AUTO_REFUND_DELAY = 1800;
    // 10 days in seconds
    const MAX_AUTO_REFUND_DELAY = 864000;
    // Default merchant brand color used if not set already
    const DEFAULT_MERCHANT_BRAND_COLOR = '#2371EC';

    const AUTO_WHITELISTED_DOMAINS = [
        'google.com',
        'apple.com',
    ];

    const AXIS_ORG_ID = 'CLTnQqDj9Si8bx';

    /**
     * List of all OrgIds which are blocked from instant activation
     */
    const INSTANT_ACTIVATION_BLOCKED_ORG_MAPPING = [
        self::AXIS_ORG_ID,
    ];

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
    const MERCHANT_BUSINESS_DETAIL  = 'merchant_business_detail';
    const GROUPS                    = 'groups';
    const ADMINS                    = 'admins';
    const FEATURES                  = 'features';
    const BALANCE                   = 'balance';
    const BANKING_BALANCE           = 'banking_balance';

    const ROLE                      = 'role';
    const BANKING_ROLE              = 'banking_role';
    const PIVOT                     = 'pivot';

    // Partner array keys
    const USER                      = 'user';
    const DETAILS                   = 'details';
    const DASHBOARD_ACCESS          = 'dashboard_access';
    const APPLICATION               = 'application';
    const KYC_ACCESS                = 'kyc_access';

    const APPLICATION_ID            = 'application_id';
    const REFERRED_APPLICATION      = 'Referred application';

    // Extra constants for Batch
    const AUTO_SUBMIT               = 'auto_submit';
    const AUTOFILL_DETAILS          = 'autofill_details';
    const AUTO_ACTIVATE             = 'auto_activate';
    const INSTANTLY_ACTIVATE        = 'instantly_activate';
    const USE_EMAIL_AS_DUMMY        = 'use_email_as_dummy';
    const PARTNER_ID                = 'partner_id';
    const SUBMERCHANT_ID            = 'submerchant_id';
    const BANKING_ACCOUNT           = 'banking_account';
    const ACCOUNTS                  = 'accounts';
    const SKIP_BA_REGISTRATION      = 'skip_ba_registration';
    const AUTO_ENABLE_INTERNATIONAL = 'auto_enable_international';
    const CREATE_SUBMERCHANT        = 'create_submerchant';
    const DEDUPE                    = 'dedupe';

    const BANKING_ACTIVATED_AT      = 'banking_activated_at';
    const PROMOTION                 = 'promotion';
    const CREDIT_BALANCE            = 'credit_balance';
    const BULK_PAYOUTS_USER_TYPE    = 'bulk_payouts_user_type';
    const DCC                       = 'dcc';
    const DCC_MARKUP_PERCENTAGE     = 'dcc_markup_percentage';

    const ALLOW_REVERSALS           = 'allow_reversals';

    const BUSINESS_BANKING_SIGNUP_AT= 'business_banking_signup_at';

    const PARTNER_ACTIVATION  = 'partnerActivation';

    const CA_ACTIVATION_STATUS      = 'ca_activation_status';

    const DCC_RECURRING                     = 'dcc_recurring';
    const DCC_RECURRING_MARKUP_PERCENTAGE   = 'dcc_recurring_markup_percentage';

    const MCC_MARKDOWN                      = 'mcc_markdown';
    const MCC_MARKDOWN_PERCENTAGE           = 'mcc_markdown_percentage';
    const INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE    = 'intl_bank_transfer_ach_mcc_markdown_percentage';
    const INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE  = 'intl_bank_transfer_swift_mcc_markdown_percentage';


    const DEFAULT_MCC_MARKDOWNS = [
        self::MCC_MARKDOWN_PERCENTAGE                           =>  self::DEFAULT_MCC_MARKDOWN_PERCENTAGE,
        self::INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE    =>  self::DEFAULT_INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE,
        self::INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE  =>  self::DEFAULT_INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE,
    ];

    const ALLOW_USER_CREATION       = 'allow_user_creation';

    protected $entity = 'merchant';
    const COUNTRY_CODE = 'country_code';

    /**
     * Merchant features, saved to this variable once fetched to avoid
     * repeated DB calls.
     *
     * @var null
     */
    protected $loadedFeatures = null;

    protected static $sign = '';

    protected static $delimiter = '';

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
        self::TRANSACTION_REPORT_EMAIL,
        self::INVOICE_CODE,
        self::ACCOUNT_CODE,
        self::CONVERT_CURRENCY
    ];

    protected $embeddedRelations = [
        self::GROUPS,
        self::ADMINS,
    ];

    protected $fillable = [
        self::ACTIVATED_AT,
        self::ACTIVATED,
        self::ID,
        self::NAME,
        self::EMAIL,
        self::SCOPE,
        self::WEBSITE,
        self::WHITELISTED_DOMAINS,
        self::CHANNEL,
        self::CATEGORY,
        self::CATEGORY2,
        self::FEE_MODEL,
        self::REFUND_SOURCE,
        self::LOGO_URL,
        self::ICON_URL,
        self::FEE_BEARER,
        self::HOLD_FUNDS,
        self::RISK_RATING,
        self::RISK_THRESHOLD,
        self::PARTNER_TYPE,
        self::BRAND_COLOR,
        self::HANDLE,
        self::BILLING_LABEL,
        self::CONVERT_CURRENCY,
        self::AUTO_REFUND_DELAY,
        self::MAX_PAYMENT_AMOUNT,
        self::MAX_INTERNATIONAL_PAYMENT_AMOUNT,
        self::INVOICE_LABEL_FIELD,
        self::LINKED_ACCOUNT_KYC,
        self::RECEIPT_EMAIL_ENABLED,
        self::RECEIPT_EMAIL_TRIGGER_EVENT,
        self::AUTO_CAPTURE_LATE_AUTH,
        self::TRANSACTION_REPORT_EMAIL,
        self::NOTES,
        self::WHITELISTED_IPS_LIVE,
        self::WHITELISTED_IPS_TEST,
        self::FEE_CREDITS_THRESHOLD,
        self::AMOUNT_CREDITS_THRESHOLD,
        self::REFUND_CREDITS_THRESHOLD,
        self::BALANCE_THRESHOLD,
        self::DISPLAY_NAME,
        self::DASHBOARD_WHITELISTED_IPS_LIVE,
        self::DASHBOARD_WHITELISTED_IPS_TEST,
        self::DEFAULT_REFUND_SPEED,
        self::PARTNERSHIP_URL,
        self::EXTERNAL_ID,
        self::SIGNUP_SOURCE,
        self::ACCOUNT_CODE,
        self::PURPOSE_CODE,
        self::SIGNUP_VIA_EMAIL,
        self::AUDIT_ID,
        self::COUNTRY_CODE
    ];

    const CONFIG_LIST = [
        self::ID,
        self::NAME,
        self::BRAND_COLOR,
        self::HANDLE,
        self::TRANSACTION_REPORT_EMAIL,
        self::LOGO_URL,
        self::INVOICE_LABEL_FIELD,
        self::AUTO_CAPTURE_LATE_AUTH,
        self::FEE_CREDITS_THRESHOLD,
        self::AMOUNT_CREDITS_THRESHOLD,
        self::REFUND_CREDITS_THRESHOLD,
        self::BALANCE_THRESHOLD,
        self::DISPLAY_NAME,
        self::DEFAULT_REFUND_SPEED,
        self::FEE_BEARER,
    ];

    const INTERNAL_CONFIG_LIST = [
        self::BILLING_LABEL,
        self::WEBSITE,
        self::RECEIPT_EMAIL_ENABLED,
        self::PARENT_ID,
        self::INTERNATIONAL,
        self::CONVERT_CURRENCY,
        self::FEE_BEARER,
        self::CATEGORY,
    ];

    /**
     * The list of Merchant configs required by checkout-service to build the
     * `/preferences` response.
     *
     * @var string[]
     */
    public const CHECKOUT_CONFIG_LIST = [
        self::ACTIVATED,
        self::BILLING_LABEL,
        self::BRAND_COLOR,
        self::CATEGORY,
        self::COUNTRY_CODE,
        self::DISPLAY_NAME,
        self::FEE_BEARER,
        self::ID,
        self::INTERNATIONAL,
        self::LIVE,
        self::LOGO_URL,
        self::NAME,
        self::ORG_ID,
        self::PARTNERSHIP_URL,
        self::CATEGORY2,
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
        self::HAS_KEY_ACCESS,
        self::FEE_BEARER,
        self::FEE_MODEL,
        self::REFUND_SOURCE,
        self::BILLING_LABEL,
        self::RECEIPT_EMAIL_ENABLED,
        self::RECEIPT_EMAIL_TRIGGER_EVENT,
        self::TRANSACTION_REPORT_EMAIL,
        self::INVOICE_LABEL_FIELD,
        self::CHANNEL,
        self::METHODS,
        self::CONVERT_CURRENCY,
        self::MAX_PAYMENT_AMOUNT,
        self::MAX_INTERNATIONAL_PAYMENT_AMOUNT,
        self::AUTO_REFUND_DELAY,
        self::AUTO_CAPTURE_LATE_AUTH,
        self::BRAND_COLOR,
        self::HANDLE,
        self::RISK_RATING,
        self::RISK_THRESHOLD,
        self::PARTNER_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SUSPENDED_AT,
        self::ARCHIVED_AT,
        self::ICON_URL,
        self::LOGO_URL,
        self::ORG_ID,
        self::GROUPS,
        self::ADMINS,
        self::NOTES,
        self::WHITELISTED_IPS_LIVE,
        self::WHITELISTED_IPS_TEST,
        self::WHITELISTED_DOMAINS,
        self::MERCHANT_DETAIL,
        self::MERCHANT_BUSINESS_DETAIL,
        self::FEE_CREDITS_THRESHOLD,
        self::AMOUNT_CREDITS_THRESHOLD,
        self::REFUND_CREDITS_THRESHOLD,
        self::BALANCE_THRESHOLD,
        self::DISPLAY_NAME,
        self::ACTIVATION_SOURCE,
        self::BUSINESS_BANKING,
        self::SECOND_FACTOR_AUTH,
        self::RESTRICTED,
        self::DEFAULT_REFUND_SPEED,
        self::PARTNERSHIP_URL,
        self::EXTERNAL_ID,
        self::PRODUCT_INTERNATIONAL,
        self::SIGNUP_SOURCE,
        self::DCC_MARKUP_PERCENTAGE,
        self::PURPOSE_CODE,
        self::SIGNUP_VIA_EMAIL,
        self::COUNTRY_CODE,
        self::CURRENCY
     ];

    protected $defaults = [
        self::PARENT_ID                      => null,
        self::EMAIL                          => null,
        self::CATEGORY2                      => null,
        self::LIVE                           => false,
        self::ACTIVATED                      => false,
        self::ACTIVATED_AT                   => null,
        self::RECEIPT_EMAIL_ENABLED          => true,
        self::HOLD_FUNDS                     => false,
        self::FEE_BEARER                     => FeeBearer::PLATFORM,
        self::PARTNER_TYPE                   => null,
        self::BRAND_COLOR                    => null,
        self::HANDLE                         => null,
        self::RISK_RATING                    => 3,
        self::LINKED_ACCOUNT_KYC             => 0,
        self::HAS_KEY_ACCESS                 => 0,
        self::RISK_THRESHOLD                 => null,
        self::LOGO_URL                       => null,
        self::MAX_PAYMENT_AMOUNT             => null,
        self::MAX_INTERNATIONAL_PAYMENT_AMOUNT => null,
        self::ORG_ID                         => null,
        self::AUTO_REFUND_DELAY              => null,
        self::AUTO_CAPTURE_LATE_AUTH         => false,
        self::FEE_MODEL                      => FeeModel::PREPAID,
        self::REFUND_SOURCE                  => RefundSource::BALANCE,
        self::CHANNEL                        => Settlement\Channel::AXIS2,
        self::CONVERT_CURRENCY               => false,
        self::ARCHIVED_AT                    => null,
        self::SUSPENDED_AT                   => null,
        self::NOTES                          => [],
        self::WHITELISTED_IPS_LIVE           => [],
        self::WHITELISTED_IPS_TEST           => [],
        self::WHITELISTED_DOMAINS            => [],
        self::FEE_CREDITS_THRESHOLD          => null,
        self::AMOUNT_CREDITS_THRESHOLD       => null,
        self::REFUND_CREDITS_THRESHOLD       => null,
        self::BALANCE_THRESHOLD              => null,
        self::CATEGORY                       => 0,
        self::WEBSITE                        => null,
        self::INTERNATIONAL                  => 0,
        self::DASHBOARD_WHITELISTED_IPS_TEST => [],
        self::DASHBOARD_WHITELISTED_IPS_LIVE => [],
        self::RECEIPT_EMAIL_TRIGGER_EVENT    => Event::AUTHORIZED,
        self::DEFAULT_REFUND_SPEED           => RefundSpeed::NORMAL,
        self::PARTNERSHIP_URL                => null,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::LOGO_URL,
        self::DCC,
        self::RISK_THRESHOLD,
        self::CURRENCY,
    ];

    protected $casts = [
        self::ACTIVATED                      => 'bool',
        self::LIVE                           => 'bool',
        self::INTERNATIONAL                  => 'bool',
        self::RECEIPT_EMAIL_ENABLED          => 'bool',
        self::HOLD_FUNDS                     => 'bool',
        self::LINKED_ACCOUNT_KYC             => 'bool',
        self::HAS_KEY_ACCESS                 => 'bool',
        self::CATEGORY                       => 'string',
        self::RISK_THRESHOLD                 => 'int',
        self::CONVERT_CURRENCY               => 'bool',
        self::AUTO_CAPTURE_LATE_AUTH         => 'bool',
        self::WHITELISTED_IPS_LIVE           => 'array',
        self::WHITELISTED_IPS_TEST           => 'array',
        self::WHITELISTED_DOMAINS            => 'array',
        self::FEE_CREDITS_THRESHOLD          => 'int',
        self::AMOUNT_CREDITS_THRESHOLD       => 'int',
        self::REFUND_CREDITS_THRESHOLD       => 'int',
        self::BALANCE_THRESHOLD              => 'int',
        self::BUSINESS_BANKING               => 'bool',
        self::SECOND_FACTOR_AUTH             => 'bool',
        self::RESTRICTED                     => 'bool',
        self::DASHBOARD_WHITELISTED_IPS_TEST => 'array',
        self::DASHBOARD_WHITELISTED_IPS_LIVE => 'array',
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

    /**
     * These attributes will be exposed when toArrayPartner() is called,
     * along with the attributes defined in the $public array.
     *
     * @var array
     */
    protected $partner = [
        self::ID,
        self::DETAILS,
        self::USER,
        self::DASHBOARD_ACCESS,
        self::APPLICATION,
        self::PRODUCT,
        self::BANKING_ACCOUNT,
        self::KYC_ACCESS,
    ];

    protected $adminRestrictedWithFeature     = [
        Feature\Constants::AXIS_ORG_FEATURE => [
            self::ID,
            self::NAME,
            self::WEBSITE,
            self::CATEGORY,
            self::CATEGORY2,
            self::BILLING_LABEL,
            'business_registered_city',
            'business_registered_state',
            'company_pan',
        ],
    ];

    const MAX_PAYMENT_AMOUNT_DEFAULT                  = 'max_payment_amount_default';
    const MAX_INTERNATIONAL_PAYMENT_AMOUNT_DEFAULT    = 'max_international_payment_amount_default';
    const MAX_PAYMENT_AMOUNT_DEFAULT_FOR_UNREGISTERED = 'max_payment_amount_default_for_unregistered';
    const RISK_THRESHOLD_DEFAULT                      = 8;
    const DCC_MARKUP_PERCENTAGE_DEFAULT               = 8;
    const DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_APPS      = 6;
    const DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_INTL_BANK_TRANSFER = 3;
    const DCC_RECURRING_MARKUP_PERCENTAGE_DEFAULT     = 4;
    const DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_PAYPAL    = 5;
    const DEFAULT_MCC_MARKDOWN_PERCENTAGE             = 2;
    const DEFAULT_INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE    = 2;
    const DEFAULT_INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE  = 2;
    const DEFAULT_INTL_BANK_TRANSFER_MCC_MARKDOWN_PERCENTAGE = 2;

    const COUNTRY_MAXIMUM_AMOUNT = [
        "IN" => [
            self::MAX_PAYMENT_AMOUNT_DEFAULT                    => 50000000,
            self::MAX_PAYMENT_AMOUNT_DEFAULT_FOR_UNREGISTERED   => 1000000,
            self::MAX_INTERNATIONAL_PAYMENT_AMOUNT_DEFAULT      => 50000000,
        ],
        "MY" => [
            self::MAX_PAYMENT_AMOUNT_DEFAULT                    => 3000000,
            self::MAX_PAYMENT_AMOUNT_DEFAULT_FOR_UNREGISTERED   => 60000,
            self::MAX_INTERNATIONAL_PAYMENT_AMOUNT_DEFAULT      => 3000000
        ]
    ];

    // Increase txn limit for B2B intl_bank_transfer payments
    // Higher limit is now Rs 8.5L base amount
    // https://razorpay.slack.com/archives/C024U3B04LD/p1681131023230559
    const MAX_PAYMENT_AMOUNT_DEFAULT_INTL_BANK_TRANSFER = 85000000;

    public function getMaxPaymentAmountDefault()
    {
        $country = $this->getCountry();

        return self::COUNTRY_MAXIMUM_AMOUNT[$country][self::MAX_PAYMENT_AMOUNT_DEFAULT];
    }

    public function getMaxPaymentAmountDefaultForUnregistered()
    {
        $country = $this->getCountry();

        return self::COUNTRY_MAXIMUM_AMOUNT[$country][self::MAX_PAYMENT_AMOUNT_DEFAULT_FOR_UNREGISTERED];
    }

    public function getMaxInternationalPaymentAmountDefault()
    {
        $country = $this->getCountry();

        return self::COUNTRY_MAXIMUM_AMOUNT[$country][self::MAX_INTERNATIONAL_PAYMENT_AMOUNT_DEFAULT];
    }

    /**
     * {@inheritDoc}
     */
    protected $dispatchesEvents = [
        // Event 'saved' fires on insert and update both.
        'saved'   => EventSaved::class,
    ];

    public function refresh()
    {
        $instance = parent::refresh();

        // Base Eloquent Model doesn't unset/refresh arbitrary keys set. So, loadedFeatures have to be unset explicitly.
        $instance->loadedFeatures = null;

        return $instance;
    }

    public function reload()
    {
        $instance = parent::reload();

        // Base Eloquent Model doesn't unset/refresh arbitrary keys set. So, loadedFeatures have to be unset explicitly.
        $instance->loadedFeatures = null;

        return $instance;
    }

    public function enablePgInternational()
    {
        $productInternationalField = new ProductInternationalField($this);

        $productInternationalField->setProductStatus(ProductInternationalMapper::PAYMENT_GATEWAY,
                                                     ProductInternationalMapper::ENABLED);
    }

    protected function generateTransactionReportEmail($input)
    {
        if (isset($input[self::EMAIL]) === true)
        {
            $email = array($input[self::EMAIL]);

            $this->setAttribute(self::TRANSACTION_REPORT_EMAIL, $email);
        }
    }

    protected function generateInvoiceCode($input)
    {
        $id = $this->getAttribute(self::ID);

        $first8 = substr($id, 0, 8);

        $last4 = substr($id, -4);

        $invoiceCode = strtoupper($first8 . $last4);

        $this->setAttribute(self::INVOICE_CODE, $invoiceCode);
    }


    // Currently we are setting convert currency as false for every merchant by default
    // Which means that we are supporting MCC by default on every merchant. Now in case of MY merchants,
    // We need to set this as null to disable MCC flow on them and enable only when required
    protected function generateConvertCurrency($input)
    {
        if(isset($input[self::COUNTRY_CODE]) === true && $input[self::COUNTRY_CODE] === "MY")
        {
            $this->setAttribute(self::CONVERT_CURRENCY, null);
        }
    }

    protected function generateAccountCode($input)
    {
        if (isset($input[self::CODE]) === true)
        {
            $this->setAttribute(self::ACCOUNT_CODE, $input[self::CODE]);
        }
    }

    public function isActivated()
    {
        return $this->getAttribute(self::ACTIVATED);
    }

    public function isSuspended()
    {
        return ($this->getAttribute(self::SUSPENDED_AT) !== null);
    }

    public function getSuspendedAt()
    {
        return $this->getAttribute(self::SUSPENDED_AT);
    }

    public function isSignupViaEmail()
    {
        return $this->getAttribute(self::SIGNUP_VIA_EMAIL) === 1;
    }

    public function isArchived()
    {
        return ($this->getAttribute(self::ARCHIVED_AT) !== null);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function isFeeBearerPlatform()
    {
        return $this->getAttribute(self::FEE_BEARER) === FeeBearer::PLATFORM;
    }

    public function isFeeBearerCustomer()
    {
        return $this->getAttribute(self::FEE_BEARER) === FeeBearer::CUSTOMER;
    }

    public function isFeeBearerDynamic()
    {
        return $this->getAttribute(self::FEE_BEARER) === FeeBearer::DYNAMIC;
    }

    public function isFeeBearerCustomerOrDynamic()
    {
        return (($this->isFeeBearerDynamic() === true) or
                ($this->isFeeBearerCustomer() === true));
    }

    public function isPrepaid()
    {
        return $this->getAttribute(self::FEE_MODEL) === FeeModel::PREPAID;
    }

    public function isPostpaid()
    {
        return $this->getAttribute(self::FEE_MODEL) === FeeModel::POSTPAID;
    }

    public function isLive()
    {
        return $this->getAttribute(self::LIVE);
    }

    public function getActivatedAt()
    {
        return $this->getAttribute(self::ACTIVATED_AT);
    }

    public function getActivated()
    {
        return $this->getAttribute(self::ACTIVATED);
    }

    public function getLegalEntityId()
    {
        return $this->getAttribute(self::LEGAL_ENTITY_ID);
    }

    public function getReceiptEmailTriggerEvent()
    {
        return $this->getAttribute(self::RECEIPT_EMAIL_TRIGGER_EVENT);
    }

    public function getPartnershipUrl()
    {
        return $this->getAttribute(self::PARTNERSHIP_URL);
    }

    public function isSecondFactorAuth(): bool
    {
        return ($this->getAttribute(self::SECOND_FACTOR_AUTH) === true);
    }

    public function setSecondFactorAuth(bool $enabled)
    {
        $this->setAttribute(self::SECOND_FACTOR_AUTH, $enabled);
    }

    public function getRestricted(): bool
    {
        return ($this->getAttribute(self::RESTRICTED) === true);
    }

    public function setRestricted(bool $restricted)
    {
        $this->setAttribute(self::RESTRICTED, $restricted);
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

    public function isRouteKeyMerchant() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::ROUTE_KEY_MERCHANTS_QUEUE);
    }

    public function isCapitalFloatRouteMerchant() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::CAPITAL_FLOAT_ROUTE_MERCHANT);
    }

    public function isSliceRouteMerchant() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::SLICE_ROUTE_MERCHANT);
    }

    public function isDisplayParentPaymentId(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::DISPLAY_LA_PARENT_PAYMENT_ID);
    }

    public function isRouteNoDocKycEnabled() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::ROUTE_NO_DOC_KYC);
    }

    public function isAxisExpressPayEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::AXIS_EXPRESS_PAY);
    }

    public function isTerminalOnboardingEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::TERMINAL_ONBOARDING);
    }

    public function isGooglePayOmnichannelEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::GOOGLE_PAY_OMNICHANNEL);
    }

    public function isGooglePayEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::GPAY);
    }

    public function isPhonePeIntentEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::PHONEPE_INTENT);
    }

    public function isUseMswipeTerminalsEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::USE_MSWIPE_TERMINALS);
    }

    public function canHoldPayment(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::PAYMENT_ONHOLD);
    }

    public function isRouteCodeEnabled() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::ROUTE_CODE_SUPPORT);
    }

    public function isEnableMerchantExpiryForPPEnabled() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::ENABLE_MERCHANT_EXPIRY_PP);
    }

    public function isEnableCustomerAmountEnabled() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::ENABLE_CUSTOMER_AMOUNT);
    }

    public function linkedAccountsRequireKyc(): bool
    {
        return $this->getAttribute(self::LINKED_ACCOUNT_KYC);
    }

    public function getHasKeyAccess(): bool
    {
        return ($this->getAttribute(self::HAS_KEY_ACCESS) === true);
    }

    public function setBusinessBanking(bool $businessBanking)
    {
        $this->setAttribute(self::BUSINESS_BANKING, $businessBanking);
    }

    public function setHasKeyAccess(bool $hasKeyAccess)
    {
        $this->setAttribute(self::HAS_KEY_ACCESS, $hasKeyAccess);
    }

    public function setActivationSource(string $activationSource)
    {
        $this->setAttribute(self::ACTIVATION_SOURCE, $activationSource);
    }

    public function setSignupSource(string $signupSource){
        $this->setAttribute(self::SIGNUP_SOURCE, $signupSource);
    }

    protected function setReceiptEmailTriggerEventAttribute($event)
    {
        // For now we are allowing only one of the bit to be set to true, ensuring this by allowing one element in
        // $eventArray and passing initial $hex as 0
        $hex = 0;

        if ($event !== null)
        {
            $eventArray = [$event => '1'];

            $hex = Event::getCustomerEventHexValue($eventArray, $hex);
        }

        $this->attributes[self::RECEIPT_EMAIL_TRIGGER_EVENT] = $hex;
    }

    protected function getReceiptEmailTriggerEventAttribute()
    {
        $hex = $this->attributes[self::RECEIPT_EMAIL_TRIGGER_EVENT];

        $events =  Event::getCustomerEventsFromHex($hex);

        return $events[0] ?? null;
    }

    public function getReferrer()
    {
        $tagNames = $this->tagNames();

        foreach ($tagNames as $tagName)
        {
            if (substr($tagName, 0, 4) === 'Ref-')
            {
                return substr($tagName, 4);
            }
        }

        return null;
    }

    public function isEducationCategory()
    {
        $eduCategories = Constants::EDUCATION_CATEGORIES;

        return in_array($this->getAttribute(self::CATEGORY), $eduCategories);
    }

    public function isBFSIMerchantCategory()
    {
        $bfsiCategories = Constants::BFSI_MERCHANT_CATEGORIES;

        return in_array($this->getAttribute(self::CATEGORY), $bfsiCategories);
    }

    public function isInsuranceCategory()
    {
        $insuranceCategories = Constants::INSURANCE_CATEGORIES;

        return in_array($this->getAttribute(self::CATEGORY), $insuranceCategories);
    }

    public function isFeatureEnabled(string $featureName): bool
    {
        $assignedFeatures = $this->getEnabledFeatures();

        return (in_array($featureName, $assignedFeatures, true) === true);
    }

    public function isFeatureEnabledOnNonPurePlatformPartner(string $featureName): bool
    {
        $nonPurePlatformPartner = $this->getNonPurePlatformPartner();

        return isset($nonPurePlatformPartner) ? $nonPurePlatformPartner->isFeatureEnabled($featureName) : false;
    }

    public function isFeatureEnabledOnParentMerchant(string $featureName)
    {
        $parentMerchant = $this->parent;

        return isset($parentMerchant) ? $parentMerchant->isFeatureEnabled($featureName) : false;
    }

    public function isRouteNoDocKycEnabledForParentMerchant()
    {
        return $this->isFeatureEnabledOnParentMerchant(Feature\Constants::ROUTE_NO_DOC_KYC);
    }

    public function isAtLeastOneFeatureEnabled(array $features): bool
    {
        $assignedFeatures = $this->getEnabledFeatures();

        //
        // NOTE that it should be weak check because
        // array_intersect returns back an array.
        //
        return (array_intersect($features, $assignedFeatures) == true);
    }

    public function isRecurringEnabled(): bool
    {
        return ($this->isAtLeastOneFeatureEnabled(Feature\Constants::$recurringFeatures) === true);
    }

    public function isExposeARNRefundEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::EXPOSE_ARN_REFUND) === true);
    }

    public function isExposeCardExpiryEnabled(): bool
    {
       return ($this->isFeatureEnabled(Feature\Constants::EXPOSE_CARD_EXPIRY) === true);
    }

    public function isFTSRequestNotesEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::FTS_REQUEST_NOTES) === true);
    }

    //disabled dcc for optimiser merchants because of money leak incidents
    public function isDCCEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::DISABLE_NATIVE_CURRENCY) === false) && ($this->isFeatureEnabled(Feature\Constants::RAAS) === false);
    }

    public function isSubMerchantOnDirectMasterMerchant(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::SUB_VA_FOR_DIRECT_BANKING) === true);
    }

    /**
     * @return bool
     * This flag is used to enable dcc on custom, embedded & direct for specific merchants.
     */
    public function isDCCEnabledOnOtherLibraries(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::DCC_ON_OTHER_LIBRARY) === true);
    }

    public function isAddressRequiredEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::ADDRESS_REQUIRED) === true);
    }

    public function isOpgspImportEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::OPGSP_IMPORT_FLOW) === true);
    }

    public function isOpgspImportSettlementEnabled(): bool
    {
        return ($this->isFeatureEnabled(Dcs\Features\Constants::ImportSettlement) === true);
    }

    public function isAVSEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::AVS) === true);
    }

    public function isAVSEnabledInternationalMerchant(): bool
    {
        return ($this->isInternational() && $this->isAVSEnabled());
    }

    public function isDCCEnabledInternationalMerchant(): bool
    {
        return($this->isDCCEnabled() === true && $this->isInternational() === true);
    }

    public function isCustomerFeeBearerAllowedOnInternational(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::ALLOW_CFB_INTERNATIONAL) === true);
    }

    public function isEarlyMandatePresentmentEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::EARLY_MANDATE_PRESENTMENT) === true);
    }

    public function isCustomCheckoutConsentScreenEnabledForMerchant(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::CUSTOM_CHECKOUT_CONSENT_SCREEN);
    }

    public function isCustomCheckoutNetworkTokenisationEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_PAID);
    }

    public function isCollectConsentEnabledForMerchant(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::DISABLE_COLLECT_CONSENT) === false);
    }

    public function isCollectConsentEnabledForMerchantRecurring(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::NO_CUSTOM_CHECKOUT_RECURRING_CONSENT) === false);
    }

    public function isTokenisedCardPaymentEnabledForMerchant(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::DISABLE_TOKENISED_PAYMENT) === false);
    }

    public function isShowMorTncEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::SHOW_MOR_TNC) === true);
    }

    /**
     * Get the non-pureplatform partner if it exists
     *
     * @return null|Entity
     */
    public function getNonPurePlatformPartner()
    {
        $accessMap = (new AccessMap\Repository)->getNonPurePlatformPartnerMapping($this->getId());

        /** @var Merchant\Entity $partner */
        $partner = optional($accessMap)->entityOwner;

        return $partner;
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

        if (Pricing\Repository::shouldDistributeQueryCacheLoad($this) === true)
        {
            $cacheTags = Feature\Entity::getDistrubutedCacheTagsForNames($this->entity, $this->getId());
        }
        else
        {
            $cacheTags = Feature\Entity::getCacheTagsForNames($this->entity, $this->getId());
        }
        $apiResponse = $this->features()
                             ->remember($cacheTtl)
                             ->cacheTags($cacheTags)
                             ->pluck(Feature\Entity::NAME)
                             ->toArray();
        $dcs = App::getFacadeRoot()['dcs'];
        $dcsResponse = $dcs->getDcsEnabledFeatures(Feature\Constants::MERCHANT, $this->getId())
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

    public function getEmiSubvention()
    {
        $subvention = Emi\Subvention::CUSTOMER;

        if ($this->isFeatureEnabled(Feature\Constants::EMI_MERCHANT_SUBVENTION))
        {
            $subvention = Emi\Subvention::MERCHANT;
        }

        return $subvention;
    }

    public function getActivationSource()
    {
        return $this->getAttribute(self::ACTIVATION_SOURCE);
    }

    public function getSignupSource(){
        return $this->getAttribute(self::SIGNUP_SOURCE);
    }

    public function activate()
    {
        $this->setAttribute(self::ACTIVATED, true);
        $this->liveEnable();
        $this->setAttribute(self::ACTIVATED_AT, time());
    }

    public function deactivate()
    {
        $this->setAttribute(self::ACTIVATED, false);
        $this->liveDisable();
        $this->holdFunds();
    }

    /**
     * Due to a bug which shows up intermittently, a few of the merchant's attributes (live, activated, activated_at)
     * are not set in the activation flow and hence must be forcefully set through a database query. This function
     * enables the same through the admin dashboard. The bug could not be reproduced.
     * Ref - https://razorpay.slack.com/archives/C3TGQGX19/p1553853920002200 for more details.
     *
     * This action must be used only when this particular issue is observed.
     */
    public function forceActivate()
    {
        if (($this->isActivated() === true) or ($this->isLive() === true))
        {
            return;
        }

        $this->setAttribute(self::ACTIVATED, true);
        $this->setAttribute(self::LIVE, true);
        $this->setAttribute(self::ACTIVATED_AT, time());

        app('trace')->info(
            TraceCode::MERCHANT_FORCE_ACTIVATED,
            [
                'activated'         => $this->isActivated(),
                'activated_at'      => $this->getactivatedAt(),
                'live'              => $this->isLive(),
                'activation_status' => $this->merchantDetail->getActivationStatus(),
                'activation_flow'   => $this->merchantDetail->getActivationFlow(),
            ]);
    }

    public function suspend()
    {
        $this->setAttribute(self::SUSPENDED_AT, time());
        $this->liveDisable();
        $this->setHoldFunds(true);

        $this->fireEventWithMerchantPayload('api.account.suspended');

        (new Core())->suspendLinkedAccountsOfParentMerchantIfPresent($this->getMerchantId());
    }

    public function unsuspend()
    {
        $this->setAttribute(self::SUSPENDED_AT, null);
        $this->liveEnable();
        $this->setHoldFunds(false);

        $this->fireEventWithMerchantPayload('api.account.unsuspended');

        (new Core())->unsuspendLinkedAccountsOfParentMerchantIfPresent($this->getMerchantId());
    }

    public function isCACEnabled() :bool
    {
        $isCACExperimentEnabledVariant = app('razorx')->getTreatment($this->getId(),
            RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_ENABLED,
            MODE::LIVE);

        $isCACExperimentDisabledVariant = app('razorx')->getTreatment($this->getId(),
            RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_DISABLED,
            MODE::LIVE);

        app('trace')->info(TraceCode::CAC_EXPERIMENT_VARIANTS_STATUS,
            [
                'isCACExperimentEnabledVariant' => $isCACExperimentEnabledVariant,
                'isCACExperimentDisabledVariant' => $isCACExperimentDisabledVariant,
                'merchant_id' => $this->getId()
            ]);

        if ($isCACExperimentEnabledVariant != RazorxTreatment::RAZORX_VARIANT_ON)
        {
            return $isCACExperimentDisabledVariant != RazorxTreatment::RAZORX_VARIANT_ON;
        }
        else
        {
            return $isCACExperimentEnabledVariant === RazorxTreatment::RAZORX_VARIANT_ON;
        }
    }

    public function liveEnable()
    {
        $this->setAttribute(self::LIVE, true);

        $this->fireEventWithMerchantPayload('api.account.payments_enabled');
    }

    public function liveDisable()
    {
        $this->setAttribute(self::LIVE, false);

        $this->fireEventWithMerchantPayload('api.account.payments_disabled');
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

    public function emiPlans()
    {
        return $this->hasMany('RZP\Models\Merchant\EmiPlans\Entity');
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function balances()
    {
        return $this->hasMany(Balance\Entity::class);
    }

    /**
     * @deprecated
     * This method won't work correctly for merchant having multiple balances.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function balance()
    {
        // Constructing new Exception instance and tracing gives stack trace helpful for debugging.
        app('trace')->traceException(
            new LogicException('Deprecated method balance() of Merchant referenced!'),
            Logger::WARNING);

        return $this->hasOne(Balance\Entity::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function primaryBalance()
    {
        return $this->hasOne(Balance\Entity::class)
                    ->where(Balance\Entity::TYPE, Balance\Type::PRIMARY);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sharedBankingBalance()
    {
        return $this->hasOne(Balance\Entity::class)
                    ->where(Balance\Entity::TYPE, Balance\Type::BANKING)
                    ->where(Balance\Entity::ACCOUNT_TYPE, Balance\AccountType::SHARED);
    }

    public function bankingBalances()
    {
        return $this->hasMany(Balance\Entity::class)
                    ->where(Balance\Entity::TYPE, Balance\Type::BANKING);
    }

    public function directBankingBalances()
    {
        return $this->bankingBalances()
                    ->where(Balance\Entity::ACCOUNT_TYPE, Balance\AccountType::DIRECT);
    }

    public function hasDirectBankingBalance()
    {
        return $this->directBankingBalances->count() > 0;
    }

    public function commissionBalance()
    {
        return $this->hasOne(Balance\Entity::class)
                    ->where(Balance\Entity::TYPE, Balance\Type::COMMISSION);
    }

    public function reservePrimaryBalance()
    {
        return $this->hasOne(Balance\Entity::class)
            ->where(Balance\Entity::TYPE, Balance\Type::RESERVE_PRIMARY);
    }

    public function reserveBankingBalance()
    {
        return $this->hasOne(Balance\Entity::class)
            ->where(Balance\Entity::TYPE, Balance\Type::RESERVE_BANKING);
    }

    public function getBalanceByType(string $type)
    {
        switch ($type)
        {
            case Balance\Type::PRIMARY:
                return $this->primaryBalance;

            case Balance\Type::BANKING:
                return $this->sharedBankingBalance;

            case Balance\Type::COMMISSION:
                return $this->commissionBalance;

            case Balance\Type::RESERVE_PRIMARY:
                return $this->reservePrimaryBalance;

            case Balance\Type::RESERVE_BANKING:
                return $this->reserveBankingBalance;

            default:
                throw new LogicException(
                    "Invalid balance type - {$type}",
                    null,
                    [
                        Entity::MERCHANT_ID => $this->getId(),
                    ]);
        }
    }

    public function getBalanceByTypeOrFail(string $type): Balance\Entity
    {
        $balance = $this->getBalanceByType($type);

        if ($balance === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BALANCE_DOES_NOT_EXIST,
                null,
                [
                    self::ID             => $this->getKey(),
                    Balance\Entity::TYPE => $type,
                ]);
        }

        return $balance;
    }

    public function payoutLinks()
    {
        return $this->hasMany(
            'RZP\Models\PayoutLink\Entity',
            self::MERCHANT_ID,
            self::ID);
    }

    public function bankAccount()
    {
        return $this->hasOne(
            'RZP\Models\BankAccount\Entity', 'entity_id', self::ID);
    }

    public function merchantDocuments()
    {
        return $this->hasMany('RZP\Models\Merchant\Document\Entity');
    }

    public function merchantProducts()
    {
        return $this->hasMany('RZP\Models\Merchant\Product\Entity');
    }

    public function legalEntity()
    {
        return $this->belongsTo('RZP\Models\Merchant\LegalEntity\Entity');
    }

    public function methods()
    {
        return $this->hasOne(
            'RZP\Models\Merchant\Methods\Entity', self::MERCHANT_ID);
    }

     /*
      * Because we didn't do the data migration for old Merchants.
      * We are doing that as we try to access the methods.
      */
    public function getMethods()
    {
        if ($this->hasRelation('methods') === false)
        {
            $app = App::getFacadeRoot();
            return $app['repo']->methods->getMethodsForMerchant($this);
        }

        return $this->methods;
    }

    public function terminals()
    {
        return $this->hasMany(
            'RZP\Models\Terminal\Entity');
    }

    public function promotions()
    {
        return $this->hasMany(
            'RZP\Models\Merchant\Promotion\Entity');
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

    /**
     * Different communication emails for various purposes that are stored
     * in merchant_emails table against the merchant.
     */
    public function emails()
    {
        return $this->hasMany(Email\Entity::class);
    }

    public function features()
    {
        return $this->morphMany('RZP\Models\Feature\Entity', 'entity');
    }

    public function transfers()
    {
        return $this->morphMany('RZP\Models\Transfer\Entity', 'to');
    }

    public function workflows()
    {
        return $this->morphMany(Action\Entity::class, Action\Entity::MAKER);
    }

    public function merchantDetail()
    {
        return $this->hasOne(Detail\Entity::class, self::MERCHANT_ID, self::ID);
    }

    public function merchantBusinessDetail()
    {
        return $this->hasOne(BusinessDetail\Entity::class, self::MERCHANT_ID, self::ID);
    }

    public function dccPaymentConfig()
    {
        return $this->hasMany(PaymentConfig\Entity::class, PaymentConfig\Entity::MERCHANT_ID, Entity::ID)
                     ->where(PaymentConfig\Entity::TYPE,PaymentConfig\Type::DCC);
    }

    public function firstDccPaymentConfig()
    {
        return $this->dccPaymentConfig()
                     ->orderBy(PaymentConfig\Entity::CREATED_AT, 'desc')
                     ->first();
    }

    public function dccRecurringPaymentConfig()
    {
        return $this->hasMany(PaymentConfig\Entity::class, PaymentConfig\Entity::MERCHANT_ID, Entity::ID)
                     ->where(PaymentConfig\Entity::TYPE,PaymentConfig\Type::DCC_RECURRING);
    }

    public function firstDccRecurringPaymentConfig()
    {
        return $this->dccRecurringPaymentConfig()
                     ->orderBy(PaymentConfig\Entity::CREATED_AT, 'desc')
                     ->first();
    }

    public function mccMarkdownPaymentConfig()
    {
        return $this->hasMany(PaymentConfig\Entity::class, PaymentConfig\Entity::MERCHANT_ID, Entity::ID)
                     ->where(PaymentConfig\Entity::TYPE,PaymentConfig\Type::MCC_MARKDOWN);
    }

    public function latestMccMarkdownPaymentConfig()
    {
        return $this->mccMarkdownPaymentConfig()
                     ->orderBy(PaymentConfig\Entity::CREATED_AT, 'desc')
                     ->first();
    }

    public function commissions()
    {
        return $this->hasMany(Commission\Entity::class, Commission\Entity::PARTNER_ID, Entity::ID);
    }

    public function setPricingPlan($planId)
    {
        $this->setAttribute(self::PRICING_PLAN_ID, $planId);
    }

    public function setMaxPaymentAmount(int $maxAmount)
    {
        $this->setAttribute(self::MAX_PAYMENT_AMOUNT, $maxAmount);
    }

    public function setMaxInternationalPaymentAmount(int $maxAmount)
    {
        $this->setAttribute(self::MAX_INTERNATIONAL_PAYMENT_AMOUNT, $maxAmount);
    }

    public function merchantInheritanceMap()
    {
        return $this->hasOne('RZP\Models\Merchant\InheritanceMap\Entity');
    }

    public function slab(string $type)
    {
        return (new  Slab\Repository())->findByMerchantIdAndType($this->getId(), $type);
    }

    public function getShippingInfoUrlConfig()
    {
        return (new Merchant1ccConfig\Repository())
            ->findByMerchantAndConfigType(
                $this->getId(),
                Merchant1ccConfig\Type::SHIPPING_INFO_URL
            );
    }

    public function getFetchCouponsUrlConfig()
    {
        return (new Merchant1ccConfig\Repository())
            ->findByMerchantAndConfigType(
                $this->getId(),
                Merchant1ccConfig\Type::FETCH_COUPONS_URL
            );
    }

    public function getApplyCouponUrlConfig()
    {
        return (new Merchant1ccConfig\Repository())
            ->findByMerchantAndConfigType(
                $this->getId(),
                Merchant1ccConfig\Type::APPLY_COUPON_URL
            );
    }

    public function getMerchantPlatformConfig()
    {
        return (new Merchant1ccConfig\Repository())
            ->findByMerchantAndConfigType(
                $this->getId(),
                Merchant1ccConfig\Type::PLATFORM
            );
    }

    public function getCODIntelligenceConfig() : bool
    {
        $codIntelligenceConfig =  (new Merchant1ccConfig\Repository())->
        findByMerchantAndConfigType($this->getId(), Merchant1ccConfig\Type::COD_INTELLIGENCE);
        return $codIntelligenceConfig !==  null && $codIntelligenceConfig->getValue() === "1";
    }

    public function getManualControlCodOrderConfig() : bool
    {
        $manualControlCodOrderConfig =  (new Merchant1ccConfig\Repository())->
        findByMerchantAndConfigType($this->getId(), Merchant1ccConfig\Type::MANUAL_CONTROL_COD_ORDER);
        return $manualControlCodOrderConfig !==  null && $manualControlCodOrderConfig->getValue() === "1";
    }

    public function getFetchOrderStatusUpdateUrlConfig()
    {
        return (new Merchant1ccConfig\Repository())
            ->findByMerchantAndConfigType(
                $this->getId(),
                Merchant1ccConfig\Type::ORDER_STATUS_UPDATE_URL
            );
    }

    public function get1ccConfig($type)
    {
        return  (new Merchant1ccConfig\Repository())->
        findByMerchantAndConfigType($this->getId(), $type);
    }

    public function get1ccConfigFlagStatus($type) : bool
    {
        $configStatus =  (new Merchant1ccConfig\Repository())->
        findByMerchantAndConfigType($this->getId(), $type);
        return $configStatus !==  null && $configStatus->getValue() === "1";
    }

    public function getShippingMethodProvider()
    {
        return (new Merchant1ccConfig\Repository())
            ->findByMerchantAndConfigType(
                $this->getId(),
                Merchant1ccConfig\Type::SHIPPING_METHOD_PROVIDER
            );
    }

    public function setBrandColor($brandColor)
    {
        $this->setAttribute(self::BRAND_COLOR, $brandColor);
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

    public function setIconUrl($iconUrl)
    {
        $this->setAttribute(self::ICON_URL, $iconUrl);
    }

    public function setCategory2($category)
    {
        $this->setAttribute(self::CATEGORY2, $category);
    }

    public function getCategory2()
    {
        return $this->getAttribute(self::CATEGORY2);
    }

    public function isCategory2Cryptocurrency()
    {
        return ($this->getCategory2() === Terminal\Category::CRYPTOCURRENCY);
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

    public function getExternalId()
    {
        return $this->getAttribute(self::EXTERNAL_ID);
    }

    public function getAccountCode()
    {
        return $this->getAttribute(self::ACCOUNT_CODE);
    }

    public function offers()
    {
        return $this->hasMany('RZP\Models\Offer\Entity');
    }

    public function bankingAccounts()
    {
        return $this->hasMany(BankingAccount\Entity::class)
            ->whereNot(BankingAccount\Entity::STATUS, BankingAccount\Status::TERMINATED);
    }

    public function hasBankingAccounts()
    {
        return ($this->bankingAccounts->count() > 0);
    }

    /**
     * use activeBankingAccounts() function instead of magic property activeBankingAccounts
     * Eg: $this->merchant->activeBankingAccounts();
     * @return Collection
     */
    public function activeBankingAccounts(): Collection
    {
        $activeAccounts = $this->bankingAccounts()
                               ->where(BankingAccount\Entity::STATUS, BankingAccount\Status::ACTIVATED)
                               ->get();

        // Some CAs (ICICI, Axis, Yes Bank) exist at banking account service.
        $basAccount = app('banking_account_service')->fetchActivatedDirectAccountsFromBas($this);

        if(empty($basAccount) === false)
        {
            $activeAccounts->add($basAccount);
        }

        return $activeAccounts;
    }

    public function vaBankingAccounts(): Collection
    {
        return $this->bankingAccounts()
                    ->where(BankingAccount\Entity::ACCOUNT_TYPE, BankingAccount\AccountType::NODAL)
                    ->get();
    }

    protected function getMaxPaymentAmountAttribute()
    {
        $amount = $this->attributes[self::MAX_PAYMENT_AMOUNT];

        if (($amount === null) or
            ($amount === '0'))
        {
            $amount = (new Core())->getMaxPayAmount($this);
        }

        return (int) $amount;
    }

    protected function getMaxInternationalPaymentAmountAttribute()
    {
        $amount = $this->attributes[self::MAX_INTERNATIONAL_PAYMENT_AMOUNT];

        if (($amount === null) or
            ($amount === '0'))
        {

            $domesticMaxPaymentAmount = $this->getAttribute(self::MAX_PAYMENT_AMOUNT);
            $amount = max($this->getMaxInternationalPaymentAmountDefault(), $domesticMaxPaymentAmount);
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

    protected function getRefundSourceAttribute()
    {
        return RefundSource::getRefundSourceStringForValue($this->attributes[self::REFUND_SOURCE]);
    }

    public function getInternationalAttribute()
    {
        return (bool) $this->attributes[self::INTERNATIONAL];
    }

    protected function getReceiptEmailEnabledAttribute()
    {
        return (bool) $this->attributes[self::RECEIPT_EMAIL_ENABLED];
    }

    public function setReceiptEmailEnabledAttribute($setReceiptEmail)
    {
        $this->attributes[self::RECEIPT_EMAIL_ENABLED] = $setReceiptEmail;
    }

    protected function getHoldFundsAttribute()
    {
        return (bool) $this->attributes[self::HOLD_FUNDS];
    }

    protected function getCategoryAttribute()
    {
        return $this->attributes[self::CATEGORY];
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
        return $this->attributes[self::EMAIL] ?? null;
    }

    public function setEmail($email)
    {
        $this->setEmailAttribute($email);
    }

    public function getName()
    {
        return $this->attributes[self::NAME];
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function setCategory($category)
    {
        $this->setAttribute(self::CATEGORY, $category);
    }

    public function getMaxPaymentAmount()
    {
        return $this->getAttribute(self::MAX_PAYMENT_AMOUNT);
    }

    public function getMaxPaymentAmountTransactionType($international = false, $method = '')
    {
        // Increase txn limit for B2B intl_bank_transfer payments
        // https://razorpay.slack.com/archives/C024U3B04LD/p1681131023230559
        if ((empty($method) === false) && ($method === Method::INTL_BANK_TRANSFER))
        {
            return self::MAX_PAYMENT_AMOUNT_DEFAULT_INTL_BANK_TRANSFER;
        }

        if ($international)
        {
            $maxPaymentAmount = $this->getAttribute(self::MAX_INTERNATIONAL_PAYMENT_AMOUNT);
            if($maxPaymentAmount === null){
                $domesticMaxPaymentAmount = $this->getAttribute(self::MAX_PAYMENT_AMOUNT);
                $maxPaymentAmount = max($this->getMaxInternationalPaymentAmountDefault(), $domesticMaxPaymentAmount);
            }
            return $maxPaymentAmount;
        }
        else
            return $this->getAttribute(self::MAX_PAYMENT_AMOUNT);
    }

    public function getInvoiceLabelField()
    {
        return $this->getAttribute(self::INVOICE_LABEL_FIELD);
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

    public function getMerchantDetail() {
        return $this->getAttribute(self::MERCHANT_DETAIL);
    }

    public function getDefaultRefundSpeed()
    {
        return $this->getAttribute(self::DEFAULT_REFUND_SPEED);
    }

    public function getMerchantId()
    {
        return $this->getId();
    }

    /**
     * Helper method to fetch the actual display_name for an
     * invoice, based on merchant-defined field preference from merchant_detail:
     * `business_name` or `business_dba`
     *
     * Fallback to `merchant_detail.business_name` if the invoice_label_field setting is not defined
     *
     * If invoice_label_field is defined but the attribute is null, use merchant.billing_label instead.
     *
     * @return mixed
     */
    public function getLabelForInvoice()
    {
        $field = $this->getInvoiceLabelField() ?: Detail\Entity::BUSINESS_NAME;

        $value = optional($this->merchantDetail)->getAttribute($field);

        return $value ?: $this->getBillingLabel();
    }

    public function getDbaName()
    {
        $value = optional($this->merchantDetail)->getAttribute(Detail\Entity::BUSINESS_DBA);

        return $value ?: $this->getName();
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

    public function getWhitelistedIpsLive()
    {
        return $this->getAttribute(self::WHITELISTED_IPS_LIVE);
    }

    public function getWhitelistedIpsTest()
    {
        return $this->getAttribute(self::WHITELISTED_IPS_TEST);
    }

    public function getMerchantDashboardWhitelistedIpsLive()
    {
        return $this->getAttribute(self::DASHBOARD_WHITELISTED_IPS_LIVE);
    }

    public function getMerchantDashboardWhitelistedIpsTest()
    {
        return $this->getAttribute(self::DASHBOARD_WHITELISTED_IPS_TEST);
    }

    public function getWhitelistedDomains()
    {
        return $this->getAttribute(self::WHITELISTED_DOMAINS);
    }

    public function setWhitelistedDomains(array $whitelistedDomains)
    {
        return $this->setAttribute(self::WHITELISTED_DOMAINS, $whitelistedDomains);
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function getSignedOrgId()
    {
        return Org\Entity::getSignedId($this->getAttribute(self::ORG_ID));
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

    /**
     * set convert_currency to either of (null, true, false)
     * if convert_currency === null, then international payments are off
     * if convert_currency === false, then conversion is handled by Gateway
     * if convert_currency === true, then conversion is handled by us
     *
     * @param $val
     */
    public function setCurrencyConversion($val)
    {
        $this->setAttribute(self::CONVERT_CURRENCY, $val);
    }

    public function getBrandColor()
    {
        return $this->getAttribute(self::BRAND_COLOR);
    }

    public function getBrandColorOrDefault(string $default = self::DEFAULT_MERCHANT_BRAND_COLOR): string
    {
        return $this->getBrandColor() ?: $default;
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getBrandColorElseDefault()
    {
        return $this->getAttribute(self::BRAND_COLOR) ??
            Merchant\Checkout::CHECKOUT_DEFAULT_THEME_COLOR;
    }

    protected function getBrandColorAttribute()
    {
        $storedBrandColor = $this->attributes[self::BRAND_COLOR] ?? null;

        if ($storedBrandColor === null)
        {
            return null;
        }

        return '#' . $storedBrandColor;
    }

    public function getBrandColorOrOrgPreference()
    {
        $brandColour = $this->getBrandColor();

        if (empty($brandColour) === false)
        {
            return $brandColour;
        }

        if ($this->org === null)
        {
            return self::DEFAULT_MERCHANT_BRAND_COLOR;
        }

        $orgMerchantStyles = $this->org->getMerchantStyles();

        if (($this->isRazorpayOrgId() === true) ||
            ($this->shouldShowCustomOrgBranding() === false) ||
            (empty($orgMerchantStyles) === true))
        {
            return self::DEFAULT_MERCHANT_BRAND_COLOR;
        }

        $orgMerchantStyles = json_decode($orgMerchantStyles, true);

        if (($orgMerchantStyles !== null) &&
            (array_key_exists('checkout_theme_color', $orgMerchantStyles) === true))
        {
            return $orgMerchantStyles['checkout_theme_color'];
        }

        return self::DEFAULT_MERCHANT_BRAND_COLOR;
    }

    public function getLogoUrl()
    {
        return $this->getAttribute(self::LOGO_URL);
    }

    public function getIconUrl()
    {
        return $this->getAttribute(self::ICON_URL);
    }

    public function getDisplayName()
    {
        return $this->getAttribute(self::DISPLAY_NAME);
    }

    public function getDisplayNameElseName()
    {
        return (empty($this->getDisplayName()) === false) ? $this->getDisplayName() : $this->getName();
    }

    public function setDisplayName($displayName)
    {
        $this->setAttribute(self::DISPLAY_NAME, $displayName);
    }

    public function getFeeBearer()
    {
        return $this->getAttribute(self::FEE_BEARER);
    }

    public function setFeeBearer($feeBearer)
    {
        $this->setAttribute(self::FEE_BEARER, $feeBearer);
    }

    public function setFeeModel($feeModel)
    {
        $this->setAttribute(self::FEE_MODEL, $feeModel);
    }

    public function getFeeModel()
    {
        return $this->getAttribute(self::FEE_MODEL);
    }

    public function getFeeCreditsThreshold()
    {
        return $this->getAttribute(self::FEE_CREDITS_THRESHOLD);
    }

    public function getAmountCreditsThreshold()
    {
        return $this->getAttribute(self::AMOUNT_CREDITS_THRESHOLD);
    }

    public function getRefundCreditsThreshold()
    {
        return $this->getAttribute(self::REFUND_CREDITS_THRESHOLD);
    }

    public function getBalanceThreshold()
    {
        return $this->getAttribute(self::BALANCE_THRESHOLD);
    }

    public function getRefundSource()
    {
        return $this->getAttribute(self::REFUND_SOURCE);
    }

    public function getProductInternational()
    {
        return $this->getAttribute(self::PRODUCT_INTERNATIONAL);
    }

    public function setProductInternational($productInternational)
    {
        $this->setAttribute(self::PRODUCT_INTERNATIONAL, $productInternational);
    }

    public function getContrastOfBrandColor()
    {
        $brandColor = $this->getBrandColorOrDefault();

        $relativeLuminance = get_contrast_with_white(str_replace('#', '', $brandColor));

        // similar as in checkout (instead of #000000 checkout has rgba(0, 0, 0, 0.85)),
        return $relativeLuminance < 0.5 ? '#FFFFFF' : '#000000';

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

        $regionName = $awsConfig['logo_bucket_region'];

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
        $emails = explode(',', $this->attributes[self::TRANSACTION_REPORT_EMAIL] ?? null);

        // Just so there is no whitespace before or after the email
        return array_filter(array_map('trim', $emails));
    }

    public function getPartnerType()
    {
        return $this->getAttribute(self::PARTNER_TYPE);
    }

    public function isPartner(): bool
    {
        return $this->isAttributeNotNull(self::PARTNER_TYPE);
    }

    public function isInheritanceParent(): bool
    {
        $inheritanceMap = (new InheritanceMap\Repository)->getInheritanceMapByParentMerchantId($this->getId());

        return (sizeof($inheritanceMap) !== 0);
    }

    public function isFullyManagedPartner(): bool
    {
        return ($this->getPartnerType() === Constants::FULLY_MANAGED);
    }

    public function isPurePlatformPartner(): bool
    {
        return ($this->getPartnerType() === Constants::PURE_PLATFORM);
    }

    public function isResellerPartner(): bool
    {
        return ($this->getPartnerType() === Constants::RESELLER);
    }

    public function isBankCaOnboardingPartner(): bool
    {
        return ($this->getPartnerType() === Constants::BANK_CA_ONBOARDING_PARTNER);
    }

    public function isAggregatorPartner(): bool
    {
        return ($this->getPartnerType() === Constants::AGGREGATOR);
    }

    public function hasAggregatorFeature(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::AGGREGATOR));
    }

    public function hasRazorpaywalletFeature(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::RAZORPAY_WALLET));
    }

    public function hasOptionalSubmerchantEmailFeature(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::ALLOW_SUBMERCHANT_WITHOUT_EMAIL));
    }

    public function isKycHandledByPartner(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::KYC_HANDLED_BY_PARTNER));
    }

    public function isPartnerInvoiceAutoApprovalDisabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::AUTO_COMM_INV_DISABLED));
    }

    public function shouldRetainMerchantName(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::RETAIN_SUB_MERCHANT_NAME));
    }

    public function canCommunicateWithSubmerchant(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::NO_COMM_WITH_SUBMERCHANTS) === false);
    }

    public function canSkipWorkflowToAccessSubmerchantKyc(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::PARTNER_SUB_KYC_ACCESS) === true);
    }

    public function forceGreyListInternational(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::FORCE_GREYLIST_INTERNAT) === true);
    }

    public function skipWebsiteForInternational(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::SKIP_WEBSITE_INTERNAT) === true);
    }

    public function hasDirectTransferFeature()
    {
        return ($this->isFeatureEnabled(Feature\Constants::DIRECT_TRANSFER));
    }

    public function isOptionalEmailAllowedAggregator(): bool
    {
        return (($this->isAggregatorPartner() === true) and ($this->hasOptionalSubmerchantEmailFeature() === true));
    }

    public function isNoDocOnboardingFeatureEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::NO_DOC_ONBOARDING) === true);
    }

    public function isNoDocOnboardingEnabled(): bool
    {
        return (($this->isFeatureEnabled(Feature\Constants::NO_DOC_ONBOARDING) === true)
            and ($this->isNoDocPartiallyActivatedTagAttached() === false));
    }

    public function isNoDocOnboardingPaymentsEnabled(): bool
    {
        return (($this->isFeatureEnabled(Feature\Constants::NO_DOC_ONBOARDING) === true)
            and ($this->isNoDocPartiallyActivatedTagAttached() === true));
    }

    public function isNoDocPartiallyActivatedTagAttached()
    {
        $existingTags = $this->liveTagNames();

        return (in_array(AccountConstants::NO_DOC_PARTIALLY_ACTIVATED, array_map('strtolower', $existingTags)) === true);
    }

    public function isRoutePartnershipsEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::ROUTE_PARTNERSHIPS) === true);
    }


    public function isSubmerchantManualSettlementEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::SUBM_MANUAL_SETTLEMENT) === true);
    }

    protected function setEmailAttribute($email)
    {
        $formattedEmail = ($email === null) ? null : mb_strtolower(trim($email));

        $this->attributes[self::EMAIL] =  $formattedEmail;
    }

    public function setChannel(string $channel)
    {
        $this->attributes[self::CHANNEL] = $channel;
    }

    public function setWebsiteAttribute($website)
    {
        $this->attributes[self::WEBSITE] = $website;
    }

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
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

    protected function setRefundSourceAttribute($refundSource)
    {
        $this->attributes[self::REFUND_SOURCE] = RefundSource::getValueForRefundSourceString($refundSource);
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

    protected  function setPublicCurrencyAttribute(array & $array) {
        $array[self::CURRENCY] = $this->getCurrency();
    }

    public function setPublicDCCAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isAdminAuth() === true)
        {
            $array[self::DCC_MARKUP_PERCENTAGE] = $this->getDccMarkupPercentage();
        }
    }

    public function setPublicRiskThresholdAttribute(array & $array)
    {
        $app = \App::getFacadeRoot();
        $routeName = $app['request.ctx']->getRoute();

        if (($app['basicauth']->isAdminAuth() === true ) and ($routeName == 'admin_fetch_entity_by_id'))
        {
            array_pull($array, self::RISK_THRESHOLD);
        }
    }

    /** To compute if dcc markup is to be shown in frontend or not
     * @param $input
     * @param $merchant
     * @return bool
     */
    public function isDCCMarkupVisible(): bool
    {
        $result = false;

        //Check either of merchant feature or admin config is enabled
        if (((bool) Admin\ConfigKey::get(Admin\ConfigKey::PAYMENT_SHOW_DCC_MARKUP, false) == true) or
            ($this->isFeatureEnabled(Feature\Constants::PAYMENT_SHOW_DCC_MARKUP) == true))
        {
            $result = true;
        }

        return $result;
    }

    public function getDccMarkupPercentage()
    {
        $dccPaymentConfigEntity = $this->firstDccPaymentConfig();

        if($dccPaymentConfigEntity === null ) {
            return self::DCC_MARKUP_PERCENTAGE_DEFAULT;
        }

        $data = $dccPaymentConfigEntity->getFormattedConfig();

        return $data[self::DCC_MARKUP_PERCENTAGE];
    }

    public function getDccRecurringMarkupPercentage()
    {
        $dccPaymentConfigEntity = $this->firstDccRecurringPaymentConfig();

        if($dccPaymentConfigEntity === null ) {
            return self::DCC_RECURRING_MARKUP_PERCENTAGE_DEFAULT;
        }

        $data = $dccPaymentConfigEntity->getFormattedConfig();

        return $data[self::DCC_RECURRING_MARKUP_PERCENTAGE];
    }

    public function getDccMarkupPercentageForApps()
    {
        return self::DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_APPS;
    }

    public function getMccMarkdownMarkdownPercentage($payment = null)
    {
        $key = $this->getMccMarkdownConfigKey($payment);
        return $this->getMccMarkdownPercentageValue($key);
    }

    private function getMccMarkdownConfigKey($payment):array
    {
        $methods = [
            Method::INTL_BANK_TRANSFER => [
                PaymentProcessor\IntlBankTransfer::ACH   => self::INTL_BANK_TRANSFER_ACH_MCC_MARKDOWN_PERCENTAGE,
                PaymentProcessor\IntlBankTransfer::SWIFT => self::INTL_BANK_TRANSFER_SWIFT_MCC_MARKDOWN_PERCENTAGE,
            ],
            "default" => self::MCC_MARKDOWN_PERCENTAGE
        ];

        $configKeys = [
            "config_key"            =>  $methods["default"],
            "fallback_config_key"   =>  $methods["default"],
        ];

        // return default mark down which is common for all method
        if(empty($payment))
        {
            return $configKeys;
        }

        $response = null;
        if(array_key_exists($payment->getMethod(),$methods))
        {
            $response = $methods[$payment->getMethod()];
        }

        // if method is not present return the default key
        if (empty($response) === true )
        {
            return $configKeys;
        }
        if ((is_array($response) === true))
        {
            if ($payment->getMethod() === Method::INTL_BANK_TRANSFER )
            {
                $mode = $payment->getWallet();
                $configKeys["config_key"] = $response[$mode];
                return $configKeys;
            }
        }
        else
        {
            $configKeys["config_key"] = $response;
        }
        return $configKeys;
    }

    private function getMccMarkDownConfig() : array
    {
        $mccMarkdownPaymentConfigEntity = $this->latestMccMarkdownPaymentConfig();
        if(empty($mccMarkdownPaymentConfigEntity)=== true)
        {
            return [];
        }
        return $mccMarkdownPaymentConfigEntity->getFormattedConfig();
    }

    private function getMccMarkdownFromRedis() : array
    {
        $result = [];
        $result = ConfigKey::get(ConfigKey::MCC_DEFAULT_MARKDOWN_PERCENTAGE_CONFIG);
        if(empty($result) === true)
        {
           // for backward compatibility in case MCC_DEFAULT_MARKDOWN_PERCENTAGE_CONFIG is not present
           // fetch the value from old redis key MCC_DEFAULT_MARKDOWN_PERCENTAGE
           $defaultMccMarkdown = ConfigKey::get(ConfigKey::MCC_DEFAULT_MARKDOWN_PERCENTAGE);
           if($defaultMccMarkdown === null)
           {
               return [];
           }
           return [self::MCC_MARKDOWN_PERCENTAGE => $defaultMccMarkdown ];
       }
       return $result;
    }

    private function getMccMarkdownPercentageValue(array $keys): string {
        $fallback = null;
        $mccMarkdownSources = [
            1 =>    function() { return $this->getMccMarkDownConfig(); },
            2 =>    function() { return $this->getMccMarkdownFromRedis();},
            3 =>    function() { return self::DEFAULT_MCC_MARKDOWNS;},
        ];

        foreach ($mccMarkdownSources as $index => $source)
        {
            $response = [];
            $mccMarkdownConfig = $source();
            if(empty($mccMarkdownConfig) === false)
            {
                $response = $this->getConfigValues($keys,$mccMarkdownConfig);
            }
            if(isset($response['config_key']) === true)
            {
                return $response['config_key'];
            }
            if((isset($fallback) === false) and (isset($response['fallback_config_key']) === true))
            {
                $fallback = $response['fallback_config_key'];
            }
        }
        return $fallback;
    }

    // [ArrayShape(['config_key' => "mixed|null", 'fallback_config_key' => "mixed|null"])]
    private function getConfigValues(array $keys, array $config): array
    {
        $response = ['config_key' => null,'fallback_config_key' => null];
        if( empty($config[$keys['config_key']]) === false)
        {
            $response['config_key'] = $config[$keys['config_key']];
        }

        if( empty($config[$keys['fallback_config_key']]) === false)
        {
            $response['fallback_config_key'] = $config[$keys['fallback_config_key']];
        }
        return $response;
    }

    public function getDccMarkupPercentageForIntlBankTransfer()
    {
        return self::DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_INTL_BANK_TRANSFER;
    }

    public function setDefaultMethodsBasedOnCategory()
    {
        (new MethodCore())->resetDefaultMethodsBasedOnMerchantCategories($this);
    }

    /**
     * Signifies weather a Merchant has business banking knowledge or not.
     *
     * @return bool
     */
    public function isBusinessBankingEnabled()
    {
        return $this->getAttribute(self::BUSINESS_BANKING) == true;
    }

    public function getHoldFunds()
    {
        return $this->getAttribute(self::HOLD_FUNDS);
    }

    public function getHoldFundsReason()
    {
        return $this->getAttribute(self::HOLD_FUNDS_REASON);
    }

    public function isFundsOnHold(): bool
    {
        return (bool) $this->getHoldFunds();
    }

    public function holdFunds()
    {
        $this->setHoldFunds(true);
    }

    public function releaseFunds()
    {
        $this->setHoldFunds(false);
    }

    public function setReceiptEmailEventAuthorized()
    {
        $this->setReceiptEmailTriggerEventAttribute(Event::AUTHORIZED);
    }

    public function setReceiptEmailEventCaptured()
    {
        $this->setReceiptEmailTriggerEventAttribute(Event::CAPTURED);
    }

    public function setHoldFunds($holdFunds)
    {
        $this->setAttribute(self::HOLD_FUNDS, $holdFunds);

        if ($holdFunds === true)
        {
            $this->fireEventWithMerchantPayload('api.account.funds_hold');
        }
        else
        {
            $this->setHoldFundsReason();

            $this->fireEventWithMerchantPayload('api.account.funds_unhold');
        }
    }

    public function setHoldFundsReason(string $reason = null)
    {
        $this->setAttribute(self::HOLD_FUNDS_REASON, $reason);
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

    public function getGstStateCode()
    {
        $gstStateCode = null;

        $merchantDetail = $this->merchantDetail;

        if ($merchantDetail !== null)
        {
            $gstStateCode = $merchantDetail->getGstStateCode();
        }

        return $gstStateCode;
    }

    public function getGstin()
    {
        if ($this->merchantDetail === null)
        {
            return null;
        }

        $this->setRelation('merchantDetail', $this->merchantDetail);

        return $this->merchantDetail->getGstin() ?: $this->merchantDetail->getPGstin();
    }

    public function getCompanyCin()
    {
        return optional($this->merchantDetail)->getCompanyCin();
    }

    public function getBusinessRegisteredAddressAsText(string $delimiter = PHP_EOL)
    {
        return optional($this->merchantDetail)->getBusinessRegisteredAddressAsText($delimiter);
    }

    public function getBusinessRegisteredState()
    {
        if ($this->merchantDetail === null)
        {
            return null;
        }

        return $this->merchantDetail->getBusinessRegisteredState();
    }

    public function getBankIfsc()
    {
        if ($this->merchantDetail === null)
        {
            return null;
        }

        return $this->merchantDetail->getBankBranchIfsc();
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

        $this->fireEventWithMerchantPayload('api.account.international_enabled');
    }

    public function disableInternational()
    {
        $this->setAttribute(self::INTERNATIONAL, false);

        $this->fireEventWithMerchantPayload('api.account.international_disabled');
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

    public function isHeadlessEnabled() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::HEADLESS_DISABLE) === false;
    }

    public function isTokenInteroperabilityEnabled() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::TOKEN_INTEROPERABILITY);
    }

    /**
     * The show_email_on_checkout feature flag is used for displaying email on std/hosted checkout.
     * This feature flag can be overridden by checkout options sent by merchants.
     * Email customizations on std/hosted checkout based on feature flags -
     * show_email_on_checkout => false and email_optional_on_checkout => false ==> email-less checkout
     * show_email_on_checkout => true and email_optional_on_checkout => false ==> email is mandatory on checkout
     * show_email_on_checkout => true and email_optional_on_checkout => true ==> email is optional on checkout
     * show_email_on_checkout => false and email_optional_on_checkout => true ==> email-less checkout
     *
     * @return bool
     */
    public function isEmailShownOnCheckout()
    {
        return $this->isFeatureEnabled(Dcs\Features\Constants::ShowEmailOnCheckout);
    }

    /**
     * The email_optional_on_checkout feature flag is used for making email optional on only std/hosted checkout.
     * For making email optional on custom checkout/S2S please use email_optional feature flag.
     *
     * @return bool
     */
    public function isEmailOptionalOnCheckout()
    {
        return $this->isFeatureEnabled(Dcs\Features\Constants::EmailOptionalOnCheckout);
    }

    public function isIvrEnabled() : bool
    {
        return ($this->isHeadlessEnabled() === true) and
                ($this->isFeatureEnabled(Feature\Constants::IVR_DISABLE) === false);
    }

    public function isTokenizationEnabled() : bool
    {
        return $this->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_LIVE) || $this->isFeatureEnabled(Feature\Constants::ISSUER_TOKENIZATION_LIVE);
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

        $data[self::ID] = Account\Entity::getSignedId($this->getAttribute(self::ID));

        return $data;
    }

    public function toArrayPublic()
    {
        $data = parent::toArrayPublic();

        if (($this->isLinkedAccount() === true) and
            ($this->parent->isRouteCodeEnabled() === true))
        {
            $data[self::CODE] = $this->getAccountCode();
        }

        return $data;
    }

    public function toArrayWithRawValuesForAccountService() : array {
        $array = $this->toArray();
        if(array_key_exists(self::BRAND_COLOR, $array)) {
            $array[self::BRAND_COLOR] = $this->getAttributes()[self::BRAND_COLOR];
        }
        return $array;
    }

    public function groups()
    {
        return $this->morphedByMany('\RZP\Models\Admin\Group\Entity', 'entity', Table::MERCHANT_MAP);
    }

    public function admins()
    {
        return $this->morphedByMany('\RZP\Models\Admin\Admin\Entity', 'entity', Table::MERCHANT_MAP);
    }

    public function activationStates()
    {
        return $this->hasMany('\RZP\Models\State\Entity', State\Entity::ENTITY_ID)
                    ->where(State\Entity::ENTITY_TYPE, 'merchant_detail');
    }

    public function currentActivationState()
    {
        return $this->activationStates()
                    ->orderBy(State\Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getActivationStatusChangeLog()
    {
        return $this->activationStates()
                    ->orderBy(State\Entity::CREATED_AT)
                    ->get();
    }

    /**
     * Get the owners of the merchant.
     * This function is used in partners and primary product so filtering it by primary
     *
     * @param Balance/Type $product
     */
    public function owners($product = Product::PRIMARY)
    {
        return $this->users()->where('role','owner')->where(self::PRODUCT, $product);
    }

    public function ownersAndAdmins(string $product)
    {
        return $this->users()->whereIn('role',['owner', 'admin'])->where(self::PRODUCT, $product)->get();
    }

    /**
     * Get the primary linked account owner.
     */
    public function primaryLinkedAccountOwner()
    {
        return $this->users()
                    ->where('role', User\Role::LINKED_ACCOUNT_OWNER)
                    ->where(self::PRODUCT, Product::PRIMARY)
                    ->first();
    }

    /**
     * Get the primary owner of the merchant.
     */
    public function primaryOwner($product = Product::PRIMARY)
    {
        return $this->owners($product)->first();
    }

    /**
     * For linked accounts owner role is linked account owner.
     * @return string
     */
    public function getUserOwnerRole()
    {
        $role = User\Role::OWNER;

        if ($this->isLinkedAccount() === true)
        {
            $role = User\Role::LINKED_ACCOUNT_OWNER;
        }

        return $role;
    }

    public function users()
    {
        /**
         * order of users
         * 1. owner user with same email as the merchant
         * 2. other owner users
         * 3. other users
         */
        $sql = "CASE WHEN email=? AND role='owner' THEN 0
                     WHEN role='owner' THEN 1
                     else 2 END";

        //
        // The foreign key should be specified explicitly as it otherwise fetches from the
        // entity name by appending '_id' to it. When this code is called from Account\Entity,
        // it tries to look for account_id and crashes.
        //
        $query = $this->belongsToMany(User\Entity::class, Table::MERCHANT_USERS, self::MERCHANT_ID)
                    ->withPivot([User\Entity::ROLE, User\Entity::PRODUCT]);

        // if the merchant entity is already loaded with data
        if (empty($this->attributes[self::EMAIL]) === false)
        {
            $query->orderByRaw($sql, [$this->getEmail()]);
        }
        else
        {
            $query->orderBy(self::NAME);
        }

        return $query;
    }

    public function invitations()
    {
        return $this->hasMany(Invitation\Entity::class)
                    ->orderBy(Invitation\Entity::CREATED_AT, 'desc');
    }

    /**
     * Returns tag names for merchant, ensures to read from LIVE mode.
     * @return array
     */
    public function liveTagNames(): array
    {
        $liveConnection = app('basicauth')->getLiveConnection();

        $tags = $this->getConnectionName() === $liveConnection ?
                $this->tagNames() :
                (clone $this)->setConnection($liveConnection)->tagNames();

        return array_map('strtolower', $tags);
    }

    public function isEmailOptional()
    {
        return $this->isFeatureEnabled(Feature\Constants::EMAIL_OPTIONAL);
    }

    public function isMagicEnabled()
    {
        return $this->isFeatureEnabled(Feature\Constants::MAGIC);
    }

    public function isPhoneOptional()
    {
        return $this->isFeatureEnabled(Feature\Constants::CONTACT_OPTIONAL);
    }

    public function isSaveVpaEnabled()
    {
        return $this->isFeatureEnabled(Feature\Constants::SAVE_VPA);
    }

    public function shouldSaveVpa()
    {
        return (($this->isSaveVpaEnabled() === true) and ($this->methods->isUpiEnabled() === true));
    }

    public function isUpiOtmEnabled()
    {
        return $this->isFeatureEnabled(Feature\Constants::UPI_OTM);
    }

    public static function hascustomerTransactionHistoryEnabled($merchantId)
    {
        return (in_array($merchantId, Merchant\Preferences::CUSTOMER_TRANSACTION_HISTORY_ENABLED_MID, true) === true);
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

    public function getAccountStatus()
    {
        return ($this->isSuspended() === true) ? AccountStatus::SUSPENDED : $this->merchantDetail->getActivationStatus();
    }

    public function getPaymentFlows(IIN\Entity $iin = null)
    {
        $app = App::getFacadeRoot();

        $data = [];

        if (empty($iin) === true)
        {
            return $data;
        }

        if ($this->isFeatureEnabled(Feature\Constants::ATM_PIN_AUTH) === true)
        {
            $data[IIN\Constants::PIN] = $iin->isDebitPin();
        }

        $headless   = false;
        $ivr        = false;
        $expressPay = false;

        if ($this->isHeadlessEnabled() === true)
        {
            $headless = $iin->isHeadLessOtp();
        }

        if ($this->isIvrEnabled() === true)
        {
            $ivr = $iin->isIvr();
        }

        if (($this->isAxisExpressPayEnabled() === true) and
            ($iin->getIssuer() === IFSC::UTIB))
        {
            $expressPay = $iin->isOtp();
        }

        if (($headless === true) or
            ($ivr === true) or
            ($expressPay === true))
        {
            $data[IIN\Constants::OTP] = true;
        }

        try {
            $data[IIN\Entity::RECURRING] = (new Card\Entity)->isRecurringSupportedOnIIN($this, $iin);
        } catch (\Exception $exception) {
            app('trace')->traceException($exception, Logger::ERROR, TraceCode::IIN_RECURRING_CHECK_FAILED);

            $data[IIN\Entity::RECURRING] = false;
        }

        /*
         * Iframe is only cosumed by checkout public auth.
         */
        $routeName = $app['api.route']->getCurrentRouteName();
        if ($app['basicauth']->isPublicAuth() === true || $routeName == 'customer_fetch_tokens_internal')
        {
            $data[IIN\Flow::IFRAME] = $iin->isIframeApplicable();
        }

        return $data;
    }

    /**
     * @param string $tagName
     *
     * @return bool
     */
    public function isTagAdded(string $tagName): bool
    {
        $tagNames = $this->liveTagNames();

        return in_array(strtolower($tagName), $tagNames, true) === true;
    }

    /**
     * @param string $tagPrefix
     *
     * @return bool
     */
    public function isTagAddedBasedOnPrefix(string $tagPrefix): bool
    {
        $tagNames = $this->liveTagNames();

        return empty(preg_grep("/^".$tagPrefix."/", $tagNames)) === false;
    }

    public function toArrayUser()
    {
        $attributes = [
            self::ID                    => $this->getAttribute(self::ID),
            self::NAME                  => $this->getAttribute(self::NAME),
            self::BILLING_LABEL         => $this->getAttribute(self::BILLING_LABEL),
            self::EMAIL                 => $this->getAttribute(self::EMAIL),
            self::ACTIVATED             => $this->getAttribute(self::ACTIVATED),
            self::ACTIVATED_AT          => $this->getAttribute(self::ACTIVATED_AT),
            self::ARCHIVED_AT           => $this->getAttribute(self::ARCHIVED_AT),
            self::SUSPENDED_AT          => $this->getAttribute(self::SUSPENDED_AT),
            self::HAS_KEY_ACCESS        => $this->getAttribute(self::HAS_KEY_ACCESS),
            self::LOGO_URL              => $this->getFullLogoUrlWithSize(self::MEDIUM_SIZE),
            self::DISPLAY_NAME          => $this->getAttribute(self::DISPLAY_NAME),
            self::REFUND_SOURCE         => $this->getAttribute(self::REFUND_SOURCE),
            self::PARTNER_TYPE          => $this->getAttribute(self::PARTNER_TYPE),
            self::RESTRICTED            => $this->getAttribute(self::RESTRICTED),
            self::CREATED_AT            => $this->getAttribute(self::CREATED_AT),
            self::UPDATED_AT            => $this->getAttribute(self::UPDATED_AT),
            self::SECOND_FACTOR_AUTH    => $this->getAttribute(self::SECOND_FACTOR_AUTH),
            self::PARENT_ID             => $this->getAttribute(self::PARENT_ID),
            Constants::PARENT_NAME      => null,
        ];

        if (empty($attributes[self::PARENT_ID]) === false)
        {
            $parentMerchant = $this->parent()->get()->first();
            $attributes[Constants::PARENT_NAME] = $parentMerchant->getAttribute(self::NAME);
        }

        $attributes[self::ROLE] = $this->getAttribute(self::PIVOT)->role;
        $attributes[self::PRODUCT] = $this->getAttribute(self::PIVOT)->product;

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

    /**
     * @param string|null $partnerType
     */
    public function setPartnerType(string $partnerType = null)
    {
        $this->setAttribute(self::PARTNER_TYPE, $partnerType);
    }

    /**
     * @param null $appType
     * @return bool
     */
    public function allowSubmerchantDashboardAccess($appType = null): bool
    {
        $allowUserCreation = \Request::all()[self::ALLOW_USER_CREATION] ?? true;

        if ($allowUserCreation === false)
        {
            return false;
        }

        // Later change to only fully managed partners
        return (($appType === MerchantApplications\Entity::MANAGED) and
                (($this->isFullyManagedPartner() === true) or ($this->isAggregatorPartner() === true)));
    }

    /**
     * @return bool
     */
    public function isNonPurePlatformPartner(): bool
    {
        return (($this->isPartner() === true) and ($this->getPartnerType() !== Constants::PURE_PLATFORM));
    }

    /**
     * @return bool
     */
    public function isPartnerWithSettingsAccess(): bool
    {
        return (($this->isPartner() === true) and
                (in_array($this->getPartnerType(), Constants::$settingsAccessPartnerTypes, true) === true));
    }

    /**
     * @return bool
     */
    public function isPartnerWithWebhooksAccess(): bool
    {
        return (($this->isPartner() === true) and
            (in_array($this->getPartnerType(), Constants::$webhooksAccessPartnerTypes, true) === true));
    }

    /**
     * Appends the merchant id with the Account entity's sign
     *
     * @param array $array
     */
    protected function setSignedId(array & $array)
    {
        $array[self::ID] = Account\Entity::getSignedId($array[self::ID]);
    }

    /**
     * toArrayPartner() comprises of all Public attributes and a few additional attributes exposed only to the partners.
     *
     * @return array
     */
    public function toArrayPartner(): array
    {
        $array = parent::toArrayPartner();

        // Prepend the Account id sign
        $this->setSignedId($array);

        return $array;
    }

    public function toListSubmerchantsArray(): array
    {
        $array = $this->toArray();

        // Prepend the Account id sign
        $this->setSignedId($array);

        return $array;
    }

    protected function fireEventWithMerchantPayload(string $event)
    {
        $entity = clone $this;

        $eventPayload = [
            ApiEventSubscriber::MAIN => $entity,
        ];

        $app = App::getFacadeRoot();

        $app['events']->dispatch($event, $eventPayload);
    }

    public function isInternationalEnabledForProduct(string $product)
    {
        if (in_array($product,ProductInternational\ProductInternationalMapper::LIVE_PRODUCTS,  true) === true)
        {
            $merchantProductInternationalField = new ProductInternational\ProductInternationalField($this);

            $status = $merchantProductInternationalField->getProductStatus($product, $this->getProductInternational());

            return ($status === ProductInternational\ProductInternationalMapper::ENABLED);
        }

        return true;
    }

    public function isRazorpayOrgId() :bool
    {
        return $this->getOrgId() === Org\Entity::RAZORPAY_ORG_ID;
    }

    /**
     * Few orgs are very often on direct settlements, so instant activation means
     * they are fully activated, (without any KYC)
     * So blocking instant_activation for them
     *
     * @return bool
     */
    public function isBlockedOrgForInstantActivation(): bool
    {
        return in_array($this->getOrgId(), self::INSTANT_ACTIVATION_BLOCKED_ORG_MAPPING, true);
    }

    // returns true if the 'show_refund_public_status' or 'refund_pending_status' feature is enabled
    public function isFeatureRefundPublicStatusOrPendingStatusEnabled(): bool
    {
        if (($this->isFeatureEnabled(Feature\Constants::SHOW_REFUND_PUBLIC_STATUS) === true) or
            ($this->isFeatureEnabled(Feature\Constants::REFUND_PENDING_STATUS) === true))
        {
            return true;
        }

        return false;
    }

    public function merchantNotificationConfigs()
    {
        return $this->hasMany(Merchant\MerchantNotificationConfig\Entity::class);
    }

    public function shouldShowCustomOrgBranding(): bool
    {
        if ($this->org->getId() === Org\Constants::RZP)
        {
            return false;
        }

        if (($this->isFeatureEnabled(Feature\Constants::ORG_CUSTOM_BRANDING) === true) or
            ($this->org->isFeatureEnabled(Feature\Constants::ORG_CUSTOM_BRANDING) === true))
        {
            return true;
        }

        return false;
    }

    public function partnerActivation()
    {
        return $this->hasOne('RZP\Models\Partner\Activation\Entity', self::MERCHANT_ID, self::ID);
    }

    public function isActivateForFourMonths() : bool
    {
        $activatedAt = Carbon::createFromTimestamp($this->getActivatedAt());

        return Carbon::today()->diffInMonths($activatedAt) >= 4;
    }

    public function getPurposeCode()
    {
        return $this->getAttribute(self::PURPOSE_CODE);
    }

    public function getMerchantProperties()
    {
         return([
                'id'        => $this->getId(),
                'name'      => $this->getBillingLabel(),
                'mcc'       => $this->getCategory(),
                'category'  => $this->getCategory2(),
         ]);
    }

    public function getPurposeCodeDescription()
    {
        if (empty($this->getPurposeCode()) === false) {
            return PurposeCodeList::getPurposeCodeDescDescription($this->getPurposeCode());
        }

        return null;
    }

    public function getIecCode()
    {
        if ($this->merchantDetail !== null)
        {
            return $this->merchantDetail->getIecCode();
        }

        return null;
    }

    public function getQueueableRelations()
    {
        /**
         * This introduced seg faults during serialize function call
         * where 1 of the relations had a relationship defined back
         * to the initial model, thus creating an infinite loop.
         *
         * @see Model::getQueueableRelations()
         * @see https://github.com/laravel/framework/issues/23505
         */
        $relations = [];

        foreach ($this->getRelations() as $key => $relation) {
            if (!method_exists($this, $key)) {
                continue;
            }

            $relations[] = $key;
        }

        return array_unique($relations);
    }

    public function isXDemoAccount(): bool
    {
        return Account::isXDemoAccount($this->getId());
    }

    public function isAddressWithNameRequiredEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::ADDRESS_NAME_REQUIRED) === true);
    }

    public function Is3dsDetailsRequiredEnabled(): bool
    {
        return ($this->isFeatureEnabled(Feature\Constants::ENABLE_3DS2) === true);
    }

    public function IsAvsCheckMandatoryEnabled(): bool
    {
       return ($this->isFeatureEnabled(Feature\Constants::MANDATORY_AVS_CHECK) === true);
    }

    public function isSignupCampaign($signupCampaign): bool
    {
        $app = App::getFacadeRoot();

        $deviceDetail = $app['repo']->user_device_detail->fetchByMerchantIdAndUserRole($this->getId());

        if (optional($deviceDetail)->getSignupCampaign() === $signupCampaign)
        {
            return true;
        }

        return false;
    }

    public function getService()
    {
        $app = App::getFacadeRoot();

        $deviceDetail = $app['repo']->user_device_detail->fetchByMerchantIdAndUserRole($this->getId());

        return optional($deviceDetail)->getValueFromMetaData('service');
    }

    public function isSignupSourceIn($signupSourceList): bool
    {
        $app = App::getFacadeRoot();

        $deviceDetail = $app['repo']->user_device_detail->fetchByMerchantIdAndUserRole($this->getId());

        if (empty($deviceDetail) === true)
        {
            return false;
        }

        if (in_array($deviceDetail->getSignupSource(), $signupSourceList, true) === true)
        {
            return true;
        }

        return false;
    }

    public function isBilldeskSIHubEnabled(): bool
    {
        return $this->isFeatureEnabled(Feature\Constants::RECURRING_CARD_MANDATE_BILLDESK_SIHUB);
    }

    public function isFieldHasValue($field): bool
    {
        if (!empty($field))
        {
            return $this->isAttributeNotNull($field);
        }

        return false;
    }

    public function getFullManagedPartnerWithTokenInteroperabilityFeatureIfApplicable($merchant)
    {
        try {
            $this->app = App::getFacadeRoot();
            $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();
            $partner = $this->app['repo']->merchant->find($partnerMerchantId);

            if (($partner != null) and
                ($partner->isTokenInteroperabilityEnabled() === true) and
                ($partner->isFullyManagedPartner() === true))
            {
                app('trace')->info(
                    TraceCode::TOKEN_INTEROPERABILITY_PARTNER_MERCHANT_USED,
                    [
                        'partner_id'        => $partnerMerchantId,
                        'merchant_id'       => $merchant->getId(),
                    ]);

                return $partner;
            }
            else {
                return $merchant;
            }
        }
        catch (\Exception $exception) {
            app('trace')->traceException($exception, Logger::ERROR, TraceCode::TOKEN_INTEROPERABILITY_FETCH_PARTNER_EXCEPTION);
            return $merchant;
        }
    }

    public function getMerchantLegalEntityName()
    {
        $name = null;

        switch ($this->merchantDetail->getBusinessType())
        {
            case BusinessType::INDIVIDUAL:
            case BusinessType::NOT_YET_REGISTERED:
                $name = $this->getBillingLabel();
                break;
            default:
                $name = $this->merchantDetail->getBusinessName();
                break;
        }

        return $name;
    }

    /**
     * This function can be used while preparing payload to send notifications via stork (to fit 160 char limit for SMS)
     *
     * @param int    $limit
     * @param string $suffix
     */
    public function getTrimmedName($limit = 25, $suffix = "...")
    {
        return mb_strimwidth($this->getName(), 0, $limit, $suffix);
    }

    public function getCountry()
    {
        $country  = $this->getAttribute(self::COUNTRY_CODE);

        return $country ?? 'IN';
    }

    public function getCurrency()
    {
        return Currency::getCurrencyForCountry($this->getCountry()) ?? "INR";
    }

    public function getTimeZone(){

        $country = $this->getCountry();
        if ($country == 'MY'){
            return Timezone::MYT;
        }
        return Timezone::IST;
    }
}
