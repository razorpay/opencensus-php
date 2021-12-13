<?php

namespace RZP\Services;

use RZP\Constants\Country;
use RZP\Exception\BadRequestValidationFailureException;

class LocationService
{

    const STATES_FILE  = "json/states_by_country.json";
    const CACHE_PREFIX = "locations:states_by_country";
    const CACHE_TTL    = 86400 * 60;

    protected $cache;

    public function __construct($app)
    {
        $this->cache = $app['cache'];
    }

    protected function getCacheKey(string $countryCode): string
    {
        return sprintf("%s:%s", self::CACHE_PREFIX, $countryCode);
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateCountryCode(string $countryCode)
    {
        if (Country::exists($countryCode) === false)
        {
            throw new BadRequestValidationFailureException();
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function getStatesByCountry(string $countryCode)
    {
        $this->validateCountryCode($countryCode);

        $statesFile = resource_path(self::STATES_FILE);
        $key = $this->getCacheKey($countryCode);
        $states = $this->cache->get($key);

        if ($states === null)
        {
            // Populating the cache
            $statesJson = json_decode(file_get_contents($statesFile), true);
            foreach ($statesJson as $country)
            {
                $countryCodeStr = strtolower($country['country_code']);
                $cacheKey = $this->getCacheKey($countryCodeStr);
                $this->cache->put($cacheKey, $country['states'], self::CACHE_TTL);
            }
        }

        return $this->cache->get($key);
    }
}
