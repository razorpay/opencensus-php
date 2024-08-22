<?php

namespace App\Services\Razorassist;

use App;
use Config;
use Request;
use App\Trace\TraceCode;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Foundation\Application;


class RazorassistClient
{

    protected $trace;

    /**
     * @var Application
     */
    protected $app;

    protected $http_client;

    const CONTENT_TYPE = 'Content-Type';

    const APPLICATION_JSON = 'application/json';

    const DEV_SERVE_HEADER = 'rzpctx-dev-serve-user';

    const X_APP_HEADER = 'X-App';

    const DASHBOARD = 'dashboard';

    const ERROR_WHILE_PROCESSING_REQUEST = 'ERROR_WHILE_PROCESSING_REQUEST';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace =  $this->app['trace'];

        // load application config
        $config = Config::get('app.razorassist');

        $this->http_client = new Guzzle([
            'base_uri' => $config['url'],
            'defaults' => [
                'timeout' => $config['timeout'],
            ],
            'headers' =>  [
                self::CONTENT_TYPE => self::APPLICATION_JSON,
                self::X_APP_HEADER => self::DASHBOARD,
                self::DEV_SERVE_HEADER => Request::header('rzpctx-dev-serve-user'),
            ],
            'auth' => [$config['user_name'], $config['secret']],
        ]);

    }


    public function generateAzureBotDirectLinkToken($merchant, $user)
    {
        $response  = null;

        $user_array = $user->toArray();

        $user_email = $user_array['email'] ?? "";

        $user_contact_number = $user_array['contact_mobile'] ?? "";

        // Construct the query parameters array
        $query_params = [
            'merchant_id' => $merchant->id,
            'use_case' => 'ray_dashboard',
            'user_name' => $user->name,
            'user_id' => $user->id,
            'user_role' => $merchant->role,
        ];

        // Add email and contact_mobile only if they are not empty
        if (!empty($user_email)) {
            $query_params['email'] = $user_email;
        }
        if (!empty($user_contact_number)) {
            $query_params['contact_mobile'] = $user_contact_number;
        }

        try
        {
            $response  = $this->http_client->get(sprintf('chat/init?%s', http_build_query($query_params)));

        } catch (\Exception $ex)
        {
            $this->trace->error(TraceCode::AZURE_BOT_DIRECT_LINE_TOKEN_GENERATE_FAIL, [
                'error' => $ex->getMessage(),
            ]);

            return [[$ex->getMessage()], null];
        }

        $statusCode = $response->getStatusCode();

        $body = json_decode($response->getBody(), true);

        if($statusCode == 200)
        {
            return [null, $body];
        }

        $this->trace->error(TraceCode::AZURE_BOT_DIRECT_LINE_TOKEN_GENERATE_FAIL, [
            'status_code' => $statusCode,
            'response' => $body
        ]);

        return [[self::ERROR_WHILE_PROCESSING_REQUEST], null];
    }

    public function pushRazorassistEvent($input)
    {

        try
        {
            $response  = $this->http_client->post("chat/events", [
                'json' => $input
            ]);

        } catch (\Exception $ex)
        {
            $this->trace->error(TraceCode::RAZORASSIST_REQUEST_FAiLED, [
                'error' => $ex->getMessage(),
            ]);

            return [[$ex->getMessage()], null];
        }

        $statusCode = $response->getStatusCode();

        $body = json_decode($response->getBody(), true);

        if($statusCode == 200)
        {
            return [null, $body];
        }

        $this->trace->error(TraceCode::RAZORASSIST_REQUEST_FAiLED, [
            'status_code' => $statusCode,
            'response' => $body
        ]);

        return [[self::ERROR_WHILE_PROCESSING_REQUEST], null];
    }
}


