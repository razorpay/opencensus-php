<?php

namespace RZP\Gateway\Netbanking\Hdfc\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Netbanking\Hdfc;
use RZP\Gateway\Netbanking\Hdfc\Fields;

class Gateway extends Hdfc\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = $this->route->getUrlWithPublicAuth(
                                'mock_netbanking_payment',
                                ['bank' => $this->bank]);

        $request['url'] = $url;

        return $request;
    }

    public function getPaymentIdFromServerCallback($input)
    {
        return $input["paymentId"];
    }
}
