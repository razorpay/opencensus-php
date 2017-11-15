<?php

namespace RZP\Services\Geolocation\Providers;

use Requests;
use RZP\Trace\TraceCode;
use RZP\Models\GeoIP\Entity;
use RZP\Exception\BadRequestException;
use RZP\Services\GeoLocation\ProviderInterface;

class Eureka extends Base
{
    const NA = '-';
    const EUREKA_KEY_INDEX = 'eureka_key_index';

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
     * Default value for key to use
     * @var int
     */
    protected $keyIndex = 0;

    /**
     * @param array $input
     * @throws BadRequestException
     */
    public function validateAndSetInput(array $input)
    {
        $keyIndex = $input[Eureka::EUREKA_KEY_INDEX] ?? 0;

        if (isset($this->options['keys'][$keyIndex]) === false)
        {
            throw new BadRequestException('Invalid key : ' . $keyIndex);
        }

        $this->keyIndex = $keyIndex;
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

            if (isset($response['geolocation_data']))
            {
                $geolocation = $response['geolocation_data'];
            }
            else
            {
                $message = $response['query_status']['query_status_description'] ??
                               'Invalid response form eureka';

                $this->trace->warning(
                    TraceCode::GEOLOCATION_FAILURE,
                    [
                        'message'  => $message,
                        'ip'       => $ip,
                        'provider' => get_called_class(),
                    ]);
            }

            $geolocations[$ip] = $geolocation;
        }

        return $geolocations;
    }

    protected function mockedGeolocations(array $ips)
    {
        $response = [];
        foreach ($ips as $ip)
        {
            $geolocation = [
                'continent_code' => 'AS',
                'continent_name' => 'Asia',
                'country_code_iso3166alpha2' => 'IN',
                'country_code_iso3166alpha3' => 'IND',
                'country_code_iso3166numeric' => '356',
                'country_code_fips10-4' => 'IN',
                'country_name' => 'India',
                'region_code' => 'IN19',
                'region_name' => 'Karnataka',
                'city' => 'Bangalore',
                'postal_code' => '560030',
                'metro_code' => '-',
                'area_code' => '-',
                'latitude' => 12.9833,
                'longitude' => 77.5833,
                'isp' => 'Bharti Broadband',
                'organization' => 'Bharti Airtel',
            ];

            if (in_array($ip, self::FAILURE_IPS, true))
            {
                $geolocation = [];
            }

            $response[$ip] = $geolocation;
        }

        return $response;
    }

    private function sendQuery($ip)
    {
        $query = [
            'key'    => $this->options['keys'][$this->keyIndex],
            'format' => 'JSON',
            'ip'     => $ip
        ];
        $queryString = http_build_query($query);

        $url = $this->options['url'] . $queryString;

        $response = Requests::get($url);

        $response = json_decode($response->body, true);

        return $response;
    }
}
