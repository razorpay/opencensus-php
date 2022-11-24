<?php

namespace RZP\Services;

use App;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class ShieldSlackClient
{
    const AUTHORIZATION   = 'Authorization';

    const TIMEOUT         = 'timeout';

    const REQUEST_TIMEOUT = 2;

    protected $config;
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->config = $app['config']->get('applications.shield.slack');

        $this->trace = $app['trace'];
    }

    public function sendRequest(array $content): array
    {
        $url = $this->config['url'];

        $headers = $this->getHeaders();

        $options = $this->getOptions();

        try
        {
            $this->trace->info(TraceCode::SHIELD_SLACK_REQUEST_INITIATED, [
                'content' => $content,
            ]);

            $response = Requests::post(
                $url,
                $headers,
                $content,
                $options
            );

            $this->trace->info(TraceCode::SHIELD_SLACK_REQUEST_COMPLETE, [
                'content' => $content,
            ]);

            return $this->formatResponse($response);
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $data = [
                'exception'     => $e->getMessage(),
                'url'           => $url,
                'content'       => $content,
            ];

            $this->trace->error(TraceCode::SHIELD_SLACK_INTEGRATION_ERROR, $data);

            throw $e;
        }

        return [];
    }

    private function getHeaders(): array
    {
        $bearerToken = $this->config['bearer_token'];

        return [
            self::AUTHORIZATION => 'Bearer ' . $bearerToken,

        ];
    }

    private function getOptions(): array
    {
        return [
            self::TIMEOUT  => self::REQUEST_TIMEOUT,
        ];
    }

    private function formatResponse($response): array
    {
        $responseArray = [];

        $responseBody = $response->body;

        if (empty($responseBody) === false)
        {
            $responseArray = json_decode($responseBody, true);
        }

        $formattedResponse = [
            'status_code' => $response->status_code,
        ];

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $formattedResponse['error'] = 'invalid json, error code - ' . json_last_error();
            $formattedResponse['body'] = $responseBody;
        }
        else
        {
            $formattedResponse['body'] = $responseArray;
        }

        return $formattedResponse;
    }
}
