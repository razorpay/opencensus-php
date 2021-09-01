<?php


namespace RZP\Services\DE;

use App;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class PersonalisationService
{
    const ACCEPT_HEADER            = 'Accept';
    const APPLICATION_JSON         = 'application/json';
    const CONTENT_TYPE             = 'Content-Type';
    const REQUEST_TIMEOUT          = 1;

    protected $trace;
    protected $app;
    protected $config;
    protected $url;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('services.de_personalisation');

        $this->url = $this->config['url'];
    }


    public function sendPersonalisationRequest(array $content)
    {
        $this->trace->info(TraceCode::PERSONALISATION_CONTENT, $content);

        $method = 'POST';

        $headers[self::ACCEPT_HEADER] = self::APPLICATION_JSON;

        $headers[self::CONTENT_TYPE]= self::APPLICATION_JSON;

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [
                $this->config['username'],
                $this->config['password']
            ],
        );

        try
        {
            $startTime = microtime(true);

            $response = Requests::request(
                $this->url,
                $headers,
                json_encode($content),
                $method,
                $options);

            $this->trace->info(TraceCode::PERSONALISATION_RESPONSE_TIME, [
                'responseTimeInSeconds'  =>  microtime(true) - $startTime,
            ]);
        }
        catch(\Exception $e)
        {
            $this->trace->error(TraceCode::PERSONALISATION_EXCEPTION, [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            return null;
        }

        return $response;
    }
}
