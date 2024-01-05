<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
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

class MerchantOnboardingProxyController extends BaseProxyController
{

    // route key
    const MERCHANT_ACTIVATION_SAVE       = 'merchant_activation_save';
    const MERCHANT_SIGN_UP               = 'merchant_sign_up';
    const MERCHANT_DOCUMENT_UPLOAD       = 'merchant_document_upload';
    const MERCHANT_DOCUMENT_DELETE       = 'merchant_document_delete';
    const GET_MERCHANT_BMC_RESPONSE      = 'get_merchant_bmc_response';
    const SAVE_MERCHANT_BMC_RESPONSE     = 'save_merchant_bmc_response';
    const MERCHANT_UPDATE_BY_ADMIN       = 'merchant_update_by_admin';
    const MERCHANT_CONSENTS_SAVE         = 'merchant_consents_save';

    // fee based gating routes
    const MERCHANT_GATING_LOGIC_SAVE     = 'merchant_gating_logic_save';
    const PAYMENT_ORDER_CREATE           = 'payment_order_create';
    const PAYMENT_ORDER_VERIFY           = 'payment_order_verify';
    const PAYMENT_ORDER_WEBHOOK          = 'payment_order_webhook';
    const MERCHANT_FETCH_GATING_LOGIC    = 'merchant_fetch_gating_logic';
    const MERCHANT_INVOICE_LOGIC_SAVE    = 'merchant_invoice_logic_save';

    // Merchant Activation Business categories v3 mapping
    const MERCHANT_CATEGORIES_V3                    = 'fetch_merchant_categories';
    const MERCHANT_CATEGORIES_ADMIN_V3              = 'fetch_merchant_categories_admin';
    const MERCHANT_CATEGORIES_V3_ELIGIBILITY_SAVE   = 'merchant_categories_v3_eligibility_save';

    const GET_CLEARBIT_DOMAIN_INFO       = 'get_clearbit_domain_info';
    const MERCHANT_DETAILS_PATCH         = 'merchant_details_patch';
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

    const GENERATE_MERCHANT_IDENTITY_VERIFICATION_URL        = 'generate_merchant_identity_verification_url';
    const PROCESS_MERCHANT_IDENTITY_VERIFICATION             = 'process_merchant_identity_verification';

    // merchant_document routes
    const SAVE_MERCHANT_DOCUMENT_DETAILS   = 'save_merchant_document';
    const FETCH_MERCHANT_DOCUMENT_DETAILS  = 'fetch_merchant_document';
    const MERCHANT_DOCUMENT_VALIDITY_CHECK = 'merchant_document_validity_check';

    const PGOS_SHADOW_MODE_EXPERIMENT_ID = 'app.pgos_shadow_mode_experiment_id';
    const PGOS_LIVE_MODE_EXPERIMENT_ID   = 'app.pgos_live_mode_experiment_id';
    const ENABLE                         = 'enable';
    const LIVE                           = 'live';

    const PGOS_FETCH_DEVICE_CONFIG           = 'merchant_fetch_device_config';
    const PGOS_CREATE_DEVICE_ORDER           = 'merchant_pos_create_order';
    const PGOS_UPDATE_DEVICE_ORDER           = 'merchant_pos_update_order';
    const PGOS_FETCH_DEVICE_ORDER            = 'merchant_pos_fetch_order';
    const PGOS_FETCH_ALL_DEVICE_ORDER        = 'merchant_pos_fetch_all_order';
    const MERCHANT_POS_PAYMENT_CALLBACK      = 'merchant_pos_payment_callback';
    const MERCHANT_POS_FETCH_LATEST_ORDER    = 'merchant_pos_fetch_latest_order';

    const MERCHANT_FETCH_POS_ACTIVATION_FLOW = 'merchant_fetch_pos_activation_flow';
    const PGOS_FETCH_PGOS_ACTIVATION_STATUS  = 'merchant_pgos_fetch_activation_status';
    const PGOS_UPDATE_PGOS_ACTIVATION_STATUS = 'merchant_pgos_update_activation_status';
    const UPDATE_ACTION_STATE                = 'update_action_state';
    const FETCH_ACTION_STATE_COUNT           = 'fetch_action_state_count';
    const MERCHANT_POS_STATE_LOGS            = 'merchant_pos_state_logs';
    const POST_MERCHANT_CONFIG               = 'pos_merchant_config';

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
        self::GET_MERCHANT_BMC_RESPONSE,
        self::SAVE_MERCHANT_BMC_RESPONSE,

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
        self::MERCHANT_CATEGORIES_V3,
        self::SEND_SMS_OTP,
        self::VERIFY_OTP
    ];

    const ADMIN_ROUTES = [
        self::GET_MERCHANT_BMC_RESPONSE,
        self::MERCHANT_UPDATE_BY_ADMIN,
        self::SAVE_MERCHANT_DOCUMENT_DETAILS,
        self::FETCH_MERCHANT_DOCUMENT_DETAILS,
        self::MERCHANT_DOCUMENT_VALIDITY_CHECK,
        self::MERCHANT_CATEGORIES_ADMIN_V3
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
        self::SAVE_MERCHANT_DOCUMENT_DETAILS   => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantDocumentMetadata',
        self::FETCH_MERCHANT_DOCUMENT_DETAILS  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantDocumentMetadata',
        self::MERCHANT_DOCUMENT_VALIDITY_CHECK => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/CheckMerchantDocumentDetailsValidity',
        self::MERCHANT_GET_L2_DYNAMIC_CONFIGS           => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantGetL2DynamicConfigs',
        self::MERCHANT_GET_POLICY_COMPLIANCE_DETAILS    => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantGetPolicyComplianceDetails',
        self::MERCHANT_SAVE_POLICY_COMPLIANCE_DETAILS   => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantSavePolicyComplianceDetails',
        self::MERCHANT_POLICY_SECTION_PUBLISH_V2        => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantPolicySectionPublish',
        self::GET_MERCHANT_ONBOARDING_DOCS_VERIFICATION => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantOnboardingDocVerification',
        self::MERCHANT_GATING_LOGIC_SAVE       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantGatingLogic',
        self::PAYMENT_ORDER_CREATE             => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/PaymentOrderCreate',
        self::PAYMENT_ORDER_VERIFY             => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/PaymentOrderVerify',
        self::MERCHANT_FETCH_GATING_LOGIC      => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantGatingLogic',
        self::PAYMENT_ORDER_WEBHOOK            => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/PaymentOrderWebhook',
        self::GET_MERCHANT_ELIGIBILITY_FOR_AUTOMATION_ACTIVATION => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantEligibilityForAutomationActivation',
        self::MERCHANT_INVOICE_LOGIC_SAVE                  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SaveMerchantInvoiceLogic',
        self::MERCHANT_CONSENTS_SAVE                       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantConsentsSave',
        self::GENERATE_MERCHANT_IDENTITY_VERIFICATION_URL  => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GenerateMerchantIdentityVerificationUrl',
        self::PROCESS_MERCHANT_IDENTITY_VERIFICATION       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/ProcessMerchantIdentityVerification',
        self::MERCHANT_WEBSITE_SECTION_PAGE_LOAD_V2        => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/GetMerchantWebsitePolicyPreview',
        self::MERCHANT_CATEGORIES_V3                       => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantCategoriesV3Map',
        self::MERCHANT_CATEGORIES_ADMIN_V3                 => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/FetchMerchantCategoriesAdminV3Map',
        self::MERCHANT_CATEGORIES_V3_ELIGIBILITY_SAVE      => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/MerchantCategoriesV3EligibilitySave',
        self::SEND_SMS_OTP      => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/SendSMSOTP',
        self::VERIFY_OTP        => '/twirp/rzp.pg_onboarding.onboarding.v1.OnboardingService/VerifyOTP',
        self::PGOS_FETCH_DEVICE_CONFIG                      => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchDeviceConfig',
        self::PGOS_CREATE_DEVICE_ORDER                      => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/CreateDeviceOrder',
        self::PGOS_UPDATE_DEVICE_ORDER                      => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/UpdateDeviceOrder',
        self::PGOS_FETCH_DEVICE_ORDER                       => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchOrderById',
        self::PGOS_FETCH_ALL_DEVICE_ORDER                   => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchAllOrdersForMerchant',
        self::MERCHANT_POS_PAYMENT_CALLBACK                 => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/MerchantDevicePaymentCallback',
        self::MERCHANT_POS_FETCH_LATEST_ORDER               => '/twirp/rzp.pg_onboarding.external.pos.v1.DeviceManagementService/FetchLatestOrder',
        self::MERCHANT_FETCH_POS_ACTIVATION_FLOW            => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/FetchPosActivationFlow',
        self::PGOS_FETCH_PGOS_ACTIVATION_STATUS  =>  'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/GetPosActivationStatus',
        self::PGOS_UPDATE_PGOS_ACTIVATION_STATUS => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/UpdatePosActivationStatus',
        self::UPDATE_ACTION_STATE                => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/UpdateState',
        self::FETCH_ACTION_STATE_COUNT           => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/GetActionStateCount',
        self::MERCHANT_POS_STATE_LOGS            => 'twirp/rzp.pg_onboarding.external.pos.v1.PosActivationStatusService/GetActionStateLogs',
        self::POST_MERCHANT_CONFIG               => '/twirp/rzp.pg_onboarding.external.pos.v1.TerminalProcurementConsumerService/Consume',
    ];

    // timeout in seconds
    const PATH_TIMEOUT_MAP = [
        //merchant_document routes
        self::SAVE_MERCHANT_DOCUMENT_DETAILS   => 15,
        self::FETCH_MERCHANT_DOCUMENT_DETAILS  => 15,
        self::MERCHANT_DOCUMENT_VALIDITY_CHECK => 15,
        self::MERCHANT_ACTIVATION_SAVE                  => 15,
        self::MERCHANT_SIGN_UP                          => 20,
        self::MERCHANT_DOCUMENT_UPLOAD                  => 15,
        self::MERCHANT_GET_POLICY_COMPLIANCE_DETAILS    => 15,
        self::MERCHANT_SAVE_POLICY_COMPLIANCE_DETAILS   => 15,
        self::MERCHANT_WEBSITE_SECTION_PAGE_LOAD_V2     => 15,

        // TODO: Revert back once the root cause for OBS latency is found and fixed.
        // This is temporarily being increased to unblock curlec signup flows.
        // https://razorpay.slack.com/archives/C043K5N223F/p1700641894030849?thread_ts=1699005756.802759&cid=C043K5N223F
        self::SEND_OTP                                  => 20,
    ];

    const ROUTES_WITH_PGOS_EXPERIMENT_ALWAYS_ENABLE = [
        self::GET_MERCHANT_BMC_RESPONSE,
        self::SAVE_MERCHANT_BMC_RESPONSE,
        self::MERCHANT_UPDATE_BY_ADMIN,
        self::GET_MERCHANT_ONBOARDING_DOCS_VERIFICATION,
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
        self::MERCHANT_CATEGORIES_V3,
        self::MERCHANT_CATEGORIES_ADMIN_V3,
        self::MERCHANT_CATEGORIES_V3_ELIGIBILITY_SAVE,
        self::MERCHANT_RM_FETCH,
        self::MERCHANT_RM_CREATE,
        self::MERCHANT_RM_UPDATE
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
            default => null,
        };
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

        if ($mock === true)
        {
            return $this->pgosMockResponses($routeKey);
        }

        // check if for the merchant the experiment is enabled or not
        // check if merchant is a regular merchant or not

        if (in_array($routeKey, self::ROUTES_WITH_PGOS_EXPERIMENT_ALWAYS_ENABLE) and
            (new Core)->isRegularMerchant($merchant) === true)
        {
            $ignoreRoutingConditions = true;
        }

        if ($ignoreRoutingConditions or $this->shouldMerchantOnboardViaPGOS($merchantId, $merchant->getCountry()) or $this->shouldMerchantOnboardForPOS($merchant))
        {
            // get path from defined route url map
            $twirpPath = self::ROUTES_URL_MAP[$routeKey];

            $route = $this->getRoute($twirpPath);

            $headers = $this->getHeadersForDashboardRequest($payload, $merchantId);

            $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
                'route'     => $route,
                'twirpPath' => $twirpPath,
            ]);

            return $this->sendRequestAndParseResponse($routeKey, 'POST', $twirpPath, $payload, $headers);
        }

        return null;
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

        $body = $request->all();

        // get path from defined route url map
        $twirpPath = self::ROUTES_URL_MAP[$routeKey];

        $route = $this->getRoute($twirpPath);

        $headers = $this->getHeadersForDashboardRequest($body, $id);

        $this->trace->info(TraceCode::PGOS_DASHBOARD_PROXY_REQUEST, [
            'route'     => $route,
            'twirpPath' => $twirpPath,
            'request'   => $request,
        ]);

        try
        {
            $validationResponse = $this->routeSpecificPreValidations($routeKey, $body);

            if ($validationResponse['validated'] === true)
            {
                $this->routeSpecificPreProcessor($routeKey, $body);

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
        // Doing this check again to fall back
        if ($this->isPGOSExperimentEnabledForMerchant($merchantId, self::PGOS_LIVE_MODE_EXPERIMENT_ID,
                self::ENABLE) === true or $merchantCountryCode === 'MY')
        {
            $this->trace->info(TraceCode::PGOS_PROXY_REQUEST, [
                'shouldMerchantOnboardViaPGOS-merchantId' => $merchantId,
            ]);

            $userDeviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRole($merchantId);

            if (empty($userDeviceDetail) === false)
            {
                $merchantOnboardedViaService = $userDeviceDetail->getValueFromMetaData(DeviceDetailConstants::SERVICE);

                if (empty($merchantOnboardedViaService) === false)
                {
                    return $merchantOnboardedViaService === DeviceDetailConstants::SERVICE_PGOS;
                }
            }
        }

        return false;
    }

    public function shouldMerchantOnboardForPOS(Merchant\Entity $merchant)
    {

        $merchantDetails = $merchant->merchantDetail;

        $businessDetail = $merchantDetails->businessDetail;

        if (empty($businessDetail) === false)
        {
            $isPosMerchant = (new Merchant\Detail\Core())->isPOSMerchant($businessDetail);

            if ($isPosMerchant === true and $this->isPOSExperimentEnabledForCity($merchantDetails->getBusinessOperationCity()))
            {
                return true;
            }
        }

        return false;
    }

    public function isPOSExperimentEnabledForCity(string $city): bool{

        $properties = [
            'request_data' => "{\"city\": \"$city\"}",
            'experiment_id' => $this->app['config']->get('app.enable_routes_for_pos_merchant_exp_id'),
        ];

        $isExpEnabled =  (new Merchant\Core())->isSplitzExperimentEnable($properties, 'allow omni onboarding');

        if ($isExpEnabled === true)
        {
            $this->trace->info(TraceCode::POS_MERCHANT_ONBOARDING_REQUEST, [
                'message' => "POS merchant has requested for onboarding",
            ]);

            return true;
        }

        return false;
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

    private function routeSpecificPreProcessor(string $routeKey, array &$body)
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
               $response['validated'] = (new Merchant\Detail\Core())->isGatingWebhookRequest($body);

               break;
        }

        return $response;
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
