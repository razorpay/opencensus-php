<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Models\Admin;
use RZP\Models\Admin\Service as AdminService;

class PreferencesHelper extends P2pHelper
{
    public function getGatewayPreferences(string $gatewayId, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('turbo/preferences',[$gatewayId]);

        $default = [
            'customer_id' => $this->fixtures->customer->getPublicId(),
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
