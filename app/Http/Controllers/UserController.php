<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use Cookie;
use Request;
use Session;
use App\User;
use App\Admin;
use App\Merchant;
use App\Lib\Util;
use App\Http\ApiUrl;
use App\User\Helper;
use App\User\Constants;
use App\Edge\EdgeClient;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use App\Base\UniqueIdEntity;
use App\Admin\ApiRequestAny;
use Illuminate\Http\Response;
use GuzzleHttp\Client as Guzzle;
use App\User\RecoverableException;
use Razorpay\Api\Errors\ErrorCode;
use Razorpay\Api\Errors\BadRequestError;
use App\Splitz\Service as SplitzService;
use App\Constants\Constants as AppConstants;
use App\Metrics\Constants as MetricConstants;
use App\Merchant\Constants as MerchantConstants;
use App\User\Constants as UserConstants;
use Symfony\Component\HttpFoundation\StreamedResponse;


const EVENT_TRIGGER_COUNT = 1;

class UserController extends Controller
{

    const ROOT_PATH = '/';

    const SPLITZ_BULK_EVALUATE_PATH = 'splitz/bulkEvaluate';

    const ORG_ERRORS = ['No db records found.'];

    protected $guard = 'users';

    protected $app;

    protected $trace;

    protected $splitzExprimentData;

    const DASHBOARD_USER_CONCURRENT_API_CALL = 'DASHBOARD_USER_CONCURRENT_API_CALL';

    const ONBOARDING_FTUX = 'ONBOARDING_FTUX';

    const ONBOARDING_FTUX_AFTER_L2 = 'ONBOARDING_FTUX_AFTER_L2';
    
    const ELIGIBLE_FOR_POS = 'ELIGIBLE_FOR_POS';
    /**
     * @var \App\Admin\Service|null
     */
    private ?Admin\Service $adminService;
    /**
     * @var \GuzzleHttp\Client|null
     */
    private ?Guzzle $httpClient;
    /**
     * @var \App\User\Service|null
     */
    private ?User\Service $userService;

    /**
     * @var \GuzzleHttp\Client|null
     */
    private $edgeClient;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->metrics = $app['metrics'];

        $this->edgeClient = new EdgeClient();
    }

    public function getDataForRendering($details, $org, $userError, $orgError): array
    {
        $data = [
            'isAuthenticated'       => false,
            'isConfirmed'           => false,
            'isMobileConfirmed'     => false,
            'preSignupData'         => [],
            'isPreSignupComplete'   => false,
            'org'                   => json_encode($org),
            'session_id'            => Session::getId(),
            'cdnDashboardUrl'       => \Config::get('app.cdn_dashboard_url'),
        ];

        $data['requestPath'] = \Request::path();

        if (empty($userError) and empty($orgError))
        {
            $data['isConfirmed']        = $details['user']['confirmed'];
            $data['isMobileConfirmed']  = $details['user']['contact_mobile_verified'];
            $data['preSignupData']      = $details['pre_signup'];
            $data['isPreSignupComplete']= $details['pre_signup_complete'];
        }

        return $data;
    }

    public function setSplitzVariantBulkData($currentMerchant)
    {
        if (is_null($currentMerchant))
        {
            $this->splitzExprimentData = [] ;
            return;
        }

        $currentMerchantId = $currentMerchant->id;

        $concurrentApiCallExperimentId = config('splitz.experiments')[self::DASHBOARD_USER_CONCURRENT_API_CALL];
        $onboardingFtuxExperiment = config('splitz.experiments')[self::ONBOARDING_FTUX];
        $onboardingFtuxAfterL2Experiment = config('splitz.experiments')[self::ONBOARDING_FTUX_AFTER_L2];
        $splitzCachingEnabled = config('splitz.experiments')[Constants::SPLITZ_API_CACHING_ENABLED];
        $razorxCachingEnabled = config('splitz.experiments')[Constants::RAZORX_CACHING_ENABLED];
        $eligibleForPosExperiment = config('splitz.experiments')[self::ELIGIBLE_FOR_POS];

        $experimentIds = [$onboardingFtuxExperiment, $concurrentApiCallExperimentId, $splitzCachingEnabled, $razorxCachingEnabled, $onboardingFtuxAfterL2Experiment, $eligibleForPosExperiment];

        $data = (new SplitzService([AppConstants::HTTP_CLIENT => $this->httpClient]))->getVariantBulk(
            $currentMerchantId,
            $experimentIds,
            [AppConstants::HTTP_CLIENT => $this->httpClient],
            self::SPLITZ_BULK_EVALUATE_PATH
        );

        $this->splitzExprimentData = $data;
    }

    public function IsDomainRedirectionEnabled($id): bool
    {
        if (empty($id)) {
            // No experiment is set
            return true;
        }
        $experimentId = env($id);
        $data = (new SplitzService([AppConstants::HTTP_CLIENT => $this->httpClient]))->getVariant(
            $experimentId,
            "",
        );

        if ($data === null)
        {
            return false;
        }

        return ($data["variables"][0]["value"] ?? null) === 'true';
    }

    public function viewOrRedirectToUrl($details, $org, $userError, $orgError, $startTime, $isConcurrentApiCall = false)
    {

        $data = $this->getDataForRendering($details,$org, $userError, $orgError);

        $currentRouteName = \Route::currentRouteName();

        if (empty($userError) and empty($orgError))
        {
            $data = [
                'isAuthenticated'       => (bool) $details['user'],
                'isConfirmed'           => $details['user']['confirmed'],
                'isMobileConfirmed'     => $details['user']['contact_mobile_verified'],
                'preSignupData'         => $details['pre_signup'],
                'isPreSignupComplete'   => $details['pre_signup_complete'],
                'user'                  => json_encode($details),
                'org'                   => json_encode($org),
                'api_host'              => ApiUrl::getCheckoutApi(),
                'session_id'            => Session::getId(),
            ];

            if ($this->isRedirectionApplicable($details) === true)
            {
                $ttl = 12 * 60;
                $this->trace->info(TraceCode::EASY_DASHBOARD_URL_REDIRECTION, [
                    'redirection_url' => env('EASY_DASHBOARD_URL'),
                    'cookie_set'      => true,
                    'condition'       => $details['user']['signup_campaign'] ?? null,
                    'user'            => $data['user'] ?? null,
                    'api_host'        => $data['api_host'] ?? null,
                    'session_id'      => $data['session_id'] ?? null
                ]);

                $id = $details['id'] ?? null;
                $userId = $details['user']['id'] ?? null;

                return redirect(env('EASY_DASHBOARD_URL'))->withCookies([
                    Cookie::make('rzp_merchant_id', $id, $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false),
                    Cookie::make('rzp_user_id', $userId, $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false),
                ]);
            }

            $orgCode = $org[MerchantConstants::CUSTOM_CODE] ?? '';
            $isOrgRZP = $orgCode === MerchantConstants::RZP;
            $isApplicableForFtuxRedirection = $this->isRedirectionApplicableForFtux($details) === true and $isOrgRZP === true;

            if ($isApplicableForFtuxRedirection)
            {
                $this->trace->info(TraceCode::EASY_DASHBOARD_URL_REDIRECTION, [
                    'redirection_url' => env('EASY_DASHBOARD_URL') . '/onboarding/overview',
                    'cookie_set'      => false,
                    'condition'       => 'FTUX',
                    'user'            => $data['user'] ?? null,
                    'api_host'        => $data['api_host'] ?? null,
                    'session_id'      => $data['session_id'] ?? null
                ]);

                return redirect(env('EASY_DASHBOARD_URL') . '/onboarding/overview');
            }

            $signupCampaign = $details['user']['signup_campaign'] ?? null;

            $submitted = $details['submitted'] ?? null;

            $milestone = $details['activation_form_milestone'] ?? null;

            if (($signupCampaign === 'p2pm_onboarding') and
                ($submitted == 0) and
                ($milestone !== 'L2'))
            {
                $this->trace->info(TraceCode::EASY_DASHBOARD_URL_REDIRECTION, [
                    'redirection_url' => env('EASY_DASHBOARD_URL') . '/onboarding/p2pm',
                    'cookie_set'      => false,
                    'condition'       => $signupCampaign,
                    'user'            => $data['user'] ?? null,
                    'api_host'        => $data['api_host'] ?? null,
                    'session_id'      => $data['session_id'] ?? null,
                ]);

                return redirect(env('EASY_DASHBOARD_URL') . '/onboarding/p2pm');
            }

            if ($this->canCookieSetForEasyOnboardingPostL1Submit($details) === true)
            {
                $ttl = 12 * 60;

                $id = $details['id'] ?? null;
                $userId = $details['user']['id'] ?? null;

                Cookie::queue('rzp_merchant_id',$id, $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false);
                Cookie::queue('rzp_user_id', $userId, $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false);
            }
        }

        $data['cdnDashboardUrl'] = \Config::get('app.cdn_dashboard_url');
        $data['cdnBaseUrl'] = \Config::get('app.cdn_base_url');
        $data['ljKey'] = \Config::get('app.lj_key');
        $data['env'] = \Config::get('app.env');

        $baseUrl = $this->getDashboardBaseUrl();
        $requestPath = \Request::path();

        $data['redirectUrl']    = $baseUrl . '?next=' . $requestPath;
        $data['requestPath']    = $requestPath;
        $data['rootPath']       = self::ROOT_PATH;
        $data['isAuthPath']     = false;

        if (empty($currentRouteName) === false and ($currentRouteName === "signup" || $currentRouteName === "signin" || $currentRouteName === "resetpassword" || $currentRouteName === "emailupdate"))
        {
            $data['isAuthPath'] = true;

            // redirect guests to unified signup page based on experiment and current conditions
            if ($currentRouteName === "signup" and $this->isRedirectionApplicableToUnifiedLogin($org)) {

                $redirectPath = \Config::get('app.unified_signup_redirect_path');

                $this->trace->info(TraceCode::UNIFIED_SIGNUP_REDIRECTION, [
                    'redirection_url' => $redirectPath,
                    'cookie_set'      => false,
                    'condition'       => 'GUEST_SIGNUP',
                    'user'            => $data['user'] ?? null,
                    'api_host'        => $data['api_host'] ?? null,
                    'session_id'      => $data['session_id'] ?? null,
                ]);

                return redirect($redirectPath);
            }

            if ($currentRouteName === 'signup')
            {
                if ($this->redirectionApplicableForGuest($org) === true)
                {
                    $this->trace->info(TraceCode::UNIFIED_SIGNUP_REDIRECTION, [
                        'redirectionApplicableForGuest'     => "redirectionApplicableForGuest",
                    ]);
                    $redirectPath = env('EASY_DASHBOARD_URL') . \Request::getRequestUri();
                    $redirectPath = preg_replace('/\?/', '&', $redirectPath); // because we are adding a new query param at the begining
                    $redirectPath = preg_replace('/signup/', 'onboarding?source=website', $redirectPath);

                    $this->trace->info(TraceCode::EASY_DASHBOARD_URL_REDIRECTION, [
                        'redirection_url' => $redirectPath,
                        'cookie_set'      => false,
                        'condition'       => 'GUEST_SIGNUP',
                        'user'            => $data['user'] ?? null,
                        'api_host'        => $data['api_host'] ?? null,
                        'session_id'      => $data['session_id'] ?? null,
                    ]);

                    return redirect($redirectPath);
                }
            }

            // redirect guests to unified signin page based on experiment on top of existing conditions
            if ($currentRouteName === "signin" and $this->isRedirectionApplicableToUnifiedLogin($org)) {

                $redirectPath = \Config::get('app.unified_login_redirect_path');

                $this->trace->info(TraceCode::UNIFIED_LOGIN_REDIRECTION, [
                    'redirection_url' => $redirectPath,
                    'cookie_set'      => false,
                    'condition'       => 'GUEST_LOGIN',
                    'user'            => $data['user'] ?? null,
                    'api_host'        => $data['api_host'] ?? null,
                    'session_id'      => $data['session_id'] ?? null,
                ]);

                return redirect($redirectPath);
            }
        }

        // $data is used to run diferent pieces of JS
        if (isset($data['user']) === true and isset($details['linked_account']) === true and $details['linked_account'] === true)
        {
            return view('merchant.la', $data);
        }
        else
        {
            if (empty($details) === false)
            {
                $oldNotification = (new Merchant\Notifications\Service)->getOldNotificationsForUser($details, $org);
                $newNotification = (new Merchant\Notifications\Service)->getNewNotificationsForUser($details, $org);
                $data['old_notifications'] = json_encode($oldNotification);
                $data['new_notifications'] = json_encode($newNotification);
                //TODO: Remove this in next release
                $data['notifications']     = json_encode(array_merge($oldNotification,$newNotification));
            }
            /*
             * Null case is strict in laravel >= 6
             * */
            $currentMerchantId = $details['current'] ?? null;


            if(is_null($currentMerchantId) === false)
            {
                $data['custom_notes'] = json_encode((new Merchant\CustomNotes\Service)->getNotesForPaymentLinksForMerchant($currentMerchantId));
                $data['pl_expiry_in_hrs'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getDefaultExpiryTimeForPaymentLinksForMerchant($currentMerchantId));
                $data['pl_extra_fields'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getExtraFormFieldsByMID($currentMerchantId));
                $data['pl_customized_form_fields'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getCustomizedFormFieldsByMID($currentMerchantId));
                $data['is_pl_customer_name_field_enabled'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getIsCustomerNameFieldEnabledByMID($currentMerchantId));
            }
            else {
                $data['custom_notes'] = null;
                $data['pl_expiry_in_hrs'] = null;
                $data['pl_extra_fields'] = null;
                $data['pl_customized_form_fields'] = null;
                $data['is_pl_customer_name_field_enabled'] = null;
            }

            $data['is_banking_request'] = json_encode(ApiUrl::isBankingOriginRequest());

            // If a user accesses PG dashboard using X demo account, then we log out and redirect to sign-in
            // Since this is a PG dashboard route, no need to check product origin explicitly
            if (in_array($currentMerchantId,MerchantConstants::X_DEMO_MERCHANT_IDS,true))
            {
                $this->getLogout();
                $data['isAuthenticated'] = false;
                $data['isConfirmed'] = false;
                $data['isMobileConfirmed'] = false;
            }

            // Chunk based straming: get the flag to check streaming
            $isMerchantLogin = Session::get('is_merchant_login');

            $this->trace->info(TraceCode::CHUNKED_DETAILS, [
                'isMerchantLogin'     => $isMerchantLogin,
                'currentMerchantId'   => $currentMerchantId
            ]);

            if (is_null($isMerchantLogin) === true)
            {

                if (is_null($currentMerchantId) === false)
                {
                    // Chunk based straming: set flag to enable streaming
                    Session::put('is_merchant_login', true);
                }

                $timeTaken = self::millitime() - $startTime;
                $this->pushUserRenderDataToMetrics($timeTaken, true, $isConcurrentApiCall);

                $domain = \Request::server('SERVER_NAME');

                foreach (UserConstants::DOMAIN_REDIRECT_MAP as $domainKey => $domainData) {
                    if ($domain == $domainKey) {
                        $redirectUrl = $domainData['redirect_url'];
                        if ($this->IsDomainRedirectionEnabled($domainData['id'])) {
                            return redirect($redirectUrl);
                        }
                    }
                }

                return view('merchant.index', $data);
            }
            else
            {
                // Chunk based straming: send second chunk
                $view = view('merchant.index2', $data)->render();
                $this->trace->info(TraceCode::CHUNKED_DETAILS, [
                    'chunkRendered' => '2',
                ]);

                $timeTaken = self::millitime() - $startTime;
                $this->pushUserRenderDataToMetrics($timeTaken, true, $isConcurrentApiCall);

                echo($view);
                ob_flush();
                flush();
            }
        }
    }

    private function isConcurrentApiCallEnabledForDashboardUser(): bool
    {
        $experimentId = config('splitz.experiments')[self::DASHBOARD_USER_CONCURRENT_API_CALL];

        if (!array_key_exists($experimentId, $this->splitzExprimentData))
        {
            return false;
        }

        return ($this->splitzExprimentData[$experimentId]['variables']['result'] ?? null) === 'on';

    }

    private function getSecondChunkedData(array $firstChunkData, bool $isConcurrentApiCallEnabled): array
    {
        $isSplitzCachingEnabled = $this->isSplitzCachingEnabled();

        $isRazorxCachingEnabled = $this->isRazorxCachingEnabled();

        $params = [
            Constants::SPLITZ_API_CACHING_ENABLED => $isSplitzCachingEnabled,
            Constants::RAZORX_CACHING_ENABLED     => $isRazorxCachingEnabled
        ];

        if ($isConcurrentApiCallEnabled)
        {
            return $this->userService->getSecondChunkUserDetailsConcurrent($firstChunkData, $params);
        }

        return $this->userService->getSecondChunkUserDetails($firstChunkData, $params);
    }

    static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }

    public function pushUserRenderDataToMetrics($timeTaken, $cbsFlow, $concurrentApICall = false)
    {
        $domain = \Request::server('SERVER_NAME') ?? 'unknown_domain';

        $currentRouteName = \Route::currentRouteName() ?? 'unknown_route';

        $dimensions = [
            MetricConstants::LABEL_HTTP_REQUESTS_ORIGIN               => ApiUrl::getRequestOrigin(),
            MetricConstants::LABEL_HTTP_REQUESTS_DOMAIN               => $domain,
            MetricConstants::LABEL_HTTP_REQUESTS_ROUTE                => $currentRouteName,
            MetricConstants::LABEL_DASHBOARD_CBS                      => $cbsFlow,
            MetricConstants::LABEL_DASHBOARD_CONCURRENT_API_CALL      => $concurrentApICall,
            MetricConstants::LABEL_DASHBOARD_RAZORX_CACHING_API_CALL  => $this->isRazorxCachingEnabled(),
            MetricConstants::LABEL_API_BASE_URL                       => ApiUrl::getApiHost(),
        ];

        $this->trace->info(TraceCode::USER_RENDER_DATA, $dimensions + ['time_taken' => $timeTaken]);

        try
        {
            $this->metrics->count(MetricConstants::METRIC_USER_PAGE_RENDER, EVENT_TRIGGER_COUNT, $dimensions);

            $this->metrics->histogram(MetricConstants::METRIC_HISTOGRAM_USER_PAGE_RENDER, $timeTaken, $dimensions);
        }
        catch (\Throwable $t)
        {
            $this->trace->warning(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $t->getMessage() ?? 'unknown_message',
            ]);
        }
    }

    private function isSplitzCachingEnabled(): bool
    {
        $experimentId = config('splitz.experiments')[Constants::SPLITZ_API_CACHING_ENABLED];

        if (!array_key_exists($experimentId, $this->splitzExprimentData))
        {
            return false;
        }

        return ($this->splitzExprimentData[$experimentId]['variables']['result'] ?? null) === 'on';
    }

    /**
     * Returns the base template for angular.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function getIndex()
    {
        $startTime = self::millitime();

        $domain = \Request::server('SERVER_NAME');

        $this->httpClient   = $this->getHttpClient(ApiUrl::getApiBaseUrl(), \Config::get('api.request_timeout'));
        $this->adminService = new Admin\Service([AppConstants::HTTP_CLIENT => $this->httpClient]);
        $this->userService  = new User\Service([AppConstants::HTTP_CLIENT => $this->httpClient]);

        [$orgError, $org] = $this->adminService->getOrg($domain);

        if (empty($orgError) == false)
        {
            $this->trace->info(TraceCode::FETCH_ORG_DETAILS_ERROR, [
                'error' => $orgError
            ]);
            if(in_array( $orgError[0], self::ORG_ERRORS)){
                throw new BadRequestError(
                    $orgError[0],
                    ErrorCode::BAD_REQUEST_ERROR,
                    400
                );
            }
        }

        // From here logic for chunked Based Streaming has started.

        // Overall scenario for Chunked Based Streaming
        // 1. When merchant will log in the dashboard, then we will get some details by calling getFirstChunkUserDetails function
        // 2. Then we will check for is_merchant_login value in session id this will be null initially, so we will execute
        //    first condition and then get other details of merchant by calling getSecondChunkUserDetails function.
        // 3. After then, we are calling viewOrRedirectToUrl function by making this params ($isChunkedBasedEnable as true )
        //    which will redirect or view the blade.php file based on condition.
        // 4. Inside viewOrRedirectToUrl function, $isChunkedBasedEnable is true, so we will see whether the
        //    is_merchant_login value in session is true or false , if false then we will make this as true and normally view the
        //    index blade file without chunked based.
        // 5. if is_merchant_login is true in one session then we will view index1 blade file using chunked based streaming and then
        //    we will get other details by calling getSecondChunkUserDetails function and then we will call viewOrRedirectToUrl function
        //    and view index2 blade file using chunked based streaming.

        [$userError, $firstChunkData] = $this->userService->getFirstChunkUserDetails();

        if (empty($userError) == false)
        {
            $this->trace->info(TraceCode::FETCH_USER_DETAILS_ERROR, [
                'error' => $userError
            ]);
        }

        $isMerchantLogin = Session::get('is_merchant_login');

        $this->trace->info(TraceCode::CHUNKED_DETAILS, [
            'shouldRenderCBS' => $isMerchantLogin,
        ]);

        $currentMerchant = $firstChunkData['currentMerchant'] ?? null;

        $this->setSplitzVariantBulkData($currentMerchant);

        $isConcurrentApiCallEnabled = $this->isConcurrentApiCallEnabledForDashboardUser();

        if (is_null($isMerchantLogin) === true)
        {
            if (empty($userError) and empty($orgError))
            {
                list($userError2, $secondChunkData) = $this->getSecondChunkedData($firstChunkData, $isConcurrentApiCallEnabled);
            }

            $details = $secondChunkData['details'] ?? [];

            return $this->viewOrRedirectToUrl($details, $org, $userError, $orgError, $startTime, $isConcurrentApiCallEnabled);
        }
        else
        {
            // Chunk based straming: start streaming the response
            $response = new StreamedResponse();

            $response->setCallback(function () use ($firstChunkData, $org, $userError, $orgError, $startTime, $isConcurrentApiCallEnabled){

                $firstDetails = $firstChunkData['details'] ?? [];

                $dataForRender = $this->getDataForRendering($firstDetails, $org, $userError, $orgError);

                $data = array_merge($firstDetails, $dataForRender);

                $data['chunkStreamingEnabled'] = true;

                // Chunk based straming: send first chunk
                $view = view('merchant.index1', $data)->render();
                $this->trace->info(TraceCode::CHUNKED_DETAILS, [
                    'chunkRendered' => '1',
                ]);

                echo $view;
                ob_flush();
                flush();

                if (empty($userError) and empty($orgError))
                {
                    list($secondUserError, $secondChunkData) = $this->getSecondChunkedData($firstChunkData, $isConcurrentApiCallEnabled);
                }

                $secondDetails = $secondChunkData['details'] ?? [];

                $this->viewOrRedirectToUrl($secondDetails, $org, $userError, $orgError, $startTime, $isConcurrentApiCallEnabled);
            });

            return $response;
        }
    }

    public function getDummyIFrameForEasyDashboard(){
        return view('merchant.easy-dashboard-iframe');
    }

    private function redirectionApplicableForGuest(array $org): bool
    {
        if (ApiUrl::isBankingOriginRequest() === true)
        {
            return false;
        }

        $uuid = Cookie::get('rzp_ab_uuid') ?? UniqueIdEntity::generateUniqueId();

        Cookie::queue('rzp_ab_uuid', $uuid);

        // EASY_ONBOARDING_REDIRECT as true.
        $referralExpId = config('splitz.experiments')['PARTNERSHIPS_SUBMERCHANT_ONBOARDING_VIA_EASY'];

        $queryParams = Request::all();

        $requestData = [
            'referral_code' => $queryParams['referral_code'] ?? '',
            'easy' => $queryParams['eo'] ?? '',
            'org'  => $org['custom_code'] ?? ''
        ];

        $data = (new SplitzService())->getVariantBulk($uuid, [$referralExpId], [], "splitz/bulkEvaluate", $requestData);

        $isReferralExpEnabled = ($data[$referralExpId]['variables']['result'] ?? null) === 'on';

        if ($this->matchExclusionsToRedirect($isReferralExpEnabled))
        {
            return false;
        }

        return true;
    }

    private function isRedirectionApplicableToUnifiedLogin(array $org): bool
    {

        $existingRedirectionConditions = $this->redirectionApplicableForGuest($org);

        $this->trace->info(TraceCode::UNIFIED_SIGNUP_REDIRECTION, [
            'existingRedirectionConditions' => $existingRedirectionConditions,
        ]);

        if ($existingRedirectionConditions === false) {
            return false;
        }

        $uuid = UniqueIdEntity::generateUniqueId();

        if (empty($_COOKIE['ab_user_id']) === false) {
            $cookie = $_COOKIE['ab_user_id'];

            $this->trace->info(TraceCode::UNIFIED_SIGNUP_REDIRECTION, [
                'cookieSetFromFE' => $_COOKIE['ab_user_id'],
            ]);

            $uuid = $cookie;
        } else {
            if (isset($this->options['cookies']['ab_user_id']) === false) {
                $this->options['cookies']['ab_user_id'] = $uuid;
            }
        }

        // UNIFIED LOGIN SIGN UP EASY_ONBOARDING_REDIRECT as true.
        $unifiedExperimentID = \Config::get('splitz.experiments')['UNIFIED_PG_REDIRECTION_ENABLED'];

        $data = (new SplitzService())->getVariantBulk($uuid, [$unifiedExperimentID], [], "splitz/bulkEvaluate");

        $this->trace->info(TraceCode::UNIFIED_SIGNUP_REDIRECTION, [
            '$data' => $data,
        ]);

        return ($data[$unifiedExperimentID]['variables']['result'] ?? null) === 'on';
    }

    private function matchExclusionsToRedirect(bool $isExpEnabled = false): bool
    {
        $uri = trim(\Request::getRequestUri(), '/');

        $pattern = '/(\br=partner\b)|(\bauth_source\b)|(\breferral_code\b)|(\bcoupon_code\b)|(\bmerchant_invitation\b)|(\binvitation\b)/';

        if($isExpEnabled)
        {
            $pattern = '/(\br=partner\b)|(\bauth_source\b)|(\bcoupon_code\b)|(\bmerchant_invitation\b)|(\binvitation\b)/';
        }

        if (preg_match($pattern, $uri))
        {
            return true;
        }

        return false;
    }

    private function isAuthSourceHasWebsite(): bool
    {
        $queryParams = Input::all();

        $authSource = $queryParams['auth_source'] ?? null;

        if ($authSource === 'website' or $authSource === 'website_homepage')
        {
            return true;
        }

        return false;
    }

    private function canCookieSetForEasyOnboardingPostL1Submit($details): bool
    {
        if ($this->isAuthSourceHasWebsite() === true)
        {
            return false;
        }

        $signupCampaign = $details['user']['signup_campaign'] ?? null;

        $activationFormMilestone = $details['activation_form_milestone'] ?? null;

        if (($signupCampaign === 'easy_onboarding') and
            ($activationFormMilestone == 'L1' or $activationFormMilestone == 'L2'))
        {
            return true;
        }

        $submitted = $details['submitted'] ?? null;
        $activationStatus = $details['activation_status'] ?? null;

//      WEBSITE_COMPLIANCE_FLOW_EXP is true
        if (($activationFormMilestone === 'L1' or $activationFormMilestone === 'L2' or $submitted === 1) and $activationStatus !== 'activated') {
            return true;
        };

        return false;

    }

    private function isRedirectionApplicable($details): bool
    {

        if (ApiUrl::isBankingOriginRequest() === true)
        {
            return false;
        }

        if ($this->isAuthSourceHasWebsite() === true)
        {
            return false;
        }

        $signupCampaign = $details['user']['signup_campaign'] ?? null;

        $submitted = $details['submitted'] ?? null;

        if (($signupCampaign === 'easy_onboarding') and
            (empty($details['activation_form_milestone']) === true) and
            ($submitted == 0))
        {
            return true;
        }

        return false;
    }

    public function getTnc()
    {
        $domain = \Request::server('SERVER_NAME');
        list($orgError, $org) = (new Admin\Service)->getOrg($domain);

        $data = [
            'env'              => \Config::get('app.env'),
            'cdnBaseUrl'       => \Config::get('app.cdn_base_url'),
            'cdnDashboardUrl'  => \Config::get('app.cdn_dashboard_url'),
            'api_host'         => ApiUrl::getCheckoutApi(),
            'org'              => json_encode($org),
        ];

        return view('merchant.tnc', $data);
    }

    /**
     * Returns an empty success to keep the user session active..
     *
     * @return Response
     */
    public function getKeepAlive()
    {
        $queryParams = Input::all();

        $sendMidUid = filter_var($queryParams['send_mid_uid'] ?? null, FILTER_VALIDATE_BOOLEAN);

        if ($sendMidUid === true)
        {
            $user = Auth::user();

            $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

            $response = [
                'merchant_id' => $currentMerchantId,
                'user_id' => $user->id
            ];

            return AppResponse::jsonResponse([], $response);
        }

        return AppResponse::jsonResponse([]);
    }

    protected function checkCaptchaDisableInPayload($input)
    {
        $env = \App::environment();

        if ($env === 'production' and isset($input['captcha_disable']) === true)
        {

            $this->trace->info(
                TraceCode::CAPTCHA_DISABLE_INVALID_PAYLOAD_ERROR,
                ['data' => Util::maskLoginSignupInput($input)]
            );

            $this->metrics->count(MetricConstants::USER_REGISTER_REQUEST_WITHOUT_CAPTCHA_COUNT ,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::PRODUCT                => ApiUrl::isBankingOriginRequest() ? MetricConstants::BANKING : MetricConstants::PRIMARY,
                    MetricConstants::LABEL_API_BASE_URL     => ApiUrl::getApiHost(),
                ]);

            throw new \Razorpay\Api\Errors\BadRequestError(
                'invalid payload',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }
    }

    protected function checkCaptchaDisableInPayloadForLogin($input)
    {
        $email = $input['email'] ?? null;

        if (in_array($email, Constants::WHITELIST_CAPTCHA_EMAILS, true) === true)
        {
            return;
        }

        $mobileApp = \Request::header('X-Razorpay-App');

        if (empty($mobileApp) === false)
        {
            return;
        }

        $this->checkCaptchaDisableInPayload($input);
    }

    public function postRegisterSendOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data, $httpCode) = (new User\Service)->registerWithOtp($input);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::SEND_SIGNUP_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::SEND_SIGNUP_OTP, $input, $error, $timeTaken);

        if(
            isset($error["internal_error_code"]) and
            in_array($error['internal_error_code'], Admin\ApiRequestAny::INTERNAL_ERROR_CODES)
        )
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postSendOtpForSalesForceUser() {
        $input = Input::all();
        list($error, $data, $httpCode) = (new User\Service)->sendOtpForSalesForceUser($input);
        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postVerifyOtpForSalesForceUser() {
        $input = Input::all();
        list($error, $data, $httpCode) = (new User\Service)->verifyOtpForSalesForceUser($input);
        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postRegisterVerifyOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        $this->checkCaptchaDisableInPayload($input);

        list($error, $data, $httpCode) = (new User\Service)->registerWithOtpVerify($input);

        if (isset($input['email']))
        {
            $signupMedium = MetricConstants::EMAIL;
        }
        else
        {
            $signupMedium = MetricConstants::CONTACT_MOBILE;
        }

        $res = [];

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);
            Auth::login($genericUser, false);
            $this->app[Constants::SESSION]->put(Constants::DASHBOARD_USER_PAYLOAD, $genericUser);

            $user = Auth::user();

            $res = [
                "id"                => $user->currentMerchant() ? $user->currentMerchant()->id : null,
                "name"              => $user->currentMerchant() ? $user->currentMerchant()->name : null,
                "email"             => $user->email,
                "contact_mobile"    => $user->contact_mobile,
                "user_id"           => $user->id,
                "logged_in_via"     => (isset($input[UserConstants::EMAIL]) === true) ? UserConstants::EMAIL : UserConstants::CONTACT_MOBILE
            ];
        }

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_SIGNUP_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::VERIFY_SIGNUP_OTP, $input, $error, $timeTaken);

        if(
            isset($error["internal_error_code"]) and
            in_array($error['internal_error_code'], Admin\ApiRequestAny::INTERNAL_ERROR_CODES)
        )
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $res, $httpCode);
    }

    public function postRegister()
    {
        $timeStart = microtime(true);
        $input = Input::all();
        $data = null;
        $error = [];

        try
        {
            $this->checkCaptchaDisableInPayload($input);

            list($error, $data, $httpCode) = (new User\Service)->register($input);

            if (empty($error))
            {
                $credentials = [
                    'email'             => $input['email'],
                    'password'          => $input['password'],
                    'captcha_disable'   => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                ];

                $loginResult = Auth::attempt($credentials, false, true);

                app('trace')->info(TraceCode::USER_REGISTER_LOGIN_ATTEMPT, ["email" => $input['email'], "login_result" => $loginResult]);

                $twoFaDuringSignup = false;

                if ($loginResult === false)
                {
                    list($error, $data) = (new User\Service)->login($credentials);

                    if (empty($error) === false and
                        isset($error[UserConstants::INTERNAL_ERROR_CODE]) === true and
                        $error[UserConstants::INTERNAL_ERROR_CODE] === 'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED')
                    {
                        $twoFaDuringSignup = true;
                    }
                }

                // push metrics on no error or if error is about two_fa_setup
                if (empty($error) === true or $twoFaDuringSignup === true)
                {
                    $this->metrics->count(MetricConstants::USER_SIGNUP_COUNT,
                        EVENT_TRIGGER_COUNT,
                        [
                            MetricConstants::TWO_FA_DURING_SIGNUP         => $twoFaDuringSignup,
                            MetricConstants::LABEL_API_BASE_URL           => ApiUrl::getApiHost(),
                        ]);
                }

                if(isset($input["email"]) === true)
                {
                    $data["logged_in_via"] = UserConstants::EMAIL;
                }
                else
                {
                    $data["logged_in_via"] = UserConstants::CONTACT_MOBILE;
                }
            }
        }
        catch (User\RecoverableException $e)
        {
            $error = [$e->getMessage()];
        }

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_SIGNUP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_SIGNUP, $input, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postRegisterUnbounce()
    {
        $timeStart = microtime(true);
        $input = Input::all();
        $data = null;
        $error = [];

        try
        {
            $formInput = json_decode($input['data_json']);

            $registerInput = [
                'email'             => $formInput->email[0],
                'password'          => $formInput->password[0],
                'captcha_disable'   => "DISABLE_THE_CAPTCHA_YOU_SHALL",
                'signup_campaign'   => 'unbounce'
            ];

            list($error, $data, $httpCode) = (new User\Service)->register($registerInput);

        }
        catch (User\RecoverableException $e)
        {
            $error = [$e->getMessage()];
        }

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_SIGNUP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_SIGNUP, $registerInput, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $httpCode);

    }

    public function postOauthRegister()
    {
        $timeStart = microtime(true);
        $input = Input::all();

        try
        {
            list($error, $data, $httpCode) = (new User\Service)->oauthRegisterAndSignIn($input);
        }
        catch (RecoverableException $e)
        {
            $error = [$e->getMessage()];
            $data  = null;
            $httpCode = 400;
        }

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_OAUTH_SIGNUP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_OAUTH_SIGNUP, $input, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    /**
     * Handle the authentication request from the user.
     *
     * @return Response
     */
    public function postSignin()
    {
        // Epos App Deprecated. Blocking Signin for Epos App Users
        $mobileApp = \Request::header('X-Razorpay-App');

        if ((empty($mobileApp) === false) and
            ($mobileApp === 'Epos'))
        {
            $this->trace->info(TraceCode::LOGIN_BLOCKED_EPOS_APP, []);

            $response = User\Constants::EPOS_APP_DEPRECATED_MESSAGE;

            return AppResponse::jsonResponse($response);
        }

        $timeStart = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        $this->trace->info(TraceCode::USER_LOGIN_KEYS, [
            'captcha' => $input['captcha'] ?? null,
            'email'   => $input['email'] ?? null,
        ]);

        $this->checkCaptchaDisableInPayloadForLogin($input);

        $userService = (new User\Service());

        $userService->addUserBrowserDetails($input);

        list($error, $data, $httpCode) = $userService->login($input);

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_LOGIN_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_LOGIN, $input, $error, $timeTaken);

        $headers = $this->getMobileOauthHeaders($data, $error);

        return AppResponse::jsonResponse($error, $data, $httpCode, $headers);
    }

    /**
     * Handle demoLogin for X Demo Account
     *
     * @return Response
     */
    public function postDemoSignin()
    {
        list($error, $data) = (new User\Service)->demoLogin();

        $result = AppResponse::jsonResponse($error, $data);

        return $result;
    }

    /**
     * Handle the authentication request from the user for OTP logins and send OTP.
     *
     * @return Response
     */
    public function postSendLoginOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data, $httpCode) = (new User\Service)->otpLogin($input);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::SEND_LOGIN_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::SEND_LOGIN_OTP, $input, $error, $timeTaken);

        if((isset($error[UserConstants::INTERNAL_ERROR_CODE]) === true) and
            (in_array($error[UserConstants::INTERNAL_ERROR_CODE], Admin\ApiRequestAny::INTERNAL_ERROR_CODES) === true))
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    /**
     * Handle the request to verify the user and send OTP.
     *
     * @return Response
     */
    public function postSendVerifyUserOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data) = (new User\Service)->otpVerifyUser($input);

        $timeEnd = microtime(true);

        $timeTaken = $timeEnd - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::SEND_USER_VERIFY_OTP_DURATION);

        if((isset($error[UserConstants::INTERNAL_ERROR_CODE]) === true) and
            (in_array($error[UserConstants::INTERNAL_ERROR_CODE], Admin\ApiRequestAny::INTERNAL_ERROR_CODES) === true))
        {
            $error = [$error];

            $login_medium  = isset($input['email']) ? MetricConstants::EMAIL : MetricConstants::CONTACT_MOBILE;

            $this->metrics->count(MetricConstants::LOGIN_OTP_FAILED,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::LOGIN_MEDIUM                => $login_medium,
                    MetricConstants::LOGIN_METHOD                => MetricConstants::OTP,
                    MetricConstants::LABEL_API_BASE_URL          => ApiUrl::getApiHost(),
                ]);
        }

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Handle the authentication request from the user for OTP logins and send OTP.
     *
     * @return Response
     */
    public function postVerifyLoginOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data, $httpCode) = (new User\Service)->verifyOtpLogin($input);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_LOGIN_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::VERIFY_LOGIN_OTP, $input, $error, $timeTaken);

        $headers = $this->getMobileOauthHeaders($data, $error);

        return AppResponse::jsonResponse($error, $data, $httpCode, $headers);
    }

    /**
     * Handle the 2FA with password on OTP based login
     *
     * @return Response
     */
    public function postOtpLogin2faPassword()
    {
        $timeStarted = microtime(true);
        $input = Input::all();

        list($error, $data) = (new User\Service)->verify2FAMode($input, UserConstants::LOGIN_2FA_WITH_PASSWORD);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::OTP_LOGIN_2FA_PASSWORD_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::OTP_LOGIN_2FA_PASSWORD, $input, $error, $timeTaken);

        $headers = $this->getMobileOauthHeaders($data, $error);

        return AppResponse::jsonResponse($error, $data, null, $headers);
    }

    /**
     * Handle the authentication request from the user for OTP logins and send OTP.
     *
     * @return Response
     */
    public function postVerifyUserOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data) = (new User\Service)->verifyVerificationOtp($input);

        $dimensions = [
            MetricConstants::LOGIN_METHOD       => MetricConstants::OTP,
            MetricConstants::LOGIN_MEDIUM       => MetricConstants::EMAIL,
            MetricConstants::LOGIN_ACTION       => MetricConstants::OTP_LOGIN,
            MetricConstants::LABEL_API_BASE_URL =>ApiUrl::getApiHost(),
        ];

        if (empty($error) === true)
        {
            $this->metrics->count(MetricConstants::USER_VERIFY_COUNT,
                EVENT_TRIGGER_COUNT,
                $dimensions
            );
        } else {
            $this->metrics->count(MetricConstants::USER_LOGIN_VERIFY_OTP_FAIL_COUNT,
                EVENT_TRIGGER_COUNT,
                $dimensions
            );
        }

        $timeEnd = microtime(true);

        $timeTaken = $timeEnd - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_VERIFICATION_OTP_DURATION);

        $headers = $this->getMobileOauthHeaders($data, $error);

        return AppResponse::jsonResponse($error, $data, null, $headers);
    }

    public function oauthLogout()
    {
        $genericController = new GenericController();

        return $genericController->handleAny('live', 'users/mobile_oauth/logout');
    }

    public function postOauthSignIn()
    {
        $timeStart = microtime(true);
        $input = Input::all();

        try
        {
            list($error, $data, $httpCode) = (new User\Service)->oauthSignIn($input);
        }
        catch (RecoverableException $e)
        {
            $error = [$e->getMessage()];
            $data  = null;
            $httpCode = 400;
        }

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_OAUTH_LOGIN_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_OAUTH_LOGIN, $input, $error, $timeTaken);

        $headers = $this->getMobileOauthHeaders($data, $error);

        return AppResponse::jsonResponse($error, $data, $httpCode, $headers);
    }

    /**
     * Handle the authentication request from the user.
     *
     * @return Response
     */
    public function postSetup2faVerifyOtp()
    {
        $timeStarted = microtime(true);
        $input = Input::all();

        list($error, $data) = (new User\Service)->verify2FAMode($input, UserConstants::LOGIN_2FA_WITH_OTP);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::PASSWORD_LOGIN_2FA_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::PASSWORD_LOGIN_2FA_OTP, $input, $error, $timeTaken);

        $headers = $this->getMobileOauthHeaders($data, $error);

        return AppResponse::jsonResponse($error, $data, null, $headers);
    }

    /**
     * Forwards OTP verification request to API
     */
    public function verifyUserViaOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)
            ->verifyOtpAndMarkUserTwoFactorVerified($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Same operation as above using different URL
     */
    public function verifyContact()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)
            ->verifyUserContactAndMarkUserTwoFactorVerified($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * @return Response
    */
    public function postUpdate2faContact()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postUpdate2faContact($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postSetPassword()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postSetPassword($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postUserExists()
    {
        $input = Input::all();

        list($error, $data, $httpCode) = (new User\Service)->postUserExists($input);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postSendEmailOtp()
    {
        $input = Input::all();

        list($error, $data, $httpCode) = (new User\Service)->postSendEmailOtp($input);

        if(isset($error["internal_error_code"]) and
            in_array($error['internal_error_code'], Admin\ApiRequestAny::INTERNAL_ERROR_CODES)
        )
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postVerifyEmailOtp()
    {
        $input = Input::all();

        list($error, $data, $httpCode) = (new User\Service)->postVerifyEmailOtp($input);

        if(isset($error["internal_error_code"]) and
            in_array($error['internal_error_code'], Admin\ApiRequestAny::INTERNAL_ERROR_CODES)
        )
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postUserDetailsToSalesforce()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postUserDetailsToSalesforce($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function post2faOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->post2faOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postResendOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postResendOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Log out the currently authenticated user.
     *
     * @return Response
     */
    public function getLogout()
    {
        $user = Auth::guard('user');

        $userDetails = $user->user();

        $traceData = [
            'id'          => $userDetails->id,
        ];

        $this->trace->info(TraceCode::USER_LOGOUT, $traceData);


       $this->metrics->count(MetricConstants::USER_LOGOUT_COUNT,
           EVENT_TRIGGER_COUNT,
           [
               MetricConstants::LOGIN_METHOD                => $this->getLoginMethodFromSession(),
               MetricConstants::LABEL_API_BASE_URL          => ApiUrl::getApiHost(),
           ]);


        // revoke user token on edge using jti
        $this->revokeTokenOnEdge();

        $user->logoutCurrentDevice();

        // Clearing all session data as session keys like current_merchant_id are persisted even after logout

        Session::forget(User\Constants::OAUTH_LOGIN);

        Session::forget(User\Constants::TWO_FA_VERIFIED);

        Session::forget(User\Constants::USER_ID);

        Session::forget('logged_in_via');

        Session::forget('current_merchant_id');

        Session::forget('dashboard_user_payload');

        Session::forget('show_tnc_popup');

        Session::forget('is_merchant_login');

        Cookie::expire(AppConstants::RZP_ACCESS_TOKEN, AppConstants::ROOT_PATH);

        Cookie::expire(AppConstants::RZP_REFRESH_TOKEN, AppConstants::ROOT_PATH);

        return AppResponse::jsonResponse([]);
    }

    /**
     * revokes user token on edge
     * @return void
     */
    public function revokeTokenOnEdge() {
        try {
            $this->edgeClient->revokeToken();
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::EDGE_TOKEN_REVOKE_FAILED, [
                "message"   => "Failed to revoke user session token at edge for logout: " . $e->getMessage(),
            ]);

            // TODO: throw exception when we move out of shadow mode
            // return if user token can not be revoked at edge
            // we will not alter the sessions at redis unless edge tokens are revoked successfully
//            throw new ServerErrorException(
//                "Session deletion failed: " . $e->getMessage(),
//                \Razorpay\Api\Errors\ErrorCode::SERVER_ERROR,
//                500);
        }
    }

    /**
     * Handle the request from user to change his current password.
     *
     * @return Response
     */
    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->changePassword($input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Switch the merchant the user is currently viewing.
     *
     * @param  string  $merchantId
     * @return Response
     */
    public function switchCurrentMerchant($merchantId)
    {
        $input = Input::all();

        $user = Auth::user();

        $error = (new User\Service)->switchCurrentMerchantForUser($merchantId, $user);

        return AppResponse::jsonResponse($error);
    }

    public function resendEmailOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->resendEmailOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function verifyEmailOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->verifyEmailOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getUserDetailsV2()
    {
        $timeStart = microtime(true);

        $params = Input::all();

        list($error, $data) = (new User\Service)->getUserDetails($params);

        $result = AppResponse::jsonResponse($error, $data);

        $timeEnd = microtime(true);
        $timeTaken = $timeEnd - $timeStart;
        $this->traceDuration($timeTaken, TraceCode::GET_USER_DURATION);

        return $result;
    }

    public function getLoginMetadata()
    {
        $response = [];
        //Returns oauth or password depending on the login type
        $response['login_method'] = $this->getLoginMethodFromSession();

        return AppResponse::jsonResponse([], $response);
    }

    public function traceDuration($timeTaken, $traceCode){

        $user = Auth::user();

        if ($user !== null){
            $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

            $this->trace->info($traceCode, [
                "duration"    => $timeTaken, // seconds
                "user_id"     => $user->id,
                "merchant_id" => $currentMerchantId,
            ]);
        }
    }


    public function getUserDetailsForMobile()
    {
        list($error, $data) = (new User\Service)->getUserDetailsForMobile();

        return AppResponse::jsonResponse($error, $data);
    }

    public function postUpgradeUserToMerchant()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->upgradeUserToMerchant($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Fetch data for the currently active user session
     *
     * @return mixed
     */
    public function getSessionData()
    {
        $queryParams = Input::all();

        list($error, $data) = (new User\Service)->getSessionData($queryParams);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Creates an identity token for the logged in user and share the same via redirect URL
     *
     * @param string $clientId
     * @return mixed
     */
    public function getIdentityToken(string $clientId)
    {
        $queryParams = Input::all();

        list($error, $url) = (new User\Service)->generateIdentityToken($clientId, $queryParams);

        //
        // if there are no errors then do a 302 redirect to the service providers
        // callback url with the token
        //
        // if there is an error then we should redirect the user to login page
        // with the ?next param as the user/identifier. So that once the user
        // is authenticated successfully we can pass the token to service provider
        //
        if (empty($error) === true)
        {
            return redirect($url);
        }

        return AppResponse::jsonResponse($error, null);
    }


    private function isRazorxCachingEnabled(): bool
    {
        $experimentId = config('splitz.experiments')[Constants::RAZORX_CACHING_ENABLED];

        if (!array_key_exists($experimentId, $this->splitzExprimentData))
        {
            return false;
        }

        return ($this->splitzExprimentData[$experimentId]['variables']['result'] ?? null) === 'on';
    }

    /**
     * The auth-service gets details of the currently logged in user
     * using this route (once it has the token)
     *
     * @param string $token
     *
     * @return Response
     */
    public function getDetailsFromToken(string $token)
    {
        list($error, $data) = (new User\Service)->getDetailsFromSessionToken($token);

        $response = AppResponse::jsonResponse($error, $data);

        return $response;
    }

    /**
     * Return base template for browser extensions
     *
     * @return Response
     */
     public function getBrowserExtensionIndex()
     {
        $dashboardCdn = \Config::get('app.cdn_dashboard_url');
        $data['cdnUrl'] = $dashboardCdn ? substr($dashboardCdn, 0, -10) : "http://static.razorpay.in";
        return view('extension.index', $data);
     }

    /**
     * Generates a JWT token with the context and returns the token to the client.
     * https://github.com/lcobucci/jwt
     */
    public function generateJWT()
    {
        list($error, $result) = (new User\Service)->generateJWT();

        return AppResponse::jsonResponse($error, $result);
    }

    /**
     * On extension logout if their is any user available we should log the user out.
     *
     * @return mixed
     */
    public function getExtensionLogout()
    {
        $user = Auth::guard('user');

        if (empty($user->user()) === false)
        {
            $userDetails = $user->user();

            $traceData = [
                'id'          => $userDetails->id,
            ];

            $this->trace->info(TraceCode::USER_LOGOUT, $traceData);

            $user->logout();
        }

        return AppResponse::jsonResponse([]);
    }

    public function validateJWT()
    {
        return ['success' => true];
    }

    public function getPartnerConfig()
    {
        $input = Input::all();

        list($error, $data, $httpCode) = (new User\Service)->getPartnerConfig($input);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    private function getDashboardBaseUrl()
    {
        $isSessionSecure = \Config::get('session.secure');

        if ($isSessionSecure === true)
        {
            $connection = 'https://';
        }
        else
        {
            $connection = 'http://';
        }

        return $connection . \Request::server('SERVER_NAME');
    }

    private function getLoginMethodFromSession()
    {
        $isOauthLogin = Session::get(User\Constants::OAUTH_LOGIN, false);

        return $isOauthLogin === true ? MetricConstants::OAUTH : MetricConstants::PASSWORD;
    }

    private function isRedirectionApplicableForFtux($details)
    {
        try
        {
            $isSubMerchant = $details[MetricConstants::IS_SUB_MERCHANT] ?? false;
            $partnerType = $details[MetricConstants::PARTNER_TYPE] ?? null;

            if($isSubMerchant === true || empty($partnerType) === false || $this->isEligibleForPos($details) === true)
            {
                return false;
            }

            $signupCampaign = $details['user']['signup_campaign'] ?? null;

            if ($signupCampaign !== 'easy_onboarding')
            {
                return false;
            }

            if($this->isFtuxExperimentEnabled() === false)
            {
                return false;
            }

            if(empty($_COOKIE['ftuxSession']) === false)
            {
                return false;
            }

            if($this->isFtuxAfterL2ExperimentEnabled($details) === true and $details[MerchantConstants::ACTIVATION_STATUS] === null)
            {
                return false;
            }

            if($details['activation_status'] !== 'activated' and $details['activation_status'] !== 'activated_mcc_pending')
            {
                return true;
            }

            if($details['isTransacted'] === false)
            {
                return true;
            }

            $request = new ApiRequestAny(['client_type' => 'merchant']);

            list($configError, $configData) = $request->send("merchants/config/store?namespace=onboarding", "GET");

            if(empty($configError) === false)
            {
                $this->trace->info(TraceCode::GET_CONFIG_STORE_KEYS_FAILED, [
                    'error' => $configError[0],
                    'code'  => ErrorCode::BAD_REQUEST_ERROR
                ]);

                return false;
            }

            if($configData['show_ftux_final_screen'] === true)
            {
                return true;
            }

            return false;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::FTUX_DASHBOARD_REDIRECTION_FAILED,
                [
                    "exception" => $e->getMessage()
                ]
            );

            return false;
        }

    }

    private function isFtuxExperimentEnabled()
    {
        $experimentId = config('splitz.experiments')[self::ONBOARDING_FTUX];

        if (!array_key_exists($experimentId, $this->splitzExprimentData))
        {
            return false;
        }
        return ($this->splitzExprimentData[$experimentId][Constants::VARIABLES][Constants::RESULT] ?? null) === 'on';
    }
    
    private function isFtuxAfterL2ExperimentEnabled()
    {

        $experimentId = config('splitz.experiments')[self::ONBOARDING_FTUX_AFTER_L2];

        if (!array_key_exists($experimentId, $this->splitzExprimentData))
        {
            return false;
        }
        return ($this->splitzExprimentData[$experimentId][Constants::VARIABLES][Constants::RESULT] ?? null) === 'on';
    }

    private function isEligibleForPos($details)
    {

        $physicalStore = $details[MerchantConstants::MERCHANT_BUSINESS_DETAIL][MerchantConstants::WEBSITE_DETAILS][MerchantConstants::PHYSICAL_STORE] ?? false;

        if($physicalStore !== true) {
            return false;
        }

        $experimentId = config('splitz.experiments')[self::ELIGIBLE_FOR_POS];

        if (!array_key_exists($experimentId, $this->splitzExprimentData))
        {
            return false;
        }
        return ($this->splitzExprimentData[$experimentId][Constants::VARIABLES][Constants::RESULT] ?? null) === 'on';
    }

    private function getHttpClient(string $baseUrl, $timeOut): Guzzle
    {
        return new Guzzle([
            'base_uri' => $baseUrl,
            'defaults' => [
                'timeout' => $timeOut,
            ]
        ]);
    }
}
