<?php

namespace RZP\Gateway\Fss\Mock;

use RZP\Exception;
use RZP\Gateway\Fss\Constants;
use RZP\Gateway\Base;
use RZP\Gateway\Fss;

class Gateway extends Fss\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    public function getPurchaseRequestFieldsArray($content = [], $method = 'post', $type = null)
    {
        $purchaseUrl = parent::getPurchaseRequestFieldsArray($content, $method, $type);

        $gatewayAquirer = $this->input['terminal']->getGatewayAcquirer();

        $purchaseUrl['url'] .= '&acquirer=' . $gatewayAquirer;

        return $purchaseUrl;
    }
}
