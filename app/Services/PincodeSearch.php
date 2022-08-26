<?php

namespace RZP\Services;

use Cache;
use RZP\Constants\Country;
use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Models\Pincode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\IndianStates;


class PincodeSearch
{
    const REQUEST_TIMEOUT = 5;

    // @see: https://data.gov.in/resources/all-india-pincode-directory/api
    const ROUTE = '/resource/6176ee09-3d56-4a3b-8115-21841576b2f6';

    const LIMIT = 1;

    // Multiplying by 60 since cache put() expect ttl in seconds
    const CACHE_TTL = 86400 * 60;

    const CACHE_KEY_FORMAT = 'pincode:pincodesearch_%s';

    const STATE_NAME = 'state_name:';

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

    protected function getCacheKey($pincode, bool $useStateName = false, string $country = Country::IN)
    {
        if ($country !== Country::IN)
        {
            return sprintf(
                ($useStateName === true ? self::STATE_NAME : '') . static::CACHE_KEY_FORMAT."_%s",
                $pincode, $country
            );
        }
        $key = sprintf(
          ($useStateName === true ? self::STATE_NAME : '') . static::CACHE_KEY_FORMAT,
          $pincode
        );

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

        $response = [];

        switch($method)
        {
            case Requests::GET:
                $response = Requests::$method(
                    $request['url'],
                    $request['headers'],
                    $request['options']);
                break;
        }

        return $response;
    }

    protected function checkErrors($response)
    {
        if (!is_array($response) or isset($response['status']) === false)
        {
            throw new Exception\IntegrationException(
                'Third Party Error',
                null,
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
                null,
                $response
            );
        }

        throw new Exception\IntegrationException(
            'Something Went Wrong',
            null,
            $response);
    }

    public function fetchCityAndStateFromPincodeAndCountry($pincode, $country): array
    {
        $key = $this->getCacheKey($pincode, false, $country);
        if ($response = Cache::get($key))
        {
            return $response;
        }
        $response = (new GoogleMapsClient())->fetchCityAndState($country, $pincode);
        $this->cache->put($key, $response, static::CACHE_TTL);
        return $response;
    }

    public function fetchCityAndStateFromPincode($pincode, $useStateName = false, $useGstCodes = false, $country = "in"): array
    {
        $country = strtolower($country);
        if ($country !== Country::IN)
        {
            return $this->fetchCityAndStateFromPincodeAndCountry($pincode, $country);
        }

        $pincodeValidator = new Pincode\Validator(Pincode\Pincode::IN);

        if ($pincodeValidator->validate($pincode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $pincode . ' is not correct.');
        }

        $key = $this->getCacheKey($pincode, $useStateName);

        if ($response = Cache::get($key))
        {
            $this->replaceSpecialCharsInCity($response);

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
            'city'          => $response['districtname'] ?? null,
            'state'         =>
                ($useStateName === true ? ucwords(strtolower($response['statename'])) : $response['circlename']) ?? null,
            'state_code'    => IndianStates::getStateCode($response['statename'], $useGstCodes),
        ];

        $this->replaceSpecialCharsInCity($response);

        $this->cache->put($key, $response, static::CACHE_TTL);

        return $response;
    }

    private function replaceSpecialCharsInCity(&$address)
    {
        if (empty($address['city']) === false)
        {
            $address['city'] = trim(preg_replace("/[^A-Za-z]/", ' ', $address['city']));
        }
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
