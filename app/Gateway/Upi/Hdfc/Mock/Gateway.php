<?php

namespace RZP\Gateway\Upi\Hdfc\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Hdfc;

class Gateway extends Hdfc\Gateway
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
                        'mock_upi_payment', ['bank' => 'hdfc']);
        return $url;
    }
}
