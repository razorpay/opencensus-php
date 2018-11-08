<?php

namespace RZP\Gateway\NpciPaySecure;

use RZP\Gateway\Base;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;
    use RequestHandlerTrait;

    protected $gateway = 'npci_paysecure';

    public function __construct()
    {
        parent::__construct();

        $this->wsdlDetails =  [
            'header' => [
                'namespace' => 'https://paysecure/merchant.soap.header/',
                'key' => 'RequestorCredentials',
            ],
            'body' => [
                'namespace' => 'https://paysecure/merchant.soap/',
                'key' => 'CallPaySecure'
            ],
        ];

        $this->wsdlDetails['wsdl_file'] = dirname(__FILE__) . '/rupay.wsdl.test';
    }

    public function authorize(array $input)
    {
        parent::authorize($input);

        // Todo: Don't trace card number and cvv
        $this->app['trace']->info(
            TraceCode::GATEWAY_REQUEST_INPUT_RECEIVED,
            $input
        );

        $checkBinResponse = $this->checkBin2();
    }



    // ------------ General helpers -----------------
    protected function getRepository()
    {
        $gateway = $this->gateway;

        return $this->app['repo']->$gateway;
    }
}
