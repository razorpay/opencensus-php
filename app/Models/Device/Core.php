<?php

namespace RZP\Models\Device;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $device = new Entity();

        $device->build($input);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function verifyAndGetToken(array $input)
    {

    }

    /**
     * Responsible for calling the gateway function
     *
     * @param  string $action      refund/capture etc.
     * @param  array  $gatewayData Relevant input for the corresponding
     *                             action
     *
     * @return array or null
     * @throws Exception\LogicException
     */
    protected function callUpiGateway($action, array $gatewayData)
    {
        $gateway = $this->payment->getGateway();

        return $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode);
    }
}
