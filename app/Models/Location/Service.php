<?php

namespace RZP\Models\Location;

use RZP\Models\Base;
use RZP\Constants\Country;
use RZP\Services\LocationService;
use RZP\Constants\InternationalStates;

class Service extends Base\Service
{
    public function getCountryDetails(): array
    {
        $data = [];

        $data = Country::getcountryDetails();

        return $data;
    }

    public function getstateDetailsFromCountryCode(string $id): array
    {
        $data = [];

        if (empty(Country::getCountryNameByCode($id))) {
            return [];
        }

        $data = array(
            Country::COUNTRYNAME => Country::getCountryNameByCode($id),
            Country::COUNTRYALPHA2CODE => $id,
            Country::COUNTRYALPHA3CODE => Country::getCountryAlpha3Code($id),
            InternationalStates::STATES => array()
        );

        $states = (new LocationService($this->app))->getStatesByCountry($id);

        foreach ($states as $state)
        {
            $data[InternationalStates::STATES][] = array(
                InternationalStates::STATENAME => $state['name'],
                InternationalStates::STATECODE => strtoupper($id).'-'.$state['state_code']
            );
        }

        return $data;
    }
}
