<?php

namespace RZP\Services\Elfin;

use Illuminate\Config\Repository as Config;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

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

    /**
     * Holds the available services for shortening urls.
     *
     * @var array
     */
    protected $services = [];

    protected $allowFallback = false;

    public function __construct(Config $config, Trace $trace)
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

    /**
     * Shorten given url.
     *
     * @param string       $url
     * @param bool|boolean $fail - If fail is passed as false, returns url itself
     *                             in case of failures.
     *
     * @return string
     */
    public function shorten(string $url, bool $fail = true)
    {
        $e = null;

        foreach ($this->getServices() as $service)
        {
            try
            {
                return $this->driver($service)->shorten($url);
            }
            catch (Exception\RuntimeException $e)
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
        // rethrow it here.
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
     * @param array  $config
     *
     * @return Impl\Base
     */
    protected function driver(string $service)
    {
        if (isset($this->drivers[$service]) === false)
        {
            $this->drivers[$service] = $this->createDriver($service);
        }

        return $this->drivers[$service];
    }

    protected function createDriver($service)
    {
        $class = __NAMESPACE__ . '\\Impl\\' . ucfirst($service);

        if (class_exists($class) === false)
        {
            throw new Exception\RuntimeException("$class does not exist.");
        }

        $config = $this->config[$service];

        return new $class($config);
    }
}
