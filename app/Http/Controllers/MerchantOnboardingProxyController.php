<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant;
use RZP\Constants\Country;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Core;
use RZP\Models\Admin\Permission\Name;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Merchant\Website\Service as WebsiteService;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BvsConstants;
use RZP\Models\Merchant\Website\Constants as WebsiteConstants;
use RZP\Models\DeviceDetail\Constants as DeviceDetailConstants;
use RZP\Models\DeviceDetail\Entity as DeviceDetailEntity;
use RZP\Models\User\Service as UserService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Admin\Org\Entity as OrgEntity;

class MerchantOnboardingProxyController extends BaseProxyController
{

    // route keys
    const MERCHANT_ACTIVATION_SAVE       = 'merchant_activation_save';

    const GET_MERCHANT_ACTIVATION_DETAILS = 'get_merchant_activation_details';
    const MERCHANT_SIGN_UP                  = 'merchant_sign_up';
    const SALES_ASSISTED_MERCHANT_SIGN_UP   = 'sales_assisted_merchant_sign_up';
    const MERCHANT_WEBSITE_POLICY_VERIFY = 'merchant_website_policy_verify';
    const MERCHANT_DOCUMENT_UPLOAD       = 'merchant_document_upload';
    const MERCHANT_DOCUMENT_DELETE       = 'merchant_document_delete';
    const GET_MERCHANT_BMC_RESPONSE      = 'get_merchant_bmc_response';
    const SAVE_MERCHANT_BMC_RESPONSE     = 'save_merchant_bmc_response';
    const MERCHANT_UPDATE_BY_ADMIN       = 'merchant_update_by_admin';
    const MERCHANT_CONSENTS_SAVE         = 'merchant_consents_save';

    // modular onboarding APIs
    const ONBOARDING_GET      = 'onboarding_get';
    const ONBOARDING_SAVE     = 'onboarding_save';
    const ONBOARDING_CREATE_OR_FETCH = 'onboarding_create_or_fetch';

    const MERCHANT_ACTIVATION_FETCH_INTERNAL    = 'merchant_activation_fetch_internal';

    // fee based gating routes
    const MERCHANT_GATING_LOGIC_SAVE     = 'merchant_gating_logic_save';
    const PAYMENT_ORDER_CREATE           = 'payment_order_create';
    const PAYMENT_ORDER_VERIFY           = 'payment_order_verify';
    const PAYMENT_ORDER_WEBHOOK          = 'payment_order_webhook';
    const MERCHANT_FETCH_GATING_LOGIC    = 'merchant_fetch_gating_logic';
    const MERCHANT_INVOICE_LOGIC_SAVE    = 'merchant_invoice_logic_save';

    // white glove onboarding
    const ONBOARDING_MANAGER                    = 'onboarding_manager';
    const FETCH_ONBOARDING_PAYMENTS_DETAILS     = 'fetch_onboarding_payment_details';

    // Merchant Activation Business categories v3 mapping
    const MERCHANT_CATEGORIES_V3                    = 'fetch_merchant_categories';
    const MERCHANT_CATEGORIES_ADMIN_V3              = 'fetch_merchant_categories_admin';
    const ACTIVATION_DOCUMENT_TYPES                 = 'activation_document_types';
    const UPLOAD_MERCHANT_DOCUMENT_BY_AGENT         = 'upload_merchant_document_by_agent';
    const MERCHANT_DOCUMENT_DELETE_V2               = 'merchant_document_delete_v2';
    const MERCHANT_DOCUMENT_UPLOAD_V2              = 'merchant_document_upload_v2';
    const MERCHANT_CATEGORIES_V3_ELIGIBILITY_SAVE   = 'merchant_categories_v3_eligibility_save';

    const GET_CLEARBIT_DOMAIN_INFO       = 'get_clearbit_domain_info';
    const MERCHANT_DETAILS_PATCH         = 'merchant_details_patch';
    const MERCHANT_DETAILS_PATCH_V2      = 'merchant_details_patch_v2';
    const MERCHANT_RM_FETCH              = 'merchant_rm_details_fetch';
    const MERCHANT_RM_CREATE             = 'merchant_rm_details_create';
    const MERCHANT_RM_UPDATE             = 'merchant_rm_details_update';
    const SEND_OTP                       = 'send_otp';

    // Website Policy Wizard v2 Routes
    const MERCHANT_GET_L2_DYNAMIC_CONFIGS                    = 'merchant_get_l2_dynamic_configs';
    const MERCHANT_GET_POLICY_COMPLIANCE_DETAILS             = 'merchant_get_policy_compliance_details';
    const MERCHANT_SAVE_POLICY_COMPLIANCE_DETAILS            = 'merchant_save_policy_compliance_details';
    const MERCHANT_POLICY_SECTION_PUBLISH_V2                 = 'merchant_policy_section_publish_v2';

    const SEND_SMS_OTP                                       = 'send_sms_otp';
    const VERIFY_OTP                                         = 'verify_otp';

    const GET_MERCHANT_ONBOARDING_DOCS_VERIFICATION          = 'get_merchant_onboarding_docs_verification';
    const GET_MERCHANT_ELIGIBILITY_FOR_AUTOMATION_ACTIVATION = 'get_merchant_eligibility_for_automation_activation';
    const MERCHANT_WEBSITE_SECTION_PAGE_LOAD_V2              = 'merchant_policy_preview';
    const MERCHANT_WEBSITE_POLICY_PREVIEW_V2                 = 'merchant_policy_preview_v2'; // version 2 for modular

    const GENERATE_MERCHANT_IDENTITY_VERIFICATION_URL        = 'generate_merchant_identity_verification_url';
    const PROCESS_MERCHANT_IDENTITY_VERIFICATION             = 'process_merchant_identity_verification';

    // merchant_document routes
    const SAVE_MERCHANT_DOCUMENT_DETAILS   = 'save_merchant_document';
    const FETCH_MERCHANT_DOCUMENT_DETAILS  = 'fetch_merchant_document';
    const MERCHANT_DOCUMENT_VALIDITY_CHECK = 'merchant_document_validity_check';

    const PGOS_SHADOW_MODE_EXPERIMENT_ID                = 'app.pgos_shadow_mode_experiment_id';
    const PGOS_LIVE_MODE_EXPERIMENT_ID                  = 'app.pgos_live_mode_experiment_id';
    const EASY_SUBMERCHANT_PGOS_LIVE_MODE_EXPERIMENT_ID = 'app.easy_submerchant_pgos_live_mode_experiment_id';
    const PHANTOM_SUBMERCHANT_PGOS_LIVE_MODE_EXPERIMENT_ID = 'app.pgos_phantom_live_mode_experiment_id';

    const ENABLE                         = 'enable';
    const LIVE                           = 'live';

    const PGOS_FETCH_DEVICE_CONFIG           = 'merchant_fetch_device_config';
    const PGOS_CREATE_DEVICE_ORDER           = 'merchant_pos_create_order';
    const PGOS_UPDATE_DEVICE_ORDER           = 'merchant_pos_update_order';
    const PGOS_FETCH_DEVICE_ORDER            = 'merchant_pos_fetch_order';
    const PGOS_FETCH_ALL_DEVICE_ORDER        = 'merchant_pos_fetch_all_order';
    const MERCHANT_POS_PAYMENT_CALLBACK      = 'merchant_pos_payment_callback';

    const MERCHANT_RIZE_PAYMENTLINK_CALLBACK      = 'merchant_rize_paymentlink_callback';
    const MERCHANT_RIZE_PAYMENT_CALLBACK          = 'merchant_rize_payment_callback';
    const MERCHANT_POS_FETCH_LATEST_ORDER    = 'merchant_pos_fetch_latest_order';

    const MERCHANT_FETCH_POS_ACTIVATION_FLOW     = 'merchant_fetch_pos_activation_flow';
    const PGOS_FETCH_PGOS_ACTIVATION_STATUS      = 'merchant_pgos_fetch_activation_status';
    const PGOS_BULK_FETCH_PGOS_ACTIVATION_STATUS = 'merchant_pgos_bulk_fetch_activation_status';
    const PGOS_UPDATE_PGOS_ACTIVATION_STATUS     = 'merchant_pgos_update_activation_status';
    const UPDATE_ACTION_STATE                    = 'update_action_state';
    const FETCH_ACTION_STATE_COUNT               = 'fetch_action_state_count';
    const MERCHANT_POS_STATE_LOGS                = 'merchant_pos_state_logs';
    const POST_MERCHANT_CONFIG                   = 'pos_merchant_config';
    const FETCH_SALES_ASSISTED_MERCHANTS         = 'fetch_sales_assisted_merchants';
    const MERCHANT_ACTIVATION_DETAILS_SALES      = 'merchant_activation_details_sales';
    const ONBOARDING_GET_SALES                   = 'onboarding_get_sales';
    const ONBOARDING_SAVE_SALES                  = 'onboarding_save_sales';
    const L2_SUBMIT_SHADOW                       = 'l2_submit_shadow';
    const FETCH_PGOS_MERCHANT_CONSENTS           = 'fetch_pgos_merchant_consents';
    const GET_APPLICABLE_ACTIVATION_STATUS       = 'get_applicable_activation_status';
    const FETCH_BRAND_DEALER_DETAILS             = 'fetch_brand_dealer_details';
    const UPDATE_BRAND_DEALER_DETAILS            = 'update_brand_dealer_details';
    const INITIATE_POS_ONBOARDING                = 'initiate_pos_onboarding';

    const ONBOARDING_ROUTES = [self::ONBOARDING_GET, self::ONBOARDING_SAVE, self::ONBOARDING_CREATE_OR_FETCH, self::MERCHANT_WEBSITE_POLICY_PREVIEW_V2];

    const PGOS_OWNED_FIELDS = [
        'activation_form_milestone',
        'contact_name',
        'email',
        'contact_mobile',
        'promoter_pan',
        'promoter_pan_name',
        'company_pan',
        'business_name',
        'business_type',
        'business_parent_category',
        'business_category',
        'business_subcategory',
        'business_model',
        'string business_website',
        'business_dba',
        'blacklisted_products_cate',
        'physical_store',
        'social_media',
        'others',
        'others_present',
        'website_present',
        'android_app_present',
        'ios_app_present',
        'playstore_url',
        'appstore_url',
        'company_pan_name',
        'merchant_id',
        'business_registered_address',
        'business_registered_state',
        'business_registered_city',
        'business_registered_pin',
        'business_operation_addres',
        'business_operation_state',
        'business_operation_city',
        'business_operation_pin',
        'gstin',
        'company_cin',
        'shop_establishment_number',
        'bank_account_number',
        'bank_account_name',
        'bank_branch_ifsc'
    ];

    const MERCHANT_ROUTES = [
        self::MERCHANT_ACTIVATION_SAVE,
        self::MERCHANT_SIGN_UP,
        self::SALES_ASSISTED_MERCHANT_SIGN_UP,
        self::GET_MERCHANT_BMC_RESPONSE,
        self::SAVE_MERCHANT_BMC_RESPONSE,

        self::MERCHANT_WEBSITE_POLICY_VERIFY,
        self::MERCHANT_GET_L2_DYNAMIC_CONFIGS,
        self::MERCHANT_GET_POLICY_COMPLIANCE_DETAILS,
        self::MERCHANT_SAVE_POLICY_COMPLIANCE_DETAILS,
        self::MERCHANT_POLICY_SECTION_PUBLISH_V2,

        self::MERCHANT_GATING_LOGIC_SAVE,
        self::PAYMENT_ORDER_CREATE,
        self::PAYMENT_ORDER_VERIFY,
        self::MERCHANT_FETCH_GATING_LOGIC,
        self::PAYMENT_ORDER_WEBHOOK,
        self::MERCHANT_WEBSITE_SECTION_PAGE_LOAD_V2,
        self::MERCHANT_WEBSITE_POLICY_PREVIEW_V2,
        self::MERCHANT_CATEGORIES_V3,
        self::SEND_SMS_OTP,
        self::VERIFY_OTP,
        self::ONBOARDING_GET,
        self::ONBOARDING_SAVE,
        self::ONBOARDING_CREATE_OR_FETCH,
    ];

    const ADMIN_ROUTES = [
        self::GET_MERCHANT_BMC_RESPONSE,
        self::MERCHANT_UPDATE_BY_ADMIN,
        self::SAVE_MERCHANT_DOCUMENT_DETAILS,
        self::FETCH_MERCHANT_DOCUMENT_DETAILS,
        self::MERCHANT_DOCUMENT_VALIDITY_CHECK,
        self::MERCHANT_CATEGORIES_ADMIN_V3,
        self::ACTIVATION_DOCUMENT_TYPES
    ];

    const RESTRICTED_ACTIVATION_STATUSES_FOR_MERCHANT_UPDATES = [
        DetailStatus::ACTIVATED,
        DetailStatus::KYC_QUALIFIED_UNACTIVATED,
        DetailStatus::REJECTED,
    ];


    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_MERCHANT_BMC_RESPONSE        => Name::VIEW_ALL_ENTITY,
        self::MERCHANT_UPDATE_BY_ADMIN         => Name::VIEW_ALL_ENTITY,
        self::SAVE_MERCHANT_DOCUMENT_DETAILS   => Name::MERCHANT_DOCUMENT_SAVE,
        self::FETCH_MERCHANT_DOCUMENT_DETAILS  => Name::MERCHANT_DOCUMENT_FETCH,
        self::MERCHANT_CATEGORIES_ADMIN_V3     => Name::VIEW_ALL_ENTITY,
    ];

    const ROUTES_URL_MAP = [
        self::MERCHANT_ACTIVATION_SAVE         => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantActivationSave',
        self::GET_MERCHANT_ACTIVATION_DETAILS  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantActivationDetails',
        self::MERCHANT_SIGN_UP                 => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/CreateWorkflow',
        self::MERCHANT_DOCUMENT_UPLOAD         => 'twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDocumentUpload',
        self::MERCHANT_DOCUMENT_DELETE         => 'twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDocumentDelete',
        self::GET_MERCHANT_BMC_RESPONSE        => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantBMCResponse',
        self::SAVE_MERCHANT_BMC_RESPONSE       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantBMCResponse',
        self::MERCHANT_UPDATE_BY_ADMIN         => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantUpdateByAdmin',
        self::GET_CLEARBIT_DOMAIN_INFO         => 'twirp/rzp.pg_onboarding.leads.v1.LeadsService/GetClearbitDomainInfo',
        self::MERCHANT_RM_CREATE               => 'twirp/rzp.pg_onboarding.external.rmdetails.v1.RmDetailsService/CreateRMDetails',
        self::MERCHANT_RM_FETCH                => 'twirp/rzp.pg_onboarding.external.rmdetails.v1.RmDetailsService/GetRMDetails',
        self::MERCHANT_RM_UPDATE               => 'twirp/rzp.pg_onboarding.external.rmdetails.v1.RmDetailsService/UpdateRMDetails',
        self::SEND_OTP                         => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SendOTP',
        self::MERCHANT_DETAILS_PATCH           => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDetailsPatch',
        self::MERCHANT_DETAILS_PATCH_V2           => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDetailsPatchV2',
        self::SAVE_MERCHANT_DOCUMENT_DETAILS   => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantDocumentMetadata',
        self::FETCH_MERCHANT_DOCUMENT_DETAILS  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantDocumentMetadata',
        self::MERCHANT_DOCUMENT_VALIDITY_CHECK => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/CheckMerchantDocumentDetailsValidity',
        self::ONBOARDING_GET                   => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/OnboardingGet',
        self::ONBOARDING_CREATE_OR_FETCH       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/OnboardingCreateOrFetch',
        self::ONBOARDING_SAVE                  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/OnboardingSave',
        self::MERCHANT_WEBSITE_POLICY_VERIFY           => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantIndividualPolicyVerification',
        self::MERCHANT_GET_L2_DYNAMIC_CONFIGS           => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantGetL2DynamicConfigs',
        self::MERCHANT_GET_POLICY_COMPLIANCE_DETAILS    => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantGetPolicyComplianceDetails',
        self::MERCHANT_SAVE_POLICY_COMPLIANCE_DETAILS   => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantSavePolicyComplianceDetails',
        self::MERCHANT_POLICY_SECTION_PUBLISH_V2        => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantPolicySectionPublish',
        self::GET_MERCHANT_ONBOARDING_DOCS_VERIFICATION => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantOnboardingDocVerification',
        self::MERCHANT_GATING_LOGIC_SAVE       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantGatingLogic',
        self::PAYMENT_ORDER_CREATE             => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/PaymentOrderCreate',
        self::PAYMENT_ORDER_VERIFY             => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/PaymentOrderVerify',
        self::MERCHANT_FETCH_GATING_LOGIC      => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantGatingLogic',
        self::FETCH_ONBOARDING_PAYMENTS_DETAILS      => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchOnboardingPaymentDetails',
        self::PAYMENT_ORDER_WEBHOOK            => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/PaymentOrderWebhook',
        self::GET_MERCHANT_ELIGIBILITY_FOR_AUTOMATION_ACTIVATION => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantEligibilityForAutomationActivation',
        self::MERCHANT_INVOICE_LOGIC_SAVE                  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantInvoiceLogic',
        self::MERCHANT_CONSENTS_SAVE                       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantConsentsSave',
        self::GENERATE_MERCHANT_IDENTITY_VERIFICATION_URL  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GenerateMerchantIdentityVerificationUrl',
        self::PROCESS_MERCHANT_IDENTITY_VERIFICATION       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/ProcessMerchantIdentityVerification',
        self::MERCHANT_WEBSITE_SECTION_PAGE_LOAD_V2        => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantWebsitePolicyPreview',
        self::MERCHANT_WEBSITE_POLICY_PREVIEW_V2           => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantWebsitePolicyPreviewV2',
        self::MERCHANT_CATEGORIES_V3                       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantCategoriesV3Map',
        self::MERCHANT_CATEGORIES_ADMIN_V3                 => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantCategoriesAdminV3Map',
        self::ACTIVATION_DOCUMENT_TYPES                    => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchAllDocumentsTypesList',
        self::UPLOAD_MERCHANT_DOCUMENT_BY_AGENT            => 'twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/UploadMerchantDocumentByAgent',
        self::MERCHANT_DOCUMENT_UPLOAD_V2                  => 'twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDocumentUploadV2',
        self::MERCHANT_DOCUMENT_DELETE_V2                  => 'twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantDocumentDeleteV2',
        self::MERCHANT_CATEGORIES_V3_ELIGIBILITY_SAVE      => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantCategoriesV3EligibilitySave',
        self::SEND_SMS_OTP                                  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SendSMSOTP',
        self::VERIFY_OTP                                    => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/VerifyOTP',
        self::MERCHANT_ACTIVATION_FETCH_INTERNAL            => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantActivationFetchInternal',
        self::PGOS_FETCH_DEVICE_CONFIG                      => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchDeviceConfig',
        self::PGOS_CREATE_DEVICE_ORDER                      => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/CreateDeviceOrder',
        self::PGOS_UPDATE_DEVICE_ORDER                      => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/UpdateDeviceOrder',
        self::PGOS_FETCH_DEVICE_ORDER                       => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchOrderById',
        self::PGOS_FETCH_ALL_DEVICE_ORDER                   => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchAllOrdersForMerchant',
        self::MERCHANT_POS_PAYMENT_CALLBACK                 => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/MerchantDevicePaymentCallback',
        self::MERCHANT_RIZE_PAYMENTLINK_CALLBACK            => '/twirp/rzp.pg_onboarding.external.rize.v1.PaymentService/PLCallback',
        self::MERCHANT_RIZE_PAYMENT_CALLBACK                => '/twirp/rzp.pg_onboarding.external.rize.v1.PaymentService/PaymentCallback',
        self::MERCHANT_POS_FETCH_LATEST_ORDER               => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchLatestOrder',
        self::MERCHANT_FETCH_POS_ACTIVATION_FLOW            => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/FetchPosActivationFlow',
        self::PGOS_FETCH_PGOS_ACTIVATION_STATUS             => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/GetPosActivationStatus',
        self::PGOS_BULK_FETCH_PGOS_ACTIVATION_STATUS        => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/GetBulkPosActivationStatus',
        self::PGOS_UPDATE_PGOS_ACTIVATION_STATUS            => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/UpdatePosActivationStatus',
        self::SALES_ASSISTED_MERCHANT_SIGN_UP               => '/twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/CreateWorkflow',
        self::UPDATE_ACTION_STATE                           => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/UpdateState',
        self::FETCH_ACTION_STATE_COUNT                      => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/GetActionStateCount',
        self::MERCHANT_POS_STATE_LOGS                       => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/GetActionStateLogs',
        self::POST_MERCHANT_CONFIG                          => '/twirp/rzp.pg_onboarding.external.pos.v1.TerminalProcurementConsumerService/Onboard',
        self::FETCH_SALES_ASSISTED_MERCHANTS                => '/twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/FetchSalesAssistedMerchants',
        self::L2_SUBMIT_SHADOW                              => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/L2SubmitShadow',
        self::GET_APPLICABLE_ACTIVATION_STATUS              => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetApplicableActivationStatus',
        self::MERCHANT_ACTIVATION_DETAILS_SALES             => '/twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/FetchSalesAssistedMerchantActivationDetails',
        self::ONBOARDING_GET_SALES                          => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SalesAssistedOnboardingGet',
        self::ONBOARDING_SAVE_SALES                         => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SalesAssistedOnboardingSave',
        self::FETCH_PGOS_MERCHANT_CONSENTS                  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantConsents',
        self::FETCH_BRAND_DEALER_DETAILS                    => '/twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/FetchBrandDealerDetails',
        self::UPDATE_BRAND_DEALER_DETAILS                   => '/twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/UpdateBrandDealerDetails',
        self::INITIATE_POS_ONBOARDING                       => '/twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/InitiatePosOnboarding',
    ];

    // timeout in seconds
    const PATH_TIMEOUT_MAP = [
        //merchant_document routes
        self::SAVE_MERCHANT_DOCUMENT_DETAILS   => 15,
        self::FETCH_MERCHANT_DOCUMENT_DETAILS  => 15,
        self::MERCHANT_DOCUMENT_VALIDITY_CHECK => 15,
        self::MERCHANT_ACTIVATION_SAVE                  => 15,
        // todo: this is a temporary solution to increase the API timeout.
        // context: OnboardingSave is being used in Master KYC onboarding flows and PGOS
        // will throw context canceled error in case of timeout. This has to be reverted
        // once the latencies of the API is optimised.
        self::ONBOARDING_SAVE                           => 50,
        self::ONBOARDING_GET                            => 15,
        self::MERCHANT_SIGN_UP                          => 20,
        self::SALES_ASSISTED_MERCHANT_SIGN_UP           => 20,
        self::MERCHANT_DOCUMENT_UPLOAD                  => 15,
        self::MERCHANT_GET_POLICY_COMPLIANCE_DETAILS    => 15,
        self::MERCHANT_SAVE_POLICY_COMPLIANCE_DETAILS   => 15,
        self::MERCHANT_WEBSITE_SECTION_PAGE_LOAD_V2     => 15,
        self::MERCHANT_WEBSITE_POLICY_PREVIEW_V2        => 15,
        self::ONBOARDING_CREATE_OR_FETCH                => 30,

        // TODO: Revert back once the root cause for OBS latency is found and fixed.
        // This is temporarily being increased to unblock curlec signup flows.
        // https://razorpay.slack.com/archives/C043K5N223F/p1700641894030849?thread_ts=1699005756.802759&cid=C043K5N223F
        self::SEND_OTP                                  => 20,
        self::MERCHANT_WEBSITE_POLICY_VERIFY            => 40,
        self::L2_SUBMIT_SHADOW                          => 1,
        self::GET_APPLICABLE_ACTIVATION_STATUS          => 3,
    ];

    const ROUTES_WITH_PGOS_EXPERIMENT_ALWAYS_ENABLE = [
        self::POST_MERCHANT_CONFIG,
        self::MERCHANT_POS_STATE_LOGS,
        self::FETCH_ACTION_STATE_COUNT,
        self::UPDATE_ACTION_STATE,
        self::PGOS_UPDATE_PGOS_ACTIVATION_STATUS,
        self::PGOS_FETCH_PGOS_ACTIVATION_STATUS,
        self::MERCHANT_FETCH_POS_ACTIVATION_FLOW,
        self::MERCHANT_ACTIVATION_SAVE,
        self::PGOS_FETCH_DEVICE_CONFIG,
        self::PGOS_CREATE_DEVICE_ORDER,
        self::PGOS_UPDATE_DEVICE_ORDER,
        self::PGOS_FETCH_DEVICE_ORDER,
        self::PGOS_FETCH_ALL_DEVICE_ORDER,
        self::MERCHANT_POS_PAYMENT_CALLBACK,
        self::MERCHANT_RIZE_PAYMENTLINK_CALLBACK,
        self::MERCHANT_RIZE_PAYMENT_CALLBACK,
        self::MERCHANT_POS_FETCH_LATEST_ORDER,
        self::GET_MERCHANT_BMC_RESPONSE,
        self::SAVE_MERCHANT_BMC_RESPONSE,
        self::MERCHANT_UPDATE_BY_ADMIN,
        self::GET_MERCHANT_ONBOARDING_DOCS_VERIFICATION,
        self::MERCHANT_WEBSITE_POLICY_VERIFY,
        self::MERCHANT_GET_L2_DYNAMIC_CONFIGS,
        self::MERCHANT_GET_POLICY_COMPLIANCE_DETAILS,
        self::MERCHANT_SAVE_POLICY_COMPLIANCE_DETAILS,
        self::MERCHANT_POLICY_SECTION_PUBLISH_V2,
        self::MERCHANT_GATING_LOGIC_SAVE,
        self::PAYMENT_ORDER_CREATE,
        self::PAYMENT_ORDER_VERIFY,
        self::MERCHANT_FETCH_GATING_LOGIC,
        self::PAYMENT_ORDER_WEBHOOK,
        self::GET_MERCHANT_ELIGIBILITY_FOR_AUTOMATION_ACTIVATION,
        self::SAVE_MERCHANT_DOCUMENT_DETAILS,
        self::FETCH_MERCHANT_DOCUMENT_DETAILS,
        self::MERCHANT_DOCUMENT_VALIDITY_CHECK,
        self::MERCHANT_DOCUMENT_UPLOAD,
        self::MERCHANT_CONSENTS_SAVE,
        self::GENERATE_MERCHANT_IDENTITY_VERIFICATION_URL,
        self::PROCESS_MERCHANT_IDENTITY_VERIFICATION,
        self::MERCHANT_WEBSITE_SECTION_PAGE_LOAD_V2,
        self::MERCHANT_WEBSITE_POLICY_PREVIEW_V2,
        self::MERCHANT_CATEGORIES_V3,
        self::MERCHANT_CATEGORIES_ADMIN_V3,
        self::MERCHANT_CATEGORIES_V3_ELIGIBILITY_SAVE,
        self::MERCHANT_RM_FETCH,
        self::MERCHANT_RM_CREATE,
        self::MERCHANT_RM_UPDATE,
        self::ACTIVATION_DOCUMENT_TYPES,
        self::MERCHANT_SIGN_UP,
        self::SALES_ASSISTED_MERCHANT_SIGN_UP
    ];

    public function __construct()
    {
        parent::__construct("pgos");

        $this->trace = $this->app['trace'];

        $this->registerRoutesMap(self::ROUTES_URL_MAP);

        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->setDefaultTimeout(10);

        $this->registerAdminRoutes(self::ADMIN_ROUTES, self::ADMIN_ROUTES_VS_PERMISSION);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);

    }

    protected function pgosMockResponses(string $routeKey)
    {
        //mocking default response based on RouteKey
        return match ($routeKey)
        {
            self::PGOS_BULK_FETCH_PGOS_ACTIVATION_STATUS => [
                'pos_activation_status' => [
                    "10000000000009" => "under_review"
                ]
            ],
            self::MERCHANT_ACTIVATION_SAVE => [
              "success" => true,
            ],
            self::MERCHANT_SIGN_UP, self::SALES_ASSISTED_MERCHANT_SIGN_UP => [
                "workflow_id" => "test_workflow",
                "modular_workflow_id" => "test_workflow"
            ],
            self::FETCH_MERCHANT_DOCUMENT_DETAILS => [
                "ffmc_license" => [
                    [
                        "id"            => "MuiZWKXnd61h78",
                        "file_store_id" => "1cXSLlUU8V9sXl",
                        "merchant_id"   => "KqsQEszAud2PqZ",
                        "created_at"    => "0",
                        "metadata"      => [
                            "expiry_applicable" => "true",
                            "expiry_date"       => "1699615221",
                            "expiry_mandatory"  => "true"
                        ]
                    ],
                ],
                // ... (and so on for the other document types)
            ],
            self::MERCHANT_DOCUMENT_UPLOAD => [
                "activation_response" => [],
            ],
            self::MERCHANT_CATEGORIES_V3_ELIGIBILITY_SAVE, self::MERCHANT_DOCUMENT_VALIDITY_CHECK => [
                "success" => true
            ],
            self::MERCHANT_POS_STATE_LOGS => [
              "data" => [
                  [
                      "id" => "MuiZWKXnd61h78",
                      "name" => "under_review",
                      "admin_id" => "MuiZWKXnd61h00",
                      "merchant_id" => "10000000000009",
                      "onboarding_type" => "pos",
                      "metadata" => [
                          "actor_details" => [
                              "id" => "10000000000000",
                              "type" => "user",
                              "name" => "test",
                              "email" => "test@gmail.com",
                              "role" => "partner_agent"
                          ]
                      ]
                  ]
              ],
              "success" => true
            ],
            default => null,
        };
    }



    public function isModularMerchant($merchant): bool
    {
        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRoleFromMaster($merchant->getId());

        if (empty($userDeviceDetail) === true)
        {
            return false;
        }

        if ($this->isIndiaPgModularMerchantFromUserDeviceDetail($merchant, $userDeviceDetail) === true)
        {
            return true;
        }

        $workflowType = $userDeviceDetail->getValueFromMetaData(DeviceDetailConstants::WORKFLOW_TYPE);

        return (empty($workflowType) === false && $workflowType === DeviceDetailConstants::MODULAR_ONBOARDING);
    }

    public function isCurlecModularMerchant($merchant): bool
    {
        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRoleFromMaster($merchant->getId());

        if (empty($userDeviceDetail) === true)
        {
            return false;
        }


        $workflowType = $userDeviceDetail->getValueFromMetaData(DeviceDetailConstants::WORKFLOW_TYPE);

        return (empty($workflowType) === false && $workflowType === DeviceDetailConstants::MODULAR_ONBOARDING);
    }

    public function isIndiaPgModularMerchant($merchant): bool
    {
        if (strtolower($merchant->getCountry()) !== Country::IN || $merchant->getOrgId() !== OrgEntity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRoleFromMaster($merchant->getId());

        if (empty($userDeviceDetail) === true)
        {
            return false;
        }

        return $this->isIndiaPgModularMerchantFromUserDeviceDetail($merchant, $userDeviceDetail);
    }

    protected function isIndiaPgModularMerchantFromUserDeviceDetail($merchant, $userDeviceDetail): bool
    {
        if (strtolower($merchant->getCountry()) !== Country::IN || $merchant->getOrgId() !== OrgEntity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        return $this->getProductSpecificWorkflowType($userDeviceDetail, DeviceDetailConstants::PRODUCT_PG_ONBOARDING)
            === DeviceDetailConstants::MODULAR_ONBOARDING;
    }

    public function getProductSpecificWorkflowType($userDeviceDetail, $product)
    {
        $workflowDetails = $userDeviceDetail->getValueFromMetaData(DeviceDetailConstants::WORKFLOW_DETAILS);

        if (empty($workflowDetails) === true)
        {
            return null;
        }

        $productPgOnboardingWorkflowTypeKey = sprintf(DeviceDetailConstants::PRODUCT_WORKFLOW_TYPE_TEMPLATE, $product);

        if (isset($workflowDetails[$productPgOnboardingWorkflowTypeKey]) === false)
        {
            return null;
        }

        return $workflowDetails[$productPgOnboardingWorkflowTypeKey];
    }

    public function  shouldRouteAdminViaPGOSV2($merchant){

        if($this->app['basicauth']->isAdminAuth() === true and $this->isModularMerchant($merchant)){
            return true;
        }

        return false;
    }

    public function getPayloadForFileUpload($input){
        $fileObj = $input['file'];
        $filePayload = [
            "original_name" => $fileObj->getClientOriginalName(),
            "content" =>  base64_encode(file_get_contents($fileObj)),
            "mime_type" =>  '',
            "extention" =>  '',
        ];
        $input['file'] = $filePayload;
        return $input;
    }

    public function handlePGOSProxyRequests($routeKey, $payload, $merchant, $ignoreRoutingConditions = false)
    {
        $merchantId = $merchant->getMerchantId();

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'merchantId' => $merchantId,
            'routeKey'   => $routeKey,
            'payload'    => $payload
        ]);

        $app = App::getFacadeRoot();

        $mock = $app['config']['pgos.proxy.request.mock'];

        // check if for the merchant the experiment is enabled or not
        // check if merchant is a regular merchant or not

        if (in_array($routeKey, self::ROUTES_WITH_PGOS_EXPERIMENT_ALWAYS_ENABLE) and
            (new Core)->isRegularMerchant($merchant) === true)
        {
            $ignoreRoutingConditions = true;
        }

        if ($ignoreRoutingConditions or $this->shouldMerchantOnboardViaPGOS($merchantId, $merchant->getCountry()))
        {
            if ($mock === true)
            {
                return $this->pgosMockResponses($routeKey);
            }

            // get path from defined route url map
            $twirpPath = self::ROUTES_URL_MAP[$routeKey];

            $route = $this->getRoute($twirpPath);

            $headers = $this->getHeadersForDashboardRequest($payload, $merchantId);

            if ($routeKey === self::GET_MERCHANT_ACTIVATION_DETAILS)
            {
                $headers['Asv-Exp-Enabled'] = "true";
            }

            $headers['X-Route-Name'] = $routeKey;

            $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
                'route'     => $route,
                'twirpPath' => $twirpPath,
            ]);

            return $this->sendRequestAndParseResponse($routeKey, 'POST', $twirpPath, $payload, $headers);
        }

        return null;
    }
    public function handlePGOSProxyRequestsForAssistedMerchants($routeKey, $payload, $merchantId, $ignoreRoutingConditions = false)
    {
        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'merchantId' => $merchantId,
            'routeKey'   => $routeKey,
            'payload'    => $payload
        ]);

        $app = App::getFacadeRoot();

        $mock = $app['config']['pgos.proxy.request.mock'];


        if ($mock === true)
        {
            return $this->pgosMockResponses($routeKey);
        }

        // get path from defined route url map
        $twirpPath = self::ROUTES_URL_MAP[$routeKey];

        $route = $this->getRoute($twirpPath);

        $headers = $this->getHeadersForDashboardRequest($payload, $merchantId);

        if ($routeKey === self::GET_MERCHANT_ACTIVATION_DETAILS)
        {
            $headers['Asv-Exp-Enabled'] = "true";
        }

        $headers['X-Route-Name'] = $routeKey;

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'route'     => $route,
            'twirpPath' => $twirpPath,
        ]);

        return $this->sendRequestAndParseResponse($routeKey, 'POST', $twirpPath, $payload, $headers);
    }
    public function handleMerchantSignup($payload, $merchant)
    {
        $signupCampaign = $payload[DeviceDetailEntity::SIGNUP_CAMPAIGN];
        $routeKey = self::MERCHANT_SIGN_UP;
        if ((new UserService())->isAssistedOnboardingSignupCampaign($signupCampaign))
        {
            $routeKey = self::SALES_ASSISTED_MERCHANT_SIGN_UP;
        }
        return $this->handlePGOSProxyRequests($routeKey,$payload,$merchant,true);

    }

    //We are not passing $path here as done in BaseProxyController since we are getting path from request itself.
    //We are passing path params as arguments in this function instead.
    public function handleDashboardProxyRequests($id = '')
    {
        $request = Request::instance();

        $path = $request->getPathInfo();

        if (empty($id) === true)
        {
            $routeKey = str_replace('/v1/pg/onboarding/', '', $path);
        }
        else
        {
            $routeKey = str_replace('/v1/pg/onboarding/' . $id . '/', '', $path);
        }

        // TODO: Migrate this for every route as we should be using route names rather than regex replacement done above
        try
        {
            $routeName = $request->route()->getName();

            if (in_array($routeName, self::ONBOARDING_ROUTES))
            {
                $routeKey = $routeName;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'error_message'     => $ex->getMessage()
            ]);

        }

        $this->overrideRouteKeyIfApplicable($routeKey, $id);

        $body = $request->all();

        // get path from defined route url map
        $twirpPath = self::ROUTES_URL_MAP[$routeKey];

        $route = $this->getRoute($twirpPath);

        $headers = $this->getHeadersForDashboardRequest($body, $id);

        $this->trace->info(TraceCode::PGOS_DASHBOARD_PROXY_REQUEST, [
            'route'     => $route,
            'twirpPath' => $twirpPath,
            'body'      => $body,
        ]);

        try
        {
            $validationResponse = $this->routeSpecificPreValidations($routeKey, $body);

            if ($validationResponse['validated'] === true)
            {
                $this->routeSpecificPreProcessor($routeKey, $body, $id);

                $response = $this->sendRequestAndParseResponse($routeKey, 'POST', $twirpPath, $body, $headers);

                $this->routeSpecificPostProcessor($routeKey, $body);

                return $response;
            }
            else
            {
                unset($validationResponse['validated']);

                return $validationResponse;
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'pgos_proxy_request'     => true,
                'error_message'          => $e->getMessage()
            ]);

            $this->trace->traceException($e);

            throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::SERVER_ERROR);
        }
    }


    public function getTwirpRouteName($routeKey)
    {
        return self::MERCHANT_ROUTES[$routeKey];
    }

    /**
     * @throws BadRequestException
     */
    protected function validatePathForRequest($routes, $path)
    {
        if (in_array($path, $routes) === false) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function getAuthorizationHeader(): string
    {
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }

    /**
     * Merchants who sign up using a reseller partner referral link are marked as a submerchant.
     * They signup via easy onboarding, hence their signup campaign is `easy_onboarding`.
     * Currently `handlePGOSOnboarding` sets the onboarding service for sub-merchants as `service_api`
     * So, their onboarding is handled by API.
     * Here we allow sub-merchants to be onboarded via PGOS if their signup campaign is easy_onboarding
     *
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function isPGOSEnabledForPGSubmerchant(Merchant\Entity $merchant): bool
    {

        $properties = [
            'id'            => $merchant->getId(),
            'experiment_id' => $this->app['config']->get(self::EASY_SUBMERCHANT_PGOS_LIVE_MODE_EXPERIMENT_ID),
        ];

        return (new Core())->isSplitzExperimentEnable($properties, self::ENABLE);
    }

    public function isPGOSEnabledForPhantomSubmerchant(Merchant\Entity $merchant): bool
    {
        $properties = [
            'id'            => $merchant->getId(),
            'experiment_id' => $this->app['config']->get(self::PHANTOM_SUBMERCHANT_PGOS_LIVE_MODE_EXPERIMENT_ID),
        ];

        return (new Core())->isSplitzExperimentEnable($properties, self::ENABLE);
    }

    public function isPGOSExperimentEnabledForMerchant($merchantId, $experimentId, $mode): bool
    {
        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'splitz_input_experiment_id' => $experimentId,
            'splitz_input_merchant_id'   => $merchantId
        ]);

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get($experimentId),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
            'splitz_output' => $variant,
        ]);

        return $variant === $mode;
    }

    public function shouldMerchantOnboardViaPGOS($merchantId, $merchantCountryCode = 'IN'): bool
    {
        try
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $merchantCore = new Core();

            // Check for POS Sub-merchants
            if ($merchantCore->isPOSSubMerchant($merchant))
            {
                return true;
            }

            $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRoleFromMaster($merchantId);

            //Check for Google OAuth merchants
            if (empty($userDeviceDetail) === false)
            {
                $merchantOnboardedViaService = $userDeviceDetail->getValueFromMetaData(DeviceDetailConstants::SERVICE);

                if (empty($merchantOnboardedViaService) === false)
                {
                    if ($merchantOnboardedViaService === DeviceDetailConstants::SERVICE_PGOS)
                    {
                        return true;
                    }
                }
            }
            return false;
        }
        catch (\Throwable $e) {

            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'pgos_proxy_request'     => true,
                'error_function'         => 'shouldMerchantOnboardViaPGOS',
                'error_message'          => $e->getMessage()
            ]);

            return false;
        }
    }

    public function isFieldsOwnedByPGOS($inputFields): bool
    {
        return (bool)count(array_intersect($inputFields, self::PGOS_OWNED_FIELDS));
    }

    /**
     * @param string $routeKey
     * @param array $body
     * @throws ServerErrorException
     */
    private function routeSpecificPostProcessor(string $routeKey, array $body)
    {
        switch ($routeKey)
        {
            case self::SAVE_MERCHANT_BMC_RESPONSE:
                (new WebsiteService())->updateCommonWebsiteQuestions($body, true);
                break;
        }

    }

    private function routeSpecificPreProcessor(string $routeKey, array &$body, string $id)
    {
        switch ($routeKey)
        {
            case self::PAYMENT_ORDER_WEBHOOK:
                (new Merchant\Detail\Core())->preProcessGatingRequest($body);
                break;

            case self::PAYMENT_ORDER_CREATE:
                (new Merchant\Detail\Core())->preProcessCreateOrderRequest($body);
                break;

            case self::MERCHANT_CATEGORIES_V3:
                (new Merchant\Detail\Core())->preProcessFetchCategoriesData($body);
                break;

            case self::FETCH_ONBOARDING_PAYMENTS_DETAILS:
                (new Merchant\Detail\Core())->preProcessWhiteGloveRequest($body, $id);
                break;
        }
    }

    private function routeSpecificPreValidations(string $routeKey, array &$body) : array
    {
        $response = [];

        //Setting true by default
        $response['validated'] = true;

        switch ($routeKey)
        {
            case self::SEND_SMS_OTP:
                try
                {
                    $userExists = (new \RZP\Models\User\Core())->checkIfMobileAlreadyExists($body["contact_mobile"]);

                    $response['validated'] = !($userExists);

                    if ($response['validated'] === false) {
                        $response['success'] = false;
                        $response['error']['code'] = "";
                        $response['error']['description'] = "Phone number already exists";
                    }

                } catch (\Throwable $e)
                {
                    $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                        'section'   => "Error in Pre Validation",
                        'case'      => self::SEND_SMS_OTP,
                        'error'     => $e->getMessage()
                    ]);
                }

                break;
            case self::MERCHANT_GET_L2_DYNAMIC_CONFIGS:
                try
                {
                    $policyEligibility = (new WebsiteService())->getPolicyEligibilityIfValid($body['revaluate_eligibility']);

                    if (empty($policyEligibility) === false) {
                        $response['validated'] = false;
                        $response['policy_eligibility'] = $policyEligibility;

                        // if policy eligibility is not v1 or not_eligible, then send website_policy_verification_status in response
                        // v1 or not_eligible doesn't consume it, hence need not be sent for them
                        $excludedEligibilities = [ WebsiteConstants::POLICY_WIZARD_V1, WebsiteConstants::NOT_ELIGIBLE ];

                        if (!in_array($policyEligibility, $excludedEligibilities)) {
                            $ba = $this->app['basicauth'];
                            $mid = $ba->getMerchant()->getId();

                            $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifierFromReplica(
                                $mid,
                                BvsConstants::WEBSITE_POLICY,
                                MVD\Constants::NUMBER
                            );

                            if (empty($verificationDetail) === false) {
                                $response['website_policy_verification_status'] = $verificationDetail->getStatus();
                            }
                        }
                    }
                } catch (\Throwable $e)
                {
                    $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                        'section'   => "Error in Pre Validation",
                        'case'      => self::MERCHANT_GET_L2_DYNAMIC_CONFIGS,
                        'error'     => $e->getMessage()
                    ]);
                }

                break;
            case self::PAYMENT_ORDER_WEBHOOK:
               $response['validated'] = (new Merchant\Detail\Core())->isOnboardingPaymentWebhookRequest($body);

               break;

            case self::FETCH_ONBOARDING_PAYMENTS_DETAILS:
                $response['validated'] = (new Merchant\Detail\Core())->isWhiteGloveOnboardingApplicable();

               break;
        }

        return $response;
    }

    public function overrideRouteKeyIfApplicable(string &$routeKey, $id)
    {
        switch ($id)
        {
            case self::ONBOARDING_MANAGER:
                $routeKey = self::FETCH_ONBOARDING_PAYMENTS_DETAILS;
                break;

            default:
        }
    }

    public function canUpdateMerchantViaPGOS(Merchant\Entity $merchant): bool
    {
        $merchantId = $merchant->getId();
        $activationStatus = $merchant->merchantDetail->getActivationStatus();

        if (in_array($activationStatus, self::RESTRICTED_ACTIVATION_STATUSES_FOR_MERCHANT_UPDATES, true) === true)
        {
            return false;
        }

        if ($this->shouldMerchantOnboardViaPGOS($merchantId, $merchant->getCountry()) === false)
        {
            return false;
        }

        return true;
    }


    /**
     * @throws Exception\IntegrationException
     */
    public function updateMerchantDetails(Merchant\Entity $merchant, $input)
    {
        $response = $this->handlePGOSProxyRequests('merchant_activation_save', $input, $merchant, true);
        $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
            'response' => $response
        ]);

        // throw PGOS response error msg if data is not present
        if(isset($response['msg']) === true)
        {
            throw new Exception\IntegrationException(
                $response['msg']
            );
        }

        return $response;
    }

    public function errorHandler($response) {
        if(isset($response['code']) === false)
        {
            // success condition, do nothing
            return;
        }

        $status_code = $response['code'];

        $this->trace->info(TraceCode::PGOS_ERROR_HANDLER, [
            'status_code' => $status_code,
        ]);

        // Check if the response status code indicates an error (4xx or 5xx)
        $error_message = $response['msg'];
        if (isset($response['meta']) && isset($response['meta']['description'])) {
            $error_message = $response['meta']['description'];
        }


        switch ($status_code)
        {
            case "invalid_argument":
                throw new Exception\BadRequestValidationFailureException($error_message);
            case "bad_request":
            case "invalid_data":
                throw new Exception\BadRequestException($error_message);
                return;
            case "internal":
            default:
                throw new ServerErrorException(
                    $error_message,
                    ErrorCode::SERVER_ERROR,
                );
                return;
        }
    }

}
