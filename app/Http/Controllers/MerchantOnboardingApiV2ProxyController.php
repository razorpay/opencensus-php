<?php

namespace RZP\Http\Controllers;

class MerchantOnboardingApiV2ProxyController extends MerchantOnboardingProxyController
{

    protected function getHeadersForDashboardRequest(array $body = [], string $id = '', string $productType = '')
    {
        $headers = parent::getHeadersForDashboardRequest($body, $id);
        // update merchant-id to take $id as merchant instead of basic auth, if id is filled.
        $headers['x-merchant-id'] = empty($id) === true ? optional($this->ba->getMerchant())->getId() : $id;
        return $headers;
    }

}
