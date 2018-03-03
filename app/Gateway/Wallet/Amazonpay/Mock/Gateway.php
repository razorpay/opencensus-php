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

        $relativeUrl = explode('?', $request['url'])[1];

        $request['url'] = $baseUrl . '&' . $relativeUrl;

        return $request;
    }
}
