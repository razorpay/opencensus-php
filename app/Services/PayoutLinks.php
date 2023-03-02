<?php

namespace RZP\Services;

use Config;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Constants\Environment;
use RZP\Http\Request\Requests;
use RZP\Models\FundAccount\Type;
use RZP\Http\Response\StatusCode;
use RZP\Models\PayoutLink\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\PayoutLink\Validator;
use Illuminate\Support\Facades\Mail;
use Razorpay\Edge\Passport\Passport;
use RZP\Models\BankingAccountService;
use RZP\Models\Batch\Type as BatchType;
use RZP\Exception\BadRequestException;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Batch\Core as BatchCore;
use RZP\Models\User\Entity as UserEntity;
use RZP\Mail\PayoutLink\SendDemoLinkInternal;
use RZP\Models\Feature\Constants as Features;
use RZP\Mail\PayoutLink\CustomerDemoOtpInternal;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\WalletAccount\Entity as WalletAccountEntity;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

/**
 * Class PayoutLinks
 * @package RZP\Services
 *
 * No validations will happen here.
 * This will just call the right endpoints and return the responses as is
 * If there is an error thrown from the MicroService, that same error with
 * the right error code will be sent back to the caller
 *
 */
class PayoutLinks
{
    const KEY                                      = 'api';
    const SOURCE                                   = 'source';
    const STATUS                                   = 'status';
    const CONTACT                                  = 'contact';
    const BATCH_ID                                 = 'batch_id';
    const DESCRIPTION                              = 'description';
    const MERCHANT_ID                              = 'merchant_id';
    const PAYOUT_LINK_ID                           = 'payout_link_id';
    const STATUS_EXPIRED                           = 'expired';
    const STATUS_PROCESSED                         = 'processed';
    const STATUS_CANCELLED                         = 'cancelled';
    const STATUS_PENDING                           = 'pending';
    const STATUS_REJECTED                          = 'rejected';
    const EXPIRE_BY                                = 'expire_by';
    const EXPIRED_AT                               = 'expired_at';
    const USER_DETAILS                             = 'user_details';
    const INTEGRATION_INFO                         = 'integration_info';
    const PENDING_ON_ROLES                         = 'pending_on_roles';
    const SOURCE_IDENTIFIER                        = 'source_identifier';
    const IS_EXPIRY_ENABLED                        = 'is_expiry_enabled';
    const REMINDER_ENTITY_ID                       = 'reminder_entity_id';
    const IS_CORRECT_MERCHANT                      = 'is_correct_merchant';
    const IS_DASHBOARD_REQUEST                     = 'is_dashboard_request';
    const IS_INTEGRATION_SUCCESS                   = 'is_integration_success';
    const APPROVE_WORKFLOW_PATH                    = 'twirp/payoutlinks.Payoutlinks/ApproveAction';
    const REJECT_WORKFLOW_PATH                     = 'twirp/payoutlinks.Payoutlinks/RejectAction';
    const WORKFLOW_SUMMARY_PATH                    = 'twirp/payoutlinks.Payoutlinks/WorkflowSummary';
    const BULK_APPROVE_PATH                        = 'twirp/payoutlinks.Payoutlinks/ApproveBulkPayoutLinks';
    const BULK_REJECT_PATH                         = 'twirp/payoutlinks.Payoutlinks/RejectBulkPayoutLinks';
    const APPROVE_OTP_PATH                         = 'twirp/payoutlinks.Payoutlinks/OtpForApproval';
    const BULK_APPROVE_OTP_PATH                    = 'twirp/payoutlinks.Payoutlinks/OtpForBulkApproval';
    const UPLOAD_ATTACHMENT_PATH                   = 'twirp/payoutlinks.Payoutlinks/UploadAttachment';
    const GET_SIGNED_URL_PATH                      = 'twirp/payoutlinks.Payoutlinks/GetSignedUrlForAttachment';
    const UPDATE_ATTACHMENTS_PATH                  = 'twirp/payoutlinks.Payoutlinks/UpdateAttachments';
    const INTEGRATE_APP_PATH                       = 'twirp/payoutlinks.Payoutlinks/IntegrateApp';
    const EXPIRE_CALLBACK_PATH                     = 'twirp/payoutlinks.Payoutlinks/ExpireCallback';
    const UPDATE_PAYOUT_LINK_PATH                  = 'twirp/payoutlinks.Payoutlinks/UpdatePayoutLink';
    const SEND_REMINDER_CALLBACK_PATH              = 'twirp/payoutlinks.Payoutlinks/SendReminderCallback';
    const SHOPIFY_INSTALL_PATH                     = 'twirp/payoutlinks.Payoutlinks/GetShopifyAppInstallRedirectURI';
    const GET_PENDING_LINKS_META_FOR_EMAIL         = 'twirp/payoutlinks.Payoutlinks/GetPendingLinksMetaForEmail';
    const SHOPIFY_UNINSTALL_PATH                   = 'twirp/payoutlinks.Payoutlinks/UninstallShopifyApp';
    const SHOPIFY_GET_ORDER_DETAILS_PATH           = 'twirp/payoutlinks.Payoutlinks/GetShopifyOrderDetails';
    const CREATE_PAYOUT_LINK_PATH                  = 'twirp/payoutlinks.Payoutlinks/CreatePayoutLink';
    const CREATE_DEMO_PAYOUT_LINK_PATH             = 'twirp/payoutlinks.Payoutlinks/CreateDemoPayoutLink';
    const CANCEL_PAYOUT_LINK_PATH                  = 'twirp/payoutlinks.Payoutlinks/CancelPayoutLink';
    const FETCH_PAYOUT_LINK_PATH                   = 'twirp/payoutlinks.Payoutlinks/FetchPayoutLink';
    const FETCH_PAYOUT_LINK_MULTIPLE_PATH          = 'twirp/payoutlinks.Payoutlinks/FetchMultiplePayoutLinks';
    const FETCH_PAYOUT_LINKS_SUMMARY_PATH          = 'twirp/payoutlinks.Payoutlinks/FetchPayoutLinksSummary';
    const GET_SETTINGS_PAYOUT_LINK_PATH            = 'twirp/payoutlinks.Payoutlinks/GetSettings';
    const UPDATE_SETTINGS_PAYOUT_LINK_PATH         = 'twirp/payoutlinks.Payoutlinks/UpdateSettings';
    const PAYOUT_LINK_GENERATE_OTP_PATH            = 'twirp/payoutlinks.Payoutlinks/GenerateOTP';
    const PAYOUT_LINK_GENERATE_OTP_DEMO_PATH       = 'twirp/payoutlinks.Payoutlinks/GenerateDemoOTP';
    const RESEND_BULK_NOTIFICATION_PATH            = 'twirp/payoutlinks.Payoutlinks/ResendBulkNotification';
    const GET_INTEGRATION_DETAILS_PATH             = 'twirp/payoutlinks.Payoutlinks/GetIntegrationDetails';
    const PAYOUT_LINK_GET_FUND_ACCOUNTS_BY_CONTACT = 'twirp/payoutlinks.Payoutlinks/GetFundAccountsByContact';
    const PAYOUT_LINK_VERIFY_OTP_PATH              = 'twirp/payoutlinks.Payoutlinks/VerifyOTP';
    const PAYOUT_LINK_VERIFY_OTP_DEMO_PATH         = 'twirp/payoutlinks.Payoutlinks/VerifyDemoOTP';
    const PAYOUT_STATUS_UPDATE                     = 'twirp/payoutlinks.Payoutlinks/UpdatePayoutLinkStatus';
    const INITIATE_PAYOUT_LINK_PATH                = 'twirp/payoutlinks.Payoutlinks/InitiatePayoutLink';
    const INITIATE_DEMO_PAYOUT_LINK_PATH           = 'twirp/payoutlinks.Payoutlinks/InitiateDemoPayoutLink';
    const GET_HOSTED_PAGE_DATA                     = 'twirp/payoutlinks.Payoutlinks/GetHostedPageData';
    const GET_DEMO_HOSTED_PAGE_DATA                = 'twirp/payoutlinks.Payoutlinks/GetDemoHostedPage';
    const RESEND_NOTIFICATION                      = 'twirp/payoutlinks.Payoutlinks/ResendNotification';
    const ON_BOARDING_STATUS                       = 'twirp/payoutlinks.Payoutlinks/OnboardingStatus';
    const CREATE_BATCH                             = 'twirp/payoutlinks.Payoutlinks/CreateBatchPayoutLinks';
    const EXPIRE_FIX_CRON_JOB                      = 'twirp/payoutlinks.Payoutlinks/ExpireFixCronJob';
    const BATCH_SUMMARY                            = 'twirp/payoutlinks.Payoutlinks/GetBatchSummary';
    const SUMMARY                                  = 'twirp/payoutlinks.Payoutlinks/Summary';
    const ADMIN_ACTIONS                            = 'twirp/payoutlinks.Payoutlinks/AdminActions';
    const BATCH_PL_PROCESSED                       = 'batch_payout_links_processed';
    const BATCH_PL_INITIATED                       = 'batch_payout_links_initiated';
    const BATCH_PL_EXPIRED                         = 'batch_payout_links_expired';
    const BATCH_PL_PENDING                         = 'batch_payout_links_pending';
    const BATCH_PL_COUNT                           = 'batch_payout_links_count';
    const BATCH_REQUEST_ROWS                       = 'batch_request_rows';
    const FUND_ACCOUNT                             = 'fund_account';
    const FUND_ACCOUNT_ID                          = 'fund_account_id';
    const ACCOUNT_NUMBER                           = 'account_number';
    const CANCELLED_AT                             = 'cancelled_at';
    const REDIRECT_URI                             = 'redirect_uri';
    const UPDATED_AT                               = 'updated_at';
    const REMINDERS                                = 'reminders';
    const SEND_SMS                                 = 'send_sms';
    const SEND_EMAIL                               = 'send_email';
    const ATTEMPT_COUNT                            = 'attempt_count';
    const PAYOUTS                                  = 'payouts';
    const USER_ID                                  = 'user_id';
    const USER                                     = 'user';
    const RECEIPT                                  = 'receipt';
    const MODE                                     = 'mode';
    const NOTES                                    = 'notes';
    const COUNT                                    = 'count';
    const ITEMS                                    = 'items';
    const USER_ROLE                                = 'user_role';
    const USER_EMAIL                               = 'user_email';
    const USER_NAME                                = 'user_name';
    const USER_TYPE                                = 'user_type';
    const USER_CONTACT                             = 'user_contact';
    const MERCHANT_FEATURES                        = 'merchant_features';
    const IS_WORKFLOW_ENABLED                      = 'is_workflow_enabled';
    const IS_SKIP_WORKFLOW_FOR_PL_API              = 'is_skip_workflow_for_pl_api';
    const REJECTED_AT                              = 'rejected_at';
    const ISSUED_AT                                = 'issued_at';
    const PENDING_ON_USER                          = 'pending_on_user';
    const WORKFLOW_HISTORY                         = 'workflow_history';
    const TOTAL_AMOUNT                             = 'total_amount';

    const INVALID_REQUEST_ERROR_MSG                = 'the json request could not be decoded';
    const INVALID_REQUEST_RESPONSE_MSG             = 'Invalid request payload';
    const TEST_MODE_ERROR_MESSAGE                  = 'Test Mode is currently not supported for Payout Links';

    const DASHBOARD_INTERNAL                       = 'DASHBOARD_INTERNAL';

    const X_SHOPIFY_TOPIC                          = 'x_shopify_topic';
    const X_SHOPIFY_HMAC_SHA256                    = 'x_shopify_hmac';
    const X_SHOPIFY_SHOP_DOMAIN                    = 'x_shopify_shop_domain';
    const X_SHOPIFY_API_VERSION                    = 'x_shopify_api_version';
    const X_SHOPIFY_WEBHOOK_ID                     = 'x_shopify_webhook_id';

    const X_APP_MODE                               = 'X-App-Mode';

    const POST                                     = 'POST';
    const X_RAZORPAY_MODE                          = 'X-Razorpay-Mode';
    const INTEGRATION_STATUS                       = 'integration_status';
    const NOT_INITIATED                            = 'not-initiated';

    const EXPAND                                   = 'expand';

    const TDS                                      = 'tds';
    const ATTACHMENTS                              = 'attachments';
    const FILE_ID                                  = 'file_id';
    const MIME_TYPE                                = 'mime_type';
    const FILE                                     = 'file';
    const FILE_NAME                                = 'file_name';
    const SUBTOTAL_AMOUNT                          = 'subtotal_amount';
    const AMOUNT                                   = 'amount';
    const META                                     = 'meta';
    const TAX_PAYMENT_ID                           = 'tax_payment_id';

    const ACCOUNT_NUMBERS                          = 'account_numbers';
    const PAYOUT_LINKS_SUMMARY                     = 'payout_links_summary';

    public static $statusValidForSupportDetailsInHostedPage = [
        self::STATUS_EXPIRED,
        self::STATUS_PENDING,
        self::STATUS_REJECTED,
    ];

    protected $baseUrl;

    protected $secret;

    protected $repo;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    protected $merchant;

    protected $app;

    protected $walletService;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $payoutLinkConfig = $this->config->get('applications.payout_links');

        $this->baseUrl = $payoutLinkConfig['micro_service_endpoint'];

        $this->secret  = $payoutLinkConfig['secret'];

        $this->timeout  = $payoutLinkConfig['timeout'];

        $this->repo  = $app['repo'];

        $this->app = $app;
    }

    protected function getMode()
    {
        $mode = Mode::LIVE;

        if (isset($this->app['rzp.mode']))
        {
            $mode = $this->app['rzp.mode'];
        }

        return $mode;
    }

    protected function isWorkflowEnabledForPLMerchant(MerchantEntity $merchant)
    {
        $payoutWorkflowsFlag = $merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS);

        if($payoutWorkflowsFlag === false)
        {
            return false;
        }

        //blacklisted_merchants => off
        //otherwise => on
        $variant = $this->app['razorx']->getTreatment($merchant->getId(),
            Merchant\RazorxTreatment::RX_PAYOUT_LINK_WORKFLOW_GA,
            $this->app['rzp.mode'] ?? 'live', 3);

        return ($variant === 'on');
    }

    public function create(MerchantEntity $merchant, array $input): array
    {
        $url = sprintf('%s/%s', $this->baseUrl, self::CREATE_PAYOUT_LINK_PATH);

        $sendSms = array_pull($input, self::SEND_SMS, "false");

        $sendMail = array_pull($input, self::SEND_EMAIL, "false");

        if(array_key_exists(self::NOTES, $input) === true)
        {
            $notes = $input[self::NOTES];

            if(is_array($notes) === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY, null, null);
            }
        }

        $input[self::MERCHANT_ID] = $merchant->getId();

        $input[self::SEND_SMS] = strval($sendSms);

        $input[self::SEND_EMAIL] = strval($sendMail);

        $input[self::MERCHANT_FEATURES] = [
            self::IS_SKIP_WORKFLOW_FOR_PL_API   => $merchant->isFeatureEnabled(Features::SKIP_WORKFLOWS_FOR_API),
            self::IS_WORKFLOW_ENABLED           => $this->isWorkflowEnabledForPLMerchant($merchant)
        ];

        $response = $this->makeRequest($url, $input, [], self::POST, $this->getMode());

        $expandArray = [0 => self::USER];

        $this->processParameters($response, false, $expandArray);

        return $response;
    }

    protected function prepareActionInput(array $input, string $payoutLinkId, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        $input[self::MERCHANT_ID] = $merchant->getMerchantId();

        $input[self::USER_DETAILS] = $this->getUserDetails($user, $userRole);

        return $input;
    }

    protected function prepareBulkActionInput(array $input, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $input[self::MERCHANT_ID] = $merchant->getMerchantId();

        $input[self::USER_ID] = $user->getUserId();

        $input[self::USER_DETAILS] = $this->getUserDetails($user, $userRole);

        return $input;
    }

    public function approvePayoutLink(string $payoutLinkId, array $input, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $this->rzpModeCheck($merchant->getId());

        $url = $this->getConstructedUrl(self::APPROVE_WORKFLOW_PATH);

        $input = $this->prepareActionInput($input, $payoutLinkId, $merchant, $user, $userRole);

        $this->makeRequest($url, $input);
    }

    public function rejectPayoutLink(string $payoutLinkId, array $input, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $this->rzpModeCheck($merchant->getId());

        $url = $this->getConstructedUrl(self::REJECT_WORKFLOW_PATH);

        $input = $this->prepareActionInput($input, $payoutLinkId, $merchant, $user, $userRole);

        $this->makeRequest($url, $input);
    }

    public function workflowSummary(MerchantEntity $merchant, string $userRole)
    {
        $url = $this->getConstructedUrl(self::WORKFLOW_SUMMARY_PATH);

        $request = [
            self::MERCHANT_ID => $merchant->getMerchantId(),
            self::USER_ROLE => $userRole,
        ];

        $response = $this->makeRequest($url, $request);

        $response[self::COUNT] = array_pull($response, self::COUNT, 0);

        $response[self::TOTAL_AMOUNT] = array_pull($response, self::TOTAL_AMOUNT, 0);

        return $response;
    }

    public function approveBulkPayoutLinks(array $input, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $this->rzpModeCheck($merchant->getId());

        $url = $this->getConstructedUrl(self::BULK_APPROVE_PATH);

        $input = $this->prepareBulkActionInput($input, $merchant, $user, $userRole);

        $this->makeRequest($url, $input);
    }

    public function rejectBulkPayoutLinks(array $input, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $this->rzpModeCheck($merchant->getId());

        $url = $this->getConstructedUrl(self::BULK_REJECT_PATH);

        $input = $this->prepareBulkActionInput($input, $merchant, $user, $userRole);

        $this->makeRequest($url, $input);
    }

    public function approvePayoutLinkOtp(string $payoutLinkId, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $this->rzpModeCheck($merchant->getId() ?? "");

        $url = $this->getConstructedUrl(self::APPROVE_OTP_PATH);

        $userDetails = $this->getUserDetails($user, $userRole);

        $input = [
            self::PAYOUT_LINK_ID => $payoutLinkId,
            self::USER_DETAILS   => $userDetails,
        ];

        return $this->makeRequest($url, $input);
    }

    public function approveBulkPayoutLinksOtp(array $input, MerchantEntity $merchant, UserEntity $user, string $userRole)
    {
        $this->rzpModeCheck($merchant->getId());

        $url = $this->getConstructedUrl(self::BULK_APPROVE_OTP_PATH);

        $userDetails = $this->getUserDetails($user, $userRole);

        $input[self::MERCHANT_ID] = $merchant->getId();

        $input[self::USER_DETAILS] = $userDetails;

        return $this->makeRequest($url, $input);
    }

    public function getSignedUrl(MerchantEntity $merchant, string $payoutLinkId, string $fileId)
    {
        $url = $this->getConstructedUrl(self::GET_SIGNED_URL_PATH);

        $input[self::MERCHANT_ID] = $merchant->getId();

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        $input[self::FILE_ID] = $fileId;

        return $this->makeRequest($url, $input, [], self::POST, $this->getMode());
    }

    public function uploadAttachment(MerchantEntity $merchant, array $input)
    {
        $url = $this->getConstructedUrl(self::UPLOAD_ATTACHMENT_PATH);

        $input[self::FILE] = base64_encode(file_get_contents($_FILES['file']['tmp_name']));

        $input[self::FILE_NAME] = $_FILES['file']['name'];

        $input[self::MIME_TYPE] = $_FILES['file']['type'];

        $input[self::MERCHANT_ID] = $merchant->getId();

        return $this->makeRequest($url, $input, [], self::POST, $this->getMode());
    }

    public function updateAttachments(string $payoutLinkId, array $input)
    {
        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        $url = $this->getConstructedUrl(self::UPDATE_ATTACHMENTS_PATH);

        return $this->makeRequest($url, $input, [], self::POST, $this->getMode());
    }

    public function fetchPendingPayoutLinks(string $merchantId)
    {
        $input = [
            self::MERCHANT_ID => $merchantId,
            self::STATUS      => 'pending',
        ];

        return $this->fetchMultiple($input);
    }

    protected function getUserDetails(UserEntity $user, string $userRole)
    {
        return [
            self::USER_ID => $user->getId(),

            self::USER_ROLE => $userRole,

            self::USER_TYPE => self::USER,

            self::USER_EMAIL => $user->getEmail(),

            self::USER_NAME => $user->getName(),

            self::USER_CONTACT => $user->getContactMobile(),
        ];
    }

    public function getSettings(string $merchantId)
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_SETTINGS_GET,
            [
                $merchantId
            ]);

        $url = $this->getConstructedUrl(self::GET_SETTINGS_PAYOUT_LINK_PATH);

        $request = [
            self::MERCHANT_ID => $merchantId
        ];

        $response = $this->makeRequest($url, $request, [], self::POST, $this->getMode());

        $settings = array_pull($response, self::MODE, []);

        $this->processSettingsParameters($settings);

        return $settings;
    }

    public function updateSettings(string $merchantId, array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_SETTINGS_GET,
            [
                $merchantId
            ]);

        $url = $this->getConstructedUrl(self::UPDATE_SETTINGS_PAYOUT_LINK_PATH);

        $request = [
            self::MERCHANT_ID  => $merchantId,
            self::MODE         => $input
        ];

        $oldSettings = $this->getSettings($merchantId);

        $response = $this->makeRequest($url, $request, [], self::POST, $this->getMode());

        $newSettings = array_pull($response, self::MODE, []);

        $this->processSettingsParameters($newSettings);

        $this->notifySettingsChangeOnSlack($merchantId, $oldSettings, $newSettings);

        return $newSettings;
    }

    public function cancel(string $payoutLinkId, string $merchantId)
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_CANCEL_REQUEST,
            [
                $payoutLinkId
            ]);

        $url = $this->getConstructedUrl(self::CANCEL_PAYOUT_LINK_PATH);

        $request = [
            self::PAYOUT_LINK_ID => $payoutLinkId,
            self::MERCHANT_ID    => $merchantId
        ];

        $response = $this->makeRequest($url, $request, [], self::POST, $this->getMode());

        $this->processParameters($response);

        return $response;
    }

    public function fetch(string $payoutLinkId, array $input, string $merchantId = "")
    {
        $forAdminResponse = true;

        $url = $this->getConstructedUrl(self::FETCH_PAYOUT_LINK_PATH);

        $input[self::PAYOUT_LINK_ID] = $this->appendPublicSignForPayoutLink($payoutLinkId);

        $isDashboardRequest = $this->app['basicauth']->isDashboardApp();

        $input[self::IS_DASHBOARD_REQUEST] = $isDashboardRequest;

        if($merchantId != "")
        {
            $forAdminResponse = false;
            $input[self::MERCHANT_ID] = $merchantId;
        }

        $expandArray = array_pull($input, 'expand', []);

        $input['expand'] = $expandArray;

        $response = $this->makeRequest($url, $input, [], self::POST, $this->getMode());

        $this->processParameters($response, $forAdminResponse, $expandArray, $isDashboardRequest);

        $this->processReminderTimelineParameter($response, $isDashboardRequest);

        return $response;
    }

    public function fetchMultiple(array $input)
    {
        $url = $this->getConstructedUrl(self::FETCH_PAYOUT_LINK_MULTIPLE_PATH);

        if(key_exists('id', $input))
        {
            $payoutlinkid = $input['id'];
            $payoutlinkid = $this->appendPublicSignForPayoutLink($payoutlinkid);

            $input[self::PAYOUT_LINK_ID] = $payoutlinkid;
        }

        $expandArray = [];

        if (key_exists('expand', $input))
        {
            $expandArray = $input['expand'];
        }

        $response = $this->makeRequest($url, $input, [], self::POST, $this->getMode());

        $response[self::COUNT] = array_pull($response, self::COUNT, 0);

        $response[self::ITEMS] = array_pull($response, self::ITEMS, []);

        $payoutlinks = &$response["items"];

        $isDashboardRequest = $this->app['basicauth']->isDashboardApp();

        foreach ($payoutlinks as &$value)
        {
            $this->processParameters($value, false, $expandArray, $isDashboardRequest);
        }

        return $response;
    }

    public function getModeAndMerchant(string $payoutLinkId)
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_GET_MODE_AND_MERCHANT,
            [
                $payoutLinkId
            ]);

        $mode = $this->getModeForPublicPage();

        $url = sprintf('%s/%s', $this->baseUrl, self::FETCH_PAYOUT_LINK_PATH);

        $request = [
            self::PAYOUT_LINK_ID        => 'poutlk_' . $payoutLinkId,
            self::IS_DASHBOARD_REQUEST  => false,
            self::EXPAND                => []
        ];

        $response =  $this->makeRequest($url, $request, [], self::POST, $mode);

        return [$mode, $response[self::MERCHANT_ID]];
    }

    public function initiate(MerchantEntity $merchant, array $input, string $payoutLinkId): array
    {
        $mode = $this->getModeForPublicPage();

        $url = sprintf('%s/%s', $this->baseUrl, self::INITIATE_PAYOUT_LINK_PATH);

        $input[self::MERCHANT_ID] = $merchant->getId();

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input, [], self::POST, $mode);
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId, array $input): array
    {
        $this->headerCheckForRequestsInCustomerFacingPage();

        $url = $this->getConstructedUrl(self::PAYOUT_LINK_GENERATE_OTP_PATH);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input);
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input): array
    {
        $mode = $this->getModeForPublicPage();

        $url = $this->getConstructedUrl(self::PAYOUT_LINK_GET_FUND_ACCOUNTS_BY_CONTACT);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        $response = $this->makeRequest($url, $input, [], self::POST, $mode);

        $response[self::COUNT] = array_pull($response, self::COUNT, 0);

        $response[self::ITEMS] = array_pull($response, self::ITEMS, []);

        return $response;
    }

    public function getHostedPageData(string $payoutLinkId, MerchantEntity $merchant = null)
    {
        $mode = $this->getModeForPublicPage();

        $url = sprintf('%s/%s', $this->baseUrl, self::GET_HOSTED_PAGE_DATA);

        $request = [
            self::PAYOUT_LINK_ID => $payoutLinkId
        ];

        $response = $this->makeRequest($url, $request, [], self::POST, $mode);

        $payoutUtr = null;

        $payoutMode = null;

        $payoutLinkInfo = $response['payout_link_response'];

        $merchant = $merchant ?: $this->repo->merchant->connection($mode)->findOrFail($payoutLinkInfo[self::MERCHANT_ID]);

        $keylessHeader = $this->getKeylessHeader($merchant->getId(), $mode);

        if (key_exists('payouts', $payoutLinkInfo)
            && key_exists('count', $payoutLinkInfo['payouts']))
        {
            $payoutsCount = $payoutLinkInfo['payouts']['count'];

            if ($payoutsCount > 0)
            {
                $payouts = $payoutLinkInfo['payouts']['items'];

                $payoutUtr = mask_by_percentage($payouts[0]['utr']);

                $payoutMode = $payouts[0]['mode'];
            }
        }

        $settings = array_pull($response['settings'], self::MODE, []);

        $bankingAccount = $this->getBankingAccountInfo($merchant, $payoutLinkInfo['account_number']);

        if ($bankingAccount === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NO_BANKING_ACCOUNT_FOR_PAYOUT_LINK);
        }

        $allowUpi = $this->allowUpi($payoutLinkInfo, $settings, $merchant, $bankingAccount);

        // allow amazon pay
        $allowAmazonPay = $this->allowAmazonPay($payoutLinkInfo, $settings, $merchant, $bankingAccount);

        $isProduction = $this->getEnvironment() === Environment::PRODUCTION;

        $fundAccountDetails = $this->extractFundAccountDetails($payoutLinkInfo, $merchant);

        $contact = $payoutLinkInfo[self::CONTACT];

        $description =  $payoutLinkInfo[self::DESCRIPTION] ?? null;

        $plStatus = $payoutLinkInfo[self::STATUS];

        // if the payout link is processed/cancelled, description should be masked
        if(empty($description) == false
            and empty($plStatus) == false
            and ($plStatus == self::STATUS_PROCESSED or $plStatus == self::STATUS_CANCELLED))
        {
            $description = mask_by_percentage($description);
        }

        $expireBy = array_pull($payoutLinkInfo, self::EXPIRE_BY, 0);

        $expiredAt = array_pull($payoutLinkInfo, self::EXPIRED_AT, 0);

        $supportPhone = '';

        $supportMail = '';

        // we want the support details in the final response only if payout link has expired, pending, rejected
        // for other status we don't want as of now coz adding it for all status will lead to the security concern
        if ((empty($plStatus) === false) and (in_array($plStatus, self::$statusValidForSupportDetailsInHostedPage) == true))
        {
            $supportPhone = array_pull($settings, Entity::SUPPORT_CONTACT, '');

            $supportMail = array_pull($settings, Entity::SUPPORT_EMAIL, '');
        }

        $data = [
            'api_host'                    => $this->config['url.api.production'],
            'payout_link_id'              => $payoutLinkInfo['id'],
            'payout_link_status'          => $payoutLinkInfo['status'],
            'amount'                      => $payoutLinkInfo['amount'],
            'currency'                    => $payoutLinkInfo['currency'],
            'description'                 => $description,
            'user_name'                   => mask_by_percentage($contact['name'] ?? null),
            'user_email'                  => mask_email($contact['email'] ?? null),
            'user_phone'                  => mask_phone($contact['contact'] ?? null),
            'receipt'                     => $payoutLinkInfo['receipt'] ?? null,
            'merchant_logo_url'           => $merchant->getFullLogoUrlWithSize(),
            'primary_color'               => $merchant->getBrandColorElseDefault(),
            'merchant_name'               => $merchant->getBillingLabel(),
            'allow_upi'                   => $allowUpi,
            'allow_amazon_pay'            => $allowAmazonPay,
            'banking_url'                 => $this->config['applications.banking_service_url'],
            'is_production'               => $isProduction,
            'fund_account_details'        => json_encode($fundAccountDetails),
            'purpose'                     => $payoutLinkInfo['purpose'] ?? null,
            'payout_utr'                  => $payoutUtr,
            'payout_mode'                 => $payoutMode,
            'payout_links_custom_message' => $settings[Entity::CUSTOM_MESSAGE] ?? null,
            'expire_by'                   => $expireBy,
            'expired_at'                  => $expiredAt,
            'support_phone'               => $supportPhone,
            'support_email'               => $supportMail,
            'keyless_header'              => $keylessHeader,
        ];

        return $data;
    }

    protected function getEnvironment() {
        return $this->app->environment();
    }

    public function pushPayoutStatus($payoutLinkId, $payoutId, $payoutStatus, $mode = Mode::LIVE)
    {
        $input = [
            'payout_link_id'  => $payoutLinkId,
            'payout_id'       => $payoutId,
            'payout_status'   => strtoupper($payoutStatus)
        ];

        $url = $this->getConstructedUrl(self::PAYOUT_STATUS_UPDATE);

        return $this->makeRequest($url, $input, [], self::POST, $mode);
    }

    public function verifyCustomerOtp(string $payoutLinkId, array $input): array
    {
        $mode = $this->getModeForPublicPage();

        $url = $this->getConstructedUrl(self::PAYOUT_LINK_VERIFY_OTP_PATH);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input, [], self::POST, $mode);
    }

    public function resendNotification(string $payoutLinkId, array $input)
    {
        $this->rzpModeCheck();

        $url = $this->getConstructedUrl(self::RESEND_NOTIFICATION);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;
        if (key_exists(self::SEND_SMS, $input) === true)
        {
            if(is_bool($input[self::SEND_SMS]))
            {
                $input[self::SEND_SMS] = $input[self::SEND_SMS] ? 'true' : 'false';
            }
            else
            {
                $input[self::SEND_SMS] = strval($input[self::SEND_SMS]);
            }
        }
        if (key_exists(self::SEND_EMAIL, $input) === true)
        {
            if(is_bool($input[self::SEND_EMAIL]))
            {
                $input[self::SEND_EMAIL] = $input[self::SEND_EMAIL] ? 'true' : 'false';
            }
            else
            {
                $input[self::SEND_EMAIL] = strval($input[self::SEND_EMAIL]);
            }
        }

        return $this->makeRequest($url, $input);
    }

    public function onBoardingStatus(string $merchantId)
    {
        $url = $this->getConstructedUrl(self::ON_BOARDING_STATUS);

        $request = [
            self::MERCHANT_ID => $merchantId
        ];

        return $this->makeRequest($url, $request);
    }

    public function summary(string $merchantId)
    {
        $url = $this->getConstructedUrl(self::SUMMARY);

        $request = [
            self::MERCHANT_ID => $merchantId
        ];

        return $this->makeRequest($url, $request, [], self::POST, $this->getMode());
    }

    public function adminActions(array $input)
    {
        $this->rzpModeCheck();

        $jsonInput = array_pull($input, 'json_data', null);

        if ($jsonInput === null )
        {
            return ['message' => 'empty data'];
        }

        $parsedData = json_decode($jsonInput, true);

        if ($parsedData == null)
        {
            return ['message' => 'json could not be decoded'];
        }

        $url = $this->getConstructedUrl(self::ADMIN_ACTIONS);

        return $this->makeRequest($url, $parsedData);
    }

    public function processBatch(array $input)
    {
        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;

        $userId = $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID) ?? null;

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        if (empty($batchId) === true)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_BATCH_ID_MISSING_FOR_PAYOUT_LINK_PROCESS_BATCH
            );
        }

        $this->trace->info(TraceCode::PAYOUT_LINK_PROCESS_BATCH_REQUEST,
            [
                self::MERCHANT_ID => $merchantId,
                self::BATCH_ID => $batchId,
                'temp_user_id'=> $userId,
            ]);

        $request[self::BATCH_ID] = $batchId;

        $request[self::MERCHANT_ID] = $merchantId;

        $request[self::BATCH_REQUEST_ROWS] = $input;

        $mode = Mode::LIVE;
        if(sizeof($input) > 0)
        {
            $mode = $input[0]['mode'];
        }
        else
        {
            $this->trace->info(TraceCode::PAYOUT_LINKS_BATCH_PROCESSING_FOR_EMPTY_INPUT, [
                'merchant_id' => $merchantId,
                'batch_id'    => $batchId,
            ]);
        }

        $merchant = $this->repo->merchant->connection($mode)->findOrFail($merchantId);

        $request[self::MERCHANT_FEATURES] = [
            self::IS_WORKFLOW_ENABLED   => $this->isWorkflowEnabledForPLMerchant($merchant)
        ];

        $url = $this->getConstructedUrl(self::CREATE_BATCH);

        return $this->makeRequest($url, $request, [], self::POST, $mode);
    }

    public function getBatchSummary(string $merchantId, string $batchId)
    {
        $request[self::BATCH_ID] = $batchId;

        $request[self::MERCHANT_ID] = $merchantId;

        $url = $this->getConstructedUrl(self::BATCH_SUMMARY);

        $response = $this->makeRequest($url, $request, [], self::POST, $this->getMode());

        $response[self::BATCH_PL_COUNT] = array_pull($response, self::BATCH_PL_COUNT, 0);

        $response[self::BATCH_PL_INITIATED] = array_pull($response, self::BATCH_PL_INITIATED, 0);

        $response[self::BATCH_PL_PROCESSED] = array_pull($response, self::BATCH_PL_PROCESSED, 0);

        $response[self::BATCH_PL_EXPIRED] = array_pull($response, self::BATCH_PL_EXPIRED, 0);

        $response[self::BATCH_PL_PENDING] = array_pull($response, self::BATCH_PL_PENDING, 0);

        return $response;
    }

    public function getShopifyAppInstallRedirectURI(array $input)
    {
        $url = $this->getConstructedUrl(self::SHOPIFY_INSTALL_PATH);

        $data['input'] = $_SERVER['QUERY_STRING'];

        $response = $this->makeRequest($url, $data);

        return $response[self::REDIRECT_URI];
    }

    public function uninstallShopifyApp(array $input)
    {
        $header[self::X_SHOPIFY_TOPIC] = $this->app['request']->header('X-Shopify-Topic');

        $header[self::X_SHOPIFY_HMAC_SHA256] = $this->app['request']->header('X-Shopify-Hmac-Sha256');

        $header[self::X_SHOPIFY_SHOP_DOMAIN] = $this->app['request']->header('X-Shopify-Shop-Domain');

        $header[self::X_SHOPIFY_API_VERSION] = $this->app['request']->header('X-Shopify-API-Version');

        $header[self::X_SHOPIFY_WEBHOOK_ID] = $this->app['request']->header('X-Shopify-Webhook-Id');

        $data['header'] = $header;

        $data['input'] = file_get_contents("php://input");

        /*$calculatedHmac = base64_encode(hash_hmac('sha256', json_encode($input, true), 'secret', true));*/

        $url = $this->getConstructedUrl(self::SHOPIFY_UNINSTALL_PATH);

        $this->makeRequest($url, $data);
    }

    public function integrateApp(array $input, MerchantEntity $merchant)
    {
        $this->rzpModeCheck($merchant->getId());

        $merchantId = array_pull($input, self::MERCHANT_ID, $merchant->getId());

        $request[self::MERCHANT_ID] = $merchantId;

        $request[self::SOURCE] = array_pull($input, self::SOURCE, "");

        $request[self::SOURCE_IDENTIFIER] = array_pull($input, self::SOURCE_IDENTIFIER, "");

        $request[self::INTEGRATION_INFO] = array_except(
            $input,
            [
                self::SOURCE,
                self::MERCHANT_ID,
                self::SOURCE_IDENTIFIER
            ]);

        $url = $this->getConstructedUrl(self::INTEGRATE_APP_PATH);

        $response = $this->makeRequest($url, $request);

        $response[self::IS_INTEGRATION_SUCCESS] = array_pull($response, self::IS_INTEGRATION_SUCCESS, false);

        return $response;
    }

    public function fetchShopifyOrderDetails(array $input, MerchantEntity $merchant)
    {
        $this->rzpModeCheck($merchant->getId());

        $input[self::MERCHANT_ID] = $merchant->getId();

        $url = $this->getConstructedUrl(self::SHOPIFY_GET_ORDER_DETAILS_PATH);

        $response = $this->makeRequest($url, $input);

        $response[self::IS_CORRECT_MERCHANT] = array_pull($response, self::IS_CORRECT_MERCHANT, false);

        return $response;
    }

    public function bulkResendNotification(array $input)
    {
        $this->rzpModeCheck();

        $url = $this->getConstructedUrl(self::RESEND_BULK_NOTIFICATION_PATH);

        $response = $this->makeRequest($url, $input);

        return $response;
    }

    public function integrationDetails(array $input, MerchantEntity $merchant)
    {
        if($this->getMode() === Mode::TEST)
        {
            return [
                self::MERCHANT_ID          => $merchant->getId(),
                self::INTEGRATION_STATUS   => self::NOT_INITIATED,
            ];
        }

        $this->trace->info(TraceCode::PAYOUT_LINKS_INTEGRATION_DETAILS_REQUEST,
            [
                'logged_in_merchant' => $merchant->getId(),
            ]);

        $input[self::MERCHANT_ID] = array_pull($input, self::MERCHANT_ID, $merchant->getId());

        $url = $this->getConstructedUrl(self::GET_INTEGRATION_DETAILS_PATH);

        $response = $this->makeRequest($url, $input);

        return $response;
    }

    public function createBatch(array $input, MerchantEntity $merchant, UserEntity $user): array
    {
        $plValidator = new Validator();

        $plValidator->setStrictFalse();

        $plValidator->validateInput(Validator::BATCH_CREATE, $input);

        // only creating payout_link_bulk type batch
        if($input['type'] === BatchType::PAYOUT_LINK_BULK or $input['type'] === BatchType::PAYOUT_LINK_BULK_V2)
        {
            $batch = (new BatchCore)->create($input, $merchant, $user);

            return $batch->toArrayPublic();
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BATCH_TYPE_FOR_PAYOUT_LINK_CREATE_BATCH,
                null,
                [
                    Entity::MERCHANT_ID     => $merchant->getId(),
                    'type'                  => $input['type']
                ]
            );
        }
    }

    public function sendReminderCallback(string $reminderEntityId)
    {
        $url = $this->getConstructedUrl(self::SEND_REMINDER_CALLBACK_PATH);

        $input = [
            self::REMINDER_ENTITY_ID => $reminderEntityId
        ];

        $response = $this->makeRequest($url, $input);

        return $this->reminderResponseHandler($response);
    }

    public function expireCallback(string $reminderEntityId, string $mode = Mode::LIVE)
    {
        $url = $this->getConstructedUrl(self::EXPIRE_CALLBACK_PATH);

        $input = [
            self::REMINDER_ENTITY_ID => $reminderEntityId
        ];

        $responseBody = $this->makeRequest($url, $input, [], self::POST, $mode);

        return $this->reminderResponseHandler($responseBody);
    }

    public function updatePayoutLink(string $payoutLinkId, array $input, MerchantEntity $merchant)
    {
        $url = $this->getConstructedUrl(self::UPDATE_PAYOUT_LINK_PATH);

        $input[self::MERCHANT_ID] = $merchant->getId();

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input, [], self::POST, $this->getMode());
    }

    public function expireCronjob()
    {
        $url = $this->getConstructedUrl(self::EXPIRE_FIX_CRON_JOB);

        $this->makeRequest($url, []);
    }

    public function getPendingPayoutLinksMetaForEmail()
    {
        $url = $this->getConstructedUrl(self::GET_PENDING_LINKS_META_FOR_EMAIL);

        return $this->makeRequest($url, []);
    }

    protected function reminderResponseHandler($httpResponseBody)
    {
        $finalStatusCode = 200;

        // response-body will be having 3 keys i.e. status_code, success_response and error_response
        // status_code is the final response code of API, and body will be success_response or error_response
        if (isset($httpResponseBody['status_code']))
        {
            $finalStatusCode = $httpResponseBody['status_code'];
        }

        if ($finalStatusCode != 200)
        {
            $finalResponseBody['error'] = $httpResponseBody['error_response'];
        }
        else
        {
            $finalResponseBody = $httpResponseBody['success_response'];
        }

        return ['status_code' => $finalStatusCode, 'response_body' => $finalResponseBody];
    }

    /**
     * 1. Setting is enabled
     * 2. Is featured enabled for merchant on RBL
     * 3. Amount less than 1 lac
     * @param array $payoutLinkInfo
     * @param array $settings
     * @param MerchantEntity $merchant
     * @param BankingAccountEntity|null $bankingAccount
     * @return bool
     */
    protected function allowUpi(array $payoutLinkInfo, array $settings,
                                MerchantEntity $merchant,
                                BankingAccountEntity $bankingAccount)
    {
        $channelSupportsUpi = true;

        $upiEnabledInSettings = ((key_exists('UPI', $settings) === true) and
            (boolval($settings['UPI']) === true));

        if($bankingAccount->getChannel() === Channel::RBL)
        {
            $channelSupportsUpi = $merchant->isFeatureEnabled(Features::RBL_CA_UPI);
        }
        // UPI is supported for ICICI. Slack Thread: https://razorpay.slack.com/archives/CR3K6S6C8/p1631695277360000
        else if($bankingAccount->getChannel() === Channel::ICICI)
        {
            $channelSupportsUpi = true;
        }

        $amountLessThanLac = $payoutLinkInfo['amount'] <= Validator::MAX_UPI_AMOUNT ? true : false;

        return $upiEnabledInSettings and $channelSupportsUpi and $amountLessThanLac;
    }

    protected function allowAmazonPay(array $payoutLinkInfo, array $settings,
                                      MerchantEntity $merchant,
                                      BankingAccountEntity $bankingAccount) {
        // Amazon pay will only be enabled for merchants that have the Amazon Pay feature enabled (that is, DISABLE_X_AMAZONPAY set to false)
        if($this->getAmazonPayWalletFeatureEnabled($merchant)) {
            $channelSupportsAmazonPay = true;

            $amazonPayEnabledInSettings = ((key_exists(Entity::AMAZON_PAY, $settings) === true) and
                (boolval($settings[Entity::AMAZON_PAY]) === true));

            // Amazon Pay is not supported for ICICI https://razorpay.slack.com/archives/CR3K6S6C8/p1631702904362300?thread_ts=1631695277.360000&cid=CR3K6S6C8
            if ($bankingAccount->getChannel() === Channel::RBL || $bankingAccount->getChannel() === Channel::ICICI)
            {
                $channelSupportsAmazonPay = false;
            }

            $amountLessThanEqualTenThousand = $payoutLinkInfo['amount'] <= Validator::MAX_AMAZON_PAY_AMOUNT;

            return $amazonPayEnabledInSettings and $channelSupportsAmazonPay and $amountLessThanEqualTenThousand;
        }
        return false;
    }

    protected function getBankingAccountInfo(MerchantEntity $merchant, string $accountNumber)
    {
        $bankingAccountList = $merchant->activeBankingAccounts();

        foreach ($bankingAccountList as $bankingAccount)
        {
            if($bankingAccount->getAccountNumber() === $accountNumber)
            {
                return $bankingAccount;
            }
        }

        return null;
    }

    protected function getAmazonPayWalletFeatureEnabled(MerchantEntity $merchant) {
        return ($merchant->isFeatureEnabled(Features::DISABLE_X_AMAZONPAY) === false);
    }

    protected function extractFundAccountDetails(array $payoutLinkInfo, MerchantEntity $merchant)
    {
        if (array_key_exists('fund_account_id' , $payoutLinkInfo) === false)
        {
            return null;
        }

        $fundAccountId = $payoutLinkInfo['fund_account_id'];

        $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $merchant);

        return $this->getMaskedFundAccountDetails($fundAccount);
    }

    /**
     * Masks the VPA details before sending to the front-end
     * todo, pl Need to move to VPA/Entity [https://razorpay.atlassian.net/browse/RX-1343]
     *
     * @param FundAccountEntity|null $fundAccount
     * @return array|null
     */
    protected function getMaskedFundAccountDetails(FundAccountEntity $fundAccount = null)
    {
        if ($fundAccount === null)
        {
            return null;
        }

        $details = $fundAccount->toArrayPublic();

        $type = $fundAccount->getAccountType();

        switch ($type)
        {
            case Type::VPA:
                $username = $details[Type::VPA][VpaEntity::USERNAME];

                $handle = $details[Type::VPA][VpaEntity::HANDLE];

                $maskedUsername = mask_by_percentage($username);

                $maskedHandle = mask_by_percentage($handle);

                $details[Type::VPA][VpaEntity::ADDRESS] = sprintf('%s@%s', $maskedUsername, $maskedHandle);

                $details[Type::VPA][VpaEntity::HANDLE] = $maskedHandle;

                $details[Type::VPA][VpaEntity::USERNAME] = $maskedUsername;

                break;

            case Type::BANK_ACCOUNT:
                $ifsc = $details[Type::BANK_ACCOUNT][BankAccountEntity::IFSC];

                $accountNumber = $details[Type::BANK_ACCOUNT][BankAccountEntity::ACCOUNT_NUMBER];

                $name = $details[Type::BANK_ACCOUNT][BankAccountEntity::NAME];

                $maskedIfsc = mask_by_percentage($ifsc);

                $maskedAccountNumber = mask_by_percentage($accountNumber);

                $maskedName = mask_by_percentage($name);

                $details[Type::BANK_ACCOUNT][BankAccountEntity::IFSC] = $maskedIfsc;

                $details[Type::BANK_ACCOUNT][BankAccountEntity::ACCOUNT_NUMBER] = $maskedAccountNumber;

                $details[Type::BANK_ACCOUNT][BankAccountEntity::NAME] = $maskedName;

                break;

            case Type::WALLET_ACCOUNT:
                $phone = $details[Type::WALLET][WalletAccountEntity::PHONE];

                $email = $details[Type::WALLET][WalletAccountEntity::EMAIL];

                $name = $details[Type::WALLET][WalletAccountEntity::NAME];

                $maskedPhone = mask_phone($phone);

                $maskedEmail = mask_email($email);

                $maskedName = mask_by_percentage($name);

                $details[Type::WALLET][WalletAccountEntity::PHONE] = $maskedPhone;

                $details[Type::WALLET][WalletAccountEntity::EMAIL] = $maskedEmail;

                $details[Type::WALLET][WalletAccountEntity::NAME] = $maskedName;

                break;
        }

        return $details;
    }

    protected function makeRequest(string $url,
                                   array $data,
                                   array $headers = [],
                                   string $method = self::POST,
                                   string $mode = Mode::LIVE)
    {
        $headers['Content-Type'] = 'application/json';

        $headers['X-Task-ID'] = $this->app['request']->getId();

        $headers[self::X_APP_MODE] = $mode;

        $headers[Passport::PASSPORT_JWT_V1] = $this->app['basicauth']->getPassportJwt($this->baseUrl);

        $options = [
            'auth' => [
                self::KEY,
                $this->secret
            ],
            // Increasing timeout to 25 seconds. Temporary fix.
            // Final FIX: https://jira.corp.razorpay.com/browse/RX-4320
            'timeout' => $this->timeout,
        ];

        $this->trace->info(TraceCode::PAYOUT_LINKS_REQUEST,
            [
                'url'  => $url,
                'mode' => $mode
            ]);

        $response = Requests::request(
            $url,
            $headers,
            json_encode($data, JSON_FORCE_OBJECT),
            $method,
            $options);

        $responseBody = json_decode($response->body, true);

        $this->trace->info(TraceCode::PAYOUT_LINKS_RESPONSE,
            [
                'url' => $url,
                'status_code' => $response->status_code
            ]);

        if ($response->status_code !== StatusCode::SUCCESS)
        {
            $this->trace->info(TraceCode::PAYOUT_LINKS_MS_ERROR_RESPONSE,
                [
                    'url' => $url,
                    'status_code' => $response->status_code,
                    'response_body' => $responseBody
                ]);

            if(empty($responseBody) === true)
            {
                $description = self::INVALID_REQUEST_RESPONSE_MSG;
            }
            else
            {
                $description = array_pull($responseBody, 'msg', $responseBody);

                if(strpos($description, self::INVALID_REQUEST_ERROR_MSG) !== false)
                {
                    $description = self::INVALID_REQUEST_RESPONSE_MSG;
                }
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
                null,
                null,
                $description);
        }
        return json_decode($response->body, true);
    }

    protected function getConstructedUrl(string $path)
    {
        return $url = sprintf('%s/%s', $this->baseUrl, $path);
    }


    /**
     * @param array $payoutLink
     * @param string $operation
     * @param array $expandArray format : ["0":"payouts","1":"user"...]
     */
    protected function processParameters(array &$payoutLink, bool $forAdminResponse = false, array $expandArray = [], bool $isDashboardRequest = false)
    {
        $payoutLink[self::FUND_ACCOUNT_ID] = array_pull($payoutLink, self::FUND_ACCOUNT_ID, null);

        $payoutLink[self::CANCELLED_AT] = array_pull($payoutLink, self::CANCELLED_AT, null);

        $payoutLink[self::ATTEMPT_COUNT] = array_pull($payoutLink, self::ATTEMPT_COUNT, 0);

        $isPayoutInExpandArray = false;

        $isUserInExpandArray = false;

        $isFundAccountInExpandArray = false;

        // existing expands: payouts, user, payouts.fund_account
        // new expands: payouts, user, payouts.fund_account and fund_account
        foreach ($expandArray as $key => $value) {
            if(strpos($value, self::PAYOUTS) !== false)
            {
                $isPayoutInExpandArray = true;
            }
            else if(strpos($value, self::FUND_ACCOUNT) !== false)
            {
                $isFundAccountInExpandArray = true;
            }
            if(strpos($value, self::USER) !== false)
            {
                $isUserInExpandArray = true;
            }
        }

        if ($isPayoutInExpandArray === true)
        {
            $defaultPayoutsCollection = [
                'entity' => 'collection',
                'count' => 0,
                'items' => [],
            ];

            $payoutsInfo = array_pull($payoutLink, self::PAYOUTS, []);

            if (sizeof($payoutsInfo) === 0)
            {
                $payoutLink[self::PAYOUTS] = $defaultPayoutsCollection;
            }
            else
            {
                $this->populatePayoutsMetaInfo($payoutsInfo, $isDashboardRequest);

                $payoutLink[self::PAYOUTS] = $payoutsInfo;
            }
        }
        else
        {
            unset($payoutLink[self::PAYOUTS]);
        }

        if ($isUserInExpandArray === true)
        {
            $userInfo = array_pull($payoutLink, self::USER, []);

            if (sizeof($userInfo) === 0)
            {
                $payoutLink[self::USER] = null;
            }
            else
            {
                $payoutLink[self::USER] = $userInfo;
            }
        }
        else
        {
            unset($payoutLink[self::USER]);
        }

        if ($isFundAccountInExpandArray === true)
        {
            $fundAccountInfo = array_pull($payoutLink, self::FUND_ACCOUNT, []);

            if (sizeof($fundAccountInfo) === 0)
            {
                $payoutLink[self::FUND_ACCOUNT] = null;
            }
            else
            {
                $payoutLink[self::FUND_ACCOUNT] = $fundAccountInfo;
            }
        }
        else
        {
            unset($payoutLink[self::FUND_ACCOUNT]);
        }

        $payoutLink[self::USER_ID] = array_pull($payoutLink, self::USER_ID, null);

        $payoutLink[self::RECEIPT] = array_pull($payoutLink, self::RECEIPT, null);

        $payoutLink[self::NOTES] = array_pull($payoutLink, self::NOTES, []);

        $payoutLink[self::SEND_SMS] = filter_var($payoutLink[self::SEND_SMS], FILTER_VALIDATE_BOOLEAN);

        $payoutLink[self::SEND_EMAIL] = filter_var($payoutLink[self::SEND_EMAIL], FILTER_VALIDATE_BOOLEAN);

        unset($payoutLink[self::ACCOUNT_NUMBER]);

        $payoutLink[Entity::CONTACT][Entity::NAME] = $payoutLink[Entity::CONTACT][Entity::NAME] ?? null;

        $payoutLink[Entity::CONTACT][Entity::EMAIL] = $payoutLink[Entity::CONTACT][Entity::EMAIL] ?? null;

        $payoutLink[Entity::CONTACT][Entity::CONTACT] = $payoutLink[Entity::CONTACT][Entity::CONTACT] ?? null;

        if ($forAdminResponse === false)
        {
            unset($payoutLink[self::UPDATED_AT]);

            unset($payoutLink[self::MERCHANT_ID]);
        }
        else
        {
            $payoutLink[Entity::CONTACT_NAME] = $payoutLink[Entity::CONTACT][Entity::NAME];

            $payoutLink[Entity::CONTACT_EMAIL] = $payoutLink[Entity::CONTACT][Entity::EMAIL];

            $payoutLink[Entity::CONTACT_PHONE_NUMBER] = $payoutLink[Entity::CONTACT][Entity::CONTACT];

            $payoutLink[Entity::ADMIN] = true;
        }

        // expire_by and expired_at should be present in cases
        // 1. expiry-feature is enabled for the merchant
        // 2. expiry-feature is disabled, but the payout-link has some expiry and request is from dashboard
        $isExpiryEnabled = array_pull($payoutLink, self::IS_EXPIRY_ENABLED, false);

        if($isExpiryEnabled === true or
            ($isDashboardRequest === true and array_key_exists(self::EXPIRE_BY, $payoutLink)))
        {
            $expireBy = array_pull($payoutLink, self::EXPIRE_BY, 0);

            $payoutLink[self::EXPIRE_BY] = $expireBy;

            $expiredAt = array_pull($payoutLink, self::EXPIRED_AT, 0);

            $payoutLink[self::EXPIRED_AT] = $expiredAt;
        }
        else
        {
            unset($payoutLink[self::EXPIRE_BY]);

            unset($payoutLink[self::EXPIRED_AT]);
        }

        if($isDashboardRequest === false)
        {
            unset($payoutLink[self::REJECTED_AT]);

            unset($payoutLink[self::ISSUED_AT]);

            unset($payoutLink[self::WORKFLOW_HISTORY]);

            unset($payoutLink[self::PENDING_ON_USER]);

            unset($payoutLink[self::TDS]);

            unset($payoutLink[self::ATTACHMENTS]);

            unset($payoutLink[self::SUBTOTAL_AMOUNT]);
        }
        else
        {
            $payoutLink[self::REJECTED_AT] = array_pull($payoutLink, self::REJECTED_AT, 0);

            $payoutLink[self::ISSUED_AT] = array_pull($payoutLink, self::ISSUED_AT, 0);

            $payoutLink[self::PENDING_ON_USER] = array_pull($payoutLink, self::PENDING_ON_USER, false);

            $payoutLink[self::WORKFLOW_HISTORY] = array_pull($payoutLink, self::WORKFLOW_HISTORY, []);

            $payoutLink[self::TDS] = array_pull($payoutLink, self::TDS, null);

            if($payoutLink[self::TDS] !== null)
            {
                $payoutLink[self::TDS][self::AMOUNT] = array_pull($payoutLink[self::TDS], self::AMOUNT, 0);
            }

            $payoutLink[self::ATTACHMENTS] = array_pull($payoutLink, self::ATTACHMENTS, []);

            $payoutLink[self::SUBTOTAL_AMOUNT] = array_pull($payoutLink, self::SUBTOTAL_AMOUNT, 0);
        }
    }

    protected function processSettingsParameters(array &$settings)
    {
        $impsValue = array_pull($settings, Entity::IMPS, "true");

        $settings[Entity::IMPS] = boolval($impsValue);

        $upiValue = array_pull($settings, Entity::UPI, "true");

        $settings[Entity::UPI] = boolval($upiValue);

        $amazonPayValue = array_pull($settings, Entity::AMAZON_PAY, "true");

        $settings[Entity::AMAZON_PAY] = boolval($amazonPayValue);
    }

    protected function processReminderTimelineParameter(array &$payoutLink, bool $isDashboardRequest)
    {
        // reminders info should be present if the request is from dashboard and $payoutLink has expire_by key
        if($isDashboardRequest === true)
        {
            if (array_key_exists(self::EXPIRE_BY, $payoutLink))
            {
                $remindersInfo = array_pull($payoutLink, self::REMINDERS, []);

                $payoutLink[self::REMINDERS] = $remindersInfo;
            }
        }
        else
        {
            unset($payoutLink[self::REMINDERS]);
        }
    }

    protected function appendPublicSignForPayoutLink(string $payoutlinkid) : string
    {
        if(!str_contains($payoutlinkid, 'poutlk_'))
        {
            return 'poutlk_' . $payoutlinkid;
        }
        else
        {
            return $payoutlinkid;
        }
    }

    protected function notifySettingsChangeOnSlack(string $merchantId, array $oldSettings, array $newSettings)
    {
        $validKeysForSlackNotification = [Entity::IMPS, Entity::UPI, Entity::AMAZON_PAY];

        foreach ($validKeysForSlackNotification as $key)
        {
            //not stopping the UpdateSettings request Logging the error
            try
            {
                if(array_key_exists($key, $newSettings) === true)
                {
                    $isValueChanged = $this->isValueChanged($key, $oldSettings, $newSettings);

                    if($isValueChanged === true)
                    {
                        $this->sendSlackNotification($merchantId, $key, $newSettings[$key]);
                    }
                }
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::PAYOUT_LINK_SETTINGS_SLACK_NOTIFICATION_FAILED,
                    [
                        'failed_slack_notification_key' => $key,
                        'merchant_id' => $merchantId,
                    ]);
            }

        }
    }

    protected function isValueChanged(string $key, array $oldArray, array $newArray)
    {
        $isValueChanged = false;

        $oldArrayValue = array_pull($oldArray, $key);

        $newArrayValue = array_pull($newArray, $key, $oldArrayValue);

        if ($oldArrayValue !== $newArrayValue)
        {
            $isValueChanged = true;
        }

        return $isValueChanged;
    }

    protected function sendSlackNotification(string $merchantId, string $key, $newValue)
    {
        $message = 'Payout Mode ';

        $message .= $key;

        if (boolval($newValue) === true)
        {
            $message .= ' enabled for ';
        }
        else
        {
            $message .= ' disabled for ';
        }

        $user = $this->getInternalUsernameOrEmail();

        $messageUser = self::DASHBOARD_INTERNAL;

        if($user !== self::DASHBOARD_INTERNAL)
        {
            $messageUser = 'Merchant User';
        }

        $message .= $merchantId . ' by ' . $messageUser;

        $this->trace->info(
            TraceCode::PAYOUT_LINK_SETTINGS_UPDATE_SLACK_NOTIFICATION,
            [
                'merchant_id' => $merchantId,
            ]
        );

        $this->app['slack']->queue(
            $message,
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }

    private function getInternalUsernameOrEmail()
    {
        $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

        return $dashboardInfo['admin_username'] ?? $dashboardInfo['user_email'] ?? self::DASHBOARD_INTERNAL;
    }

    private function rzpModeCheck(string $merchantId = "")
    {
        if($this->app['rzp.mode'] === Mode::TEST)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
                null,
                [
                    Entity::MERCHANT_ID     => $merchantId
                ],
                self::TEST_MODE_ERROR_MESSAGE
            );
        }
    }

    public function sendDemoEmailInternal($input)
    {
        if (key_exists('email_type', $input) === true)
        {
            $emailType = array_pull($input, 'email_type');
            if($emailType === 'otp')
            {
                $response = $this->sendDemoOtpEmailInternal($input);
                return $response;
            }
            else if($emailType === 'link')
            {
                $response = $this->sendDemoPayoutLinkEmailInternal($input);
                return $response;
            }
            else
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_EMAIL_TYPE,
                    null,
                    []
                );
            }
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_EMAIL_TYPE,
                null,
                []
            );
        }
    }

    public function sendDemoOtpEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_DEMO_OTP_EMAIL_INTERNAL_RULE, $input);

        $customerOtpEmail = new CustomerDemoOtpInternal(
            $input["merchantinfo"],
            $input[Entity::OTP],
            $input[Entity::TO_EMAIL],
            $input[Entity::PURPOSE]
        );

        Mail::queue($customerOtpEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function sendDemoPayoutLinkEmailInternal($input)
    {
        (new Validator())->validateInput(Validator::SEND_DEMO_LINK_EMAIL_INTERNAL_RULE, $input);

        $sendLinkEmail = new SendDemoLinkInternal(
            $input['payoutlinkresponse'],
            $input['merchantinfo'],
            $input[Entity::TO_EMAIL]
        );

        Mail::queue($sendLinkEmail);

        return [Entity::SUCCESS => Entity::OK];
    }

    public function createDemoPayoutLink(array $input): array
    {
        $demoplValidator = new Validator();

        $demoplValidator->setStrictFalse();

        $demoplValidator->validateInput(Validator::CREATE_DEMO_PAYOUT_LINK, $input);

        $url = sprintf('%s/%s', $this->baseUrl, self::CREATE_DEMO_PAYOUT_LINK_PATH);

        $response = $this->makeRequest($url, $input);

        return $response;
    }

    public function getDemoHostedPageData(string $payoutLinkId)
    {
        $url = sprintf('%s/%s', $this->baseUrl, self::GET_DEMO_HOSTED_PAGE_DATA);

        $request = [
            self::PAYOUT_LINK_ID => $payoutLinkId
        ];

        $response = $this->makeRequest($url, $request);

        $payoutUtr = null;

        $payoutMode = null;

        $payoutLinkInfo = $response['payout_link_response'];

        $merchant_info = $response['merchant_info'];

        $settings = $response['settings'];

        $isProduction = $this->getEnvironment() === Environment::PRODUCTION;

        $data = [
            'api_host'                    => $this->config['url.api.production'],
            'payout_link_id'              => $payoutLinkInfo['id'],
            'payout_link_status'          => $payoutLinkInfo['status'],
            'amount'                      => $payoutLinkInfo['amount'],
            'currency'                    => $payoutLinkInfo['currency'],
            'description'                 => $payoutLinkInfo['description'],
            'user_name'                   => $payoutLinkInfo['contact_name'] ?? null,
            'user_email'                  => mask_email($payoutLinkInfo['contact_email'] ?? null),
            'user_phone'                  => mask_phone($payoutLinkInfo['contact_phone_number'] ?? null),
            'receipt'                     => $payoutLinkInfo['receipt'] ?? null,
            'merchant_logo_url'           => $merchant_info['brand_logo'],
            'primary_color'               => $merchant_info['brand_color'],
            'merchant_name'               => $merchant_info['billing_label'],
            'allow_upi'                   => true,
            'allow_amazon_pay'            => true,
            'banking_url'                 => $this->config['applications.banking_service_url'],
            'is_production'               => $isProduction,
            'fund_account_details'        => json_encode(null),
            'purpose'                     => $payoutLinkInfo['purpose'] ?? null,
            'payout_utr'                  => null,
            'payout_mode'                 => null,
            'payout_links_custom_message' => $settings[Entity::CUSTOM_MESSAGE] ?? null,
            'support_contact'             => $settings[Entity::SUPPORT_CONTACT] ?? null,
            'support_email'               => $settings[Entity::SUPPORT_EMAIL] ?? null,
            'support_url'                 => $settings[Entity::SUPPORT_URL] ?? null,
            'expire_by'                   => 0,
            'expired_at'                  => 0,
            'support_phone'               => '',
            'keyless_header'              => '',
        ];

        return $data;
    }

    public function generateAndSendCustomerOtpDemo(string $payoutLinkId, array $input): array
    {
        $url = $this->getConstructedUrl(self::PAYOUT_LINK_GENERATE_OTP_DEMO_PATH);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input);
    }

    public function verifyCustomerOtpDemo(string $payoutLinkId, array $input): array
    {
        $url = $this->getConstructedUrl(self::PAYOUT_LINK_VERIFY_OTP_DEMO_PATH);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input);
    }

    public function initiateDemo(string $payoutLinkId, array $input): array
    {
        $url = sprintf('%s/%s', $this->baseUrl, self::INITIATE_DEMO_PAYOUT_LINK_PATH);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input);
    }

    public function viewHostedPageData(string $payoutLinkId)
    {
        $hostedData = $this->getHostedPageData($payoutLinkId);

        return $this->formatHostedData($hostedData);
    }

    public function viewDemoHostedPageData(string $payoutLinkId)
    {
        $hostedData = $this->getDemoHostedPageData($payoutLinkId);

        return $this->formatHostedData($hostedData);
    }

    public function fetchTopFivePendingLinksForApprovalEmail(string $merchantId, string $role)
    {
        $input = [
            self::MERCHANT_ID       => $merchantId,
            self::PENDING_ON_ROLES  => [$role],
            self::STATUS            => 'pending',
            self::COUNT             => 5,
        ];

        $url = $this->getConstructedUrl(self::FETCH_PAYOUT_LINK_MULTIPLE_PATH);

        return $this->makeRequest($url, $input, [], self::POST, $this->getMode());
    }

    private function formatHostedData(array $hostedData)
    {
        $userDetails = [
            'name'          => $hostedData['user_name'],
            'maskedPhone'   => $hostedData['user_phone'],
            'maskedEmail'   => $hostedData['user_email'],
        ];

        $supportDetails = [
            'supportPhone'  => $hostedData['support_phone'],
            'supportEmail'  => $hostedData['support_email'],
        ];

        return [
            'primary_color'             => $hostedData['primary_color'],
            'logo'                      => $hostedData['merchant_logo_url'],
            'client'                    => $hostedData['merchant_name'],
            'amount'                    => strval($hostedData['amount']),
            'userDetails'               => $userDetails,
            'description'               => $hostedData['description'],
            'receipt'                   => $hostedData['receipt'],
            'apiHost'                   => $hostedData['api_host'] . '/v1/',
            'payoutLinkId'              => $hostedData['payout_link_id'],
            'status'                    => $hostedData['payout_link_status'],
            'allowUpi'                  => $hostedData['allow_upi'],
            'allowAmazonPay'            => $hostedData['allow_amazon_pay'],
            'fundAccountDetails'        => json_decode($hostedData['fund_account_details'], true),
            'purpose'                   => $hostedData['purpose'],
            'payoutUtr'                 => $hostedData['payout_utr'],
            'payoutMode'                => $hostedData['payout_mode'],
            'payoutLinksCustomMessage'  => $hostedData['payout_links_custom_message'],
            'expireBy'                  => $hostedData['expire_by'],
            'expiredAt'                 => $hostedData['expired_at'],
            'supportDetails'            => $supportDetails,
            'keylessHeader'             => $hostedData['keyless_header'],
        ];
    }

    protected function getKeylessHeader($merchantId, $mode)
    {
        $keylessHeader = null;

        $isKeylessHeaderEnabled = $this->app['razorx']->getTreatment(
            $merchantId,
            Merchant\RazorxTreatment::KEYLESS_HEADER_POUTLK,
            $mode
        );

        if ($isKeylessHeaderEnabled === 'on') {
            $keylessHeader = $this->app['keyless_header']->get(
                $merchantId,
                $mode);
        }

        return $keylessHeader;
    }

    protected function getModeForPublicPage()
    {
        $mode = $this->app['request']->header(self::X_RAZORPAY_MODE) ?? null;

        if($mode === null)
        {
            $mode = $this->getMode();
        }

        return $mode;
    }

    protected function headerCheckForRequestsInCustomerFacingPage(string $merchantId = "")
    {
        if($this->getModeForPublicPage() === Mode::TEST)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
                null,
                [
                    Entity::MERCHANT_ID     => $merchantId
                ],
                self::TEST_MODE_ERROR_MESSAGE
            );
        }
    }

    protected function populatePayoutsMetaInfo(array &$payoutsInfo, bool $isDashboardRequest = false)
    {
        for ($i = 0; $i < sizeof($payoutsInfo['items']); $i++)
        {
            if ($isDashboardRequest === false)
            {
                unset($payoutsInfo['items'][$i][self::META]);
            }
            else
            {
                $meta = array_pull($payoutsInfo['items'][$i], self::META, []);

                $meta[self::TAX_PAYMENT_ID] = array_pull($meta, self::TAX_PAYMENT_ID, "");

                $payoutsInfo['items'][$i][self::META] = $meta;
            }
        }
    }

    public function fetchPayoutLinksSummaryForMerchant(array $input, string $merchantId)
    {
        $input = [
            self::MERCHANT_ID       => $merchantId,
            self::STATUS            => self::STATUS_PENDING,
            self::ACCOUNT_NUMBERS   => $input[self::ACCOUNT_NUMBERS],
        ];

        $url = $this->getConstructedUrl(self::FETCH_PAYOUT_LINKS_SUMMARY_PATH);

        $response = $this->makeRequest($url, $input, [], self::POST, $this->getMode());

        return array_pull($response, self::PAYOUT_LINKS_SUMMARY, array());
    }
}

