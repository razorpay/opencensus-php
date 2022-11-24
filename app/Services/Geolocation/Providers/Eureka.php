<?php

namespace RZP\Services\Geolocation\Providers;

use RZP\Http\Request\Requests;
use RZP\Exception;
use \WpOrg\Requests\Response;
use RZP\Trace\TraceCode;
use RZP\Models\GeoIP\Entity;
use RZP\Services\GeoLocation\ProviderInterface;

class Eureka extends Base
{
    const NA                = '-';
    const EUREKA_KEY        = 'eureka_key';
    const EUREKA_KEY_INDEX  = 'eureka_key_index';

    const SUCCESS_CODE      = 'OK';

    // Provider will throw exception back to application for critical errors
    const CRITICAL_ERROR_CODES = [
        'INVALID_SERVICE_ACCESS_KEY',
        'SUBSCRIPTION_EXPIRED',

    ];

    // only used for testing purposes
    const MOCKED_CRITICAL_IPS = [
        '0.0.0.0'
    ];

    const COLUMN_MAP = [
        Entity::CITY       => 'city',
        Entity::STATE      => 'region_name',
        Entity::POSTAL     => 'postal_code',
        Entity::COUNTRY    => 'country_code_fips10-4',
        Entity::CONTINENT  => 'continent_code',
        Entity::LATITUDE   => 'latitude',
        Entity::LONGITUDE  => 'longitude',
        Entity::ISP        => 'isp',
    ];

    /**
     * Default value will we set when $key is null
     *
     * @var string
     */
    protected $key = null;

    /**
     * @param array $input
     * @throws InvalidArgumentException
     */
    public function validateAndSetInput(array $input)
    {
        $key      = $input[Eureka::EUREKA_KEY] ?? null;
        $keyIndex = $input[Eureka::EUREKA_KEY_INDEX] ?? 0;

        if (is_null($key) === false)
        {
            $this->key = $key;
        }
        else
        {
            $this->setEurekaKey($keyIndex);
        }
    }

    protected function transform(array $geolocation)
    {
        $transformed = [];

        foreach (self::COLUMN_MAP as $rzpColumn => $providerColumn)
        {
            if ((isset($geolocation[$providerColumn])) and
                ($geolocation[$providerColumn] !== self::NA))
            {
                    $transformed[$rzpColumn] = $geolocation[$providerColumn];
            }
            else
            {
                $transformed[$rzpColumn] = null;
            }
        }

        return $transformed;
    }

    protected function geolocations(array $ips)
    {
        $geolocations = [];

        foreach ($ips as $ip)
        {
            $geolocation = [];
            $response = $this->sendQuery($ip);

            if (empty($response) === false)
            {
                $geolocation = $response;
            }

            $geolocations[$ip] = $geolocation;
        }

        return $geolocations;
    }

    private function sendQuery($ip)
    {
        if ($this->mocked === true)
        {
            $body = $this->getMockedResponseBody($ip);

            $responseArray = $this->getGeolocationFromResponse($body, $ip);
        }
        else
        {
            $query = [
                'key'    => $this->getEurekaKey(),
                'format' => 'JSON',
                'ip'     => $ip
            ];
            $queryString = http_build_query($query);

            $url = $this->options['url'] . $queryString;

            $response = Requests::get($url);

            $responseArray = $this->getGeolocationFromResponse($response->body, $ip);
        }

        return $responseArray;
    }

    private function getGeolocationFromResponse($body, $ip): array
    {
        $message = $body;

        $body = json_decode($body, true);

        if (is_array($body) === true)
        {
            $code = $body['query_status']['query_status_code'] ?? null;

            if ($code === self::SUCCESS_CODE)
            {
                return $body['geolocation_data'];
            }

            if (empty($code) === false)
            {
                $message = $body['query_status']['query_status_description'];

                if ($this->isCriticalError($code))
                {
                    throw new Exception\RuntimeException($message);
                }
            }
        }

        $this->trace->warning(
            TraceCode::GEOLOCATION_FAILURE,
            [
                'message'  => $message,
                'ip'       => $ip,
                'provider' => get_called_class(),
            ]);

        return [];
    }

    private function isCriticalError(string $code): bool
    {
        return in_array($code, self::CRITICAL_ERROR_CODES, true);
    }

    /**
     * Return default key if not set
     *
     * @return string
     */
    private function getEurekaKey()
    {
        if ($this->key === null)
        {
            $this->setEurekaKey(0);
        }

        return $this->key;
    }

    /**
     * Set key in request for given index
     *
     * @param $index
     * @throws Exception\InvalidArgumentException
     */
    private function setEurekaKey($index)
    {
        if (isset($this->options['keys'][$index]) === true)
        {
            $this->key = $this->options['keys'][$index];
        }
        else
        {
            throw new Exception\InvalidArgumentException('Invalid key index: ' . $index);
        }
    }

    private function getMockedResponseBody($ip): string
    {
        $response = [
            'query_status'      => [
                'query_status_code'             => self::SUCCESS_CODE,
                'query_status_description'      => null
            ],
            'ip_address'        => $ip,
            'geolocation_data'  => [
                'continent_code'                => 'AS',
                'continent_name'                => 'Asia',
                'country_code_iso3166alpha2'    => 'IN',
                'country_code_iso3166alpha3'    => 'IND',
                'country_code_iso3166numeric'   => '356',
                'country_code_fips10-4'         => 'IN',
                'country_name'                  => 'India',
                'region_code'                   => 'IN19',
                'region_name'                   => 'Karnataka',
                'city'                          => 'Bangalore',
                'postal_code'                   => '560030',
                'metro_code'                    => '-',
                'area_code'                     => '-',
                'latitude'                      => 12.9833,
                'longitude'                     => 77.5833,
                'isp'                           => 'Bharti Broadband',
                'organization'                  => 'Bharti Airtel'
            ],
        ];

        if (in_array($ip, self::FAILURE_IPS, true) === true)
        {
            $response['query_status']['query_status_code'] = 'LOOPBACK_IP_ADDRESS';
        }

        if (in_array($ip, self::MOCKED_CRITICAL_IPS, true) === true)
        {
            $response['query_status']['query_status_code'] = self::CRITICAL_ERROR_CODES[0];
        }

        return json_encode($response);
    }
}
