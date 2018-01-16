<?php

namespace RZP\Services\Geolocation\Providers;

use App;

abstract class Base
{
    const IP          = 'IP';

    /**
     * Used in test purpose
     */
    const FAILURE_IPS = [
        'INVALID_IP',
        '127.0.0.1'
    ];

    /**
     * @var array
     */
    protected $options;

    /**
     * @var bool
     */
    protected $mocked = false;

    /**
     * Each provider must have implementation of retrieving geo location
     *
     * @param array $query
     * @return mixed
     */
    abstract protected function geolocations(array $ips);

    /**
     * Each provider must have implementation to transform geo location
     * to common format
     *
     * @param array $geolocation
     * @return mixed
     */
    abstract protected function transform(array $geolocation);

    /**
     * Base constructor.
     * @param array $options
     */
    final public function __construct(array $options)
    {
        $app = App::getFacadeRoot();

        $this->trace   = $app['trace'];

        $this->options = $options;
    }

    /**
     * @param bool $mocked
     */
    final public function setMocked(bool $mocked)
    {
        $this->mocked = $mocked;
    }

    /**
     * Return structured geo location for IP
     *
     * @param string $ip
     * @return array
     */
    final public function getGeolocation(string $ip)
    {
        $geolocations = $this->getGeolocations([$ip]);

        return $geolocations[$ip];
    }

    /**
     * Takes array, currently as [list of ips]
     *
     * @param array $ips
     * @return array Multiple geo locations indexed on IP
     */
    final public function getGeolocations(array $ips)
    {
        $geolocations = $this->geolocations($ips);

        $container = [];

        foreach ($geolocations as $ip => $geolocation)
        {
            $transformed = $this->transform($geolocation);

            $container[$ip] = $transformed;
        }

        return $container;
    }

    /**
     * Providers can use input as additional options
     *
     * @param array $input
     */
    public function validateAndSetInput(array $input)
    {
        return;
    }
}
