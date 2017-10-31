<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function processPayment(array $input)
    {
        $this->trace->info(
            TraceCode::BHARAT_QR_PAYMENT_PROCESS_REQUEST,
            $input
        );

        $valid = $this->core->processPayment($input);

        if ($valid === true)
        {
            $xml = '<RESPONSE>OK</RESPONSE>';
        }
        else
        {
            $xml = '<RESPONSE>NOK</RESPONSE>';
        }


        $response = \Response::make($xml);

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}

