<?php

namespace RZP\Gateway\Esigner\Legaldesk\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Esigner\Legaldesk\ResponseFields;

class Server extends Base\Mock\Server
{
    public function authorize($input)
    {
        $input = json_decode($input, true);

        $response = $this->getMandateCreateResponse($input);

        return $this->makeJsonResponse($response);
    }

    protected function getMandateCreateResponse($input)
    {
        $mandateId = '123916398176387';

        return [
            ResponseFields::STATUS              => 'success',
            ResponseFields::API_RESPONSE_ID     => '5b6c51139788dd40bd25ded6',
            ResponseFields::REFERENCE_ID        => '987654321',
            ResponseFields::ERROR               => 'NA',
            ResponseFields::ERROR_CODE          => 'NA',
            ResponseFields::EMANDATE_ID         => $mandateId,
            ResponseFields::RESPONSE_TIME_STAMP => '2018-08-09T20:04:59',
            ResponseFields::QUICK_INVITE_URL    => $this->getMockPaymentGatewayUrl($mandateId)
        ];
    }

    protected function getMockPaymentGatewayUrl($mandateId)
    {
        $route = 'mock_esigner_payment';

        return $this->route->getUrl($route, ['signer' => 'legaldesk']) . '?mandate_id=' . $mandateId;
    }
}
