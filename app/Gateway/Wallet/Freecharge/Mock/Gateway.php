<?php

namespace RZP\Gateway\Wallet\Freecharge\Mock;

use RZP\Http\Route;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Freecharge;

class Gateway extends Freecharge\Gateway
{
    use Base\Mock\GatewayTrait;

    public function topup($input)
    {
        $request = parent::topup($input);

        $url = Route::getUrlWithPublicAuth(
                    'mock_wallet_payment_with_paymentid',
                    ['wallet' => $input['payment']['wallet'],
                     'paymentId' => $input['payment']['id']]);

        $request['url'] = $url;

        return $request;
    }

    protected function sendGatewayRequest($request)
    {
        // Redirect the request internally
        $serverResponse = $this->callGatewayRequestFunctionInternally($request);

        $response = $this->prepareInternalResponse($serverResponse);

        // Handle API Request Failure and throw exception
        $this->handleRequestFailed($response);

        return $response;
    }
}
