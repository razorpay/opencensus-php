<?php

namespace RZP\Services;

use Config;

/**
 * Class VendorExperience
 *
 * @package RZP\Services
 *
 * No validations will happen here.
 * This will just call the right endpoints and return the responses as is
 * If there is an error thrown from the MicroService, that same error with
 * the right error code will be sent back to the caller
 *
 */

class VendorExperience {

    protected $baseUrl;

    protected $secret;

    protected $repo;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    protected $app;

    protected $timeout;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $vendorExperienceConfig = $this->config->get('applications.vendor_experience');

        $this->baseUrl = $vendorExperienceConfig['url'];

        $this->secret = $vendorExperienceConfig['secret'];

        $this->timeout = $vendorExperienceConfig['timeout'];

        $this->repo = $app['repo'];

        $this->app = $app;
    }
}
