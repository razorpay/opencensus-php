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

    protected function sendSoapRequest(array $data, string $soapRoot, string $method)
    {
        $this->trace->info(
            TraceCode::GATEWAY_SOAP_REQUEST,
            [
                'payment_id'  => $this->input['payment']['id'],
                'gateway'     => $this->gateway,
                'soap_method' => $method,
                'request'     => [
                    'soap_root' => $soapRoot,
                    'data'      => $data,
                ],
            ]);

        return $this->callGatewayRequestInternally($method, [$soapRoot => $data]);
    }

    protected function callGatewayRequestInternally(string $method, array $arguments)
    {
        $server = $this->app['gateway']->server($this->gateway);

        $server->setInput($arguments);

        $response = $server->$method($arguments);

        return $response;
    }
}
