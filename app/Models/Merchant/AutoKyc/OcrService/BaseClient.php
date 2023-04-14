<?php

namespace RZP\Models\Merchant\AutoKyc\OcrService;

use App;
use Request;
use Razorpay\Trace\Logger;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class BaseClient
{
    protected $app;

    /** @var Logger */
    protected $trace;

    protected $config;

    protected $host;

    const AUTHORIZATION_KEY = 'Authorization';

    const REQUEST_ID_KEY    = 'X-Request-ID';

    const CLIENT_ID_KEY     = 'X-Client-ID';

    const CONTENT_TYPE      = 'Content-Type';

    const OWNER_ID          = 'owner_id';

    protected $headers;

    /**
     * @var mixed|null
     */
    protected $merchant;

    function __construct($merchant = null)
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']['services.ocr_service'];

        $this->host = $this->config['host'];

        $this->merchant = $merchant;
    }

    private function getHeaders(): array
    {
        $auth = 'Basic ' . base64_encode($this->config['user'] . ':' . $this->config['password']);

        return [
            self::AUTHORIZATION_KEY => $auth,
            self::REQUEST_ID_KEY    => Request::getTaskId(),
            self::CLIENT_ID_KEY     => $this->config['client_id'],
            self::CONTENT_TYPE      => 'application/json',
            self::OWNER_ID          => $this->merchant->getId()
        ];
    }

    protected function request(string $url, string $method, array $payload = [])
    {
        $headers = $this->getHeaders();

        try
        {
            $this->trace->info(TraceCode::OCR_REQUEST_INITIATED, [
                'payload' => $payload,
                'method' => $method,
                'url' => $url
            ]);

            $content = null;

            if ($method === 'POST')
            {
                $content = json_encode($payload);
            }

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method
            );

            $this->trace->info(TraceCode::OCR_REQUEST_COMPLETED, [
                'payload' => $payload,
                'method' => $method,
                'url' => $url,
                'status_code' => $response->status_code
            ]);

            if ($response->status_code > 299) {
                /*
                 * Log only if status is not 200
                 */
                $this->trace->info(TraceCode::OCR_REQUEST_FAILURE, [
                    'payload' => $payload,
                    'method' => $method,
                    'url' => $url,
                    'status_code' => $response->status_code,
                    'body' => $response->body
                ]);
            }

            return $response;
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $data = [
                'exception' => $e->getMessage(),
                'url' => $url,
                'payload' => $payload,
                'method' => $method,
            ];

            $this->trace->error(TraceCode::OCR_INTEGRATION_ERROR, $data);
        }
    }
}

