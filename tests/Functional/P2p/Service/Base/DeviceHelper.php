<?php

namespace RZP\Tests\P2p\Service\Base;

class DeviceHelper extends P2pHelper
{
    public function initiateVerification(array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/initiate_verification';

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('customers/verification/initiate');

        $this->resetContexts();

        $default = [
            'customer_id'      => $this->fixtures->customer->getPublicId(),
            'ip'               => '179.0.0.1',
            'os'               => 'android',
            'os_version'       => '5.0.1',
            'simid'            => '683729232343',
            'uuid'             => '5637293534543',
            'type'             => 'mobile',
            'geocode'          => '12.971599,77.594566',
            'app_name'         => 'com.razorpay',
            'sdk'              => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function verification(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/verification';

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request($callback);

        $this->resetContexts();

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateGetToken(array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/initiate_get_token';

        $request = $this->request('token/initiate');

        $default = [
            'ip'               => '179.0.0.1',
            'os'               => 'android',
            'os_version'       => '5.0.1',
            'simid'            => '683729232343',
            'uuid'             => '5637293534543',
            'type'             => 'mobile',
            'geocode'          => '12.971599,77.594566',
            'app_name'         => 'com.razorpay',
            'sdk' => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function getToken(string $callback, array $content = [])
    {
        $this->validationJsonSchemaPath = 'device/get_token';

        $request = $this->request($callback);

        $default = [
            'sdk'              => []
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

    public function updateWithAction(string $id, string $action, array $content)
    {
        $request = $this->request('devices/%s/%s', [$id, $action]);

        $this->content($request, [], $content);

        return $this->post($request);
    }

    public function fetchAll(array $content = [])
    {
        $this->validationJsonSchemaPath = 'devices/fetchAll';

        $request = $this->request('devices');

        $this->content($request, [], $content);

        return $this->get($request);
    }

    public function getGatewayConfig(string $gatewayId, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        // This API work on public auth
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $this->validationJsonSchemaPath = 'device/get_gateway_config';

        $request = $this->request('turbo/%s/config',[$gatewayId]);

        $default = [
            'customer_id' => $this->fixtures->customer->getPublicId(),
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

}
