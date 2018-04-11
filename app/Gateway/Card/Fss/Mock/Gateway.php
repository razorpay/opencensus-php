<?php

namespace RZP\Gateway\Card\Fss\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Card\Fss;

class Gateway extends Fss\Gateway
{
    use Base\Mock\GatewayTrait;

    /**
     * Authorize function calling mock
     *
     * @param array $input
     *
     * @return mixed
     */
    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    /**
     * @param array  $content
     * @param string $method
     * @param null   $type
     *
     * @return array
     */
    public function getPurchaseRequestFieldsArray($content = [], $method = 'post', $type = null)
    {
        $purchaseUrl = parent::getPurchaseRequestFieldsArray($content, $method, $type);

        $gatewayAquirer = $this->input['terminal']->getGatewayAcquirer();

        $purchaseUrl['url'] .= '&acquirer=' . $gatewayAquirer;

        return $purchaseUrl;
    }
}
