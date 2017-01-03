<?php

namespace RZP\Services\UrlShortener;

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
        $this->config   = $config->get('applications.url_shortener');

        $this->trace    = $trace;

        $this->services = explode(',', $this->config['services']);

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

        foreach ($this->services as $service)
        {
            try
            {
                return $this->driver($service)->shorten($url);
            }
            catch (Exception\RuntimeException $e)
            {
                $data = ['service' => $service, 'url' => $url];

                $this->trace->traceException($e, null, null, $data);

                if ($this->shouldTryOtherServices($e->getData()) === false)
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

    protected function createDriver($driver)
    {
        if (in_array($driver, $this->getServices(), true) === false)
        {
            throw new Exception\LogicException(
                $driver . ' is not an available url shortener service');
        }

        return $this->createUrlShortenerDriver($driver);
    }

    protected function createUrlShortenerDriver($service)
    {
        $class = __NAMESPACE__ . '\\Impl\\' . ucfirst($service);

        if (class_exists($class) === false)
        {
            throw new Exception\RuntimeException("$class does not exist.");
        }

        $config = $this->config[$service];

        return new $class($config);
    }

    /**
     * Given the failure response from some service, decides if it should
     * try with next available service or not.
     *
     * @param array $data
     *
     * @return boolean
     */
    protected function shouldTryOtherServices($data)
    {
        if ($this->allowFallback === false)
        {
            return false;
        }

        //
        // Checks if it should continue with other services or just fail.
        //

        if (isset($data['status_code']) === false)
        {
            return true;
        }

        $code = $data['status_code'];

        if ($code >= 500)
        {
            return true;
        }

        if (in_array(
                $code,
                [
                    401, // Authentication issues
                    429, // Too many requests
                    451, // Unavailable for legal reasons
                ],
                true
            ))
        {
            return true;
        }

        //
        // In every other cases, such as validation error etc. should just fail
        // and not try with other services.
        //

        return false;
    }

    protected function getServices()
    {
        return $this->services;
    }
}
