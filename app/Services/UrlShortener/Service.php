<?php

namespace RZP\Services\UrlShortener;

use Illuminate\Config\Repository as Config;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

//
// This service is available throughout application
//
// It in sequence tries more than one service until it gets either the short url
// or client side failure(eg. invalid url) etc.
//
//
class Service extends Impl\Base
{
    //
    // Comma separated list of service names, eg. gimli,bitly
    //

    private $services;

    public function __construct(
        Config $config,
        Trace $trace,
        ImplProvider $provider)
    {
        $this->config   = $config->get('applications.url_shortener');

        $this->trace    = $trace;

        $this->provider = $provider;

        $this->services = explode(',', $this->config['services']);
    }

    public function shorten(string $url, bool $fail = true)
    {
        foreach ($this->services as $service)
        {
            try
            {
                $service = $this->provider->get($service, $this->config[$service]);

                return $service->shorten($url);
            }
            catch (Exception\RuntimeException $e)
            {
                $this->trace->traceException($e, null, null, [
                        'service' => $service,
                        'url'     => $url,
                    ]);

                if ($this->shouldTryOtherServices($e->getData()) === false)
                {
                    if ($fail === true)
                    {
                        throw $e;
                    }
                    else
                    {
                        return $url;
                    }
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
