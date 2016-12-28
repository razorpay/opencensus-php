<?php

namespace RZP\Gateway\Wallet\Jiomoney\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Jiomoney;

class Gateway extends Jiomoney\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
                    'mock_wallet_payment_with_paymentid',
                    ['wallet' => $input['payment']['wallet'],
                     'paymentId' => $input['payment']['id']]);

        $request['url'] = $url;

        return $request;
    }

    protected function getStatusQueryRequest(array $input)
    {
        $request = parent::getStatusQueryRequest($input);

        $content = json_decode($request['content'], true);

        $content['payload_data']['amount'] = Jiomoney\TestAmount::FAIL_STATUSQUERY_AMOUNT;

        $request['content'] = json_encode($content);

        return $request;
    }
}
