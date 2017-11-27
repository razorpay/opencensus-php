<?php

namespace RZP\Services\Geolocation;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\GeoIP\Entity;

class Service
{
    const EUREKA            = 'eureka';

    const PROVIDER_NAME     = 'provider_name';

    const ALLOWED_PROVIDERS = [
        self::EUREKA,
    ];

    const ALLOWED_INPUT     = [
        self::PROVIDER_NAME,
        Providers\Eureka::EUREKA_KEY,
        Providers\Eureka::EUREKA_KEY_INDEX,
    ];

    protected $mocked;
    protected $config;
    protected $provider;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('services.geolocation');

        $this->mocked = $this->config['mocked'];

        $this->provider = $this->getProvider($this->config['provider']);

        $this->fillable = (new Entity())->getFillable();
    }

    /**
     * Set a provider for valid providerName
     *
     * @param string $providerName
     */
    public function setProvider(string $providerName)
    {
        $this->provider = $this->getProvider($providerName);
    }

    /**
     * Removes all fields from input which are consumed by geo location service
     *
     * @param array $input
     * @return array
     */
    public function removeServiceFieldsFromInput(array $input)
    {
        return array_except($input, self::ALLOWED_INPUT);
    }

    /**
     * Validate input fields which are to be consumed by geo location service
     *
     * @param array $input
     */
    public function validateAndSetInput(array $input)
    {
        $providerName = $this->getProviderNameFromInput($input);

        if ($providerName !== null)
        {
            $this->setProvider($providerName);
        }

        $this->provider->validateAndSetInput($input);
    }

    /**
     * Return all possible details for geo location
     *
     * @param string $ip
     * @return mixed
     */
    public function getGeoLocation(string $ip)
    {
        try
        {
            $geolocation = $this->provider->getGeoLocation($ip);

            $this->validate($geolocation);

            return $geolocation;
        }
        catch (Exception\RecoverableException $e)
        {
            $this->trace->warning(
                TraceCode::GEOLOCATION_FAILURE,
                [
                    'message'  => $e->getMessage(),
                    'ip'       => $ip,
                    'provider' => $this->config['provider'],
                ]);

            return null;
        }
    }

    /**
     * Return a provider if for valid name
     *
     * @param string $providerName
     * @return Providers\Base
     */
    private function getProvider(string $providerName): Providers\Base
    {
        if (in_array($providerName, self::ALLOWED_PROVIDERS, true) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid provider name ' . $providerName);
        }

        $options = $this->config['providers'][$providerName];

        switch ($providerName)
        {
            case self::EUREKA:
                $provider = new Providers\Eureka($options);
        }

        $provider->setMocked($this->mocked);

        return $provider;
    }

    /**
     * Validate provider's response
     *
     * @param array $geolocation
     */
    private function validate(array $geolocation)
    {
        // Verify all required fields are there
        $excluded = array_diff_key(array_flip($this->fillable), $geolocation);

        if (count($excluded) > 0)
        {
            throw new Exception\LogicException('Invalid transformation');
        }

        // Country column is required
        if (empty($geolocation[Entity::COUNTRY]) === true)
        {
            throw new Exception\BadRequestValidationFailureException('Country must not be empty');
        }
    }

    /**
     * Provider name can be passed in request
     *
     * @param array $input
     */
    private function getProviderNameFromInput(array $input)
    {
        if (empty($input[self::PROVIDER_NAME]) === false)
        {
            return $input[self::PROVIDER_NAME];
        }
    }
}
