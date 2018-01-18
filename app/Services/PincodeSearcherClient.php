<?php

namespace RZP\Services;

use Requests;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Cache;

class PincodeSearcherClient
{

    const REQUEST_TIMEOUT = 5;

    const ROUTE = '/resource/6176ee09-3d56-4a3b-8115-21841576b2f6';

    const LIMIT = 1;

    // @see: https://en.wikipedia.org/wiki/Postal_Index_Number
    const MAX_PINCODE = 859999;

    const MIN_PINCODE = 110000;

    const CACHE_TTL = 86400;

    const CACHE_KEY = 'pincodesearcher_%s';

    // This is the list of all unique
    // state names in the pincodes CSV
    const STATE_MAP = [
        'ANDAMAN & NICOBAR ISLANDS'     => 'AN',
        'ANDHRA PRADESH'                => 'AP',
        'ARUNACHAL PRADESH'             => 'AR',
        'ASSAM'                         => 'AS',
        'BIHAR'                         => 'BI',
        'CHANDIGARH'                    => 'CH',
        'CHATTISGARH'                   => 'CT',
        'DADRA & NAGAR HAVELI'          => 'DN',
        'DAMAN & DIU'                   => 'DD',
        'DELHI'                         => 'DL',
        'GOA'                           => 'GO',
        'GUJARAT'                       => 'GJ',
        'HARYANA'                       => 'HA',
        'HIMACHAL PRADESH'              => 'HP',
        'JAMMU & KASHMIR'               => 'JK',
        'JHARKHAND'                     => 'JH',
        'KARNATAKA'                     => 'KA',
        'KERALA'                        => 'KE',
        'LAKSHADWEEP'                   => 'LA',
        'MADHYA PRADESH'                => 'MP',
        'MAHARASHTRA'                   => 'MH',
        'MANIPUR'                       => 'MA',
        'MEGHALAYA'                     => 'ME',
        'MIZORAM'                       => 'MI',
        'NAGALAND'                      => 'NA',
        'ODISHA'                        => 'OR',
        'PONDICHERRY'                   => 'PO',
        'PUNJAB'                        => 'PB',
        'RAJASTHAN'                     => 'RJ',
        'SIKKIM'                        => 'SI',
        'TAMIL NADU'                    => 'TN',
        'TRIPURA'                       => 'TR',
        'TELANGANA'                     => 'TS',
        'UTTAR PRADESH'                 => 'UP',
        'UTTARAKHAND'                   => 'UT',
        'WEST BENGAL'                   => 'WB',
    ];

    protected $config;

    protected $cache;

    protected $trace;

    protected $baseUrl;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->cache = $app['cache'];

        $this->config = $app['config']->get('applications.pincodesearcher');

        $this->baseUrl = $this->config['url'];

    }

    protected function getCacheKey($pincode)
    {
        $key = sprintf(static::CACHE_KEY, $pincode);

        return $key;
    }

    public function sendRequest($url, $method, $data = null)
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
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendRawRequest($request);

        $this->trace->info(TraceCode::PINCODESEARCHER_RESPONSE, [
                    'response' => $response->body
                ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::PINCODESEARCHER_RESPONSE, $decodedResponse ?? []);

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function sendRawRequest($request)
    {
        $this->traceRequest($request);

        $method = $request['method'];

        $response = Requests::$method(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['options']);

        return $response;
    }

    protected function traceRequest($request)
    {
        $this->trace->info(TraceCode::PINCODESEARCHER_REQUEST, $request);
    }

    protected function checkErrors($response)
    {
        if (isset($response['status']) === true)
        {
            if ($response['status'] === "Error")
            {
                $errorMessage = $response['message'] ?? "Third Party Error";

                throw new Exception\ServerErrorException(
                    $errorMessage,
                    ErrorCode::SERVER_ERROR);
            }

            if ($response['status'] !== "ok")
            {
                throw new Exception\ServerErrorException(
                    'Server error',
                    ErrorCode::SERVER_ERROR);
            }

        }
        else
        {
            throw new Exception\ServerErrorException(
                'Server error',
                ErrorCode::SERVER_ERROR);
        }
    }

    public function fetchCityAndStateFromPincode(int $pincode): array
    {
        if ($this->validate($pincode) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }

        $key = $this->getCacheKey($pincode);

        if ($response = Cache::get($key))
        {
            return $response;
        }

        $params = $this->getParams($pincode);

        $params = http_build_query($params);

        $url = static::ROUTE . '?' . $params;

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
            "state_code"    => self::STATE_MAP[$response['statename'] ?? null] ?? null,
        ];

        $this->cache->put($key, $response, static::CACHE_TTL);

        return $response;

    }

    /**
     * Some basic checks around pincode
     * @param  String $pincode
     * @see https://en.wikipedia.org/wiki/Postal_Index_Number
     * @return boolean
     */
    protected function validate($pincode)
    {
        if ((strlen($pincode) !== 6) or
            (ctype_digit($pincode) === false) or
            (intval($pincode) > self::MAX_PINCODE) or
            (intval($pincode) < self::MIN_PINCODE))
        {
            return false;
        }
        return true;
    }

    protected function getParams($pincode)
    {
        return [
            'limit'             => self::LIMIT,
            'api-key'           => $this->config['api_key'],
            'filters[pincode]'  => $pincode,
            'format'            => 'json'
        ];
    }
}
