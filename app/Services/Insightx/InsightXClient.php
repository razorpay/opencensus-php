<?php

namespace App\Services\Insightx;

use App;
use Config;
use Request;
use App\Trace\TraceCode;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Foundation\Application;


class InsightXClient
{

    protected $trace;

    /**
     * @var Application
     */
    protected $app;

    protected $httpClient;

    const CONTENT_TYPE = 'Content-Type';

    const APPLICATION_JSON = 'application/json';

    const DEV_SERVE_HEADER = 'rzpctx-dev-serve-user';

    const X_APP_HEADER = 'X-App';

    const HEADER_REQUEST_ID = "X-Request-ID";

    const MERCHANT_HEADER = 'X-Consumer-Id';

    const ERROR_GENERATING_SUPERSET_GUEST_TOKEN = 'ERROR_GENERATING_SUPERSET_GUEST_TOKEN';

    const DASHBOARD = 'dashboard';

    const GUEST_TOKEN_ENDPOINT = 'insightx/merchant/guest-token';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];

        $config = Config::get('app.insightx');
        
        $this->httpClient = new Guzzle([
            'base_uri' => $config['url'],
            'defaults' => [
                'timeout' => $config['timeout'],
            ],
            'headers' => [
                self::CONTENT_TYPE => self::APPLICATION_JSON,
                self::X_APP_HEADER => self::DASHBOARD,
                self::DEV_SERVE_HEADER => Request::header('rzpctx-dev-serve-user'),
                self::MERCHANT_HEADER => Request::header(self::MERCHANT_HEADER),
                self::HEADER_REQUEST_ID => $this->app["request"]->requestId,
            ],
        ]);
    }

    public function getSupersetGuestToken($merchantId)
    {
        $response = null;

        try {
            $response = $this->httpClient->get(self::GUEST_TOKEN_ENDPOINT, [
                'headers' => [
                    self::MERCHANT_HEADER => $merchantId,
                ],
            ]);
        } catch (\Exception $ex) {
            $this->trace->error(TraceCode::INSIGHTX_SUPERSET_GUEST_TOKEN_FAIL, [
                'error' => $ex->getMessage(),
            ]);

            return [[$ex->getMessage()], null];
        }

        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);

        if ($statusCode == 200) {
            return [null, $body];
        }

        $this->trace->error(TraceCode::INSIGHTX_SUPERSET_GUEST_TOKEN_FAIL, [
            'status_code' => $statusCode,
            'response' => $body
        ]);

        return [[self::ERROR_GENERATING_SUPERSET_GUEST_TOKEN], null];
    }
}
