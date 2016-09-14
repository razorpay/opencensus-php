<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use RZP\Http\Route;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Airtelmoney;

class Gateway extends Airtelmoney\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = Route::getUrlWithPublicAuth(
                    'mock_wallet_payment_with_paymentid',
                    ['wallet' => $input['payment']['wallet'],
                     'paymentId' => $input['payment']['id']]);

        $request['url'] = $url;

        return $request;
    }

    /*
     * Verify API does not send amount and we are asserting the amount in
     * authorize. So, For mock send the amount also in request to add it to
     * response
     */
    public function getVerifyRequestArray($input)
    {
        $request = parent::getVerifyRequestArray($input);

        $request['content']['amount'] = $input['payment']['amount'];

        return $request;
    }
}
