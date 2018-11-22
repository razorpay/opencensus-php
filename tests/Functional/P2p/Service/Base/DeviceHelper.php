<?php

namespace RZP\Tests\P2p\Service\Base;

class DeviceHelper extends P2pHelper
{
    public function startVerification(array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/start_verification';

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('customers/verification/start');

        $this->resetContexts();

        $default = [
            'customer_id'      => $this->fixtures->customer->getPublicId(),
            'ip'               => '179.0.0.1',
            'os'               => 'android',
            'os_version'       => '5.0.1',
            'simid'           => '683729232343',
            'uuid'             => '5637293534543',
            'type'             => 'mobile',
            'geocode'         => '12.971599,77.594566',
            'app_name'         => 'com.razorpay',
            'cl'               => [
                'capability'       => '52000002000100040006',
                'challenge'        => 'AUnhIkGYnGBK=='
            ]
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function fetchVerificationStatus(string $token)
    {
        $this->validationJsonSchemaPath = 'device/verification_status';

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('customers/verification/%s', [$token]);

        $this->resetContexts();

        return $this->get($request);
    }

    public function refreshClToken(array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/cl_refresh_token';

        $request = $this->request('cl_token_refresh');

        $default = [
            'cl' => [
                'challenge'  => 'AikxOldnJmaUbdsmHdsnaudjeGHndshsjSildsmfyneHDBd'
            ]
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function deregisterDevice()
    {
        $this->validationJsonSchemaPath = 'device/deregister';

        $request = $this->request('deregister');

        return $this->delete($request);
    }
}
