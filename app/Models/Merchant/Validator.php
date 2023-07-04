<?php

namespace RZP\Models\Merchant;

use App;
use Hash;

use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use Razorpay\Trace\Logger as Trace;

use RZP\Base;
use Carbon\Carbon;
use RZP\Constants\Country;
use RZP\Exception;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Order\Status;
use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\PurposeCode\PurposeCodeList;
use RZP\Models\Partner\Config\Constants as ConfigConstants;
use RZP\Models\User;
use FuzzyWuzzy\Fuzz;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\User\Role;
use RZP\Models\Settlement;
use RZP\Constants\Product;
use RZP\Models\Admin\Admin;
use RZP\Models\Merchant\Credits as FundCredits;
use RZP\Models\Address;
use RZP\Models\Batch\Header;
use RZP\Models\Payment\Event;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Balance;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Admin\Permission\Name as Permission;
use \RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Exception\BadRequestValidationFailureException;
use \RZP\Models\Workflow\Action\Entity as ActionEntity;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\ActivationFlow as ActivationFlow;
use RZP\Models\Merchant\Analytics\Constants as AnalyticsConstants;
use RZP\Models\RiskWorkflowAction\Constants as RiskActionConstants;
use RZP\Models\Merchant\ProductInternational\ProductInternationalField;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApplicationsEntity;
use RZP\Models\Merchant\Detail\InternationalActivationFlow\InternationalActivationFlow;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\VirtualAccount\Entity as VAEntity;
use RZP\Models\Adjustment;
use RZP\Models\Payment;
use RZP\Models\Merchant\ShippingInfo\Constants as ShippingInfoConstants;

/**
 * Class Validator
 *
 * @package RZP\Models\Merchant
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    // Maximum image size - 1M.
    const MAXIMAGESIZE = 1024 * 1024;
    const PREFERENCES     = 'preferences';
    const PERSONALISATION = 'personalisation';

    const BATCH_ID                          = 'Batch Id';
    const BULK_SUBMERCHANT_ASSIGN           = 'Bulk Submerchant Assign';
    // Rate limit on items sending for bulk submerchant assign.
    const MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT = 15;

    // Thresholds for billing label validation
    const THRESHOLD_FOR_WEBSITE_SIMILARITY = 80;

    const THRESHOLD_FOR_BUSINESS_NAME_SIMILARITY = 80;

    const NEW_BILLING_LABEL = 'new_billing_label';

    const OLD_BILLING_LABEL = 'old_billing_label';

    const IS_FROM_SUGGESTIONS =  'is_value_from_suggestions';

    const SIMILARITY_WITH_WEBSITE = 'similarity_with_website';

    const SIMILARITY_WITH_BUSINESS_NAME = 'similarity_with_business_name';

    const VALIDATION_STATUS = 'validation_status';

    const PASSED = 'passed';

    const FAILED = 'failed';

    const BILLING_LABEL_INVALID_MESSAGE = 'Invalid value, the brand name must be similar to business name or website name';

    const EMAIL_UPDATE_SAME_AS_CURRENT_VALIDATION_FAILURE_MESSAGE = 'Provided Email Should Be different than current one';

    const AUTO_AMC_LINKED_ACCOUNT_CREATION_JOB = 'worker:auto_linked_account_creation';

    const ADMIN_BATCH = 'worker:batch';

    const EXTENSIONMIMEMAP = [
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'png'   => 'image/png',
    ];

    const ACTION_PERMISSION_MAP_FOR_MERCHANT_EDIT_BULK = [
        Action::SUSPEND               => Permission::EDIT_MERCHANT_SUSPEND_BULK,
        Action::UNSUSPEND             => Permission::EDIT_MERCHANT_SUSPEND_BULK,
        Action::LIVE_DISABLE          => Permission::EDIT_MERCHANT_TOGGLE_LIVE_BULK,
        Action::LIVE_ENABLE           => Permission::EDIT_MERCHANT_TOGGLE_LIVE_BULK,
        Action::HOLD_FUNDS            => Permission::EDIT_MERCHANT_HOLD_FUNDS_BULK,
        Action::RELEASE_FUNDS         => Permission::EDIT_MERCHANT_HOLD_FUNDS_BULK,
        Action::DISABLE_INTERNATIONAL => Permission::EDIT_MERCHANT_DISABLE_INTERNATIONAL_BULK,
        Action::ENABLE_INTERNATIONAL  => Permission::EDIT_MERCHANT_ENABLE_INTERNATIONAL_BULK,
    ];

    const MERCHANT_RISK_ATTRIBUTES = [
        Entity::MAX_PAYMENT_AMOUNT,
        Entity::MAX_INTERNATIONAL_PAYMENT_AMOUNT
    ];

    const ONLY_DS_BLOCKED_TAGS = [
        'white_labelled_route',
        'white_labelled_marketplace',
        'white_labelled_virtual_accounts',
        'white_labelled_qr_codes'
    ];

    protected static $createRules = [
        Entity::ID                          => 'sometimes|alpha_num|size:14|unique:merchants',
        Entity::NAME                        => 'sometimes|string|max:200',
        Entity::EMAIL                       => 'sometimes|email',
        Entity::ORG_ID                      => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                      => 'sometimes|array',
        Entity::ADMINS                      => 'sometimes|array',
        Entity::COUPON_CODE                 => 'sometimes|string',
        Constants::PARTNER_INTENT           => 'sometimes|boolean',
        Entity::EXTERNAL_ID                 => 'sometimes|string|max:255',
        Entity::SIGNUP_SOURCE               => 'sometimes|string|max:32',
        Entity::CODE                        => 'custom',
        Entity::SIGNUP_VIA_EMAIL            => 'sometimes|in:0,1',
        Entity::COUNTRY_CODE                => 'sometimes|string|max:2|in:IN,MY',
        Entity::BILLING_LABEL               => 'sometimes|max:255',
    ];

    protected static $editRules = [
        Entity::WHITELISTED_DOMAINS                   => 'sometimes|array',
        Entity::ACTIVATED                             => 'sometimes|boolean',
        Entity::ACTIVATED_AT                          => 'sometimes|integer',
        Entity::NAME                                  => 'sometimes|string|max:200',
        Entity::EMAIL                                 => 'sometimes|email',
        Entity::HOLD_FUNDS                            => 'sometimes|in:0,1',
        Entity::WEBSITE                               => 'sometimes|url|max:255|nullable',
        Entity::CATEGORY                              => 'sometimes|string|digits:4',
        Entity::CATEGORY2                             => 'sometimes|string|max:30|custom',
        Entity::BILLING_LABEL                         => 'sometimes|max:255',
        Entity::TRANSACTION_REPORT_EMAIL              => 'sometimes|array',
        Entity::RECEIPT_EMAIL_ENABLED                 => 'sometimes|boolean',
        Entity::RECEIPT_EMAIL_TRIGGER_EVENT           => 'sometimes|nullable|string|in:authorized,captured',
        Entity::LINKED_ACCOUNT_KYC                    => 'sometimes|boolean',
        Entity::CHANNEL                               => 'sometimes|string|max:32|custom',
        Entity::RISK_RATING                           => 'sometimes|min:0|max:5',
        Entity::RISK_THRESHOLD                        => 'sometimes|integer|min:0|max:100',
        Entity::FEE_BEARER                            => 'sometimes|in:customer,platform,dynamic',
        Entity::FEE_MODEL                             => 'sometimes|in:prepaid,postpaid',
        Entity::REFUND_SOURCE                         => 'sometimes|string|max:32|in:balance,credits',
        Entity::MAX_PAYMENT_AMOUNT                    => 'sometimes|integer',
        Entity::MAX_INTERNATIONAL_PAYMENT_AMOUNT      => 'sometimes|integer',
        // max: 5 days (don't change max value without consult), min:60 minutes
        Entity::AUTO_REFUND_DELAY                     => 'sometimes|string|custom',
        Entity::DEFAULT_REFUND_SPEED                  => 'sometimes|filled|string|in:normal,optimum',
        Entity::AUTO_CAPTURE_LATE_AUTH                => 'sometimes|boolean',
        Entity::CONVERT_CURRENCY                      => 'sometimes|nullable|boolean',
        Entity::ORG_ID                                => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                                => 'sometimes|array',
        Entity::ADMINS                                => 'sometimes|array',
        Entity::WHITELISTED_IPS_LIVE                  => 'sometimes|array|max:15',
        Entity::WHITELISTED_IPS_LIVE . '.*'           => 'required_with:' . Entity::WHITELISTED_IPS_LIVE . '|ipv4',
        Entity::WHITELISTED_IPS_TEST                  => 'sometimes|array|max:15',
        Entity::WHITELISTED_IPS_TEST . '.*'           => 'required_with:' . Entity::WHITELISTED_IPS_TEST . '|ipv4',
        Entity::DASHBOARD_WHITELISTED_IPS_LIVE        => 'sometimes|array|max:20',
        Entity::DASHBOARD_WHITELISTED_IPS_LIVE . '.*' => 'distinct|required_with:' .
                                                         Entity::DASHBOARD_WHITELISTED_IPS_LIVE . '|ipv4',
        Entity::DASHBOARD_WHITELISTED_IPS_TEST        => 'sometimes|array|max:20',
        Entity::DASHBOARD_WHITELISTED_IPS_TEST . '.*' => 'distinct|required_with:' .
                                                         Entity::DASHBOARD_WHITELISTED_IPS_TEST . '|ipv4',
        Entity::FEE_CREDITS_THRESHOLD                 => 'sometimes|integer|nullable',
        Entity::AMOUNT_CREDITS_THRESHOLD              => 'sometimes|integer|nullable',
        Entity::REFUND_CREDITS_THRESHOLD              => 'sometimes|integer|nullable',
        Entity::BALANCE_THRESHOLD                     => 'sometimes|integer|nullable|min:0',
        Entity::PARTNERSHIP_URL                       => 'sometimes|max:2000',
        'reset_methods'                               => 'sometimes|boolean',
        'reset_pricing_plan'                          => 'sometimes|boolean',
        Entity::PURPOSE_CODE                          => 'sometimes|string|max:5',
//        Entity::EMAIL                                 => 'sometimes|email|unique:merchants',
        Entity::COUNTRY_CODE                          => 'sometimes|string|size:2|in:IN,MY',
    ];

    protected static $editBillingLabelRules = [
        Entity::BILLING_LABEL            => 'sometimes|filled|string|min:3|max:255|custom:billing_label'
    ];

    protected static $uniqueEmailRules = [
        Entity::EMAIL                       => 'sometimes|email|unique:merchants'
    ];

    protected static $editCreditsRules = [
        Balance\Entity::AMOUNT_CREDITS      => 'required|integer|min:0|max:50000000'
    ];

    protected static $editEmailRules = [
        Entity::EMAIL                               => 'required|email|unique:merchants',
    ];

    protected static $editEmailNonUniqueRules = [
        Entity::EMAIL                               => 'required|email',
    ];

    protected static $changeEmailTokenRules = [
        User\Entity::PASSWORD                    => 'required|between:8,50|confirmed|numbers|letters',
        User\Entity::PASSWORD_CONFIRMATION       => 'required|between:8,50',
        User\Entity::TOKEN                       => 'required|string|size:50',
        Entity::MERCHANT_ID                      => 'required|string',
    ];

    protected static $editMerchantEmailSelfServeRules = [
        Entity::EMAIL                           => 'required|email|custom:edit_email_not_same_as_current',
        Constants::REATTACH_CURRENT_OWNER       => 'sometimes|boolean',
        Constants::SET_CONTACT_EMAIL            => 'sometimes|boolean',
    ];

    protected static $editPreSignupRules = [
        Entity::NAME                        => 'required|min:4|string|max:200',
        Entity::WEBSITE                     => 'sometimes|custom:active_url|max:255|nullable',
//        Entity::EMAIL                       => 'sometimes|email|unique:merchants',
    ];

    protected static $editNameRules = [
        Entity::NAME                        => 'required|min:4|string|max:200',
    ];

    protected static $linkedAccountNameRules = [
        Entity::NAME                     => [
                                                'required',
                                                'min:4',
                                                'string',
                                                'max:200',
                                                'not_regex:"(https?:\/\/)*(w{3}\.)*[a-zA-Z0-9]+(\.)(com|in|net|co\.in|org|us|info|co)+(\ |\/|$|\n)"',
                                                'not_regex:"(<!doctype>|<!--|<.*>|&[a-z0-9]+;)+"'
                                            ]
    ];

    protected static $editConfigRules = [
        Entity::BRAND_COLOR              => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Entity::TRANSACTION_REPORT_EMAIL => 'sometimes|array',
        Entity::LOGO_URL                 => 'sometimes|max:2000',
        Entity::INVOICE_LABEL_FIELD      => 'sometimes|filled|string|max:50|in:business_name,business_dba',
        Entity::AUTO_CAPTURE_LATE_AUTH   => 'sometimes|boolean',
        Entity::HANDLE                   => 'sometimes|nullable|min:3|max:4|custom|unique:merchants,handle,null',
        Entity::DISPLAY_NAME             => 'sometimes|nullable|string|min:3|max:255',
        Entity::FEE_CREDITS_THRESHOLD    => 'sometimes|integer|nullable',
        Entity::AMOUNT_CREDITS_THRESHOLD => 'sometimes|integer|nullable',
        Entity::REFUND_CREDITS_THRESHOLD => 'sometimes|integer|nullable',
        Entity::BALANCE_THRESHOLD        => 'sometimes|integer|nullable|min:0',
        Entity::DEFAULT_REFUND_SPEED     => 'sometimes|filled|string|in:normal,optimum',
        Entity::FEE_BEARER               => 'sometimes|in:customer,platform',
        Entity::NOTES                    => 'sometimes|notes',
    ];

    protected static $actionRules = [
        Entity::ACTION                                      => 'required|custom',
        ProductInternationalMapper::INTERNATIONAL_PRODUCTS  => 'sometimes|array',
        RiskActionConstants::RISK_ATTRIBUTES                => 'sometimes|array',
        Constants::BULK_WORKFLOW_ACTION_ID                  => 'sometimes|string|size:14',
    ];

    protected static $change2faSettingRules = [
        User\Entity::PASSWORD         => 'sometimes|between:6,50',
        Entity::SECOND_FACTOR_AUTH    => 'required|boolean',
    ];

    protected static $accountNameRules = [
        Entity::NAME                    => 'required|alpha_space|between:4,255',
    ];

    protected static $bulkTagRules = [
        'action'         => 'required|string|filled|max:10|in:insert,delete',
        'name'           => 'required|string|filled',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|filled|size:14'
    ];

    protected static $bulkTagBatchRules = [
        'action'         => 'required|string|filled|max:10|in:insert,delete',
        'tags'           => 'required|string|custom',
        'merchant_id'    => 'required|string|size:14'
    ];

    protected static $bulkAssignScheduleRules = [
        'schedule'       => 'required|array',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|filled|size:14',
    ];

    protected static $bulkAssignPricingRules = [
        'pricing_plan_id' => 'required|string|size:14',
        'merchant_ids'    => 'required|array',
        'merchant_ids.*'  => 'required|string|filled|size:14',
    ];

    protected static $oauthMailRules = [
        'client_id'    => 'required|alpha_num|size:14',
        'user_id'      => 'required|alpha_num|size:14',
        'merchant_id'  => 'required|alpha_num|size:14'
    ];

    protected static $tallyAuthOtpMailRules = [
        'client_id'    => 'required|alpha_num|size:14',
        'user_id'      => 'required|alpha_num|size:14',
        'merchant_id'  => 'required|alpha_num|size:14',
        'otp'          => 'required',
        'email'        => 'required|email'
    ];

    protected static $merchantMailRules = [
        'type'           => 'required|string',
        'data'           => 'required|array'
    ];

    protected static $getPaymentFailureAnalysisRules = [
        'from'          => 'required|filled|epoch',
        'to'            => 'required|filled|epoch'
    ];

    protected static $instrumentStatusUpdateMerchantMailRules = [
        'contact_name'      => 'required|string',
        'contact_email'     => 'required|string',
        'current_status'    => 'required|string',
        'old_status'        => 'required|string',
        'instrument_name'   => 'required|string',
        'comment'           => 'sometimes|string',
        'profile_link'      => 'sometimes|string'
    ];

    protected static $featureRules = [
        'features'                   => 'required|array',
        'optout_reason'              => 'sometimes|string|max:200',
        Feature\Entity::SHOULD_SYNC  => 'sometimes|boolean',
        'es_enabled'                => 'sometimes|boolean',
    ];

    protected static $addTagsRules = [
        'tags'   => 'required|array',
        'tags.*' => 'required|string',
    ];

    protected static $updateMerchantsBulkRules = [
        'merchant_ids'           => 'required|sequential_array',
        'attributes'             => 'sometimes|associative_array',
        'risk_attributes'        => 'sometimes',
        'action'                 => 'sometimes',
        'international_products' => 'sometimes',
    ];

    protected static $keyAccessRules = [
        Entity::HAS_KEY_ACCESS => 'required|boolean',
    ];

    protected static $updateChannelRules = [
        'channel'       => 'required|string|max:32|custom',
        'merchant_ids'  => 'required|array'
    ];

    protected static $updateBankAccountRules = [
        'bank_account'   => 'required|array',
        'merchant_ids'   => 'required|array'
    ];

    protected static $createBatchRules = [
        'type'        => 'required|string|max:50',
        'data'        => 'required|array'
    ];

    protected static $payoutMailRules = [
        'content'               => 'required|array',
        'content.*.merchant_id' => 'required',
        'content.*.email'       => 'required',
    ];

    protected static $irctcRules = [
        'refund'       => 'sometimes|filled|file|mimes:txt|max:5120',
        'delta_refund' => 'sometimes|filled|file|mimes:txt|max:5120',
        'settlement'   => 'sometimes|filled|file|mimes:txt|max:5120',
    ];

    protected static $createSubMerchantUserRules = [
        'merchant_id'   => 'required|alpha_num|size:14',
        Entity::EMAIL   => 'required|email',
        Entity::PRODUCT => 'sometimes|string|in:primary,banking',
    ];

    protected static $editMethodsRules = [
        //only this method editing is allowed for now
        Methods\Entity::EMI => 'required|array',
    ];

    //only paypal method is allowed for now, change validation to allow more methods
    protected static $editMerchantMethodsRules = [
        Methods\Entity::PAYPAL   => 'sometimes|bool',
        Methods\Entity::PAYTM    => 'sometimes|bool',
        Methods\Entity::PHONEPE  => 'sometimes|bool',
        Methods\Entity::IN_APP  => 'sometimes|bool',
        Methods\Entity::INTL_BANK_TRANSFER  => 'sometimes|sequential_array',
        Methods\Entity::UPI      => 'sometimes|bool',
        Methods\Entity::SODEXO  => 'sometimes|bool',
    ];

    protected static $resetSettlementScheduleRules = [
        'merchant_ids'   => 'required|sequential_array',
        'merchant_ids.*' => 'required|alpha_num|size:14',
    ];

    protected static $trimMerchantDataRules = [
        'merchant_ids'   => 'required|sequential_array',
        'merchant_ids.*' => 'required|alpha_num|size:14',
    ];

    // app scalability validation
    protected static $appScalabilityChangeFtuxRules = [
        constants::PRODUCT       => 'required|string|in:payment_link,payment_gateway,payment_pages,qr_code,subscriptions,payment_button',
        constants::FTUX_COMPLETE => 'required|bool',
        constants::INTRODUCING   => 'sometimes|bool',
    ];


    protected static $editConfigValidators = [
        'csv_email',
    ];

    protected static $editValidators = [
        'csv_email',
    ];

    protected static $featureValidators = [
        'visible_and_editable_features',
        'mode_for_product_features',
    ];

    protected static $editEmailValidators = [
        'is_test_account',
    ];

    protected static $keyAccessValidators = [
        'key_access',
    ];

    protected static $listSubmerchantsRules = [
        Entity::NAME                      => 'sometimes|string',
        Entity::ID                        => 'sometimes|alpha_num|size:14',
        Entity::EMAIL                     => 'sometimes|email',
        Detail\Entity::CONTACT_MOBILE     => 'sometimes|max:15|contact_syntax',
        Constants::CONTACT_INFO           => 'sometimes|string',
        Constants::APPLICATION_ID         => 'sometimes|string|size:14',
        Detail\Entity::ACTIVATION_STATUS  => 'sometimes|string|in:instantly_activated,activated,under_review,needs_clarification,activated_mcc_pending,kyc_qualified_unactivated,activated_kyc_pending,not_submitted',
        Constants::FROM                   => 'integer',
        Constants::TO                     => 'integer',
        Constants::COUNT                  => 'integer|min:1|max:50',
        Constants::SKIP                   => 'integer',
        Entity::PRODUCT                   => 'required_with:is_used|in:primary,banking,capital',
        Constants::IS_USED                => 'sometimes|in:0,1',
        Entity::MERCHANT_ID               => 'sometimes|array',
        MerchantApplications\Entity::TYPE => 'sometimes|string|in:managed,referred,oauth',
    ];

    protected static $partnerSubmerchantMapRules = [
        'partner_type'              => 'required|string',
        'submerchant_id'            => 'required|string',
        'partner_merchant_id'       => 'required|string',
    ];

    protected static $merchantPartnerStatusRules = [
        'email' => 'required|email',
    ];


    protected static $bulkSyncBalanceRules = [
        Constants::INTERVAL => 'sometimes|integer|min:15|max:120'
    ];

    protected static $submitSupportCallRequestRules = [
        'contact' => 'required|contact_syntax',
    ];

    protected static $toggleInternationalRules = [
        Entity::INTERNATIONAL => 'required|boolean'
    ];

    protected static $onboardMerchantInputRules = [
        Terminal\Entity::GATEWAY               => 'required|in:hitachi,paysecure,fulcrum',
        'gateway_input'                        => 'sometimes',
        Terminal\Entity::GATEWAY_ACQUIRER      => 'sometimes',
        'currency_code'                        => 'sometimes',
        'identifiers'                          => 'sometimes',
        Terminal\Entity::GATEWAY_MERCHANT_ID   => 'sometimes',
    ];

    protected static $onboardMerchantInputHitachiRules = [
        Terminal\Entity::GATEWAY               => 'required|in:hitachi',
        'gateway_input'                        => 'required',
    ];

    protected static $onboardMerchantInputPaysecureRules = [
        Terminal\Entity::GATEWAY               => 'required|in:paysecure',
        Terminal\Entity::GATEWAY_ACQUIRER      => 'required|in:axis',
        Terminal\Entity::GATEWAY_MERCHANT_ID   => 'sometimes',
    ];

    protected static $onboardMerchantInputFulcrumRules = [
        Terminal\Entity::GATEWAY               => 'required|in:fulcrum',
        Terminal\Entity::GATEWAY_ACQUIRER      => 'required|in:ratn,axis',
        'currency_code'                        => 'required',
        'identifiers'                          => 'sometimes',
    ];

    protected static $restrictSettingsMerchantRules = [
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        Entity::ACTION      => 'required|in:add,remove',
    ];

    protected static $bulkSubmerchantAssignRules = [
        'idempotency_key'   => 'required',
        'submerchant_id'    => 'required|alpha_num|size:14',
        'terminal_id'       => 'required|alpha_num|size:14',
    ];

    protected static $suspendedMerchantRemoveRules = [
        'skip'  => 'sometimes|integer',
        'limit' => 'sometimes|integer',
    ];

    protected static $updatePartnerIntentRules = [
        Constants::PARTNER_INTENT       => 'required|boolean',
    ];

    protected static $updatePartnerTypeRules = [
        Entity::PARTNER_TYPE    => 'required|string|custom:partner_type_for_update',
        DEConstants::CONSENT    => 'sometimes|boolean',
    ];

    protected static $preferencesRules = [
        'contact_id'  => 'filled|public_id',
    ];

    protected static $personalisationRules = [
        'contact_id'  => 'filled|public_id',
    ];

    protected static $holidayNotifyRules = [
        'lists'   => 'required|string',
        'action'  => 'required|string',
    ];

    protected static $entityBatchActionRules = [
        Constants::BATCH_ACTION  => 'required|string|custom',
        Constants::ENTITY        => 'required|string|custom',
        Constants::IDEMPOTENT_ID => 'required',
        Entity::ID               => 'required|alpha_num|size:14',
    ];

    protected static $requestInternationalProductRules = [
        'products' => 'required|array|filled',
    ];

    protected static $accessMapBatchRules = [
        Constants::BATCH_ACTION  => 'required|string|custom',
        Constants::ENTITY        => 'required|string|custom',
        Constants::IDEMPOTENT_ID => 'required',
    ];

    protected static $codeRules = [
        Entity::CODE            => 'sometimes|string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
    ];

    protected static $completeSubmerchantOnboardingRules = [
        'submerchant_id'            => 'required|string',
        'partner_merchant_id'       => 'required|string',
    ];

    protected static $toggleFeeBearerRules = [
        Entity::FEE_BEARER      => 'required|string|in:platform,customer|custom:toggle_fee_bearer',
    ];

    protected static $transactionLimitSelfServeRules = [
        Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT      => 'required|integer|min:1',
        Constants::TRANSACTION_LIMIT_INCREASE_REASON      => 'required|string|min:100',
        Constants::TRANSACTION_TYPE                       => 'sometimes|string|in:domestic,international',
        Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
    ];

    protected static $fetchCouponsRequestRules = [
        'order_id'                      => 'sometimes|string',
        'contact'                       => 'sometimes|string',
        'email'                         => 'sometimes|email',
        'checkout_id'                   => 'sometimes|string',
    ];

    protected static $fetchCouponsResponseRules = [
        'code'                          => 'required|string',
        'summary'                       => 'required|string',
        'description'                   => 'sometimes|string',
        'tnc'                           => 'sometimes|array',
    ];

    protected static $fetchConfigForCheckoutRules = [
        'language_code' => 'sometimes|string|size:2',
    ];

    protected static $applyCouponRequestRules = [
        'order_id'                      => 'required|string',
        'contact'                       => 'sometimes|string',
        'email'                         => 'sometimes|email',
        'code'                          => 'required|string',
    ];

    protected static $applyCouponResponseRules = [
        'promotion'                     => 'required|array',
        'promotion.reference_id'        => 'required|string',
        'promotion.type'                => 'sometimes|string',
        'promotion.code'                => 'required|string',
        'promotion.value'               => 'required|integer',
        'promotion.value_type'          => 'sometimes|string',
        'promotion.description'         => 'sometimes|string',
        'amount'                        => 'sometimes|integer',
        'line_items'                    => 'sometimes|array',
        'line_items.*.sku'              => 'exclude_if:line_items,null|string',
        'line_items.*.variant_id'       => 'exclude_if:line_items,null|string',
        'line_items.*.price'            => 'exclude_if:line_items,null|integer',
        'line_items.*.offer_price'      => 'exclude_if:line_items,null|integer',
        'line_items.*.tax_amount'       => 'exclude_if:line_items,null|integer',
        'shipping_fee'                  => 'sometimes|integer',
        'cod_fee'                       => 'sometimes|integer',
        'line_items_total'              => 'sometimes|integer'
    ];

    protected static $removeCouponRequestRules = [
        'order_id'                      => 'required|string',
        'reference_id'                  => 'sometimes|string'
    ];

    protected static $applyGiftCardRequestRules = [
        'gift_card_number'              => 'required|string',
        'contact'                       => 'sometimes|contact_syntax',
        'email'                         => 'sometimes|email',
    ];

    protected static $applyGiftCardResponseRules = [
        'gift_card_promotion'                               => 'required',
        'gift_card_promotion.gift_card_number'              => 'required|string',
        'gift_card_promotion.balance'                       => 'required|integer',
        'gift_card_promotion.gift_card_reference_id'        => 'sometimes|string',
        'gift_card_promotion.allowedPartialRedemption'      => 'required|in:0,1',
    ];

    protected static $applyGiftCardInvalidRequestResponseRules = [
        'failure_reason'                => 'sometimes|string',
        'failure_code'                  => 'required|string',
    ];

    protected static $removeGiftCardRequestRules = [
        'gift_card_numbers'                     => 'required|array',
    ];

    protected static $applyCouponInvalidRequestResponseRules = [
        'failure_reason'                => 'sometimes|string',
        'failure_code'                  => 'required|string',
    ];

    protected static $couponCodeUrlUpdateRequestRules = [
        'url'                           => 'required|custom:active_url'
    ];

    protected static $shippingInfoRequestRules = [
        'zipcode'       => 'sometimes|string|between:0,16',
        'country'       => 'required|string|between:2,64',
        'state'         => 'sometimes|string|between:2,64',
        'state_code'    => 'sometimes|string|between:1,64',
    ];

    protected static $addressShippingInfoResponseRules = [
        'serviceable'                  => 'required|boolean',
        'cod'                          => 'sometimes|boolean',
        'cod_fee'                      => 'sometimes|integer|nullable',
        'shipping_fee'                 => 'sometimes|integer|nullable',
    ];

    protected static $serviceabilityUrlUpdateRequestRules = [
        'url'                          => 'required|url'
    ];

    protected static $merchantPlatformUpdateRequestRules = [
        'platform'                     => 'required|in:native,woocommerce,shopify,magento'
    ];

    protected static $updateSlabRequestRules = [
        'amount' => 'required|integer',
        'fee'    => 'required|integer',
    ];

    protected static $laBankAccountUpdateRules = [
        'beneficiary_name'          => 'required|string|between:4,120',
        'account_number'            => 'required|alphanum|between:5,35',
        'ifsc_code'                 => 'required|alphanum|size:11',
    ];

    protected static $merchantWorkflowClarificationRules = [
        Constants::MERCHANT_WORKFLOW_CLARIFICATION          => 'required|string',
        Constants::WORKFLOW_CLARIFICATION_DOCUMENTS_IDS     => 'sometimes|array',
    ];

    protected static $getUpdatedAccountsForAccountServiceRules = [
        "from"      => 'required|int|min:0',
        "duration"  => 'required|int|min:0|max:86400',
        "limit"     => 'sometimes|int|min:1'
    ];

    protected static $aggregatorToResellerMigrationRules = [
        'merchant_id'     => 'required|alpha_num|size:14',
    ];

    protected static $bulkAggregatorToResellerMigrationRules = [
        'merchant_ids'     => 'required|array',
        'batch_size'       => 'required|int|min:1',
    ];

    /**
     * @var array|string[]
     *
     * @see Service::validatePublicAuthOverInternalAuth()
     */
    protected static array $publicAuthOverInternalAuthRules = [
        'merchant_public_key' => 'sometimes|string|max:31',
        'merchant_account_id' => 'sometimes|filled|string|max:18',
        'x_entity_id'  => 'sometimes|filled|string|max:21',
        'order_id' => 'sometimes|filled|string|max:20',
        'invoice_id' => 'sometimes|filled|string|max:18',
        'payment_id' => 'sometimes|filled|string|max:18',
        'contact_id' => 'sometimes|filled|string|max:21',
        'customer_id' => 'sometimes|filled|string|max:19',
        'subscription_id' => 'sometimes|filled|string|max:18',
        'payment_link_id' => 'sometimes|filled|string|max:19',
        'options_id' => 'sometimes|filled|string|max:18',
        'payout_link_id' => 'sometimes|filled|string|max:21',
    ];

    protected static $ipConfigCreateOrEditRules = [
        'whitelisted_ips'   => 'required|array|min:1|max:20',
        'service'           => 'sometimes|string',
        User\Entity::TOKEN  => 'sometimes|string',
        User\Entity::OTP    => 'sometimes|string|between:4,6',
        Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
    ];

    protected static $ipConfigOptStatusEditRules = [
        'opt_out'           => 'required|boolean',
        'whitelisted_ips'   => 'required_if:opt_out,false|array|min:1|max:20',
        'service'           => 'sometimes|string',
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
    ];

    protected static $ipConfigCreateOrEditValidators = [
        'ip_whitelist_input'
    ];

    protected static $sendSubmerchantProductRules = [
        'product'       => 'sometimes|string|custom'
    ];

    protected static $partnerSubmerchantInviteCapitalRules = [
        Detail\Entity::BUSINESS_NAME            => 'required|string|max:200',
        Entity::EMAIL                           => 'required|email',
        Entity::NAME                            => 'required|string|max:200',
        Detail\Entity::CONTACT_MOBILE           => 'required|max:15|contact_syntax',
        Header::ANNUAL_TURNOVER_MIN             => 'required|int|min:0',
        Header::ANNUAL_TURNOVER_MAX             => 'required|int|min:0',
        Detail\Entity::BUSINESS_TYPE            => 'sometimes|string',
        Detail\Entity::PROMOTER_PAN             => 'sometimes|personalPan',
        Header::BUSINESS_VINTAGE                => 'sometimes|string|in:UNKNOWN,LESS_THAN_3MONTHS,BETWEEN_3MONTHS_6MONTHS,BETWEEN_6MONTHS_12MONTHS,GREATER_THAN_12MONTHS',
        Detail\Entity::GSTIN                    => 'sometimes|gstin',
        Header::COMPANY_ADDRESS_LINE_1          => 'sometimes|string|max:255',
        Header::COMPANY_ADDRESS_LINE_2          => 'sometimes|string|max:255',
        Header::COMPANY_ADDRESS_CITY            => 'sometimes|string|max:255',
        Header::COMPANY_ADDRESS_STATE           => 'sometimes|string|max:255',
        Header::COMPANY_ADDRESS_COUNTRY         => 'sometimes|string|max:255',
        Header::COMPANY_ADDRESS_PINCODE         => 'sometimes|string|max:255',
        Entity::PRODUCT                         => 'required|in:capital'
    ];

    protected static $businessTypeValidators = [
        'business_type',
    ];

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateBusinessType(array $input)
    {
        if(isset($input[Detail\Entity::BUSINESS_TYPE]) === true)
        {
            $businessType = mb_strtolower($input[Detail\Entity::BUSINESS_TYPE]);
            if(Detail\BusinessType::isValidBusinessType($businessType) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid business type: $businessType", Detail\Entity::BUSINESS_TYPE);
            }
        }
    }

    protected static array $capitalSubmerchantApplicationFetchRequestRules = [
        Constants::PRODUCT_ID   => 'required|string',
        Constants::MERCHANT_ID  => 'required|array',
    ];

    protected static array $capitalSubmerchantApplicationFetchRequestValidators = [
        'product_id'
    ];

    /**
     * @param array $input
     *
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateProductId(array $input): void
    {
        $productIds = CapitalSubmerchantUtility::getLOSProductIds();

        if(isset($input[Constants::PRODUCT_ID]) === true)
        {
            $productId = $input[Constants::PRODUCT_ID];

            if (in_array($productId, array_values($productIds)) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid product ID: $productId", Constants::PRODUCT_ID);
            }
        }
    }

    public function validateIpWhitelistInput(array $input)
    {
        $proxyAuthAttributes = ((isset($input[User\Entity::OTP]) === true) and
                                (isset($input[User\Entity::TOKEN]) === true) and
                                (isset($input[Entity::MERCHANT_ID]) === false) and
                                (isset($input['service']) === false));

        if ((app('basicauth')->isProxyAuth() === true) and
            ($proxyAuthAttributes === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'One or more fields are invalid.');
        }

        if ((app('basicauth')->isAdminAuth() === true) and
            (isset($input[Entity::MERCHANT_ID]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The merchant_id field is required.');
        }
    }

    public function validateProduct($attribute, $value)
    {
        if (in_array($value, Product::VALID_SUBMERCHANT_PRODUCTS) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid product: ' . $value);
        }
    }

    public function validateSmartDashboardMerchantEditInput(array $input)
    {
        foreach ($input as $key => $value)
        {
            if (in_array($key, Constants::immutableSmartDashboardMerchantDetailsFields) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'field ' . $key . ' is immutable');
            }
        }
    }

    public function validateSettlementsEventsCronInput(array $input, $cronLastRunAt)
    {
        if (isset($input[Constants::END_TIMESTAMP]) === true)
        {
            $endTimestamp = $input[Constants::END_TIMESTAMP];
            if ($endTimestamp > Carbon::now()->getTimestamp() or $endTimestamp <= $cronLastRunAt)
            {
                throw new Exception\BadRequestValidationFailureException('end_timestamp should be in between last_run_at and the current timestamp');
            }
        }
    }

    public function validateMerchantAccessibilityForOrg($merchantId, $orgFeature)
    {
        if($orgFeature === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $app = App::getFacadeRoot();

        $merchant = $app->repo->merchant->findOrFailPublic($merchantId);

        $merchantFeature = Feature\Constants::$merchantFeaturesForOrgAccess[$orgFeature];

        $isAccessibleByExternalOrg = $merchant->isFeatureEnabled($merchantFeature);

        if($isAccessibleByExternalOrg === false)
        {
            $app['trace']->info(TraceCode::MERCHANT_NOT_ALLOWED_FOR_ORG, [
                'mid' => $merchantId,
                'merchant feature' => $merchantFeature,
                'org feature' => $orgFeature,
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
    }

    public function validateMerchantForProductInternational(Entity $merchant)
    {
        $merchant = $merchant?: $this->entity;

        $internationalActivationFlow  = $merchant->merchantDetail->getInternationalActivationFlow();

        $activationFlow = $merchant->merchantDetail->getActivationFlow();

        if (($internationalActivationFlow === InternationalActivationFlow::BLACKLIST) or
            ( $activationFlow ===  ActivationFlow::BLACKLIST ))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Blacklisted flow');
        }
    }

    public function validateOrgForOnboarding(array $input)
    {
        $app = App::getFacadeRoot();

        try
        {
            $orgId = $app['basicauth']->getOrgId();
        }
        catch (\Exception $e)
        {
            $app['trace']->traceException($e, Trace::ERROR, TraceCode::TERMINAL_ORG_HEADERS_EXCEPTION, [
                'message' => $e->getMessage(),
                'location' => 'validateOrgForOnboarding',
            ]);
            throw $e;
        }

        if(empty($orgId) === false)
        {
            $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);
        }

        if((empty($orgId)) or ($orgId === Org\Entity::RAZORPAY_ORG_ID))
        {
            return;
        }

        $validateOrgHasFeature = (new Org\Service)->validateOrgIdWithFeatureFlag($orgId, 'axis_org');

        if($input['gateway'] === 'paysecure')
        {
            if($validateOrgHasFeature === true)
            {
                return;
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Org not allowed');
            }
        }
    }

    protected function validateIsTestAccount(array $input)
    {
        $merchant = $this->entity;

        $isTestAccount = (new Account)->isTestAccount($merchant->getId());

        if ($isTestAccount === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_FOR_TEST_ACCOUNT);
        }

    }

    /**
     * Throws an exception if a merchant tries to enable an uneditable
     * feature for live mode, or via the should_sync flag
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateModeForProductFeatures(array $input)
    {
        $requestedFeatures = array_keys($input['features']);

        $shouldSync = (bool) ($input[Feature\Entity::SHOULD_SYNC] ?? false);

        $uneditableFeatures = array_values(array_intersect($requestedFeatures,
            Feature\Constants::PRODUCT_FEATURES));

        if ((count($uneditableFeatures) > 0) and (
            ($this->isLiveMode() === true) or
            ($shouldSync === true)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
                Feature\Entity::NAMES,
                ['features' => $uneditableFeatures, 'should_sync' => $shouldSync]);
        }
    }

    protected function validateChannel($attribute, $channel)
    {
        Settlement\Channel::validate($channel);
    }

    public function validateKeyAccess(array $input)
    {
        $merchant = $this->entity;

        if (empty($input[Entity::HAS_KEY_ACCESS]) === true)
        {
            return;
        }

        if (empty($merchant->merchantDetail->getWebsite()) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Key access cannot be granted with out website details');
        }
    }

    public function validateUserDoesNotBelongToMerchantsInMultipleOrgsForEmailUpdate($user)
    {
        $app = App::getFacadeRoot();

        $merchantOrgIdsForUser = array_unique($user->merchants()->get()->pluck('org_id')->toArray());

        $numberOfOrgIdsForUser = sizeof($merchantOrgIdsForUser);

        $orgId = $app['basicauth']->getMerchant()->getOrgId();

        // if user has no merchant or user has merchant[s] belongs to requested org
        if (($numberOfOrgIdsForUser === 0) or
            (($numberOfOrgIdsForUser === 1) and ($merchantOrgIdsForUser[0] === $orgId)))
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            'We are unable to change your email Id to ' . $user->getEmail() . '. Please reach out to our support team to perform this action');

    }

    protected function validateHandle($attribute, $handle)
    {
        if ($handle !== null)
        {
            if ($handle !== strtoupper($handle))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_HANDLE_UPPERCASE_ONLY);
            }
        }
    }

    public function validateLogo($imageDetails)
    {
        $fileSize = $imageDetails['size'];
        $width    = $imageDetails['width'];
        $height   = $imageDetails['height'];

        // File size should not be more than 1M.
        if ($fileSize > self::MAXIMAGESIZE)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG);
        }

        // The image should be square
        if ($width !== $height)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE
            );
        }

        // The minimum dimensions should be 256*256
        if ($width < 256)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL
            );
        }
    }

    public function validateImage($mimeType, $extension)
    {
        $acceptedMimeArray = self::EXTENSIONMIMEMAP;

        // Checks if extension is defined in the array and if the extension and mime type match.
        if ((!isset($acceptedMimeArray[$extension])) or
            ($acceptedMimeArray[$extension] !== $mimeType))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE
            );
        }
    }

    /**
     * Validates if billing label value has similarity with business
     * or website name or value belongs from suggestion
     * It uses fuzzy logic to check similarity
     * Threshold for website similarity is 80%
     * Threshold for business name similarity is 80%
     * @param $billingLabel : billing label value which need to be validated
     * @param $attribute
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateBillingLabel($attribute, $billingLabel)
    {
        $merchant = $this->entity;

        $suggestions = (new core())->getBillingLabelSuggestions($merchant);

        $billingLabel = (new core())->preProcessStringForBillingLabelUpdate($billingLabel);

        $businessName = $merchant->merchantDetail->getBusinessName();

        $website = $merchant->merchantDetail->getWebsite();

        $traceData = [
            self::NEW_BILLING_LABEL => $billingLabel,
            self::OLD_BILLING_LABEL => $merchant->getBillingLabel(),
            self::IS_FROM_SUGGESTIONS => false
        ];

        if (in_array($billingLabel, $suggestions, true) === true)
        {
            $traceData[self::IS_FROM_SUGGESTIONS] = true;
        }

        $similarityWithWebsite = $this -> getSimilarityWithWebsiteForBillingLabelUpdate(
            $billingLabel,
            $merchant);

        $traceData[self::SIMILARITY_WITH_WEBSITE] = $similarityWithWebsite;

        $traceData[Detail\Entity::BUSINESS_WEBSITE] = $website;

        $similarityWithBusinessName = $this -> getSimilarityWithBusinessNameForBillingLabelUpdate(
            $billingLabel,
            $merchant);

        $traceData[self::SIMILARITY_WITH_BUSINESS_NAME] = $similarityWithBusinessName;

        $traceData[Detail\Entity::BUSINESS_NAME] = $businessName;

        if ((in_array($billingLabel, $suggestions, true) === true) or
            ($similarityWithWebsite >= self::THRESHOLD_FOR_WEBSITE_SIMILARITY) or
            ($similarityWithBusinessName >= self::THRESHOLD_FOR_BUSINESS_NAME_SIMILARITY))
        {
            $traceData[self::VALIDATION_STATUS] = self::PASSED;

            $this->getTrace()->info(
                TraceCode::MERCHANT_BILLING_LABEL_UPDATE_VALIDATION,
                $traceData
            );

            return;
        }

        $traceData[self::VALIDATION_STATUS] = self::FAILED;

        $this->getTrace()->info(
            TraceCode::MERCHANT_BILLING_LABEL_UPDATE_VALIDATION,
            $traceData
        );

        throw new Exception\BadRequestValidationFailureException(
            self::BILLING_LABEL_INVALID_MESSAGE . ". website: " . $website . ", business name: " . $businessName);
    }

    /**
     * Gives similarity of a string with website/domain name
     * website url should be a valid url
     * It uses fuzzy logic to check similarity
     * @param $value string
     * @param $merchant \RZP\Models\Merchant\Entity
     * @return int max of (fuzzy ratio, token_sort_ratio)
     */
    protected function getSimilarityWithWebsiteForBillingLabelUpdate($value, $merchant): int
    {
        $websiteUrl = $merchant->merchantDetail->getWebsite();

        $merchantCore = new core();

        if ((isset($websiteUrl) === false) or
            ($merchantCore->isValidSchemeAndHostForBillingLabelUpdate($websiteUrl) === false))
        {
            return 0;
        }

        $websiteUrl = (new core())->preProcessStringForBillingLabelUpdate($websiteUrl);

        $host = parse_url($websiteUrl, PHP_URL_HOST);

        $extractedDomains = (new TLDExtract())->extract($host);

        if(count($extractedDomains) >= 2)
        {
            $hostWithoutTld = $extractedDomains[0];

            // divide in subdomain and second level domain
            $hostParts = explode('.', $hostWithoutTld);

            // take second level domain(just below top level domain) as website name
            $websiteName = $hostParts[count($hostParts)-1];

            return $this->getSimilarityOfStringsForBillingLabelUpdate($websiteName, $value);


        }

        return 0;
    }

    /**
     * Gives similarity of a string with business name
     * It uses fuzzy logic to check similarity
     * @param $newBillingLabel
     * @param $merchant
     * @return int  max of (fuzzy ratio, token_sort_ratio)
     */
    protected function getSimilarityWithBusinessNameForBillingLabelUpdate($newBillingLabel, $merchant): int
    {
        $businessName = $merchant->merchantDetail->getBusinessName();

        if(isset($businessName) === false)
        {
            return 0;
        }

        $merchantCore = new core();

        $businessName =  $merchantCore->preProcessStringForBillingLabelUpdate($businessName);

        $businessName =  $merchantCore->removeBusinessTypesForBillingLabelUpdate($businessName);

        $newBillingLabel = $merchantCore->removeBusinessTypesForBillingLabelUpdate($newBillingLabel);

        return $this->getSimilarityOfStringsForBillingLabelUpdate($businessName, $newBillingLabel);
    }

    /**
     * Gives similarity percentage of two strings
     * uses fuzzy logic to check similarity
     * @param $string1
     * @param $string2
     * @return int percentage similarity between strings {max of (fuzzy ratio, token_sort_ratio)}
     */
    protected function getSimilarityOfStringsForBillingLabelUpdate($string1, $string2) : int
    {
        $fuzz = new Fuzz();

        $percentageFromRatio = $fuzz->ratio($string1, $string2);

        $percentageFromTokenSort = $fuzz->tokenSortRatio($string1, $string2);

        return max($percentageFromRatio, $percentageFromTokenSort);
    }

    public function validateEditEmailNotSameAsCurrent($attribute, $email)
    {
        $app = App::getFacadeRoot();

        $currentEmail = $app['basicauth']->getUser()->getEmail();

        if ($currentEmail === $email)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::EMAIL_UPDATE_SAME_AS_CURRENT_VALIDATION_FAILURE_MESSAGE
            );
        }
    }

    public function validateCategory2($attribute, $value)
    {
        $category = $value;

        if (Terminal\Category::isMerchantCategoryValid($category) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Category : '.$category.' invalid for merchant',
                Entity::CATEGORY2
            );
        }
    }

    public function validateMerchantForMarketplaceTransfer($account, $mode)
    {
        if (($account === null) or
            ($account->isLinkedAccount() === false) or
            ($account->getParentId() !== $this->entity->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_INVALID_ACCOUNT_ID,
                'transfers.account'
            );
        }

        if (($mode === Mode::LIVE) and
            ($account->isActivated() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED
            );
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerWithSettingsAccess(Entity $merchant)
    {
        if ($merchant->isPartnerWithSettingsAccess() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNonPurePlatformPartner(Entity $merchant)
    {
        // Block non partners and pure platforms
        if ($merchant->isNonPurePlatformPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * @param $email
     * @param $orgId
     *
     * @throws Exception\BadRequestException
     */
    public function validateMerchantEmailUnique($email, $orgId)
    {
        $merchants = app('repo')->merchant->fetchByEmailAndOrgId(mb_strtolower($email), $orgId);

        if ($merchants->count() > 0)
        {
            // throw exception if merchant by that email already exists
            $description = PublicErrorDescription::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS . $merchants->pluck(Entity::ID)->first();

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS,
                Entity::EMAIL,
                $merchants->pluck(Entity::ID)->toArray(),
                $description
            );
        }
    }

    public function validateAdminPermissionForAction($action)
    {
        if (array_key_exists($action, self::ACTION_PERMISSION_MAP_FOR_MERCHANT_EDIT_BULK) === true)
        {
            $app = App::getFacadeRoot();

            $admin = $app['basicauth']->getAdmin();

            // Check for admin permissions
            $admin->hasPermissionOrFail(self::ACTION_PERMISSION_MAP_FOR_MERCHANT_EDIT_BULK[$action]);
        }
    }

    /**
     *
     */
    public function validateTagsForOnlyDSMerchants(Entity $merchant, $tags)
    {
        if($merchant->isFeatureEnabled(Feature\Constants::ONLY_DS) === false)
        {
            return;
        }

        foreach ($tags as $tag)
        {
            if(in_array($tag, self::ONLY_DS_BLOCKED_TAGS, true) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'For MIDs with "only_ds feature flag enabled", these tags cannot be enabled: route, qr_codes, smart collect');
            }
        }
    }

    /**
     * if the contructive action is being performed by non-risk l3 on a merchant tagged by risk ops,
     * then the validator should throw validation exception
     * @param $merchant
     * @param $action
     */
    public function validateRiskPermissionForAction($merchant, $action, $admin = null)
    {
        //if the action is constructive action
        if(in_array($action, Constants::RISK_CONSTRUCTIVE_ACTION_LIST) === false)
        {
            return;
        }

        $tags = $merchant->tagNames();

        $taggedByRiskOps = false;

        $riskTags= explode(',', RiskActionConstants::RISK_TAGS_CSV);

        //Check if the merchant is tagged by Risk team
        foreach ($tags as $tag)
        {
            if (in_array(strtolower($tag), $riskTags) === true)
            {
                $taggedByRiskOps = true;

                break;
            }
        }

        //if the merchant is not tagged, no further check required
        if ($taggedByRiskOps === false)
        {

            return;
        }

        //if the merchant is tagged, we need check the permission
        $app = App::getFacadeRoot();

        if (isset($admin) === false)
        {
            $admin = $app['basicauth']->getAdmin();
        }

        $adminPermissions = $admin->getPermissionsList();

        if (in_array(Permission::MERCHANT_RISK_CONSTRUCTIVE_ACTION, $adminPermissions, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Merchant is tagged by risk team hence constructive action can be performed on this only by risk team');
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsPurePlatformPartner(Entity $merchant)
    {
        // Block non partners and non pure-platforms
        if ($merchant->isPurePlatformPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * Submerchant creation without providing an email explicitly is only allowed if
     * 1. The partner is of type fully-managed
     * 2. The partner is of type aggregator and has the feature allowing optional emails
     * 3. The merchant is not a partner but has aggregator feature (for backward compatibility)
     *
     * Submerchant can be created with an existing email if
     * 1. it's a linked account creation request
     * 2. there is no linked account associated with the email ID associated with the same parent id
     *
     * @param  array $input
     * @param  bool  $linkedAccount
     * @param  Entity  $partner
     *
     * @throws Exception\BadRequestException
     */
    public function validateSubMerchantInput(array $input, bool $linkedAccount, Entity $partner)
    {
        if (empty($input['email']) === true)
        {
            $this->validateEmptyEmailFlow($input, $linkedAccount);
        }
        else if ($linkedAccount === true)
        {
            $this->validateExistingLinkedAccountWithRequestingMerchant($input[Entity::EMAIL]);
        }
        else
        {
            $this->validateInput('unique_email', array_only($input, Entity::EMAIL));
        }

        if ($linkedAccount === false)
        {
            $this->validateInput('edit_name', array_only($input, Entity::NAME));
        }
        else
        {
            $this->validateLinkedAccountCreation($linkedAccount, $partner);

            $isUrlValidationEnabled = (new Core())->isRazorxExperimentEnable(
                $partner->getId(),
                RazorxTreatment::URL_VALIDATION_FOR_LINKED_ACCOUNT_NAME
            );
            if ($isUrlValidationEnabled === true)
            {
                $this->validateLinkedAccountNameInput('linked_account_name', array_only($input, Entity::NAME));
            }
        }
    }

    public function validateLinkedAccountCreation(bool $linkedAccount, $merchant)
    {
        if (($linkedAccount === true) and
            (in_array($merchant->getCategory(),Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED[Entity::CATEGORY]) === true) and
            (in_array($merchant->getCategory2(), Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED[Entity::CATEGORY2]) ===true) and
            (in_array(app('worker.ctx')->getJobName(),[ self::AUTO_AMC_LINKED_ACCOUNT_CREATION_JOB, self::ADMIN_BATCH]) === false) and
            (app('basicauth')->isAdminAuth() === false))
        {
            App::getFacadeRoot()['trace']->info(TraceCode::AMC_LINKED_ACCOUNT_CREATION_JOB, [
                'job_name' => app('worker.ctx')->getJobName()
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_NOT_ALLOWED
            );
        }
    }

    public function validateLinkedAccountUpdation(Entity $merchant)
    {
        if ((in_array($merchant->getCategory(),Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED[Entity::CATEGORY]) === true) and
            (in_array($merchant->getCategory2(), Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED[Entity::CATEGORY2]) ===true) and
            (app('basicauth')->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_UPDATION_NOT_ALLOWED
            );
        }
    }

    /**
     * @param $operation
     * @param $input
     * @throws BadRequestValidationFailureException
     */
    public function validateLinkedAccountNameInput(string $operation,array $input)
    {
        try
        {
            $this->validateInput($operation, $input);
        }
        catch (BadRequestValidationFailureException $e)
        {
            if ($e->getMessage() === 'validation.not_regex')
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_URL_NOT_ALLOWED_IN_LINKED_ACCOUNT_NAME
                );
            }
            throw $e;
        }
    }

    /**
     * Check that if merchant entities exist for an email.
     * If they do, check that none of them is a linked account with parent ID same as the MID
     * of the requesting merchant.
     * @param string $email
     * @throws Exception\BadRequestException
     */
    protected function validateExistingLinkedAccountWithRequestingMerchant(string $email)
    {
        $requestingMerchant     = $this->entity;
        $app                    = App::getFacadeRoot();
        $merchantRepo           = app('repo')->merchant;
        $merchantsForEmail      = $merchantRepo->fetchByEmailAndOrgId($email, $requestingMerchant->getOrgId());

        if($merchantsForEmail->count() > 0)
        {
            $isFeatureEnabled = $requestingMerchant->isFeatureEnabled(
                Feature\Constants::DISALLOW_LINKED_ACCOUNT_WITH_DUPLICATE_EMAILS
            );

            if($isFeatureEnabled === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_WITH_DUPLICATE_EMAIL_NOT_ENABLED
                );
            }

            foreach ($merchantsForEmail as $merchant)
            {
                if ($merchant->isLinkedAccount() === true)
                {
                    if($merchant->getParentId() === $requestingMerchant->getId())
                    {
                        $app['trace']->info(
                            TraceCode::LINKED_ACCOUNT_CREATE_FAILURE, [
                                "errorCode" => ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                                "email" => $email,
                            ]
                        );

                        $description = PublicErrorDescription::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS . $merchant->getParentId();

                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS,
                            Entity::EMAIL,
                            ["merchant_id" => $merchant->getId(), "parent_id" => $merchant->getParentId()],
                            $description
                        );
                    }
                }
            }
        }
    }

    /**
     * @param string $emailId
     * @param string $orgId
     * @throws BadRequestValidationFailureException
     */
    public function validateUniqueEmailExceptLinkedAccount(string $emailId, string $orgId)
    {
        $merchantRepo = app('repo')->merchant;
        $merchantsForEmail = $merchantRepo->fetchByEmailAndOrgId($emailId, $orgId);

        if($merchantsForEmail->count() > 0)
        {
            foreach ($merchantsForEmail as $merchant)
            {
                if ($merchant->isLinkedAccount() === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        PublicErrorDescription::BAD_REQUEST_EMAIL_ALREADY_EXISTS,
                        Entity::EMAIL,
                        ["merchant_id" => $merchant->getId()]
                    );
                }
            }
        }
    }

    protected function validateEmptyEmailFlow(array $input, bool $linkedAccount)
    {
        /** @var Entity $merchant */
        $merchant = $this->entity;

        //
        // Allow empty email if
        // 1. Marketplace is making linked account create request
        // 2. Else, a. Is a partner that is allowed optional email
        //          b. Is not a partner but has aggregator feature.
        //
        if (($merchant->isMarketplace() and $linkedAccount) === true)
        {
            return;
        }

        if ($merchant->isPartner() === true)
        {
            if (($merchant->isFullyManagedPartner() === false) and
                ($merchant->isOptionalEmailAllowedAggregator() === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED);
            }
        }
        else
        {
            if ($merchant->hasAggregatorFeature() === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED);
            }
        }
    }

    protected function validateCsvEmail($input)
    {
        if (empty($input[Entity::TRANSACTION_REPORT_EMAIL]) === true)
        {
            return;
        }

        $emails = $input[Entity::TRANSACTION_REPORT_EMAIL];

        foreach ($emails as $email)
        {
            $email = trim($email); // Remove whitespace
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided transaction report email is invalid: $email",
                    Entity::TRANSACTION_REPORT_EMAIL
                );
            }
        }
    }

    public function validateBeforeActivate()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $detailValidator->validateActivationFormSubmitted();

        $this->validateIsNotActivated($merchant);

        $detailValidator->validateIsNotArchived();

        // Don't validate these rest of the attributes for Marketplace accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $this->validateActivationMandatoryAttributes();
    }

    /**
     * @param array $attributes
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateMandatoryAttributes(array $attributes)
    {
        $merchant = $this->entity;

        $this->validateWebsitesPresentIfApplicable($merchant);

        foreach ($attributes as $attribute)
        {
            $value = $merchant->getAttribute($attribute);

            if (empty($value) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Please set value for attribute: ' . $attribute);
            }
        }
    }

    protected function validateWebsitesPresentIfApplicable($merchant)
    {
        /*
        We are checking two conditions here -

        1.  If the merchant has access to keys he should have either of the playstore/website/app store url.
        2.  If the business website is there in the merchant detail table then the website should also be present in the merchant table

        => getAttribute - fetches website from the merchant table
        => we need not check 2nd condition above in case of the appstore and playstore url as right now in the current scenerio we only store
        app store and play store url in the business details table

        Future scope - We need to update the business website of the merchant in the merchant detail table with the playstore url or app store url
        if the merchant has only submitted either one of them (and we will put check later on to check that the business website of the merchant should
        be set if the playstore or the app store url is present)

        */

        if (($merchant->getHasKeyAccess() === true))
        {
            if (((new Detail\Core()))->hasWebsite($merchant) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Website details are missing');
            }

            // fetching website from the merchant details table

            $website = $merchant->merchantDetail->getWebsite();

            if ((empty($website) === false) and
                (empty($merchant->getAttribute(Entity::WEBSITE)) === true))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Website attributes are missing');
            }
        }
    }

    public function validateActivationMandatoryAttributes()
    {
        $attributes = Constants::ACTIVATION_MANDATORY_FIELDS;

        $this->validateMandatoryAttributes($attributes);
    }

    public function validateInstantActivationMandatoryAttributes()
    {
        $attributes = Constants::INSTANT_ACTIVATION_MANDATORY_FIELDS;

        $this->validateMandatoryAttributes($attributes);
    }

    public function validateBeforeInstantlyActivate()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $this->validateIsNotActivated($merchant);

        $detailValidator->validateIsNotArchived();

        // LA's should directly be activated. They should not go through the instant activations flow
        if ($merchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_INSTANTLY_ACTIVATED,
                Entity::PARENT_ID,
                [
                    Entity::PARENT_ID => $merchant->getParentId(),
                ]);
        }

        $this->validateInstantActivationMandatoryAttributes();
    }

    /**
     * Ensures that the merchant feature requested is a visible feature and an editable feature.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateVisibleAndEditableFeatures(array $input)
    {
        $featureNames = array_keys($input['features']);

        $visibleFeatures = array_keys(Feature\Constants::$visibleFeaturesMap);
        $editableFeature = Feature\Constants::$merchantEditableFeatures;
        $featureDependency = array_keys(Feature\Constants::$featureDependencyMap);

        foreach ($featureNames as $feature)
        {
            // Feature must be a "visible feature" and editable by the merchant
            if ((in_array($feature, $visibleFeatures, true) === false) or
                (in_array($feature, $editableFeature, true) === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                    'feature',
                    [$feature]);
            }
            // Only Merchant who have feature ES_ON_DEMAND enabled can change ES features
            else if (($input['es_enabled'] === false) and
                    ($feature === Feature\Constants::ES_AUTOMATIC))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                    'feature',
                    [$feature]);
            }
            else
            {
                if (in_array($feature, $featureDependency, true) == true)
                {
                    $requiredFeatures = Feature\Constants::$featureDependencyMap[$feature];
                    foreach ($requiredFeatures as $requiredFeature)
                    {
                        if ($this->entity->isFeatureEnabled($requiredFeature) === false)
                        {
                            throw new Exception\BadRequestException(
                                ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                                'feature',
                                [$feature]);
                        }
                    }
                }
            }
        }
    }

    protected function validateAutoRefundDelay($attribute, $autoRefundDelayPeriod)
    {
        if ($autoRefundDelayPeriod === null)
        {
            return;
        }

        $autoRefundDelay = explode(' ', $autoRefundDelayPeriod);

        $min = $max = null;
        $time = $autoRefundDelay[0];
        $duration = $autoRefundDelay[1];

        if (filter_var($time, FILTER_VALIDATE_INT) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Auto refund delay time period should be an integer', $attribute, $time);
        }

        switch ($duration)
        {
            case 'mins':
                $min = (int) (Entity::MIN_AUTO_REFUND_DELAY / 60);
                $max = (int) (Entity::MAX_AUTO_REFUND_DELAY / 60);
                break;

            case 'hours':
                $min = (int) (ceil(Entity::MIN_AUTO_REFUND_DELAY / 3600));
                $max = (int) (ceil(Entity::MAX_AUTO_REFUND_DELAY / 3600));
                break;

            case 'days':
                $min = (int) (ceil(Entity::MIN_AUTO_REFUND_DELAY / 86400));
                $max = (int) (ceil(Entity::MAX_AUTO_REFUND_DELAY / 86400));
                break;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Auto refund delay should be in mins, hours or days', $attribute, $duration);
        }

        if (($time < $min) or ($time > $max))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Auto refund delay should be between ' . $min . ' and ' . $max . ' ' . $duration);
        }
    }

    protected function validateMerchantDetailExists($merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS);
        }
    }

    public function validateBatchAction($attribute, $BatchAction)
    {
        if (BatchAction::exists($BatchAction) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_ACTION_NOT_SUPPORTED);
        }
    }

    public function validateEntity($attribute, $BatchActionEntity)
    {
        if (BatchActionEntity::exists($BatchActionEntity) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_ACTION_ENTITY_NOT_SUPPORTED);
        }
    }

    public function validateAction($attribute, $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED);
        }

        $validator = 'validate' .studly_case($action);

        if (method_exists($this, $validator))
        {
            $this->$validator();
        }
    }

    protected function validateArchive()
    {
        $merchant = $this->entity;

        if ($merchant->isArchived() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED);
        }

        $this->validateMerchantDetailExists($merchant);
    }

    protected function validateUnarchive()
    {
        $merchant = $this->entity;

        if ($merchant->isArchived() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ARCHIVED);
        }
    }

    public function validateSuspend()
    {
        $merchant = $this->entity;

        if ($merchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED);
        }
    }

    public function validateUnsuspend()
    {
        $merchant = $this->entity;

        if ($merchant->isSuspended() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_SUSPENDED);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateLiveDisable()
    {
        $merchant = $this->entity;

        $this->validateIsActivated($merchant);
        $this->validateSuspend();

        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateLiveEnable()
    {
        $merchant = $this->entity;

        $this->validateIsActivated($merchant);
        $this->validateSuspend();

        if ($merchant->isLive() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_LIVE);
        }
    }

    protected function validateSetReceiptEmailEventAuthorized()
    {
        $merchant = $this->entity;

        if ($merchant->getReceiptEmailTriggerEvent() === Event::AUTHORIZED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_TRIGGER_EVENT_ALREADY_AUTHORISED);
        }
    }

    protected function validateSetReceiptEmailEventCaptured()
    {
        $merchant = $this->entity;

        if ($merchant->getReceiptEmailTriggerEvent() === Event::CAPTURED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_TRIGGER_EVENT_ALREADY_CAPTURED);
        }
    }

    public function validateHoldFunds()
    {
        $merchant = $this->entity;

        if ($merchant->getHoldFunds() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ALREADY_ON_HOLD);
        }
    }

    public function validateReleaseFunds()
    {
        $merchant = $this->entity;

        if ($merchant->getHoldFunds() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ALREADY_RELEASED);
        }

        $this->validateHasBankAccount();
    }

    protected function validateEnableReceiptEmails()
    {
        $merchant = $this->entity;

        if ($merchant->isReceiptEmailsEnabled() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECEIPT_EMAILS_ALREADY_ENABLED);
        }
    }

    protected function validateDisableReceiptEmails()
    {
        $merchant = $this->entity;

        if ($merchant->isReceiptEmailsEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECEIPT_EMAILS_ALREADY_DISABLED);
        }
    }

    public function validateEnableInternational()
    {
        $merchant = $this->entity;

        $productInternational = new ProductInternational\ProductInternationalField($merchant);

        if ($merchant->isInternational() === true and
            $merchant->getProductInternational() === $productInternational->getEnabledValueForLiveProducts())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_ENABLED);
        }

        $plan = app('repo')->pricing->getPricingPlanByIdWithoutOrgId($merchant->getPricingPlanId());

        if ($plan->hasInternationalPricing() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Pricing not present for international.');
        }

        //to check eligibilty of merchant to be internationally enabled
        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);
        (new Core)->shouldActivateProductInternational($merchant, $merchantDetails);
    }

    public function validateEnableProductInternational($internationalProducts)
    {
        if (empty($internationalProducts) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRODUCT_INTERNATIONAL_REQUIRED,
                null,
                ['products' => $internationalProducts]);
        }
    }

    public function validateDisableInternational()
    {
        $merchant = $this->entity;

        $productInternational = new ProductInternationalField($merchant);

        if ($merchant->isInternational() === false and
        $merchant->getProductInternational() === $productInternational->getDisabledValueForLiveProducts())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED);
        }
    }

    /**
     * Throw an error if the merchant is already a partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIfAlreadyPartner(Entity $merchant)
    {
        if ($merchant->isPartner() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_IS_ALREADY_PARTNER);
        }
    }

    /**
     * Throw an error if the merchant is not a partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsPartner(Entity $merchant)
    {
        if ($merchant->isPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * Validates merchant is partner.
     * Throws an error if the merchant is not a partner or if merchant is a reseller partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotResellerPartner(Entity $merchant)
    {
        $this->validateIsPartner($merchant);

        if ($merchant->isResellerPartner() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_NOT_ALLOWED_FOR_RESELLER,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]
            );
        }
    }

    /**
     * Validates that the merchant is a partner and an aggregator
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsAggregatorPartner(Entity $merchant)
    {
        $this->validateIsPartner($merchant);

        if ($merchant->isAggregatorPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]
            );
        }
    }

    /**
     * Validates that the merchant is a partner and can manage sub merchant config
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerCanManageSubMerchantConfig(Entity $merchant)
    {
        $this->validateIsPartner($merchant);

        $allowedPartnerTypes = [
            Constants::AGGREGATOR
        ];

        if (in_array($merchant->getPartnerType(),$allowedPartnerTypes,true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]
            );
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotLinkedAccount(Entity $merchant)
    {
        if ($merchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_PARTNER);

        }
    }

    public function validatePartnerIsNotSubmerchant(Entity $partner, Entity $submerchant)
    {
        if ($submerchant->getId() === $partner->getId())
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_CANNOT_BE_SUBMERCHANT_TO_ITSELF);
        }
    }

    public function validatePartnerType(string $partnerType)
    {
        if (in_array($partnerType, Constants::$partnerTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $partnerType,
                ]);
        }
    }

    public function validateBankCaPartnerType(string $partnerType)
    {
        if ($partnerType !== Constants::BANK_CA_ONBOARDING_PARTNER)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $partnerType,
                ]);
        }
    }

    public function validateTags(string $attribute, string $tags)
    {
        $tagArray = explode(",", $tags);

        foreach ($tagArray as $tag)
        {
            $parsedTag = str_replace(' ','',$tag);

            if(in_array($parsedTag, Constants::$capitalMerchantTags) === false)
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid tag new'.$parsedTag
                );
        }
    }

    public function validateLinkedAccount(Entity $merchant)
    {
        if ($merchant->isLinkedAccount() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_IS_NOT_LINKED_ACCOUNT);
        }
    }

    /**
     * Validate if the input application is
     *
     * @param string $inputAppId
     * @param array  $partnerAppIds
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerApplicationId(string $inputAppId, array $partnerAppIds)
    {
        if (in_array($inputAppId, $partnerAppIds, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_APPLICATION_ID,
                Constants::APPLICATION_ID,
                [
                    'partner_app_ids'         => $partnerAppIds,
                    Constants::APPLICATION_ID => $inputAppId,
                ]);
        }
    }

    public function validateLinkedAccountDashboardAccess(bool $dashboardAccess, Entity $merchant)
    {
        $merchantUsersCount = $merchant->users()->count();

        if ($dashboardAccess === true and $merchantUsersCount > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_DASHBOARD_ACCESS_ALREADY_GIVEN);
        }

        if ($dashboardAccess === false and $merchantUsersCount === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS);
        }
    }

    public function validateUserIsOwnerForMerchant($userId, $merchantId)
    {
        $userMapping = app('repo')->merchant->getMerchantUserMapping($merchantId, $userId);

        $userRoleForMerchant = $userMapping->pivot->role;

        if ($userRoleForMerchant !== Role::OWNER)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
    }

    public function validateLinkedAccountReversals(bool $allowReversals, Entity $merchant)
    {
        $merchantUsersCount = $merchant->users()->count();

        $canReverse = $merchant->isFeatureEnabled(Feature\Constants::ALLOW_REVERSALS_FROM_LA);

        if ($merchantUsersCount === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS);
        }

        if (($allowReversals === true) and ($canReverse === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_GIVEN);
        }

        if (($allowReversals === false) and ($canReverse === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_REMOVED);
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED,
                Entity::ACTIVATED);
        }
    }

    public function validateBeforeKycVerified()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $detailValidator->validateActivationFormSubmitted();

        $this->validateIsActivated($merchant);

        $detailValidator->validateIsNotArchived();
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
                Entity::ACTIVATED);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateHasBankAccount()
    {
        $merchant = $this->entity;

        $bankAccount = $merchant->bankAccount;

        $merchantDetails = $merchant->merchantDetail;

        if ($bankAccount === null)
        {
            if (empty($merchantDetails) === false)
            {
                $bankAccountStatus = $merchantDetails->getBankDetailsVerificationStatus();

                if ($bankAccountStatus === Detail\Constants::VERIFIED)
                {
                    return;
                }
            }
            // check partner bank account exists
            $partner = (new Core)->getSettledToPartnersTypeOfMerchantIfExists($merchant);

            $partnerbankAccountExits = (new Core)->isValidBankAccountForSettledToPartner($merchant, $partner);

            if ($partnerbankAccountExits === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
            }
        }
    }

    public function validateNowIsWorkingHour()
    {
        if (is_rzp_business_hour() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Now is not a working hour. Please try this request on Mon-Fri between 9 AM - 6 PM.');
        }
    }

    public function validateBusinessBankingActivated()
    {
        $merchant = $this->entity;

        if ($merchant->isBusinessBankingEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
                null,
                [
                    'merchant_id'      => $merchant->getId(),
                    'merchant_name'    => $merchant->getName(),
                    'business_banking' => $merchant->isBusinessBankingEnabled()
                ]);
        }
    }

    public function validateAndTranslateToAccountNumberForBankingIfApplicable(array & $input)
    {
        $product       = array_get($input, Entity::PRODUCT);
        $accountNumber = array_get($input, Balance\Entity::ACCOUNT_NUMBER);

        if ((empty($product) === true) or
            (empty($accountNumber) === false))
        {
            $this->validateAndTranslateAccountNumberForBanking($input);
        }
    }

    /**
     * There are service methods (list & fetch) for few models which expect
     * mandatory ACCOUNT_NUMBER in query parameter. Such models include
     * transactions, bank_transfers & payouts. This method is called from those
     * service methods to translate ACCOUNT_NUMBER to BALANCE_ID because beyond
     * service layer repository's fetch etc only understands BALANCE_ID.
     *
     * @param array $input
     *
     * @return Balance\Entity
     * @throws Exception\BadRequestException
     */
    public function validateAndTranslateAccountNumberForBanking(array & $input) : Balance\Entity
    {
        $this->validateBusinessBankingActivated();

        // Validates input has valid ACCOUNT_NUMBER.
        (new Base\JitValidator)
            ->rules([Balance\Entity::ACCOUNT_NUMBER => 'required|alpha_num|between:5,22'])
            ->strict(false)
            ->input($input)
            ->validate();

        // Replaces ACCOUNT_NUMBER with corresponding BALANCE_ID.
        $accountNumber = array_pull($input, Balance\Entity::ACCOUNT_NUMBER);

        $merchantId = array_pull($input, Balance\Entity::MERCHANT_ID);

        try
        {
            /** @var Balance\Entity $balance */
            $balance = (new Balance\Repository)->getBalanceByAccountNumberOrFail($accountNumber, $merchantId);

            $input[Balance\Entity::BALANCE_ID] = $balance->getId();

            return $balance;
        }
        catch (\Throwable $ex)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RAZORPAYX_ACCOUNT_NUMBER_IS_INVALID,
                Balance\Entity::ACCOUNT_NUMBER,
                [
                    'account_number'   => $accountNumber
                ]);
        }
    }

    /**
     * @param  Admin\Entity    $admin
     * @param  Entity          $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateAdminMerchantAccess(Admin\Entity $admin, Entity $merchant)
    {
        if (($admin->canSeeAllMerchants() === true) and ($admin->getOrgId() === $merchant->getOrgId()))
        {
            return;
        }

        if (in_array($merchant->getId(), $admin->merchants()->get()->getIds(), true) === true)
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
    }

    public function validateBatchId($batchId)
    {
        if (empty($batchId) === true)
        {
            throw new BadRequestValidationFailureException('Batch Id not present');
        }
    }

    /**
     * @param array $input
     * Rate limit on number of submerchant terminal assign in Bulk Route
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkSubmerchantAssignCount(array $input)
    {
        if (count($input) > self::MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Current batch size ' . count($input) . ', max limit of ' . self::BULK_SUBMERCHANT_ASSIGN . ' is ' . self::MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT,
                null,
                null
            );
        }
    }

    public function validatePartnerTypeForUpdate($attribute, $value)
    {
        $allowedPartnerTypes = [
            Constants::RESELLER,
            Constants::AGGREGATOR,
            Constants::PURE_PLATFORM,
        ];

        if (in_array($value, $allowedPartnerTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                Entity::PARTNER_TYPE,
                [$attribute => $value]);
        }
    }

    public function validateBeforeEnablingInternationalByMerchant($merchant)
    {
        if ($merchant->isInternational() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_INTERNATIONAL);
        }

        $this->validateWebsite($merchant);

        $merchantDetails = $merchant->merchantDetail;

        $internationalActivationFlow = $merchantDetails->getInternationalActivationFlow();

        //
        // @todo We need to remove this check once we implement feature request based international activation process
        //
        if (($internationalActivationFlow !== Detail\ActivationFlow::WHITELIST)
            and ($internationalActivationFlow !== Detail\ActivationFlow::GREYLIST))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_INTERNATIONAL_STATUS_CHANGE_REQUEST,
                Detail\Entity::INTERNATIONAL_ACTIVATION_FLOW,
                [
                    Detail\Entity::INTERNATIONAL_ACTIVATION_FLOW => $internationalActivationFlow
                ]
            );
        }
    }

    /**
     * Validates that merchant has a website
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateWebsite(Entity $merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        // Since website is not synced between merchant and merchant_detail,
        // therefore checking for both
        if ((empty($merchant->getWebsite()) === true) and
            (empty($merchantDetails->getWebsite()) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET);
        }
    }

    /**
     * Validates if the productInternational field has values updated for positions which aren't live
     * @param $attribute
     * @param $productInternational
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateProductInternational($attribute, $productInternational)
    {
        $liveProductPos = array_values(ProductInternationalMapper::PRODUCT_POSITION);

        $maxIndexPopulated = max($liveProductPos);

        for ($i = $maxIndexPopulated + 1; $i < (strlen($productInternational) - 1); $i += 1)
        {
            if ($productInternational[$i] !== '0')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provided value is not valid');
            }
        }
    }

    public function validateMerchantWorkflowType($type)
    {
        if (array_key_exists($type, Constants::MERCHANT_WORKFLOWS) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_TYPE);
        }
    }

    public function validateCode($key, $value)
    {
        $this->validateInput('code', [$key => $value]);
    }

    /**
     * Validates if the product name is either banking or primary
     *
     * @param $product
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateMerchantProduct($product)
    {
        $validProducts = [Product::PRIMARY, Product::BANKING];

        if ((empty($product) === false) and (in_array($product, $validProducts, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME);
        }
    }

    public function validateRangeForFailureAnalysis(array $input)
    {
        // max range of query can be 91 days (7862400 seconds)
        if (($input['to'] < $input['from']) or
            (($input['to'] - $input['from']) >= 7862400))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The date range is invalid', null, null );
        }
    }

    /**
     * Validates if only risk attributes
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateRiskAttributes(array $input)
    {
        if(empty($input) === true)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_MERCHANT_RISK_ATTRIBUTES_REQUIRED);
        }

        foreach(array_keys($input) as $riskAttribute)
        {
            if (in_array($riskAttribute, self::MERCHANT_RISK_ATTRIBUTES) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_MERCHANT_INVALID_RISK_ATTRIBUTE,
                    $riskAttribute);
            }
        }
    }

    /**
     * Validates if risk attributes has diff
     *
     * @param Entity $merchant
     * @param Entity $newMerchant
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateRiskAttributesHasDiff(Entity $merchant, Entity $newMerchant)
    {
        $diff = (new Differ\Core)->createDiff($merchant->toArray(), $newMerchant->toArray());

        if (empty($diff) === true)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_MERCHANT_NO_DIFF_IN_RISK_ATTRIBUTES);
        }
    }

    public function validateToggleFeeBearer($attribute, $feeBearer)
    {
        $merchant = $this->entity;

        $merchantFeeBearer = $merchant->getFeeBearer();

        if ($merchantFeeBearer == $feeBearer)
        {
            throw new Exception\BadRequestValidationFailureException('The new fee bearer is same as the previous fee bearer');
        }
    }

    protected function getMaxTransactionTypePaymentAmount(array $input, Entity $merchant)
    {
        $transactionType = $this->validateIncreaseTransactionLimitType($input);
        $isTypeInternational = $transactionType === "international";
        return $merchant->getMaxPaymentAmountTransactionType($isTypeInternational);
    }

    protected function validateIncreaseTransactionLimitType(array $input)
    {
        if (isset($input[Constants::TRANSACTION_TYPE]) === true){
            return $input[Constants::TRANSACTION_TYPE];
        }
        return Constants::TRANSACTION_TYPE_DOMESTIC;
    }

    protected function validateIncreaseTransactionLimitWorkflow(string $transactionType)
    {
        if ($transactionType === "international"){
            return Constants::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT;

        }else{
            return Constants::INCREASE_TRANSACTION_LIMIT;
        }
    }

    public function validateIncreaseTransactionLimitConditions(Entity $merchant, array $input, bool $isBusinessRegistered, bool $isMerchantKamOrDirectSales = false)
    {

        $transactionType = $this->validateIncreaseTransactionLimitType($input);
        $increaseTransactionLimitWorkflowType = $this->validateIncreaseTransactionLimitWorkflow($transactionType);
        $oldLimit = $this->getMaxTransactionTypePaymentAmount($input, $merchant);

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $businessCategory = $merchantDetails->getBusinessCategory();

        $this->validateIsActivated($merchant);

        $this->validateInput('transaction_limit_self_serve', $input);

        $this->validateTransactionLimitNotSameAsCurrent($input, $oldLimit);

        $this->validateRequestNotRaisedInLastThirtyDays($merchant, $increaseTransactionLimitWorkflowType);

        if ($isMerchantKamOrDirectSales === false)
        {
            $this->validateNotExceedingMaximumLimit($input, $isBusinessRegistered, $businessCategory, $oldLimit);

            $this->validateNotUnregiesteredGamingOrGovernmentBusinessCategory($businessCategory, $isBusinessRegistered);

            $this->validateCtsOrFtsLessThanFive($merchant);
        }
    }

    protected function validateTransactionLimitNotSameAsCurrent(array $input, int $oldLimit)
    {
        if($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT] == $oldLimit)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The new transaction limit is same as the current transaction limit'
            );
        }
    }

    protected function validateRequestNotRaisedInLastThirtyDays(Entity $merchant, string $workflowType)
    {
        [$entityId, $entity] = (new Core())->fetchWorkflowData($workflowType, $merchant);

        $action = (new ActionCore)->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]]
        );

        if (empty($action) === false)
        {
            $updatedTime = $action->getAttribute(ActionEntity::UPDATED_AT);

            $currentTime = time();

            $checkTime = strtotime('+30 days', $updatedTime);

            if ($currentTime < $checkTime)
            {
                $updatedDate = date('d-M-Y', $updatedTime);

                $checkDate = date('d-M-Y', $checkTime);

                $description = 'Our partner banks have already evaluated your profile for transaction limit updation on ' . $updatedDate . ', please wait till ' . $checkDate . ' to send another request to our partner banks';

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_EDIT_TRANSACTION_LIMIT_REQUEST_MADE_IN_LAST_30_DAYS,
                    null,
                    [
                        'updatedDate' => $updatedDate,
                        'checkDate' => $checkDate
                    ],
                    $description
                );
            }
        }
    }

    protected function validateNotExceedingMaximumLimit(array $input, bool $isBusinessRegistered, $businessCategory, int $oldLimit)
    {

        if ($isBusinessRegistered === true)
        {
            if(array_key_exists($businessCategory,  Constants::registeredMerchantMaximumTransactionLimit) === true)
            {
                $maxCategoryLimit = Constants::registeredMerchantMaximumTransactionLimit[$businessCategory];
            }
            else
            {
                $maxCategoryLimit = Constants::registeredMerchantMaximumTransactionLimit[Detail\BusinessCategory::OTHERS];
            }
        }
        else
        {
            if(array_key_exists($businessCategory,  Constants::unregisteredMerchantMaximumTransactionLimit) === true)
            {
                $maxCategoryLimit = Constants::unregisteredMerchantMaximumTransactionLimit[$businessCategory];
            }
            else
            {
                $maxCategoryLimit = Constants::unregisteredMerchantMaximumTransactionLimit[Detail\BusinessCategory::OTHERS];
            }
        }

        //If a merchant already has maximum category transaction limit set and then if they try to further increase their transaction limit
        //then this message has to be displayed
        //“Your transaction limit cannot be increased any further, as per the guidelines set by our partner banks”
        $this->checkIfEqualToMaximumLimit($oldLimit, $maxCategoryLimit, $input);

        //If the old value set is less than the maximum category transaction limit and tries to increase their transaction limit more than the maximum category transaction limit
        $this->checkIfGreaterThanMaximumLimit($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT], $maxCategoryLimit);
    }

    protected function checkIfEqualToMaximumLimit(int $oldLimit, int $maxCategoryLimit, array $input)
    {
        if (($oldLimit === $maxCategoryLimit) and
            ($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT] > $maxCategoryLimit))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Your transaction limit cannot be increased any further, as per the guidelines set by our partner banks'
            );
        }
    }

    protected function checkIfGreaterThanMaximumLimit(int $newLimit, int $maxCategoryLimit)
    {
        if ($newLimit > $maxCategoryLimit)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Enter lower Transaction Limit value'
            );
        }
    }

    protected function validateNotUnregiesteredGamingOrGovernmentBusinessCategory($businessCategory, bool $isBusinessRegistered)
    {
        if (($isBusinessRegistered === false) and
            (($businessCategory === Detail\BusinessCategory::GAMING) or
            ($businessCategory === Detail\BusinessCategory::GOVERNMENT)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED
            );
        }
    }

    protected function validateCtsOrFtsLessThanFive(Entity $merchant)
    {
        $merchantId = $merchant->getMerchantId();

        $resultArray =(new Core())->getMerchantRiskData($merchantId);

        if (empty($resultArray['domestic_merchant_chargeback_to_sale_ratio_(%)'][0]['lifetime']) === true)
        {
            $this->getTrace()->info(TraceCode::TRANSACTION_LIMIT_CTS_RATIO_NOT_FOUND, [
                Constants::MERCHANT_ID => $merchantId
            ]);
        }

        if (empty($resultArray['domestic_merchant_fraud_to_sale_ratio_(%)'][0]['lifetime']) === true)
        {
            $this->getTrace()->info(TraceCode::TRANSACTION_LIMIT_FTS_RATIO_NOT_FOUND, [
                Constants::MERCHANT_ID => $merchantId
            ]);
        }

        if (((empty($resultArray['domestic_merchant_chargeback_to_sale_ratio_(%)'][0]['lifetime']) === false) and
             ($resultArray['domestic_merchant_chargeback_to_sale_ratio_(%)'][0]['lifetime'] > 5.0)) or
            ((empty($resultArray['domestic_merchant_fraud_to_sale_ratio_(%)'][0]['lifetime']) === false) and
             ($resultArray['domestic_merchant_fraud_to_sale_ratio_(%)'][0]['lifetime'] > 5.0)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EDIT_TRANSACTION_LIMIT_CTS_OR_FTS_MORE_THAN_5
            );
        }
    }

    protected function validateCountry($attribute, $value)
    {
        $isValid = Country::checkIfValidCountry($value);

        if ($isValid === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUNTRY, null, [$value]);
        }
    }

    private function validateOrderIdForPayment($payment, $order, $paymentInput)
    {
        $app = App::getFacadeRoot();

        if($payment->order->getPublicId() !== $order->getPublicId())
        {
            $app['trace']->info(TraceCode::BAD_REQUEST_INVALID_ORDER_ID_IN_PAYMENT, [
                "payment_order_id" => $paymentInput['order_id'],
                "order_id" => $order->getId()
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ORDER_ID_IN_PAYMENT,
                null,
                [
                    "payment_order_id" => $paymentInput['order_id'],
                    "order_id" => $order->getId()
                ]
            );
        }
    }

    private function validatePaymentDetailsForFundAdditionInput($paymentInput, $payment)
    {
        $app = App::getFacadeRoot();

        if($paymentInput['amount'] !== $payment->getAmount() or
            $paymentInput['fee'] !== $payment->getFee() or
            $payment->getStatus() !== Payment\Status::CAPTURED
        )
        {
            $app['trace']->info(TraceCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED, [
                "input_fee" => $paymentInput['fee'],
                "input_amount" => $paymentInput['amount'],
                "payment_id" => $payment->getPublicId()
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED
            );
        }
    }

    private function validatePaymentDetailsForFundAdditionOrder($order, $paymentInput, $merchant)
    {
        $app = App::getFacadeRoot();

        $payment = $app['repo']->payment->findByPublicIdAndMerchant($paymentInput['id'], $merchant);

        $this->validateOrderIdForPayment($payment, $order, $paymentInput);

        $this->validatePaymentDetailsForFundAdditionInput($paymentInput, $payment);
    }

    private function validateIfOrderStatusIsPaid($order)
    {
        $app = App::getFacadeRoot();

        if($order->getStatus() !== Status::PAID)
        {
            $app['trace']->info(TraceCode::ORDER_STATUS_INVALID_FOR_FUND_ADDITION, [
                "order_id" => $order->getPublicId()
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_STATUS_INVALID_FOR_FUND_ADDITION,null,
                [
                    "order_id" => $order->getPublicId()
                ]
            );
        }
    }

    private function validateOrderAndPaymentDetails($orderInput, $paymentInput, $merchant)
    {
        $app = App::getFacadeRoot();

        $order = $app['repo']->order->findByPublicIdAndMerchant($orderInput['id'], $merchant);

        $this->validateIfOrderStatusIsPaid($order);

        $this->validatePaymentDetailsForFundAdditionOrder($order, $paymentInput, $merchant);
    }

    public function validateIfFundAlreadyAddedForGivenCampaign($campaignId, $merchantId)
    {
        $app = App::getFacadeRoot();

        $creditExists = (new FundCredits\Service)->fetchCreditsByCampaignId($campaignId, $merchantId);

        if($creditExists === true)
        {
            $app['trace']->info(TraceCode::CREDITS_ALREADY_ADDED_FOR_THE_GIVEN_CAMPAIGN, [
                "campaign" => $campaignId
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CREDITS_ALREADY_ADDED_FOR_THE_GIVEN_CAMPAIGN,
                null,
                ["campaign" => $campaignId]
            );
        }
    }

    public function validateIfAmountForFundAdditionIsValid($amount, $input, $merchantId)
    {
        $app = App::getFacadeRoot();

        if($amount <= 0)
        {
            $app['trace']->info(TraceCode::INVALID_AMOUNT_FOR_FUND_ADDITION, [
                "input" => $input,
                "merchant_id" => $merchantId
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_AMOUNT_FOR_FUND_ADDITION,
                null,
                [
                    "input" => $input,
                    "merchant_id" => $merchantId
                ]
            );
        }
    }

    public function validateInputDetailsForFundAdditionViaOrder($orderInput, $paymentInput)
    {
        $app = App::getFacadeRoot();

        if(isset($orderInput['notes']['merchant_id']) === false or
            isset($orderInput['notes']['type']) === false)
        {
            $app['trace']->info(TraceCode::MERCHANT_INFO_NOT_PRESENT_FOR_FUND_ADDITION);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_INFO_NOT_PRESENT_FOR_FUND_ADDITION);
        }

        $creditType = $orderInput['notes']['type'];

        $razorpayMerchant = (new core())->getRazorpayMerchantBasedOnType($creditType);

        $merchant =  $app['repo']->merchant->findOrFail($razorpayMerchant['merchant_id']);

        $this->validateOrderAndPaymentDetails($orderInput, $paymentInput, $merchant);

    }

    public function validatePaymentDetailsForBankTransfer($virtualAccountInput, $paymentInput, $merchant)
    {
        $app = App::getFacadeRoot();

        $payment = $app['repo']->payment->findByPublicIdAndMerchant($paymentInput['id'], $merchant);

        $this->validatePaymentDetailsForFundAdditionInput($paymentInput, $payment);

    }

    public function validateBankTransferEntityDetails($bankTransferInput, $virtualAccountInput, $paymentInput, $merchant)
    {
        $app = App::getFacadeRoot();

        $bankTransfer = $app['repo']->bank_transfer->findByPublicIdAndMerchant($bankTransferInput['id'], $merchant);

        if($bankTransfer->getPaymentId() !== PaymentEntity::stripDefaultSign($paymentInput['id']) or
            $bankTransfer->getVirtualAccountId() !==  VAEntity::stripDefaultSign($virtualAccountInput['id']))
        {
            $app['trace']->info(TraceCode::BANK_TRANSFER_DATA_TAMPERED_FOR_FUND_ADDITION, [
               "bank_transfer_id" => $bankTransfer->getId(),
               "input_payment_id" => $paymentInput['id'],
                "input_va_id"     => $virtualAccountInput['id']
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BANK_TRANSFER_INPUT_DATA_TAMPERED);

        }
    }

    public function validateInputDetailsForFundAdditionViaBankTransfer($bankTransferInput, $virtualAccountInput, $paymentInput)
    {
        $app = App::getFacadeRoot();

        if(isset($virtualAccountInput['notes']['merchant_id']) === false or
            isset($virtualAccountInput['notes']['type']) === false)
        {
            $app['trace']->info(TraceCode::MERCHANT_INFO_NOT_PRESENT_FOR_FUND_ADDITION);
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_INFO_NOT_PRESENT_FOR_FUND_ADDITION);
        }

        $creditType = $virtualAccountInput['notes']['type'];

        $razorpayMerchant = (new core())->getRazorpayMerchantBasedOnType($creditType);

        $merchant =  $app['repo']->merchant->findOrFail($razorpayMerchant['merchant_id']);

        $this->validateBankTransferEntityDetails($bankTransferInput, $virtualAccountInput, $paymentInput, $merchant);

        $this->validatePaymentDetailsForBankTransfer($virtualAccountInput, $paymentInput, $merchant);
    }

    public function validateIfReserveBalanceAlreadyAdded($description, $merchantId)
    {
        $app = App::getFacadeRoot();

        $adjustmentExists = (new Adjustment\Service)->fetchAdjustmentByDescription($description, $merchantId);

        if($adjustmentExists === true)
        {
            $app['trace']->info(TraceCode::RESERVE_BALANCE_ALREADY_ADDED_FOR_GIVEN_DESC, [
                "merchant_id" => $merchantId,
                "description" => $description
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_RESERVE_BALANCE_ALREADY_ADDED_FOR_GIVEN_DESC,
                null,["description" => $description]);
        }
    }

    /**
     * Validates if merchant IEC code is present for certain purpose codes and merchant banks
     * @param $purposeCode
     * @param $iecCode
     * @param $ifsc
     * @throws Exception\BadRequestException
     */
    public function validateIecCode($purposeCode, $iecCode, $ifsc)
    {

        if (BankCodes::isIecRequiredBank($ifsc) and in_array($purposeCode, PurposeCodeList::IEC_REQUIRED)
            and empty($iecCode))
        {
            $message = 'iec code required for given purpose code';

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, 'iec_code', [$purposeCode], $message);
        }

    }

    protected static $fetchMerchantsByParamsRules = [
        Detail\Entity::BUSINESS_CATEGORY        => 'sometimes|array',
        Detail\Entity::BUSINESS_SUBCATEGORY     => 'sometimes|string',
        Detail\Entity::BUSINESS_TYPE            => 'sometimes|string',
        Entity::WEBSITE                         => 'sometimes|string',
        Entity::CATEGORY2                       => 'sometimes|string',
        Entity::ORG_ID                          => 'sometimes|string',
        'merchant_ids'                          => 'sometimes|array',
    ];

    protected static $couponConfigDataRules = [
       'reference_id'        => 'required',
       'disabled_methods'    => 'sometimes|array',
       'visibility'          => 'sometimes|boolean',
    ];

    protected static $merchantCouponConfigRules = [
        'config'         => 'required|in:coupon_config',
        'merchant_id'    => 'required',
        'value_json'     => 'required|array',
    ];
    public function validateEnableNon3dsConditions(Entity $merchant)
    {

        $workflowType = Constants::ENABLE_NON_3DS_PROCESSING;

        [$entityId, $entity] = (new Core())->fetchWorkflowData($workflowType, $merchant);

        $action = (new ActionCore)->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]]
        );

        if (empty($action) === false) {
            $updatedTime = $action->getAttribute(ActionEntity::UPDATED_AT);

            $currentTime = time();

            $checkTime = strtotime('+30 days', $updatedTime);

            if ($currentTime < $checkTime) {
                $updatedDate = date('d-M-Y', $updatedTime);

                $checkDate = date('d-M-Y', $checkTime);

                $description = 'We have already evaluated your profile for enabling non-3ds card processing on ' . $updatedDate . ', please wait till ' . $checkDate . ' to send another request.';

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ENABLE_NON_3DS_REQUEST_MADE_IN_LAST_30_DAYS,
                    null,
                    [
                        'updatedDate' => $updatedDate,
                        'checkDate' => $checkDate
                    ],
                    $description
                );
            }
        }
    }

    /**
     * Validates applications count and types for aggregator partner
     *
     * @param PublicCollection $applications array of applications
     *
     * @return bool
     */
    public function validateAggregatorApplications(PublicCollection $applications) : bool
    {
        $appTypeDiff = array_diff(
            array_column($applications->toArray(), MerchantApplicationsEntity::TYPE),
            [MerchantApplicationsEntity::MANAGED, MerchantApplicationsEntity::REFERRED]
        );
        if(count($applications) != 2 or empty($appTypeDiff) == false )
        {
            $this->getTrace()->info(
                TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_INVALID_APPLICATION,
                [
                    '$applications' => $applications,
                ]
            );
            return false;
        }
        return true;
    }

    public function validateMerchantMarketplaceFeature(Entity $merchant)
    {
        if ($merchant->isMarketplace() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_NOT_MARKETPLACE_MERCHANT,
                null,
                [
                    'parent_mid'    =>  $merchant->getId(),
                ]
            );
        }
    }

    /**
     * If country does not have zipcodes, state and state code are mandatory
     * If country is india, zipcode is necessary
     */
    public function validateStateCode(array $address)
    {

        if (in_array(strtoupper($address['country']),ShippingInfoConstants::countryWithNoZipcodes) === true && (empty($address['state_code']) === true || empty($address['state']) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'State and State-code are required');
        }

        if (strtoupper($address['country']) === 'IN' &&  empty($address['zipcode']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'zipcode is required for India');
        }
    }

    protected static $sqlBinLogRules = [
        "database"              => 'required|string',
        "table"                 => "required|string",
        "type"                  => "required|string|in:update,insert,delete",
        "ts"                    => "required|integer",
        "xid"                   => "required|integer",
        "commit"                => "required|boolean",
        "position"              => "required|string",
        "primary_key_columns"   =>  "required|array",
        "data"                  => "required|array",
        "old"                   => "required|array"
    ];

    public function validateOrgDetails(Entity $merchant){

        ORG_ENTITY::isOrgCurlec($merchant->getOrgId()) &&  $this->validateIsActivated($merchant);

    }

    protected static $industryLevelQueryAggregationRules = [
        'details'                  => 'required|array',
        'details.index'            => 'required|string|in:cx_high_level_funnel',
        'details.group_by'         => 'required|array',
        'details.group_by.*'       => 'required|string|in:behav_submit_event,render_checkout_open_event,status,histogram_daily,histogram_hourly,histogram_weekly,histogram_monthly',
        'details.histogram_column' => 'created_at',
        'details.mode'             => 'required|string|in:test,live',
        'agg_type'                 => 'required|string|in:count',
        'filter_key'               => 'required|string|in:checkout_industry_level_sr,checkout_industry_level_cr',
    ];

    protected static $industryLevelQueryFilterRules = [
        'filters'                      => 'required|array|size:1',
        'filters.*.created_at'         => 'required|array',
        'filters.*.checkout_library'   => 'required|array',
        'filters.*.checkout_library.*' => 'string',
        'filters.*.merchant_category'  => 'required|string',
    ];

    public function validateIndustryLevelQuery(array $filters, array $aggregations): void
    {
        $this->validateinput('industry_level_query_filter', $filters);
        $this->validateinput('industry_level_query_aggregation', $aggregations);
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateCheckoutQueries(array $input): void
    {
        $aggregations = $input[AnalyticsConstants::AGGREGATIONS] ?? [];

        foreach ($aggregations as $aggregationName => $aggregation)
        {
            $filterKey = $aggregation[AnalyticsConstants::FILTER_KEY] ?? '';
            $groupBy = $aggregation[AnalyticsConstants::DETAILS][AnalyticsConstants::GROUP_BY] ?? [];

            if (in_array($aggregationName, AnalyticsConstants::CR_RELATED_AGGREGATION_NAMES))
            {
                $this->validateGroupByForCrQuery($groupBy);
            }

            if (in_array($aggregationName, AnalyticsConstants::SR_RELATED_AGGREGATION_NAMES))
            {
                $this->validateGroupByForSrQuery($groupBy);
            }

            if (in_array($aggregationName, AnalyticsConstants::ERROR_METRICS_RELATED_AGGREGATION_NAMES))
            {
                $this->validateGroupByForErrorMetricsQuery($groupBy);
            }

            if (in_array($aggregationName, AnalyticsConstants::INDUSTRY_LEVEL_QUERIES))
            {
                $filter = $input[Constants::FILTERS][$aggregationName] ?? [];

                $industryLevelFilters = [Constants::FILTERS => $filter];

                $this->validateIndustryLevelQuery($industryLevelFilters, $aggregation);
            }

            if (in_array($filterKey, AnalyticsConstants::INDUSTRY_LEVEL_QUERIES) and $filterKey !== $aggregationName)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'filter_key: ' . $filterKey . ' can only be used with ' . $filterKey . ' aggregation'
                );
            }
        }
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    private function validateGroupByForCrQuery(array $groupByFields): void
    {
        $missingStrings = array_diff(AnalyticsConstants::GROUP_BY_FIELDS_FOR_CR, $groupByFields);

        if (empty($missingStrings) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Group By field is invalid for CR query. Missing required fields: ' . implode(', ', $missingStrings)
            );
        }
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    private function validateGroupByForSrQuery(array $groupByFields): void
    {
        $missingStrings = array_diff(AnalyticsConstants::GROUP_BY_FIELDS_FOR_SR, $groupByFields);

        if (empty($missingStrings) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Group By field is invalid for SR query. Missing required fields: ' . implode(', ', $missingStrings)
            );
        }
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    private function validateGroupByForErrorMetricsQuery(array $groupByFields): void
    {
        $missingStrings = array_diff(
            [
                AnalyticsConstants::INTERNAL_ERROR_CODE,
                AnalyticsConstants::LAST_SELECTED_METHOD,
            ],
            $groupByFields
        );

        if (empty($missingStrings) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Group By field is invalid for Error Metrics query. Missing required fields: ' . implode(', ', $missingStrings)
            );
        }
    }
}
