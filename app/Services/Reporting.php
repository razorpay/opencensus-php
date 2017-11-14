<?php

namespace RZP\Services;

use App;
use Requests;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;

class Reporting
{
    const REQUEST_TIMEOUT = 20;

    const REPORT_CONFIG   = '/config';
    const REPORT_GENERATE = '/generate';

    /**
     * Configuration array
     *
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $mode;

    protected $trace;

    public function __construct($app)
    {
        $this->config = $app['config']['applications.reporting'];

        if ($this->config === null)
        {
            throw new Exception\LogicException('Reporting Config not defined');
        }

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->auth = $app['basicauth'];
    }

    public function createConfig(array $input): array
    {
        $url = self::REPORT_CONFIG;

        return $this->makeRequestAndSend($input, $url, 'post');
    }

    public function fetchConfigMultiple(array $input): array
    {
        $url = self::REPORT_CONFIG;

        return $this->makeRequestAndSend($input, $url, 'get');
    }

    public function fetchConfigById(string $id, array $input): array
    {
        $url = self::REPORT_CONFIG . '/' . $id;

        return $this->makeRequestAndSend($input, $url, 'get');
    }

    public function editConfig(string $id, array $input): array
    {
        $url = self::REPORT_CONFIG . '/' . $id;

        return $this->makeRequestAndSend($input, $url, 'patch');
    }

    public function deleteConfig(string $id): array
    {
        $url = self::REPORT_CONFIG . '/' . $id;

        return $this->makeRequestAndSend(null, $url, 'delete');
    }

    public function generateReport(string $configId, array $input)
    {
        $url = self::REPORT_GENERATE;

        // Prepare input
        $input['mode']      = $this->app['rzp.mode'];
        $input['config_id'] = $configId;

        return $this->makeRequestAndSend($input, $url, 'post');
    }

    protected function makeRequestAndSend($input = null, string $url, string $method = 'post')
    {
        $response = null;

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getAuthHeaders(),
        ];

        $headers = [
            'X-Merchant-Id' => $this->auth->getMerchantId()
        ];

        $request = [
            'url'     => $this->config['url'] . $url,
            'method'  => $method,
            'content' => $input,
            'options' => $options,
            'headers' => $headers
        ];

        // TODO: fix mode
        if ($this->mode === Mode::TEST)
        {
            $response['success'] = true;
        }
        else
        {
            $response = $this->sendRequest($request);
        }

        return json_decode($response->body, true);
    }

    protected function sendRequest(array $request)
    {
        $request['options'] = $request['options'] ?? [];

        $request['headers'] = $request['headers'] ?? [];

        $method = 'POST';

        if (isset($request['method']) === true)
        {
            $method = strtoupper($request['method']);
        }

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $method,
                $request['options']);

            return $response;
        }
        catch (\Requests_Exception $e)
        {
            $this->exception = $e;

            $data = [
                'service' => 'reporting-service',
                'url'     => $request['url'],
                'method'  => $request['method'],
                'content' => $request['content'],
            ];

            //
            // Some error occurred.
            // Check that whether the response timed out.
            //
            if (Utility::checkTimeout($e))
            {
                $this->trace->error(TraceCode::REPORTING_INTEGRATION_ERROR, $data);

                throw new Exception\IntegrationException('Reporting Service Timed out', $data);
            }

            throw new Exception\IntegrationException($e->getMessage(), $data);
        }
    }

    private function getAuthHeaders(): array
    {
        return [
            $this->config['auth']['username'],
            $this->config['auth']['password'],
        ];
    }
}
