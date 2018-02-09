<?php

namespace RZP\Services;

use Cache;
use Requests;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\IndianStates;


class PincodeSearch
{
    const REQUEST_TIMEOUT = 5;

    // @see: https://data.gov.in/resources/all-india-pincode-directory/api
    const ROUTE = '/resource/6176ee09-3d56-4a3b-8115-21841576b2f6';

    const LIMIT = 1;

    // @see: https://en.wikipedia.org/wiki/Postal_Index_Number
    const MAX_PINCODE = 859999;

    const MIN_PINCODE = 110000;

    const CACHE_TTL = 86400;

    const CACHE_KEY_FORMAT = 'pincodesearch_%s';

    protected $config;

    protected $cache;

    protected $trace;

    protected $baseUrl;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->cache = $app['cache'];

        $this->config = $app['config']->get('applications.pincodesearch');

        $this->baseUrl = $this->config['url'];
    }

    protected function getCacheKey(int $pincode)
    {
        $key = sprintf(static::CACHE_KEY_FORMAT, $pincode);

        return $key;
    }

    public function sendRequest(string $url, string $method, string $data = null): array
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Accept'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
        );

        $request = array(
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendRawRequest($request);

        $this->trace->info(TraceCode::PINCODE_SEARCH_RESPONSE, [
                    'response' => $response->body
                ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::PINCODE_SEARCH_RESPONSE, $decodedResponse ?? []);

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function sendRawRequest(array $request)
    {
        $this->trace->info(TraceCode::PINCODE_SEARCH_REQUEST, $request);

        $method = $request['method'];

        $response = Requests::$method(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['options']);

        return $response;
    }

    protected function checkErrors(array $response)
    {
        if (isset($response['status']) === false)
        {
            throw new Exception\IntegrationException(
                'Third Party Error',
                $response
            );
        }

        if ($response['status'] === 'ok')
        {
            return;
        }

        if ($response['status'] === 'Error')
        {
            $errorMessage = $response['message'] ?? 'Third Party Error';

            throw new Exception\IntegrationException(
                $errorMessage,
                $response
            );
        }

        throw new Exception\IntegrationException(
            'Something Went Wrong',
            $response);
    }

    public function fetchCityAndStateFromPincode($pincode): array
    {
        if ($this->validate($pincode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $pincode . ' is not correct.');
        }

        $key = $this->getCacheKey($pincode);

        if ($response = Cache::get($key))
        {
            return $response;
        }

        $params = $this->getParams($pincode);

        $params = http_build_query($params);

        $url = self::ROUTE . '?' . $params;

        $response = $this->sendRequest($url, Requests::GET);

        if (count($response['records']) === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND);
        }

        $response = $response['records'][0];

        $response = [
            "city"          => $response['districtname'] ?? null,
            "state"         => $response['circlename'] ?? null,
            "state_code"    => IndianStates::getStateCode($response['statename']),
        ];

        $this->cache->put($key, $response, static::CACHE_TTL);

        return $response;
    }

    /**
     * Some basic checks around pincode
     * @param $pincode Input Pincode
     * @see https://en.wikipedia.org/wiki/Postal_Index_Number
     * @return boolean
     */
    protected function validate($pincode)
    {
        if ((strlen($pincode) !== 6) or
            (ctype_digit($pincode) === false) or
            ($pincode > self::MAX_PINCODE) or
            ($pincode < self::MIN_PINCODE))
        {
            return false;
        }

        return true;
    }

    protected function getParams(int $pincode)
    {
        return [
            'limit'             => self::LIMIT,
            'api-key'           => $this->config['api_key'],
            'filters[pincode]'  => $pincode,
            'format'            => 'json'
        ];
    }
}
