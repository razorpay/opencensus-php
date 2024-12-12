<?php

namespace App\Services\MerchantExperienceService;

use Auth;
use App\Trace\Trace;
use App\Http\ApiUrl;
use App\Http\Headers;
use GuzzleHttp\Client;
use App\Trace\TraceCode;
use App\Metrics\Constants;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Response;
use Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use App\User\Constants as UserConstants;
use Razorpay\Api\Errors\BadRequestError;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;

class MerchantExperienceServiceRequest {
    const AuthKey           = "auth";

    const Headers           = "headers";
    const UserAgentKey      = "User-Agent";
    const HeaderRequestId   = "X-Request-ID";
    const HeaderUserAgent   = "X-User-Agent";
    const HeaderUserId      = "X-User-Id";
    const HeaderUserName      = "X-User-Name";
    const HeaderUserEmail   = "X-User-Email";
    const HeaderMerchantId  = "X-Razorpay-Merchant-Id";
    const HeaderMode        = "X-Razorpay-Mode";
    const ContentType       = "Content-Type";
    const ContentTypeApplicationJson = "application/json";

    const ClientTypeMerchant    = "merchant";
    const ClientTypeAdmin       = "admin";
    const ClientTypeUser        = "user";
    const ClientTypeInternal    = "internal";
    const HeaderUserRole = 'X-User-Role';
    const Header2faVerified = 'X-Dashboard-User-2FA-Verified';
    const ADMIN_AS_MERCHANT     = 'admin_as_merchant';
    const HeaderRazorpayAccount       = 'X-Razorpay-Account';
    const HeaderDashboardAdminAsMerchant = 'X-Dashboard-AdminLoggedInAsMerchant';
    const HeaderDashboardAdminId = 'X-Dashboard-AdminLoggedInAsMerchant-AdminId';


    const ModeLive = "live";
    const ModeTest = "test";
    /**
     * Guzzle Client instance
     *
     * @var Client|null
     */
    protected ?Client $client;

    /**
     * @var \App\Trace\Trace|mixed|null
     */
    private ?Trace $trace;

    private $app;

    private array $requestOptions;

    protected $metrics;

    protected int $startTime;

    protected int $endTime;

    protected string $path;

    protected string $method;

    protected string $baseUri;

    protected string $clientType;

    protected string $mode;
    private string $username;
    private string $password;

    /**
     * @param string $path
     * @param string $method
     * @param array  $requestOptions
     *              client => guzzle client
     *              client_type => admin, user, internal, merchant
     *              mode => live, test
     *
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function __construct(
        string $path,
        string $method="post",
        array $requestOptions = []
    ) {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app["trace"];

        $this->metrics = $this->app['metrics'];

        $this->path = $path;

        $this->method = $method;

        $this->client = array_pull($requestOptions, "client");

        $this->determineClientType(array_pull($requestOptions, "client_type"));

        $this->mode = array_pull($requestOptions, "mode", self::ModeLive);

        $this->populateDefaultRequestHeaders($requestOptions);

        if ($this->client === null) {
            $this->client = self::getMesClient($requestOptions);
        }
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function send(array $body=[]): array
    {
        $options = $this->requestOptions;
        $options["body"] = ! empty($body) ? json_encode($body) : null;;
        $request = new Request($this->getMethod(), $this->getPath());
        $httpCode = Response::HTTP_OK;
        $data = [];

        try
        {
            $this->setStartTime();

            $response = $this->client->send($request, $options);

            $this->setEndTime();

            $data = json_decode($response->getBody()->getContents(), true);

            $httpCode = $response->getStatusCode();

            $this->trace->error(TraceCode::MES_CALL_SUCCESSFUL, [
                "code"  => $httpCode,
            ]);
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            $this->trace->error(TraceCode::MES_CALL_FAILED, [
                "code"  => $e->getCode(),
                "error" => $e->getMessage(),
            ]);

            $this->setEndTime();

            $data = json_decode($e->getResponse()->getBody(), true);

            $httpCode = $e->getResponse()->getStatusCode();
        }
        catch(\GuzzleHttp\Exception\ServerException $e)
        {
            $this->trace->error(TraceCode::MES_CALL_FAILED, [
                "code"  => $e->getCode(),
                "error" => $e->getMessage(),
            ]);

            $this->setEndTime();

            $httpCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

            $data = ["error" =>["description" => $e->getMessage()]];
        }
        catch(ConnectException $e)
        {
            $this->trace->error(TraceCode::MES_CALL_FAILED, [
                "code"  => $e->getCode(),
                "error" => $e->getMessage(),
            ]);

            $this->setEndTime();

            $httpCode = $e->getCode() ?? Response::HTTP_INTERNAL_SERVER_ERROR;

            $data = ["error" =>["description" => $e->getMessage()]];
        }
        catch(GuzzleException $e)
        {
            $this->trace->error(TraceCode::MES_CALL_FAILED, [
                "code"  => $e->getCode(),
                "error" => $e->getMessage(),
            ]);

            $this->setEndTime();

            $httpCode = $e->getCode() ?? Response::HTTP_INTERNAL_SERVER_ERROR;

            $data = ["error" =>["description" => $e->getMessage()]];
        }

        $this->pushDataToMetric($httpCode);

        return [$httpCode, $data];
    }

    /**
     * @param array $options
     *
     * @return \GuzzleHttp\Client
     */
    private function getMesClient(array $options = []): Client
    {
        $this->baseUri = array_get($options, 'base_uri', Config::get("mes.base_url"));

        return new Client([
            'base_uri' => $this->baseUri,
            'defaults' => [
                'timeout' => array_get($options, 'timeout', Config::get("mes.request_timeout")),
            ]
        ]);
    }

    /**
     * @param array $requestOptions
     *
     * @return void
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    private function populateDefaultRequestHeaders(array $requestOptions = []): void
    {
        $this->requestOptions = $requestOptions;

        if (count(array_get($this->requestOptions, self::Headers, [])) == 0) {
            $this->requestOptions[self::Headers] = [];
        }

        $this->requestOptions[self::Headers] = $this->requestOptions[self::Headers] +
            [
                self::HeaderRequestId   => $this->app["request"]->requestId,
                self::HeaderUserAgent   => request()->header(self::UserAgentKey),
                self::HeaderMode        => $this->mode ?? self::ModeLive,
                self::ContentType       => self::ContentTypeApplicationJson,
                Headers::DEV_SERVE_USER => \Request::header(Headers::DEV_SERVE_USER) ?? ''
            ];

        $this->processAuthHeaders();

        if (count(array_get($this->requestOptions, self::AuthKey, [])) == 0)
        {
            $this->requestOptions[self::AuthKey] = [$this->username, $this->password];
        }
    }

    /**
     * @return bool
     */
    private function isAdminLoggedIn(): bool
    {
        return Auth::guard('api')->check() === true;
    }

    /**
     * @return $this
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function processAuthHeaders(): static
    {
        if ($this->clientType === self::ClientTypeMerchant)
        {
            $this->processMerchantClientHeaders();

            $isAdminAsMerchant = $this->isAdminLoggedIn();

            $this->requestOptions[self::Headers][self::HeaderDashboardAdminAsMerchant] = $isAdminAsMerchant;

            $admin = Auth::guard('api')->user();

            if (empty($admin) === false)
            {
                $this->requestOptions[self::Headers][self::HeaderDashboardAdminId] = $admin->id;
            }

            $this->trace->info(TraceCode::ADMIN_LOGGED_IN_AS_MERCHANT, [
                self::ADMIN_AS_MERCHANT => $isAdminAsMerchant
            ]);
        }
        else if ($this->clientType === self::ClientTypeAdmin)
        {
            $this->processAdminClientHeaders();
        }
        else if ($this->clientType === self::ClientTypeUser)
        {
            $this->processUserClientHeaders();
        }
        else if ($this->clientType === self::ClientTypeInternal)
        {
            // used only by the dasboard backend.
            $this->username = Config::get("mes.dashboard.user");
            $this->password = Config::get("mes.dashboard.pass");
        }

        return $this;
    }

    private function setStartTime(): void
    {
        $this->startTime = self::millitime();
    }

    private function getPath(): string
    {
        return $this->path;
    }

    private function getMethod(): string
    {
        return $this->method;
    }

    private static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }

    /**
     * @param int $httpCode
     *
     * @return void
     */
    private function pushDataToMetric(int $httpCode): void
    {
        $timeTaken = $this->endTime - $this->startTime;
        $domain = \Request::server('SERVER_NAME');

        try
        {
            $dimensions = [
                Constants::LABEL_HTTP_REQUESTS_ORIGIN                           => ApiUrl::getRequestOrigin(),
                Constants::LABEL_HTTP_REQUESTS_DOMAIN                           => $domain ?? 'unknown_domain',
                Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_STATUS            => $httpCode,
                Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_DASHBOARD_ROUTE   => \request()->route()->getName(),
                Constants::LABEL_HTTP_REQUESTS_API_DOWNSTREAM_DASHBOARD_METHOD  => $this->method,
                Constants::LABEL_HTTP_PATH                                      => $this->getPath(),
                Constants::LABEL_API_BASE_URL                                   => $this->baseUri,
            ];

            $this->metrics->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_UCS_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

            $this->metrics->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_UCS_DOWNSTREAM_DURATION, $timeTaken, $dimensions);
        }
        catch (\Throwable $t)
        {
            $this->trace->warning(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $t->getMessage() ?? 'unknown_message',
            ]);
        }
    }

    private function setEndTime(): void
    {
        $this->endTime = self::millitime();
    }

    /**
     * @return void
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    private function processMerchantClientHeaders(): void
    {
        $user = Auth::guard('user')->user();

        $userId = "";
        $email = "";

        if (empty($user) === false)
        {
            $currentMerchant = $user->currentMerchant();

            if (empty($currentMerchant) === true)
            {
                throw new BadRequestError(
                    'Invalid merchant request.',
                    \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                    400);
            }

            $twoFaVerified = Session::get(UserConstants::TWO_FA_VERIFIED, false);

            $userId = $user->id;
            $email = $user->email;

            $this->requestOptions[self::Headers][self::HeaderUserRole] = $currentMerchant->role;
            $this->requestOptions[self::Headers][self::Header2faVerified] = $twoFaVerified ? 'true' : 'false';
        }
        else
        {
            if (app('request.ctx')->isOauthRequest() === true)
            {
                $userId = app('request.ctx')->getUserId();
            }
        }

        $accountId = Request::header(self::HeaderRazorpayAccount);

        if ($accountId)
        {
            $this->requestOptions[self::Headers][self::HeaderRazorpayAccount] = $accountId;
        }

        $mid = "";

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $mid = app('request.ctx')->getMerchantId();
        }

        $currenMerchantId = $currentMerchant->id ?? $mid;

        $this->requestOptions[self::Headers][self::HeaderUserId] = $userId;
        $this->requestOptions[self::Headers][self::HeaderUserEmail] = $email;
        $this->requestOptions[self::Headers][self::HeaderMerchantId] = $currenMerchantId;

        $this->username = Config::get("mes.merchant_dashboard.user");
        $this->password = Config::get("mes.merchant_dashboard.pass");
    }

    /**
     * @return void
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    private function processAdminClientHeaders(): void
    {
        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === true)
        {
            throw new BadRequestError(
                'Invalid admin request.',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400);
        }

        $adminUsername = $adminUser->username ?? null;
        $adminUserId = $adminUser->id ?? null;

        $this->requestOptions[self::Headers][self::HeaderUserId] = $adminUserId;
        $this->requestOptions[self::Headers][self::HeaderUserName] = $adminUsername;

        // for admin users we may not have merchant id hece adding the admin user id as merchant id
        $this->requestOptions[self::Headers][self::HeaderMerchantId] = $adminUserId;

        $adminEmail = $adminUser->email ?? null;

        $this->requestOptions[self::Headers][self::HeaderUserEmail] = $adminEmail;

        $accountId = Request::header(self::HeaderRazorpayAccount);

        if ($accountId)
        {
            $this->requestOptions[self::Headers][self::HeaderRazorpayAccount] = $accountId;
        }

        $this->username = Config::get("mes.admin_dashboard.user");
        $this->password = Config::get("mes.admin_dashboard.pass");
    }

    /**
     * @return void
     */
    private function processUserClientHeaders(): void
    {
        $user = Auth::guard('user')->user();
        $userId = "";
        $email = "";

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $userId = app('request.ctx')->getUserId();
        }

        if (empty($user) === false)
        {
            $userId = $user->id;
            $email = $user->email;
        }

        $this->requestOptions[self::Headers][self::HeaderUserId] = $userId;
        $this->requestOptions[self::Headers][self::HeaderUserEmail] = $email;
        $this->requestOptions[self::Headers][self::HeaderMerchantId] = $userId;

        $this->trace->info(TraceCode::USER_CONTEXT_LOG, [
            'user_id'            => $user ? $user->id : null,
            'user_email'         => $user ? $user->email : null,
            'headers'            => $this->requestOptions[self::Headers],
        ]);

        // NOTE: We should NEVER hit this as Dashboard internal.
        $this->username = Config::get("mes.dashboard.user");
        $this->password = Config::get("mes.dashboard.pass");
    }

    /**
     * @param string|null $clientType
     *
     * @return void
     */
    private function determineClientType(?string $clientType): void
    {
        if (empty($clientType) !== true)
        {
            $this->clientType = $clientType;
            return;
        }

        $routeName = Route::currentRouteName();

        if (in_array($routeName, ['merchant', 'admin', 'extension_merchant', 'oauth_merchant', 'oauth_user_logout'], true) === false)
        {
            $this->clientType = 'user';
            return;
        }

        if (in_array($routeName,  ['oauth_merchant', 'oauth_user_logout']))
        {
            $this->clientType = 'merchant';
            return;
        }

        $this->clientType = $routeName;
    }
}
