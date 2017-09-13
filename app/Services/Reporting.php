<?php

namespace RZP\Services;

use App;
use Requests;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;

use Carbon\Carbon;

class Reporting
{
    const REQUEST_TIMEOUT = 20;

    const REPORT_CONFIG   = '/config';
    const REPORT_GENERATE = '/generate';

    /**
     * Configuration array
     * @var array
     */
    protected $config = [];

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

    private function getAuthHeaders() : array
    {
        return [
            $this->config['auth']['username'],
            $this->config['auth']['password'],
        ];
    }

    public function createConfig($input) : array
    {
        $url = self::REPORT_CONFIG;

        $headers = ['X-Merchant-Id' => $this->auth->getMerchantId()];

        return $this->makeRequestAndSend($input, $url, 'post', $headers);
    }

    public function fetchConfigMultiple($input) : array
    {
        $url = self::REPORT_CONFIG;

        $headers = ['X-Merchant-Id' => $this->auth->getMerchantId()];

        return $this->makeRequestAndSend($input, $url, 'get', $headers);
    }

    public function fetchConfigById($id, $input) : array
    {
        $url = self::REPORT_CONFIG . '/' . $id;

        $headers = ['X-Merchant-Id' => $this->auth->getMerchantId()];

        return $this->makeRequestAndSend($input, $url, 'get', $headers);
    }

    public function editConfig($id, $input) : array
    {
        $url = self::REPORT_CONFIG . '/' . $id;

        $headers = ['X-Merchant-Id' => $this->auth->getMerchantId()];

        return $this->makeRequestAndSend($input, $url, 'patch', $headers);
    }

    public function deleteConfig($id) : array
    {
        $url = self::REPORT_CONFIG . '/' . $id;

        $headers = ['X-Merchant-Id' => $this->auth->getMerchantId()];

        return $this->makeRequestAndSend(null, $url, 'delete', $headers);
    }

    public function generateReport(string $configId)
    {
        $url = self::REPORT_GENERATE;

        $headers = ['X-Merchant-Id' => $this->auth->getMerchantId()];

        // Prepare input
        $input = [
            'config_id'    => $configId,
            'generated_by' => '20000000000000',
            'start_time'   => Carbon::now()->subDays(30)->timestamp,
            'end_time'     => Carbon::now()->timestamp,
            'mode'         => $this->app['rzp.mode'],
        ];

        return $this->makeRequestAndSend($input, $url, 'post', $headers);
    }

    protected function makeRequestAndSend($input = null, $url, $method = 'post', $headers = [])
    {
        $request = [];
        $response = null;

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getAuthHeaders(),
        ];

        $request['url'] =  $this->config['url'] . $url;

        $request['method'] = $method;

        $request['content'] = $input;

        $request['options'] = $options;

        $request['headers'] = $headers;

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

    protected function sendRequest($request)
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
}
