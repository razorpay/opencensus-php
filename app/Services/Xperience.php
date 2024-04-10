<?php

namespace RZP\Services;

use Config;
use Illuminate\Http\Request;
use Razorpay\Edge\Passport\Passport;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Models\Feature\Constants as Features;
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
    const CREATE_COST_CENTER_PATH            = 'v1/cost-centers';
    const GET_COST_CENTERS_PATH              = 'v1/cost-centers';
    const SINGLE_COST_CENTER_PATH            = 'v1/cost-centers/%s';
    const DISABLE_COST_CENTER_PATH           = 'v1/cost-centers/%s/disable';
    const SINGLE_USER_DETAILS_PATH           = 'v1/users/%s';
    const LIST_USER_DETAILS_PATH             = 'v1/users';
    const ADD_USER_PATH                      = 'v1/users';
    const LIST_GROUPS_OF_USER                = 'v1/user/%s/groups';
    const LIST_USERS_OF_GROUP                = 'v1/group/%s/users';
    const REMOVE_GROUP_OF_USERS              = 'v1/users/group';
    const ADD_GROUP_FOR_USERS                = 'v1/users/group';
    const SINGLE_GROUP                       = 'v1/groups/%s';
    const LIST_GROUPS                        = 'v1/groups';
    const CREATE_GROUP                       = 'v1/groups';
    const LIST_GROUP_TYPES                   = 'v1/group-types';
    const CREATE_GROUP_TYPE                  = 'v1/group-types';
    const GET_MULTIPLE_USER_DETAILS_PATH     = 'v1/users';
    const USER_INVITE_ACCEPTED_PATH          = 'v1/users/callbacks/invite-accepted';
    const BULK_CREATE_USER_DETAILS_PATH      = 'v1/bulk-users';
    const BULK_CREATE_USER_DETAILS_RAW_PATH  = 'v1/bulk-users-raw';

    const PENDING_ENTITIES_SUMMARY_EMAIL_PATH = 'v1/aggregator/send-pending-entities-email';


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
    const POST   = 'POST';
    const GET    = 'GET';
    const PUT    = 'PUT';
    const DELETE = 'DELETE';
    const PATCH  = 'PATCH';

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

        if (empty($queryString) and !empty($body) and $method === Request::METHOD_GET) // Dashboard requests
        {
            $queryStringFromBody = http_build_query($body);

            $url .= $urlAppend . $queryStringFromBody;

            $content = [];
        }
        elseif (!empty($queryString)) // Non-dashboard requests
        {
            $url .= $urlAppend . $queryString;
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
            $requestData = !empty($data) ? json_encode($data) : null;
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
                    'response' => $response->body,
                ]);

            throw new ServerErrorException(
                'Internal Server Error occurred',
                ErrorCode::SERVER_ERROR,
                [
                    'errorDetail' => $response->body,
                    'status_code' => $response->status_code,
                ]);
        }
        else if ($response->status_code >= 400)
        {
            $this->trace->error(
                TraceCode::XPERIENCE_SERVICE_CLIENT_ERROR,
                [
                    'response' => $response->body,
                ]);

            if ($response->status_code == 400)
            {
                if (isset($parsedResponse['message']) and !empty($parsedResponse['message']))
                {
                    $description = $parsedResponse['message'];

                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR, null,
                        [
                            'errorDetail' => $response->body,
                            'status_code' => $response->status_code,
                        ], $description);
                }
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null,
                [
                    'errorDetail' => $response->body,
                    'status_code' => $response->status_code,
                ], 'Something went wrong. Please try again.');
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

    public function userInviteAccepted(array $input)
    {
        $url = $this->getConstructedUrl(self::USER_INVITE_ACCEPTED_PATH);

        $response = $this->makeRequest($url, $input);

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

    public function createCostCenters(array $input)
    {
        $url = $this->getConstructedUrl(self::CREATE_COST_CENTER_PATH);

        $response = $this->makeRequest($url, $input);

        return $response;
    }

    public function getCostCenters(array $queryParams)
    {
        $url = $this->getConstructedUrl(self::GET_COST_CENTERS_PATH);

        $response = $this->makeRequest($url, $queryParams, [], self::GET);

        return $response;
    }

    public function getCostCenter(string $costCenterId)
    {
        $url = $this->getConstructedUrl(sprintf(self::SINGLE_COST_CENTER_PATH, $costCenterId));

        $response = $this->makeRequest($url, [], [], self::GET);

        return $response;
    }

    public function updateCostCenter(string $costCenterId, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::SINGLE_COST_CENTER_PATH, $costCenterId));

        $response = $this->makeRequest($url, $input, [], self::PUT);

        return $response;
    }

    public function disableCostCenter(string $costCenterId)
    {
        $url = $this->getConstructedUrl(sprintf(self::DISABLE_COST_CENTER_PATH, $costCenterId));

        $response = $this->makeRequest($url, [], [], self::POST);

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

    public function addUser(array $input)
    {
        $url = $this->getConstructedUrl(self::ADD_USER_PATH);

        return $this->makeRequest($url, $input);
    }

    public function deleteUser(string $id)
    {
        $url = $this->getConstructedUrl(sprintf(self::SINGLE_USER_DETAILS_PATH, $id));

        return $this->makeRequest($url, [], [], self::DELETE);
    }

    public function editUser(string $id, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::SINGLE_USER_DETAILS_PATH, $id));

        return $this->makeRequest($url, $input, [], self::PATCH);
    }

    public function getUser(string $id, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::SINGLE_USER_DETAILS_PATH, $id));

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function listUsers(array $input)
    {
        $url = $this->getConstructedUrl(self::LIST_USER_DETAILS_PATH);

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function listGroupsOfUser(string $id, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::LIST_GROUPS_OF_USER, $id));

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function listUsersOfGroup(string $id, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::LIST_USERS_OF_GROUP, $id));

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function removeGroupOfUsers(array $input)
    {
        $url = $this->getConstructedUrl(self::REMOVE_GROUP_OF_USERS);

        return $this->makeRequest($url, $input, [], self::DELETE);
    }

    public function addGroupForUsers(array $input)
    {
        $url = $this->getConstructedUrl(self::ADD_GROUP_FOR_USERS);

        return $this->makeRequest($url, $input, [], self::POST);
    }

    public function updateGroup(string $id, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::SINGLE_GROUP, $id));

        return $this->makeRequest($url, $input, [], self::PATCH);
    }

    public function listGroups(array $input)
    {
        $url = $this->getConstructedUrl(self::LIST_GROUPS);

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function getGroup(string $id, array $input)
    {
        $url = $this->getConstructedUrl(sprintf(self::SINGLE_GROUP, $id));

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function createGroup(array $input)
    {
        $url = $this->getConstructedUrl(self::CREATE_GROUP);

        return $this->makeRequest($url, $input, [], self::POST);
    }

    public function listGroupTypes(array $input)
    {
        $url = $this->getConstructedUrl(self::LIST_GROUP_TYPES);

        return $this->makeRequest($url, $input, [], self::GET);
    }

    public function createGroupType(array $input)
    {
        $url = $this->getConstructedUrl(self::CREATE_GROUP_TYPE);

        return $this->makeRequest($url, $input, [], self::POST);
    }

    public function bulkCreateUserDetails(array $input)
    {
        $url = $this->getConstructedUrl(self::BULK_CREATE_USER_DETAILS_PATH);

        return $this->makeRequest($url, $input, [], self::POST);
    }

    public function bulkCreateUserDetailsRaw(array $input)
    {
        $url = $this->getConstructedUrl(self::BULK_CREATE_USER_DETAILS_RAW_PATH);

        return $this->makeRequest($url, $input, [], self::POST);
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

    public function sendPendingApprovalsEmail()
    {
        $url = $this->getConstructedUrl(self::PENDING_ENTITIES_SUMMARY_EMAIL_PATH);

        $this->makeRequest($url, [], [], self::GET);

        return ['success' => true];
    }
}

