<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Icici;

class Gateway extends Icici\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function getUrl($type = 'authorize')
    {
        $url = parent::getUrl($type);

        $url = $this->route->getUrlWithPublicAuth(
                        'mock_upi_hdfc_payment', ['bank' => 'icici']);
        return $url;
    }
}
