<?php

namespace RZP\Gateway\Wallet\Amazonpay\Mock;

use RZP\Gateway\Wallet\Amazonpay;
use RZP\Gateway\Base\Mock\GatewayTrait;
use RZP\Models\Payment\Processor\Wallet;

final class Gateway extends Amazonpay\Gateway
{
    use GatewayTrait;

    public final function authorize(array $input): array
    {
        $request = parent::authorize($input);

        $baseUrl = $this->route->getUrlWithPublicAuth('mock_wallet_payment', ['wallet' => Wallet::AMAZONPAY]);

        $queryString = parse_url($request['url'], PHP_URL_QUERY);

        $request['url'] = $baseUrl . '&' . $queryString;

        return $request;
    }
}
