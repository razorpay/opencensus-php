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

        (new Admin\Service)->setConfigKeys([
               Admin\ConfigKey::UPI_TURBO_POPULAR_BANK_LIST => [
                   [
                       'priority'  => '1',
                       'iin'       => '119753',
                   ],
                   [
                       'priority'  => '2',
                       'iin'       => '246894',
                   ],
                   [
                       'priority'  => '3',
                       'iin'       => '607152',
                   ],
                   [
                       'priority'  => '4',
                       'iin'       => '123333',
                   ],
                ],
           ]);

        $request = $this->request('turbo/preferences',[$gatewayId]);

        $default = [
            'customer_id' => $this->fixtures->customer->getPublicId(),
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
