<?php

namespace RZP\Models\Location;

use RZP\Models\Base;
use RZP\Constants\Country;
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
        
        $data = InternationalStates::getStatesByCode($id);

        return $data;
    }
}