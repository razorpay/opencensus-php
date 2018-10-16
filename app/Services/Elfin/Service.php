<?php

namespace RZP\Services\Elfin;

use Illuminate\Config\Repository as Config;

use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;

/**
 * This service is available throughout application.
 *
 * It in sequence tries more than one service until it gets either the short url
 * or client side failure(eg. invalid url) etc.
 */
class Service extends Impl\Base
{
    // Comma separated list of services, eg. 'gimli, bitly'.
    // Gimly is internal implementation while Bitly is the external service.

    const GIMLI = 'gimli';
    const BITLY = 'bitly';

    /**
     * Holds the available services for shortening urls.
     *
     * @var array
     */
    protected $services = [];

    protected $trace;

    protected $config;

    protected $allowFallback;

    public function __construct(Config $config, Logger $trace)
    {
        $this->config        = $config->get('applications.elfin');
        $this->trace         = $trace;
        $this->services      = explode(',', $this->config['services']);
        $this->allowFallback = $config['allow_fallback'];
    }

    /**
     * Sets services to a different value. By default in ApiServiceProvider reg,
     * It will take from config, but in case in code we need to update this, this
     * will be used.
     *
     * @param array $services
     *
     * @return Service
     */
    public function setServices(array $services)
    {
        $this->services = $services;

        return $this;
    }

    public function getServices()
    {
        return $this->services;
    }

    public function setNoFallback()
    {
        $this->allowFallback = false;
    }

    /**
     * Shorten given url.
     *
     * @param string       $url
     * @param array        $input
     * @param bool|boolean $fail - If fail is passed as true it'll bubble up ex.
     *
     * @return string
     * @throws \Throwable
     * @throws null
     */
    public function shorten(string $url, array $input = [], bool $fail = false)
    {
        $e = null;

        foreach ($this->getServices() as $service)
        {
            try
            {
                return $this->driver($service)->shorten($url, $input, $fail);
            }
            /**
             * Catching \Throwable as it is the base most interface and covers
             * Error as well as Exceptions of any kind.
             */
            catch (\Throwable $e)
            {
                $data = ['service' => $service, 'url' => $url];

                $this->trace->traceException($e, null, null, $data);

                if ($this->allowFallback === false)
                {
                    break;
                }
            }
        }

        // If failing is allowed, and exception is thrown earlier, then
        // re-throw it here.
        if (($fail === true) and ($e !== null))
        {
            throw $e;
        }

        return $url;
    }

    /**
     * Returns implementation of given service(eg. gimli, bitly etc.)
     *
     * @param string $service
     *
     * @return Impl\Base
     * @throws Exception\RuntimeException
     */
    public function driver(string $service)
    {
        if (isset($this->drivers[$service]) === false)
        {
            $this->drivers[$service] = $this->createDriver($service);
        }

        return $this->drivers[$service];
    }

    protected function createDriver($service)
    {
        $class = __NAMESPACE__ . '\\Impl\\' . studly_case($service);

        if (class_exists($class) === false)
        {
            throw new Exception\RuntimeException("$class does not exist.");
        }

        $config = $this->config[$service];

        return new $class($config);
    }
}
