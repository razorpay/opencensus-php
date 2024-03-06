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

    const ERROR_GENERATING_AZURE_BOT_DIRECT_LINE_TOKEN = 'ERROR_GENERATING_DIRECT_LINE_TOKEN_FOR_AZURE_BOT';

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


    public function generateAzureBotDirectLinkToken($merchantId, $name)
    {
        $response  = null;

        try
        {
            $response  = $this->http_client->get(sprintf('chat/init?merchant_id=%s&use_case=ray_dashboard&name=%s',$merchantId, $name));

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

        return [[self::ERROR_GENERATING_AZURE_BOT_DIRECT_LINE_TOKEN], null];
    }
}


