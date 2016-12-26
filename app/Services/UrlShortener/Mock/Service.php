<?php

namespace RZP\Services\UrlShortener\Mock;

use Illuminate\Config\Repository as Config;

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
        //
        // Generates random short url and returns
        //

        $url = 'http://dwarf.razorpay.dev/' . random_alphanum_string(7);

        return $url;
    }
}
