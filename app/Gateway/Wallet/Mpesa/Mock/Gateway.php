<?php

namespace RZP\Gateway\Wallet\Mpesa\Mock;

use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Mpesa;

class Gateway extends Mpesa\Gateway
{
    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $request['url'] = $this->route->getUrlWithPublicAuth(
                            'mock_wallet_payment_get',
                            [
                                'wallet'    => $input['payment']['wallet'],
                                'paymentId' => $input['payment']['id']
                            ]);

        return $request;
    }

    protected function getSoapClientObject()
    {
        $soapClient = new SoapClient($this->getUrl());

        $headers = $this->getSoapHeaders();

        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }
}
