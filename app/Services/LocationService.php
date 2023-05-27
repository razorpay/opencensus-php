<?php

namespace RZP\Services;

use RZP\Constants\Country;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\ServerErrorException;
use RZP\Exception\BadRequestValidationFailureException;

class LocationService
{

    const STATES_FILE              = "json/states_by_country.json";
    const CACHE_PREFIX             = "locations:states_by_country";
    const CACHE_PREFIX_AUTOSUGGEST = "locations:autosuggest";
    const CACHE_TTL                = 86400 * 60;
    const CACHE_TTL_30_DAY         = 86400 * 30;

    protected $cache;
    protected $trace;

    public function __construct($app)
    {
        $this->cache = $app['cache'];
        $this->trace = $app['trace'];
    }

    protected function getCacheKey(string $prefix, string $key): string
    {
        return sprintf("%s:%s", $prefix, $key);
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
        $key = $this->getCacheKey(self::CACHE_PREFIX, $countryCode);
        $states = $this->cache->get($key);

        if ($states === null)
        {
            // Populating the cache
            $statesJson = json_decode(file_get_contents($statesFile), true);
            foreach ($statesJson as $country)
            {
                $countryCodeStr = strtolower($country['country_code']);
                $cacheKey = $this->getCacheKey(self::CACHE_PREFIX, $countryCodeStr);
                $this->cache->put($cacheKey, $country['states'], self::CACHE_TTL);
            }
        }

        return $this->cache->get($key);
    }

    /**
     * @throws ServerErrorException
     * @throws BadRequestValidationFailureException
     */
    public function getAddressSuggestions(array $parameters)
    {
        $addressQuery = $parameters['input'];
        $zipcode = $parameters['zipcode'] ?? '';
        $country = $parameters['country'] ?? '';

        if ($addressQuery === "" or $country === "")
        {
            throw new BadRequestValidationFailureException("Input should not be empty");
        }

        $cacheKey = $this->getCacheKey(self::CACHE_PREFIX_AUTOSUGGEST, $addressQuery . ':' . $zipcode . ':' . $country);
        $suggestions = $this->cache->get($cacheKey);

        if ($suggestions === null)
        {
            try
            {
                $suggestions = (new GoogleMapsClient())->fetchAddressSuggestions($addressQuery, $zipcode, $country);
                $this->cache->put($cacheKey, $suggestions, self::CACHE_TTL_30_DAY);
            }
            catch (\Throwable $e)
            {
                $this->trace->error(TraceCode::ADDRESS_SUGGEST_1CC_ERROR, ['error' => $e->getMessage()]);
                throw new ServerErrorException("External Error", ErrorCode::SERVER_ERROR);
            }
        }

        return $suggestions;
    }
}
