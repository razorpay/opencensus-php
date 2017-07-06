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

    /**
     * Configuration array
     * @var array
     */
    protected $config = array();

    protected $app;

    protected $trace;

    public function __construct($app)
    {
        $this->app = $app;

        $this->config = $this->app['config']['applications.nodal'];

        $this->trace = $this->app['trace'];

        if ($this->config === null)
        {
            throw new Exception\LogicException('Nodal Config not defined');
        }
    }

    private function getAuthHeaders()
    {
        return [
            $this->config['auth']['username'],
            $this->config['auth']['password'],
        ];
    }

    public function getBalance($data)
    {
        $params = $data['account_number'];

        $request = [];

        $options = [
            'auth'=> $this->getAuthHeaders(),
        ];

        if ($this->config['mock'] === false)
        {
            $request['url'] = $this->config['url'] . '/nodal-balance/' . $data['account_number'];

            $request['method'] = 'get';

            $request['content'] = [];

            $request['options'] = $options;

            $response = $this->sendRequest($request);

            return json_decode($response->body, true);
        }
    }

    protected function sendRequest($request)
    {
        if (isset($request['options']) === false)
        {
            $request['options'] = [];
        }

        if (isset($request['headers']) === false)
        {
            $request['headers'] = [];
        }

        $method = 'post';

        if (isset($request['method']) === true)
        {
            $method = $request['method'];
        }

        if (isset($request['options']['timeout']) === false)
        {
            $request['options']['timeout'] = self::TIMEOUT;
        }

        try
        {
            $method = strtoupper($method);

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
            // Mostly it should be gateway timeout only
            //
            if (Utility::checkTimeout($e))
            {
                $this->trace->error(TraceCode::NODAL_INTEGRATION_ERROR, $data);

                throw new Exception\IntegrationException('NodalService Timed out', $data);
            }
            else
            {
                throw new Exception\IntegrationException($e->getMessage(), $data);
            }
        }
    }
}
