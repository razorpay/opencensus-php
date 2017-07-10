<?php

namespace RZP\Gateway\Upi\Base\Mock;

trait GatewayTrait
{
    public function authorize(array $input)
    {
        return $this->authorizeMock($input, self::MOCK_ROUTE);
    }

    protected function getUrl($type = 'authorize'): string
    {
        return $this->route->getUrlWithPublicAuth(
                        self::MOCK_ROUTE, ['bank' => self::ACQUIRER]);
    }
}
