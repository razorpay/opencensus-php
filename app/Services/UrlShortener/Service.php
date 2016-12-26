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
    // Comma separated list of services, eg. 'gimli,bitly'.

    private $services;

    public function __construct(Config $config, Trace $trace)
    {
        $this->config   = $config->get('applications.url_shortener');

        $this->trace    = $trace;

        $this->services = explode(',', $this->config['services']);
    }

    /**
     * Sets services to a different vaule. By default in ApiServiceProvider reg,
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
        foreach ($this->services as $service)
        {
            try
            {
                $implementation = $this->getImplementation($service, $this->config[$service]);

                $shortUrl = $implementation->shorten($url);

                return $shortUrl;
            }
            catch (Exception\RuntimeException $e)
            {
                $this->trace->traceException($e, null, null, [
                        'service' => $service,
                        'url'     => $url,
                    ]);

                if ($this->shouldTryOtherServices($e->getData()) === false)
                {
                    if ($fail === false)
                    {
                        return $url;
                    }

                    throw $e;
                }
            }
        }

        $this->trace->error(TraceCode::URL_SHORTENER_SERVICE_FAIL, ['url' => $url]);

        if ($fail === false)
        {
            return $url;
        }

        throw new Exception\RuntimeException('Failed to get short url.');
    }

    /**
     * Returns implementation of given service(eg. gimli, bitly etc.)
     *
     * @param string $service
     * @param array  $config
     *
     * @return Impl\Base
     */
    protected function getImplementation(string $service, array $config)
    {
        $impl = 'RZP\\Services\\UrlShortener\\Impl\\' . ucfirst($service);

        if (class_exists($impl) === false)
        {
            throw new Exception\RuntimeException("$impl does not exists.");
        }

        return $impl::instance($config);
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
}
