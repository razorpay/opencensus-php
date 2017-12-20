<?php

namespace RZP\Services;

use Requests;

use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Feature\Constants as Feature;

/**
 * Interface for api to talk to Reporting service
 */
class Reporting
{
    const REQUEST_TIMEOUT = 30; // In secs

    /**
     * Path for various endpoints
     */
    const CONFIG_PATH   = '/v1/configs';
    const LOG_PATH      = '/v1/logs';

    /**
     * @var array
     */
    protected $config;

    protected $trace;

    protected $mode;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;

    public function __construct($app)
    {
        $this->config = $app['config']['applications.reporting'];
        $this->trace  = $app['trace'];
        $this->mode   = $app['rzp.mode'];

        // TODO: This service should(to discuss) not depend on BA, better to pass
        // or set merchant context on the instance before using.
        $this->ba     = $app['basicauth'];
    }

    public function createConfig(array $input): array
    {
        return $this->createAndSendRequest(Requests::POST, self::CONFIG_PATH, $input);
    }

    public function fetchConfigMultiple(array $input): array
    {
        $configs = $this->createAndSendRequest(Requests::GET, self::CONFIG_PATH, $input);

        return $this->filterConfigsByFeatureAndTags($configs);
    }

    public function fetchConfigById(string $id): array
    {
        $url = self::CONFIG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function editConfig(string $id, array $input): array
    {
        $url = self::CONFIG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::PATCH, $path, $input);
    }

    public function deleteConfig(string $id): array
    {
        $path = self::CONFIG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::DELETE, $path);
    }

    public function createLog(array $input): array
    {
        //
        // Adds mode to create log input. Mode is only relavent in this endpoint
        // (creating log) as log entity's mode attribute is used to query
        // respective database of api. Config is mode independent in reporting service.
        //
        $input['mode'] = $this->mode;

        return $this->createAndSendRequest(Requests::POST, self::LOG_PATH, $input);
    }

    public function fetchLogById(string $id): array
    {
        $path = self::LOG_PATH . '/' . $id;

        return $this->createAndSendRequest(Requests::GET, $path);
    }

    public function fetchLogMultiple(array $input): array
    {
        return $this->createAndSendRequest(Requests::GET, self::LOG_PATH, $input);
    }

    protected function createAndSendRequest(
        string $method,
        string $path,
        array $input = []): array
    {
        // In case reporting is to be mocked, don't make any external call
        // and just return empty array.
        if ($this->config['mock'] === true)
        {
            return [];
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getAuthHeaders(),
        ];

        $headers = [
            'X-Merchant-Id' => $this->ba->getMerchantId()
        ];

        $request = [
            'url'     => $this->config['url'] . $path,
            'method'  => $method,
            'content' => $input,
            'options' => $options,
            'headers' => $headers
        ];

        $response = $this->sendRequest($request);

        return json_decode($response->body, true);
    }

    protected function sendRequest(array $request): \Requests_Response
    {
        try
        {
            return Requests::request(
                        $request['url'],
                        $request['headers'],
                        $request['content'],
                        $request['method'],
                        $request['options']);
        }
        catch (\Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REPORTING_INTEGRATION_ERROR,
                array_except($request, ['options.auth']));

            throw new Exception\IntegrationException('
                Could not recieve proper response from reporting service');
        }
    }

    /**
     * Returns auth headers to be used to make requests to external reporting
     * service.
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        return [
            $this->config['auth']['username'],
            $this->config['auth']['password'],
        ];
    }

    /**
     * Currently, all the merchant reports, and shared reports are returned from
     * reporting service. In this method we have some logic to filter out above
     * such additional report configs basis merchant.
     *
     * @param  array  $configs
     *
     * @return array
     */
    protected function filterConfigsByFeatureAndTags(array $configs): array
    {

        // $item is a collection for easy operations. Also $configs is empty
        // in case reporting is mocked.
        $items = collect($configs['items'] ?? []);

        $merchant = $this->ba->getMerchant();

        // Reversals, transfers are shared reports, which should be applicable
        // only to marketplace merchants and so we remove them fron configs list
        // otherwise.
        if ($merchant->isFeatureEnabled(Feature::MARKETPLACE) === false)
        {
            $items = $items->reject(function ($value, $key)
            {
                return in_array($value['type'], ['transfers', 'reversals'], true);
            });
        }

        // Payment links report is shared as well but should only be visible to
        // merchants with specific tags.
        $merchantTags = $merchant->tagNames();

        if (in_array('Payment_Link_Report', $merchantTags, true) === false)
        {
            $items = $items->reject(function ($value, $key)
            {
                return in_array($value['type'], ['invoices'], true);
            });
        }

        $configs['items'] = $items->values()->all();
        $configs['count'] = $items->count();

        return $configs;
    }
}
