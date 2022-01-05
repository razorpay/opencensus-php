<?php

namespace RZP\Services;

use RZP\Error\ErrorCode;
use Illuminate\Support\Facades\App;
use RZP\Http\Request\Requests;
use RZP\Exception\BadRequestException;

class GoogleMapsClient
{
    const BASE_URL_GEOCODE = 'https://maps.googleapis.com/maps/api/geocode/json?components=';
    const BASE_URL_PLACES  = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?';

    protected $apiKey = '';
    /**
     * @var bool
     */
    protected $mock = false;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->apiKey = $app['config']->get('applications.pincodesearch.google_api_key');
        $this->mock = $app['config']->get('applications.pincodesearch.mock') === true;
    }

    /**
     * @throws \Exception
     */
    public function fetchAddressSuggestions(string $addressQuery)
    {
        if ($this->mock === true)
        {
            return [
                "predictions" => [],
                "status" => "OK",
            ];
        }

        $url = self::BASE_URL_PLACES . $addressQuery . "&key=" . $this->apiKey;
        $response = Requests::get($url);
        $json = json_decode($response->body, true);

        if($json['status'] !== 'OK')
        {
            throw new \Exception($json['error_message']);
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
                "city"       => "anchorage",
                "state"      => "alaska",
                "state_code" => "ak",
            ];
        }

        //request parameters
        if($country === null || $postal_code == null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $query = $this->buildQuery($country, $postal_code);
        $url = self::BASE_URL_GEOCODE . $query . "&key=" . $this->apiKey;


        $response = Requests::get($url);

        $json = json_decode($response->body, true);

        //check response status

        if($json['status'] !== 'OK')
        {
            throw new \Exception($json['error_message']);
        }

        $data = [
            'city' => '',
            'state' => '',
            'state_code' => '',
        ];

        foreach ($json['results'][0]['address_components'] as $address_component)
        {
            if(in_array('locality', $address_component['types']))
            {
                $data['city'] = strtolower($address_component['short_name']);
            }
            else if(in_array('administrative_area_level_1', $address_component['types']))
            {
                $data['state'] = strtolower($address_component['long_name']);
                $data['state_code'] = strtolower($address_component['short_name'] ?? '');
            }
        }

        if(!isset($data['city']))
        {
            $data['city'] = '';
        }

        if(!isset($data['state']))
        {
            $data['state'] = '';
        }

        return $data;
    }

    private function buildQuery(string $country, string $postal_code): string
    {
        return "country:" . $country . "|postal_code:" . $postal_code;
    }
}
