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

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        $gateway = $this->gateway;

        $gatewayAcquirer = $this->terminal->getGatewayAcquirer();

        if ($gatewayAcquirer === Fss\Acquirer::SBI)
        {
            $route = 'mock_card_fss_payment_post';
        }
        else
        {
            $route = 'mock_' . $gateway . '_payment';
        }

        $url = $this->route->getUrlWithPublicAuth($route);

        if (($request['method'] === 'get') or ($gatewayAcquirer === 'sbin'))
        {
            // The key thing now is to replace the url from gateway to our mock one!
            $parts = parse_url($request['url']);

            $url = $url . '&' .$parts['query'];

            $request['url'] = $url;
        }

        $request['url'] = $url;
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
