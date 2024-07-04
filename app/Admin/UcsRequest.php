<?php

namespace App\Admin;

use App\Trace\Trace;
use App\Http\ApiUrl;
use App\Http\Headers;
use GuzzleHttp\Client;
use App\Trace\TraceCode;
use App\Metrics\Constants;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Razorpay\Api\Errors\BadRequestError;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ConnectException;

class UcsRequest {
    const Auth              = "auth";
    const Headers           = "headers";
    const UserAgentKey      = "User-Agent";
    const HeaderRequestId   = "X-Request-ID";
    const HeaderUserAgent   = "X-User-Agent";
    const HeaderUserId      = "X-User-Id";
    const HeaderUserEmail   = "X-User-Email";
    const ContentType       = "Content-Type";
    const ContentTypeApplicationJson = "application/json";

    /**
     * Guzzle Client instance
     *
     * @var Guzzle|null
     */
    protected ?Guzzle $client;

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


    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function __construct(
        string $path,
        string $method="post",
        Client $guzzlelClient=null,
        array $requestOptions = []
    ) {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app["trace"];

        $this->metrics = $this->app['metrics'];

        $this->path = $path;

        $this->method = $method;

        $this->client = $guzzlelClient;

        $this->populateDefaultRequestHeaders($requestOptions);

        if ($this->client === null) {
            $this->client = self::getUcsClient($requestOptions);
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
        $options["json"] = ! empty($body) ? json_encode($body) : null;;
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

            $this->trace->error(TraceCode::UCS_CALL_SUCCESSFUL, [
                "code"  => $httpCode,
            ]);
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            $this->trace->error(TraceCode::UCS_CALL_FAILED, [
                "code"  => $e->getCode(),
                "error" => $e->getMessage(),
            ]);

            $this->setEndTime();

            $data = json_decode($e->getResponse()->getBody(), true);

            $httpCode = $e->getResponse()->getStatusCode();
        }
        catch(\GuzzleHttp\Exception\ServerException $e)
        {
            $this->trace->error(TraceCode::UCS_CALL_FAILED, [
                "code"  => $e->getCode(),
                "error" => $e->getMessage(),
            ]);

            $this->setEndTime();

            $httpCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

            $data = ["error" =>["description" => $e->getMessage()]];
        }
        catch(ConnectException $e)
        {
            $this->trace->error(TraceCode::UCS_CALL_FAILED, [
                "code"  => $e->getCode(),
                "error" => $e->getMessage(),
            ]);

            $this->setEndTime();

            $httpCode = $e->getCode() ?? Response::HTTP_INTERNAL_SERVER_ERROR;

            $data = ["error" =>["description" => $e->getMessage()]];
        }
        catch(GuzzleException $e)
        {
            $this->trace->error(TraceCode::UCS_CALL_FAILED, [
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

    private function getUcsClient(array $options = []): Guzzle
    {
        $this->baseUri = array_get($options, 'base_uri', Config::get("ucs.base_url"));

        return new Guzzle([
            'base_uri' => $this->baseUri,
            'defaults' => [
                'timeout' => array_get($options, 'timeout', Config::get("ucs.request_timeout")),
            ]
        ]);
    }

    private function populateDefaultRequestHeaders(array $requestOptions = []): void
    {
        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === true)
        {
            throw new BadRequestError(
                'Invalid admin request.',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400);
        }

        $this->requestOptions = $requestOptions;

        if (count(array_get($this->requestOptions, self::Auth, [])) == 0)
        {
            $this->requestOptions[self::Auth] = [
                Config::get('ucs.admin_auth_user'),
                Config::get('ucs.admin_auth_pass')
            ];
        }

        if (count(array_get($this->requestOptions, self::Headers, [])) == 0) {
            $this->requestOptions[self::Headers] = [];
        }

        $this->requestOptions[self::Headers] = $this->requestOptions[self::Headers] +
            [
                self::HeaderUserId      => $adminUser->id,
                self::HeaderUserEmail   => $adminUser->email,
                self::HeaderRequestId   => $this->app["request"]->requestId,
                self::HeaderUserAgent   => request()->header(self::UserAgentKey),
                self::ContentType       => request()->header(self::ContentType, self::ContentTypeApplicationJson),
                Headers::DEV_SERVE_USER => \Request::header(Headers::DEV_SERVE_USER) ?? ''
            ];
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
}
