<?php


namespace RZP\Services\SumoLogic;

use App;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Client
{
    const AUTHORIZATION   = 'Authorization';

    const TIMEOUT         = 'timeout';

    const REQUEST_TIMEOUT = 5; // in seconds

    const SEARCH_JOB_PATH = '/api/v1/search/jobs';

    protected $config;
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->config = $app['config']->get('services.sumo_logic');

        $this->trace = $app['trace'];
    }

    public function createSearchjob(array $payload) : ?string
    {
        $url = $this->config['url'] . self::SEARCH_JOB_PATH;

        $response = $this->request($url, 'POST', $payload);

        if($response->status_code >= 200 && $response->status_code < 299)
        {
            $responseBody = json_decode($response->body, true);

            return $responseBody['id'];
        }

        return null;
    }

    public function fetchJobResult(string $jobId): ?array
    {
        $url = $this->config['url'] . self::SEARCH_JOB_PATH . '/' . $jobId;

        $response = $this->request($url, 'GET');

        if($response->status_code >= 200 && $response->status_code < 299)
        {
            return json_decode($response->body, true);
        }

        return null;
    }

    public function fetchJobMessages(string $jobId,int $offset,int $limit): ?array
    {
        $url = $this->config['url'] . self::SEARCH_JOB_PATH . '/' . $jobId . '/messages?offset=' . $offset . '&limit=' . $limit;

        $response = $this->request($url, 'GET');

        if ($response->status_code >= 200 && $response->status_code < 299)
        {
            return json_decode($response->body, true);
        }

        return null;
    }

    private function request(string $url, string $method, array $payload = [])
    {
        $headers = $this->getHeaders();

        $options = $this->getOptions();

        try
        {
            $this->trace->info(TraceCode::SUMO_LOGIC_REQUEST_INITIATED, [
                'payload'   => $payload,
                'method'    => $method,
                'url'       => $url
            ]);

            $content = null;

            if($method === 'POST')
            {
                $content = json_encode($payload);
            }

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            $this->trace->info(TraceCode::SUMO_LOGIC_REQUEST_COMPLETE, [
                'payload'       => $payload,
                'method'        => $method,
                'url'           => $url,
                'status_code'   => $response->status_code
            ]);

            if($response->status_code > 299)
            {
                /*
                 * Log only if status is not 200
                 */
                $this->trace->info(TraceCode::SUMO_LOGIC_REQUEST_FAILURE, [
                    'payload'       => $payload,
                    'method'        => $method,
                    'url'           => $url,
                    'status_code'   => $response->status_code,
                    'body'          => $response->body
                ]);
            }

            return $response;
        }
        catch (\WpOrg\Requests\Exception $e)
        {
            $data = [
                'exception'     => $e->getMessage(),
                'url'           => $url,
                'payload'       => $payload,
                'method'        => $method,
            ];

            $this->trace->error(TraceCode::SUMO_LOGIC_INTEGRATION_ERROR, $data);

            throw $e;
        }
    }

    private function getHeaders(): array
    {
        $auth = $this->config['auth'];

        return [
            self::AUTHORIZATION => 'Basic ' . base64_encode($auth['access_id'] . ':' . $auth['access_key']),
            'Content-Type'      => 'application/json',
        ];
    }

    private function getOptions(): array
    {
        return [
            self::TIMEOUT  => self::REQUEST_TIMEOUT,
        ];
    }
}
