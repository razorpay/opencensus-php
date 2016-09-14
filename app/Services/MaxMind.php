<?php

namespace RZP\Services;

use CreditCardFraudDetection;
use RZP\Exception;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class MaxMind
{
    const LICENSE_KEY = 'license_key';

    protected $licenseKey;

    protected $config;

    protected $trace;

    protected $maxmind;

    protected $request;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->request = $app['request'];

        $this->config = $app['config']->get('applications.maxmind');

        $this->licenseKey = $this->config['secret'];

        $this->maxmind = new CreditCardFraudDetection;
    }

    public function query($input)
    {
        $default = array(
            "license_key"       => $this->licenseKey,
            "i"                 => $this->request->server('REMOTE_ADDR'),
            'user_agent'        => $this->request->header('user-agent'),
            'accept_language'   => $this->request->header('accept_language')
        );

        $input = array_merge($input, $default);

        $this->maxmind->input($input);
        $this->maxmind->query();

        $response = $this->maxmind->output();

        $this->trace->info(TraceCode::MAXMIND_RESPONSE, [
                'input' => $input,
                'response' => $response]);

        return $response;
    }
}