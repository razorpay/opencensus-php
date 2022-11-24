<?php

namespace RZP\Services;

use App;
use Config;
use Request;

use Razorpay\Trace\Logger;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use function GuzzleHttp\json_encode;

class DeveloperConsole
{

    protected $baseUrl;

    protected $config;

    protected $ba;

    protected $trace;

    protected $app;


    /** Header Constants **/
    const CONTENT_TYPE          = 'Content-Type';
    const AUTHORIZATION         = 'Authorization';
    const X_MERCHANT_ID         = 'X-Merchant-ID';
    const X_REQUEST_MODE        = 'X-Request-Mode';
    const X_ADMIN_ID            = 'X-Admin-Id';
    const X_RAZORPAY_REQUEST_ID = 'X-Razorpay-Request-ID';
    const CONTENT_TYPE_JSON     = 'application/json';

    /** Config Constants  */
    const USERNAME_ADMIN_CONFIG_KEY = 'username_admin';
    const PASSWORD_ADMIN_CONFIG_KEY = 'password_admin';
    const USERNAME_MERCHANT_CONFIG_KEY = 'username_merchant';
    const PASSWORD_MERCHANT_CONFIG_KEY = 'password_merchant';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app     = $app;

        $this->ba      = $app['basicauth'];

        $this->trace   = $app['trace'];

        $this->config  = $app['config']->get('applications.developer_console');

        $this->baseUrl = $this->config['host'];
    }

    public function sendRequestAndParseResponse(string $path, string $method, string $auth, array $data = []): array
    {
        $request = $this->generateRequest($path, $method, $data, $auth);

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
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param string $auth
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data, string $auth): array
    {
        $url =  $this->baseUrl . '/v1/' . $endpoint;

        $this->trace->info(TraceCode::DEVELOPER_CONSOLE_REQUEST, [
            'url' => $url,
        ]);

        $headers = $this->getHeaders($auth);

        if (array_key_exists('terms', $data) && $data['terms'] == []){
            unset($data['terms']);
        }

        // json encode if data is must, else ignore.
        $data = (empty($data) === false) ? json_encode($data) : null;

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'body'      => $data
        ];
    }

    /**
     * GetHeaders sets the headers required by downstream service
     * @param string $authType
     *
     * @return array $headers
     */
    protected function getHeaders(string $authType): array
    {
        $headers = [];

        $headers[self::CONTENT_TYPE]          = self::CONTENT_TYPE_JSON;
        $headers[self::X_REQUEST_MODE]        = $this->ba->getMode();
        $headers[self::X_RAZORPAY_REQUEST_ID] = $this->app->request->getTaskId();

        $this->getHeadersBasedOnAuth($headers, $authType);

        return $headers;
    }

    protected function getHeadersBasedOnAuth(array &$headers, string $authType)
    {
        switch ($authType) {
            case 'merchant':

                $auth = base64_encode($this->config[self::USERNAME_MERCHANT_CONFIG_KEY] . ':' . $this->config[self::PASSWORD_MERCHANT_CONFIG_KEY]);

                $headers[self::AUTHORIZATION] = 'Basic ' . $auth;

                $headers[self::X_MERCHANT_ID] = $this->ba->getMerchantId();

                break;

            case 'admin':

                $auth = base64_encode($this->config[self::USERNAME_ADMIN_CONFIG_KEY] . ':' . $this->config[self::PASSWORD_ADMIN_CONFIG_KEY]);

                $headers[self::AUTHORIZATION] = 'Basic ' . $auth;

                $headers[self::X_ADMIN_ID]    = $this->ba->getAdmin()->getId();

                break;
        }
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

}
