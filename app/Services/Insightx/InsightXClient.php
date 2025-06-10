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

    const ERROR_GENERATING_METHOD_AGGREGATE = 'ERROR_GENERATING_METHOD_AGGREGATE';

    const DASHBOARD = 'dashboard';

    const GUEST_TOKEN_ENDPOINT = 'insightx/merchant/guest-token';

    const METHOD_AGGREGATE_ENDPOINT = 'insightx/merchant/method-aggregate';

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

    /**
     * Retrieves aggregated payment method analytics data for a merchant from InsightX.
     * 
     * This method calls the InsightX API to fetch consolidated metrics and statistics
     * about payment methods used by the specified merchant. The aggregated data typically
     * includes success rates, transaction volumes, and performance metrics grouped by
     * payment method types (cards, UPI, wallets, etc.).
     *
     * @param string $merchantId The merchant ID to fetch method aggregate data for
     * @return array Returns a tuple: [errors, data]
     *               - On success: [null, array] where array contains the aggregated method data
     *               - On failure: [array of error messages, null]
     */
    public function methodAggregate($merchantId)
    {
        $response = null;

        try {
            $response = $this->httpClient->get(self::METHOD_AGGREGATE_ENDPOINT, [
                'headers' => [
                    self::MERCHANT_HEADER => $merchantId,
                ],
            ]);
        } catch (\Exception $ex) {
            $this->trace->error(TraceCode::INSIGHTX_METHOD_AGGREGATE_FAIL, [
                'error' => $ex->getMessage(),
            ]);

            return [[$ex->getMessage()], null];
        }

        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);

        if ($statusCode == 200) {
            return [null, $body];
        }

        $this->trace->error(TraceCode::INSIGHTX_METHOD_AGGREGATE_FAIL, [
            'status_code' => $statusCode,
            'response' => $body
        ]);

        return [[self::ERROR_GENERATING_METHOD_AGGREGATE], null];
    }
    
}
