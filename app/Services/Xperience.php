<?php

namespace RZP\Services;

use Config;
use Illuminate\Http\Request;
use Razorpay\Edge\Passport\Passport;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\Environment;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Http\Response\StatusCode;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\User\Core as UserCore;
use RZP\Trace\TraceCode;

/**
 * Class Xperience
 *
 * @package RZP\Services
 *
 * No validations will happen here.
 * This will just call the right endpoints and return the responses as is
 * If there is an error thrown from the MicroService, that same error with
 * the right error code will be sent back to the caller
 *
 */
class Xperience
{
    // RPCs
    const BULK_PAYOUT_GET_PATH               = 'v1/bulk-payouts/%s';
    const BULK_PAYOUTS_FETCH_MULTIPLE_PATH   = 'v1/bulk-payouts';
    const OWNER_REJECT_BULK_PAYOUTS_PATH     = 'v1/bulk-payouts/reject/owner';
    const GET_PENDING_BULK_PAYOUTS_PATH      = 'v1/bulk-payouts/pending';
    const APPROVE_BULK_PAYOUTS_PATH          = 'v1/bulk-payouts/approve';
    const REJECT_BULK_PAYOUTS_PATH           = 'v1/bulk-payouts/reject';
    const WORKFLOW_SUMMARY_PATH              = 'v1/bulk-payouts/workflow/summary?user_role=%s';
    const GET_BULK_PAYOUTS_META_SUMMARY_PATH = 'v1/bulk-payouts/_meta/summary';
    const CREATE_BULK_PAYOUT_PATH            = 'v1/bulk-payouts';
    const GET_BULK_PAYOUT_ROWS_PATH          = 'v1/bulk-payouts/%s/rows';
    const PROCESS_BULK_PAYOUT_PATH           = 'v1/bulk-payouts/%s/process';
    const MIGRATE                            = 'v1/bulk-payouts/migrate';


    // header constants
    const X_APP_MODE            = 'X-App-Mode';
    const X_REQUEST_ID          = 'x-razorpay-request-id';
    const X_TASK_ID             = 'X-Task-ID';
    const X_RAZORPAY_USER_ID    = 'X-Razorpay-User-Id';
    const X_RAZORPAY_USER_NAME  = 'X-Razorpay-User-Name';
    const X_RAZORPAY_USER_EMAIL = 'X-Razorpay-User-Email';
    const X_RAZORPAY_USER_ROLE  = 'X-Razorpay-User-Role';
    const CONTENT_TYPE          = 'Content-Type';
    const JSON_CONTENT          = 'application/json';
    const BASIC_AUTH_USER       = 'api';

    // http method constants
    const POST = 'POST';
    const GET  = 'GET';

    // parameter constants
    const IS_BULK_WORKFLOW_ENABLED = 'is_bulk_workflow_enabled';
    const SCHEDULED_AT             = 'scheduled_at';
    const BULK_PAYOUT_IDS          = 'bulk_payout_ids';
    const ACCOUNT_NUMBERS          = 'account_numbers';
    const STATUS                   = 'status';
    const USER_COMMENT             = 'user_comment';
    const FILE                     = 'file';
    const USER_DETAILS             = 'user_details';
    const USER_EMAIL               = 'user_email';
    const USER_MOBILE              = 'user_mobile';
    const USER_NAME                = 'user_name';
    const USER_ROLE                = 'user_role';
    const TOKEN                    = 'token';
    const OTP                      = 'otp';
    const EMAIL                    = 'email';
    const NAME                     = 'name';
    const CONTACT                  = 'contact';
    const ROLE                     = 'role';

    protected $baseUrl;

    protected $secret;

    protected $repo;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    protected $app;

    protected $walletService;

    protected $timeout;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $xperienceConfig = $this->config->get('applications.xperience');

        $this->baseUrl = $xperienceConfig['url'];

        $this->secret = $xperienceConfig['secret'];

        $this->timeout = $xperienceConfig['timeout'];

        $this->repo = $app['repo'];

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

    protected function getConstructedUrl(string $path)
    {
        return sprintf('%s/%s', $this->baseUrl, $path);
    }

    /**
     * Dashboard backend passes the get params in request body. In case of GET request, this function extracts the query
     * params from request body and adds them in the URL, setting the request body to empty array.
     *
     * @param $url
     * @param $content
     *
     * @return void
     */
    public function processQueryParamsForDashboardGetRequest(&$url, &$content): void
    {
        /* @var Request $request */
        $request = $this->app['request'];

        $body = $request->post();

        $method = $request->getMethod();

        $queryString = $request->getQueryString();

        $urlAppend = '?';

        if (empty($queryString) === true and
            empty($body) === false and
            $method === Request::METHOD_GET)
        {
            $queryParams = http_build_query($body);

            $url .= $urlAppend . $queryParams;

            $content = [];
        }
    }

    /**
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    protected function makeRequest(string $url,
                                   array  $data = [],
                                   array  $headers = [],
                                   string $method = self::POST,
                                   string $mode = Mode::LIVE)
    {
        $globalHeaders = [
            self::CONTENT_TYPE        => self::JSON_CONTENT,
            self::X_REQUEST_ID        => $this->app['request']->getId(),
            self::X_TASK_ID           => $this->app['request']->getTaskId(),
            self::X_APP_MODE          => $mode,
            Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl),
        ];

        $headers = array_merge($headers, $globalHeaders);

        $devstackLabel = $this->app['request']->header(RequestHeader::DEV_SERVE_USER);

        if ($this->app['env'] !== Environment::PRODUCTION && empty($devstackLabel) === false)
        {
            $headers[RequestHeader::DEV_SERVE_USER] = $devstackLabel;
        }

        $options = [
            'auth'    => [
                self::BASIC_AUTH_USER,
                $this->secret
            ],
            'timeout' => $this->timeout,
        ];

        // Add user details in headers if applicable
        $user = $this->app['basicauth']->getUser();

        if ($user !== null)
        {
            $headers[self::X_RAZORPAY_USER_ID] = $user->getId();

            $headers[self::X_RAZORPAY_USER_EMAIL] = $user->getEmail();

            $headers[self::X_RAZORPAY_USER_NAME] = $user->getName();

            $headers[self::X_RAZORPAY_USER_ROLE] = $this->app['basicauth']->getUserRole();
        }

        // JSON encode and add request body if it's not a GET request
        if ($method === self::GET)
        {
            // Dashboard backend passes the get params in request body
            $this->processQueryParamsForDashboardGetRequest($url, $data);
            $requestData = ''; // GET request doesn't have request body
        }
        else
        {
            $requestData = json_encode($data);
        }

        $this->trace->info(TraceCode::XPERIENCE_SERVICE_REQUEST,
                           [
                               'url'  => $url,
                               'mode' => $mode,
                           ]);

        $response = Requests::request(
            $url,
            $headers,
            $requestData,
            $method,
            $options);

        $this->trace->info(TraceCode::XPERIENCE_SERVICE_RESPONSE,
                           [
                               'url'         => $url,
                               'status_code' => $response->status_code
                           ]);

        $parsedResponse = json_decode($response->body, true);

        if ($response->status_code >= 500)
        {
            $this->trace->error(
                TraceCode::XPERIENCE_SERVICE_SERVER_ERROR,
                [
                    'error' => $parsedResponse['error'] ?? json_encode($response->body, true),
                ]);

            throw new ServerErrorException(
                'Internal Server Error occurred',
                ErrorCode::SERVER_ERROR);
        }
        else
        {
            if ($response->status_code >= 400)
            {
                if (empty($parsedResponse['error']) === false)
                {
                    $error = $parsedResponse['error'];
                }
                else
                {
                    $error = json_encode($response->body, true);
                }
                $this->trace->error(
                    TraceCode::XPERIENCE_SERVICE_CLIENT_ERROR,
                    [
                        'error' => $error,
                    ]);

                if ($response->status_code == 400)
                {
                    if (isset($error['description']))
                    {
                        $description = $error['description'];

                        throw new BadRequestException(
                            ErrorCode::BAD_REQUEST_ERROR, null,
                            [
                                'errorDetail' => $response->body
                            ], $description);
                    }
                }
                else
                {
                    throw new BadRequestException(
                        ErrorCode::SERVER_ERROR, null,
                        [
                            'errorDetail' => $response->body
                        ], $error);
                }
            }
        }

        return json_decode($response->body, true);
    }

    public function ownerBulkRejectBulkPayouts(array $input)
    {
        $ba = app('basicauth');

        $user = $ba->getUser();

        $bulkRejectPayload = [
            self::USER_COMMENT    => $input[self::USER_COMMENT],
            self::USER_EMAIL      => $user->getEmail(),
            self::USER_NAME       => $user->getName(),
            self::BULK_PAYOUT_IDS => $input[self::BULK_PAYOUT_IDS],
        ];

        $url = $this->getConstructedUrl(self::OWNER_REJECT_BULK_PAYOUTS_PATH);

        $response = $this->makeRequest($url, $bulkRejectPayload);

        return $response;
    }

    public function getBulkPayoutById(string $bulkPayoutId)
    {

        $url = $this->getConstructedUrl(sprintf(self::BULK_PAYOUT_GET_PATH, $bulkPayoutId));

        $response = $this->makeRequest($url, [], [], self::GET);

        return $response;
    }

    public function getBulkPayouts(array $queryParams)
    {

        $url = $this->getConstructedUrl(self::BULK_PAYOUTS_FETCH_MULTIPLE_PATH);

        $response = $this->makeRequest($url, $queryParams, [], self::GET);

        return $response;
    }

    public function getPendingBulkPayouts(array $input)
    {
        $url = $this->getConstructedUrl(self::GET_PENDING_BULK_PAYOUTS_PATH);

        $request = [
            self::ACCOUNT_NUMBERS => $input[self::ACCOUNT_NUMBERS],
        ];

        $response = $this->makeRequest($url, $request);

        return array_pull($response, "pending_bulk_payouts", array());
    }

    public function approveBulkPayouts(array $input)
    {
        $url = $this->getConstructedUrl(self::APPROVE_BULK_PAYOUTS_PATH);

        $ba = app('basicauth');

        $user = $ba->getUser();

        $request = [
            self::BULK_PAYOUT_IDS => $input[self::BULK_PAYOUT_IDS],
            self::USER_COMMENT    => $input[self::USER_COMMENT],
            self::USER_NAME       => $user->getName(),
            self::USER_EMAIL      => $user->getEmail(),
            self::USER_MOBILE     => $user->getContactMobile(),
            self::USER_ROLE       => $ba->getUserRole(),
            self::OTP             => $input[self::OTP],
            self::TOKEN           => $input[self::TOKEN],
        ];

        $response = $this->makeRequest($url, $request);

        return $response;
    }

    public function rejectBulkPayouts(array $input)
    {
        $url = $this->getConstructedUrl(self::REJECT_BULK_PAYOUTS_PATH);

        $ba = app('basicauth');

        $user = $ba->getUser();

        $request = [
            self::BULK_PAYOUT_IDS => $input[self::BULK_PAYOUT_IDS],
            self::USER_COMMENT    => $input[self::USER_COMMENT],
            self::USER_EMAIL      => $user->getEmail(),
            self::USER_NAME       => $user->getName(),
            self::USER_ROLE       => $ba->getUserRole(),
        ];

        $response = $this->makeRequest($url, $request);

        return $response;
    }

    public function workflowSummary()
    {
        $userRole = app('basicauth')->getUserRole() ?? '';

        $url = $this->getConstructedUrl(sprintf(self::WORKFLOW_SUMMARY_PATH, $userRole));

        $response = $this->makeRequest($url, [], [], self::GET);

        return $response;
    }

    public function migrateBulkPayouts(array $input)
    {
        $url = $this->getConstructedUrl(self::MIGRATE);

        $response = $this->makeRequest($url, $input);

        return $response;
    }

    public function getBulkPayoutsMetaSummary()
    {
        $url = $this->getConstructedUrl(self::GET_BULK_PAYOUTS_META_SUMMARY_PATH);

        return $this->makeRequest($url, [], [], self::GET);
    }

    public function createBulkPayout(array $input)
    {
        $url = $this->getConstructedUrl(self::CREATE_BULK_PAYOUT_PATH);

        $fileWithMime = array_pull($input, self::FILE, '');

        $fileArr = explode(',', $fileWithMime);

        $input[self::FILE] = $fileArr[1];

        return $this->makeRequest($url, $input);
    }

    public function getBulkPayoutRows(string $bulkPayoutId, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::GET_BULK_PAYOUT_ROWS_PATH, $bulkPayoutId));

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function processBulkPayout(string $bulkPayoutId, array $input)
    {
        $input[self::IS_BULK_WORKFLOW_ENABLED] = $this->isBulkPayoutsWorkflowEnabled();

        $input[self::USER_DETAILS] = $this->getUserDetails();

        $scheduledAt = array_pull($input, self::SCHEDULED_AT, '');

        if (empty($scheduledAt) === true)
        {
            $scheduledAt = 0;
        }

        $input[self::SCHEDULED_AT] = $scheduledAt;

        $url = $this->getConstructedUrl(sprintf(self::PROCESS_BULK_PAYOUT_PATH, $bulkPayoutId));

        return $this->makeRequest($url, $input);
    }

    protected function isBulkPayoutsWorkflowEnabled(): bool
    {
        $ba = app('basicauth');

        $merchant = $ba->getMerchant();

        return (($merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS))
                and ($merchant->isFeatureEnabled(Features::BULK_PAYOUT_WORKFLOW)));
    }

    protected function getUserDetails(): array
    {
        $ba = app('basicauth');

        $user = $ba->getUser();

        return [
            self::NAME    => $user->getName(),
            self::CONTACT => $user->getContactMobile(),
            self::EMAIL   => $user->getEmail(),
            self::ROLE    => $ba->getUserRole(),
        ];
    }
}

