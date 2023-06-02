<?php

namespace RZP\Services;

use RZP\Error\ErrorCode;
use Illuminate\Support\Facades\App;
use RZP\Http\Request\Requests;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Trace\TraceCode;

class GoogleMapsClient
{
    const BASE_URL_GEOCODE      = 'https://maps.googleapis.com/maps/api/geocode/json?components=';
    const BASE_URL_PLACES       = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?';
    const LOCATION_CACHE_PREFIX = 'locations:lat_long';
    const CACHE_TTL             = 24 * 60 * 60 * 60; //60 days

    protected $apiKey = '';
    protected $mock = false;
    protected $cache = null;
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->apiKey = $app['config']->get('applications.pincodesearch.google_api_key');
        $this->mock = $app['config']->get('applications.pincodesearch.mock') === true;
        $this->cache = $app['cache'];
        $this->trace = $app['trace'];
    }

    protected function buildAutosuggestQuery(string $query, $location): string
    {
        $params = 'input=' . $query . '&key=' . $this->apiKey;

        if ($location !== null)
        {
            $params = $params . '&location=' . $location['lat'] . ',' . $location['lng'] . '&strictbounds=true&radius=' . $location['radius'];
        }
        return $params;
    }

    /**
     * @throws \Exception
     */
    public function fetchAddressSuggestions(string $addressQuery, string $zipcode = '', string $country = '')
    {
        if ($this->mock === true)
        {
            return [
                'predictions' => [],
                'status'      => 'OK',
            ];
        }

        // Geobounding
        $location = $this->getLocationDetailsForZipcode($zipcode, $country);
        $url = self::BASE_URL_PLACES . $this->buildAutosuggestQuery($addressQuery, $location);
        $response = Requests::get($url);
        $json = json_decode($response->body, true);

        if ($json['status'] !== 'OK' && $json['status'] !== 'ZERO_RESULTS')
        {
            $this->trace->error(TraceCode::ADDRESS_SUGGEST_1CC_ERROR, ['response' => $json]);
            throw new ServerErrorException($json['status'], ErrorCode::SERVER_ERROR);
        }

        return $json;
    }

    /**
     * @throws \Exception
     */
    public function fetchCityAndState(string $country, string $postal_code)
    {
        if ($this->mock === true)
        {
            return [
                'city'       => 'anchorage',
                'state'      => 'alaska',
                'state_code' => 'ak',
            ];
        }

        //request parameters
        if ($country === null || $postal_code == null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $query = $this->buildQuery($country, $postal_code);
        $url = self::BASE_URL_GEOCODE . $query . '&key=' . $this->apiKey;


        $response = Requests::get($url);

        $json = json_decode($response->body, true);

        //check response status

        $status = $json['status'] ?? '';
        if ($status !== 'OK')
        {
            throw new ServerErrorException($status, ErrorCode::SERVER_ERROR);
        }

        $data = [
            'city'       => '',
            'state'      => '',
            'state_code' => '',
        ];

        foreach ($json['results'][0]['address_components'] as $address_component)
        {
            if (in_array('locality', $address_component['types']))
            {
                $data['city'] = strtolower($address_component['short_name']);
            }
            else
            {
                if (in_array('administrative_area_level_1', $address_component['types']))
                {
                    $data['state'] = strtolower($address_component['long_name']);
                    $data['state_code'] = strtolower($address_component['short_name'] ?? '');
                }
            }
        }

        if (!isset($data['city']))
        {
            $data['city'] = '';
        }

        if (!isset($data['state']))
        {
            $data['state'] = '';
        }

        return $data;
    }

    protected function getLocationDetailsForZipcode(string $zipcode, string $country)
    {
        $location = $this->cache->get(implode(':', [self::LOCATION_CACHE_PREFIX, $zipcode, $country]));
        if ($location !== null)
        {
            return $location;
        }
        $query = $this->buildQuery($country, $zipcode);
        $url = self::BASE_URL_GEOCODE . $query . '&key=' . $this->apiKey;


        $response = Requests::get($url);

        $json = json_decode($response->body, true);

        if ($json['status'] !== 'OK')
        {
            return null;
        }

        $location = $json['results'][0]['geometry']['location'];
        $northeast = $json['results'][0]['geometry']['bounds']['northeast'];
        $southwest = $json['results'][0]['geometry']['bounds']['southwest'];
        // Calculating radius in meters
        $location['radius'] = $this->distance($northeast['lat'], $northeast['lng'], $southwest['lat'], $southwest['lng']) / 2;
        $this->cache->put(implode(':', [self::LOCATION_CACHE_PREFIX, $zipcode, $country]), $location, self::CACHE_TTL);
        return $location;
    }

    private function buildQuery(string $country, string $postal_code): string
    {
        return 'country:' . $country . '|postal_code:' . $postal_code;
    }

    protected function distance($latitudeFrom, $longitudeFrom,
                                $latitudeTo, $longitudeTo)
    {
        $long1 = deg2rad($longitudeFrom);
        $long2 = deg2rad($longitudeTo);
        $lat1 = deg2rad($latitudeFrom);
        $lat2 = deg2rad($latitudeTo);

        //Haversine Formula
        $dlong = $long2 - $long1;
        $dlati = $lat2 - $lat1;

        $val = pow(sin($dlati / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlong / 2), 2);

        $res = 2 * asin(sqrt($val));

        $radius = 6378137;

        return ($res * $radius);
    }

}
