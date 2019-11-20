<?php


namespace RZP\Services;

use Requests;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;

class Express
{
    const CONTENT_TYPE      = 'content-type';
    const URL               = 'url';
    const REQUEST           = 'request';
    const CONTENT           = 'content';
    const RESPONSE          = 'response';
    const BODY              = 'body';
    const STATUS_CODE       = 'status_code';
    const HEADERS           = 'headers';

    protected $app;

    protected $config;

    protected $trace;

    protected $mode;

    protected $baseUrl;

    /**
     * Express constructor.
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->config = $this->app['config']->get('applications.express');

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];

        $this->baseUrl = $this->getBaseUrl();
    }

    private function getBaseUrl()
    {
        $urlConfig = 'applications.express.url';

        return $this->app['config']->get($urlConfig);
    }

    private function getHeaders()
    {
        return [
            self::CONTENT_TYPE      => 'Application/json',
        ];
    }

    private function getOptions()
    {
        $auth = [
            'api',
            $this->config['password'],

        ];

        return [
            'auth' => $auth
        ];
    }

    protected function sendRequest(string $path, string $content, string $method = Requests::POST): \Requests_Response
    {
        $url = $this->baseUrl . $path;

        $headers = $this->getHeaders();

        $options = $this->getOptions();

        try
        {
            $this->trace->info(TraceCode::EXPRESS_SERVICE_REQUEST, [
                self::REQUEST => [
                    self::URL           => $url,
                    self::CONTENT       => $content,
                ],
            ]);

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            if ($response->status_code !== 200)
            {
                throw new \Exception('Express failed with status code :' . $response->status_code);
            }

            $this->trace->info(TraceCode::EXPRESS_SERVICE_RESPONSE, [
                self::RESPONSE => [
                    self::STATUS_CODE => $response->status_code,
                    self::BODY        => $response->body,
                ],
            ]);

            return $response;
        }
        catch (\Exception $exception)
        {
            $data = [
                'exception'      => $exception->getMessage(),
                'url'            => $url,
                'content'        => $content,
            ];

            $this->trace->error(TraceCode::EXPRESS_INTEGRATION_ERROR, $data);

            throw new IntegrationException(
                $exception->getMessage(),
                ErrorCode::SERVER_ERROR_EXPRESS_SERVICE_ERROR
            );
        }
    }

    protected function parseTranslateWebhookResponse(\Requests_Response $response): array
    {
        $parsedData = [
            self::CONTENT       => (string) $response->body,
            self::HEADERS       => $response->headers->getAll(),
        ];

        return $parsedData;
    }

    public function translateWebhook(string $translateWebHookUrl, string  $payload) : array
    {
        $response = $this->sendRequest($translateWebHookUrl, $payload);

        return $this->parseTranslateWebhookResponse($response);
    }
}
