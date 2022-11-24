<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger;
use RZP\Http\Request\Requests;
use function GuzzleHttp\json_encode;
use RZP\Exception\BadRequestException;

class DeveloperConsoleController extends Controller
{

    /**
     * contains host and api key which should be used to make request
     * @var array
     */
    protected $config;

    protected $developerConsoleService;

    const TYPES   = [
            'incoming',
            'outgoing',
        ];

    const ACTIONS = [
        'apis',
        'stats',
        'search',
        'fetchById',
    ];

    /** Header Constants **/
    const CONTENT_TYPE          = 'Content-Type';
    const AUTHORIZATION         = 'Authorization';
    const X_MERCHANT_ID         = 'X-Merchant-ID';
    const X_REQUEST_MODE        = 'X-Request-Mode';
    const X_RAZORPAY_REQUEST_ID = 'X-Razorpay-Request-ID';
    const CONTENT_TYPE_JSON     = 'application/json';

    /** Config Constants  */
    const USERNAME_MAINTENANCE_CONFIG_KEY = 'username_maintenance';
    const PASSWORD_MAINTENANCE_CONFIG_KEY = 'password_maintenance';
    const USERNAME_CONFIG_KEY = 'username';
    const PASSWORD_CONFIG_KEY = 'password';


    public function __construct()
    {
        parent::__construct();

        $this->config      = app('config')->get('services.developer_console');

        $this->developerConsoleService = $this->app['developer_console'];
    }

    public function runMaintenance($type)
    {
        $request = Request::instance();

        $method = $request->method();

        $uri = '/v1/' . $type . '/maintenance/run';

        $input = Request::all();

        $authValue = base64_encode($this->config[self::USERNAME_MAINTENANCE_CONFIG_KEY] . ':' . $this->config[self::PASSWORD_MAINTENANCE_CONFIG_KEY]);

        $response = $this->sendRequest($uri, $method, $authValue, $input);

        return ApiResponse::json($response);
    }

    public function dashboardSearch($type, $action)
    {
        $request = Request::instance();

        $method = $request->method();

        $uri = $this->getURI($type, $action);

        $input = Request::all();

        $authValue = base64_encode($this->config[self::USERNAME_CONFIG_KEY] . ':' . $this->config[self::PASSWORD_CONFIG_KEY]);

        $response = $this->sendRequest($uri, $method, $authValue, $input);

        return ApiResponse::json($response);
    }

    /**
     * @throws BadRequestException
     */
    protected function getURI($type, $action): string
    {
        if (!in_array($type, self::TYPES) or
            !in_array($action, self::ACTIONS)) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);;
        }

        return '/v1/' . $type . '/'. $action;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data, string $authValue): array
    {
        $url =  $this->config['host'] . $endpoint;

        $this->trace->info(TraceCode::DEVELOPER_CONSOLE_REQUEST, [
            'url' => $url,
        ]);

        $headers = [];

        $headers[self::CONTENT_TYPE]          = self::CONTENT_TYPE_JSON;
        $headers[self::AUTHORIZATION]         = 'Basic ' . $authValue;
        $headers[self::X_MERCHANT_ID]         = $this->ba->getMerchantId();
        $headers[self::X_REQUEST_MODE]        = $this->ba->getMode();
        $headers[self::X_RAZORPAY_REQUEST_ID] = $this->app->request->getTaskId();

        // json encode if data is must, else ignore.
        $data = (empty($data) === false) ? json_encode($data) : null;

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'body'   => $data
        ];
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $data
     *
     * @return array
     * @throws \Throwable
     */
    protected function sendRequest(
        string $url,
        string $method,
        string $auth,
        array $data = []): array
    {
        $request = $this->generateRequest($url, $method, $data, $auth);

        $response = $this->sendDevConsoleRequest($request);

        $this->trace->info(TraceCode::DEVELOPER_CONSOLE_RESPONSE, [
            'response_code' => $response->status_code
        ]);

        $decodedResponse = json_decode($response->body, true);

        return [
            'body' => $decodedResponse,
            'code' => $response->status_code,
        ];

    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws \Throwable
     */
    protected function sendDevConsoleRequest(array $request)
    {
        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['body'],
                $request['method']);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::DEVELOPER_CONSOLE_ERROR,
                [
                    'method' => $request['method'],
                    'url'    => $request['url'],
                ]);

            throw $e;
        }
        return $response;
    }

    public function merchantDashboard($path)
    {
        $request = Request::instance();

        $method = 'POST';

        $payload = $request->all();

        $response = $this->developerConsoleService->sendRequestAndParseResponse($path, $method, 'merchant', $payload);

        return ApiResponse::json($response);
    }

    public function adminDashboard($path)
    {
        $request = Request::instance();

        $method = 'POST';

        $payload = $request->all();

        $response = $this->developerConsoleService->sendRequestAndParseResponse($path, $method, 'admin',$payload);

        return ApiResponse::json($response);
    }

}
