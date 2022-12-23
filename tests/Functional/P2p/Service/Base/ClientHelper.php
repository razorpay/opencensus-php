<?php

namespace RZP\Tests\P2p\Service\Base;

class ClientHelper extends P2pHelper
{
    public function getGatewayConfig(string $gatewayId, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $this->validationJsonSchemaPath = 'client/get_gateway_config';

        $request = $this->request('turbo/%s/config',[$gatewayId]);

        $default = [
            'customer_id' => $this->fixtures->customer->getPublicId(),
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
