<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Http\Request\Requests;
use GuzzleHttp\Exception\InvalidArgumentException;
use Http\Discovery\Exception\NotFoundException;
use Razorpay\Trace\Logger;
use RZP\Exception\ServerNotFoundException;
use RZP\Trace\TraceCode;
use function GuzzleHttp\json_encode;

//TODO: Refactor to separate DeveloperConsole and DeveloperConsoleController files
class DeveloperConsoleController extends Controller
{

    /**
     * contains host and api key which should be used to make request
     * @var array
     */
    protected $config;

    const TYPES   = [
            'incoming',
            'outgoing',
        ];

    const ACTIONS = [
        'apis',
        'stats',
        'search',
    ];

    const USERNAME_MAINTENANCE_CONFIG_KEY = 'username_maintenance';
    const PASSWORD_MAINTENANCE_CONFIG_KEY = 'password_maintenance';
    const USERNAME_CONFIG_KEY = 'username';
    const PASSWORD_CONFIG_KEY = 'password';


    public function __construct()
    {
        parent::__construct();

        $this->config      = app('config')->get('services.developer_console');
    }

    public function runMaintenance($type)
    {
        $request = Request::instance();

        $method = $request->method();

        $uri = '/v1/' . $type . '/maintenance/run';

        $input = Request::all();

        $authValue = base64_encode($this->config[self::USERNAME_MAINTENANCE_CONFIG_KEY] . ':' . $this->config[self::PASSWORD_MAINTENANCE_CONFIG_KEY]);

        $response = $this->sendRequest($uri, $method, $input, $authValue);

        return ApiResponse::json($response);
    }

    public function dashboardSearch($type, $action)
    {
        $request = Request::instance();

        $method = $request->method();

        $uri = $this->getURI($type, $action);

        $input = Request::all();

        $authValue = base64_encode($this->config[self::USERNAME_CONFIG_KEY] . ':' . $this->config[self::PASSWORD_CONFIG_KEY]);

        $response = $this->sendRequest($uri, $method, $input, $authValue);

        return ApiResponse::json($response);
    }

    protected function getURI($type, $action): string {
        if (!in_array($type, self::TYPES) or
            !in_array($action, self::ACTIONS)) {
            throw new ServerNotFoundException();
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

        $headers['Content-Type'] = 'application/json';
        $headers['Authorization'] = 'Basic ' . $authValue;
        $headers['X-Merchant-ID'] = $this->ba->getMerchantId();
        $headers['X-Request-Mode'] = $this->ba->getMode();
        $headers['X-Razorpay-Request-ID'] = $this->app->request->getTaskId();

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
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    protected function sendRequest(
        string $url,
        string $method,
        array $data = [],
        string $auth,
        bool $throwExceptionOnFailure = false): array
    {
        $request = $this->generateRequest($url, $method, $data, $auth);

        $response = $this->sendDevConsoleRequest($request);

        $this->trace->info(TraceCode::DEVELOPER_CONSOLE_REQUEST, [
            'response' => $response->body
        ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::DEVELOPER_CONSOLE_REQUEST, $decodedResponse ?? []);

        return [
            'body' => $decodedResponse,
            'code' => $response->status_code,
        ];

    }

    /**
     * @param array $request
     *
     * @return \Requests_Response
     * @throws \Throwable
     */
    protected function sendDevConsoleRequest(array $request): \Requests_Response
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
                    'body'   => $request['body'],
                ]);

            throw $e;
        }
        return $response;
    }

}
