<?php

namespace RZP\Services;

use App;
use Requests;

use RZP\Exception;
use RZP\Gateway\Utility;
use RZP\Trace\TraceCode;

class Nodal
{
    /**
     * Nodal Service should return response in 5 sec
     */
    const TIMEOUT = 5;

    const GET_BALANCE = '/nodal-balance/';

    /**
     * Configuration array
     * @var array
     */
    protected $config = [];

    protected $trace;

    public function __construct($app)
    {
        $this->config = $app['config']['applications.nodal'];

        if ($this->config === null)
        {
            throw new Exception\LogicException('Nodal Config not defined');
        }

        $this->trace = $this->app['trace'];
    }

    private function getAuthHeaders() : array
    {
        return [
            $this->config['auth']['username'],
            $this->config['auth']['password'],
        ];
    }

    public function getBalance($data) : array
    {
        $params = $data['account_number'];

        $request = [];

        $options = [
            'auth'=> $this->getAuthHeaders(),
        ];

        if ($this->config['mock'] === false)
        {
            $request['url'] = $this->config['url'] . self::GET_BALANCE . $data['account_number'];

            $request['method'] = 'get';

            $request['content'] = [];

            $request['options'] = $options;

            $response = $this->sendRequest($request);

            return json_decode($response->body, true);
        }
        else
        {
            return [
                'account_number' => $data['account_number'],
                'mock'           => true,
                'balance'        => 1000,
            ];
        }
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

        $request['options']['timeout'] = $request['options']['timeout'] ?? self::TIMEOUT;

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
                'service' => 'nodal-service',
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
                $this->trace->error(TraceCode::NODAL_INTEGRATION_ERROR, $data);

                throw new Exception\IntegrationException('NodalService Timed out', $data);
            }

            throw new Exception\IntegrationException($e->getMessage(), $data);
        }
    }
}
