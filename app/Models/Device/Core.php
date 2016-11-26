<?php

namespace RZP\Models\Device;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Upi;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @var Upi\Core
     */
    protected $upiCore;

    public function __construct()
    {
        parent::__construct();

        $this->upiCore = new Upi\Core;
    }

    public function create(array $input)
    {
        $device = new Entity();

        $device->build($input);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function verify(Entity $device, Customer\Entity $customer)
    {
        $device->setStatus(Status::VERIFIED);

        $device->customer()->associate($customer);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function sendGetTokenRequestToGateway(Entity $device, Customer\Entity $customer)
    {
        $gatewayInput['device'] = $device->toArrayPublic();
        $gatewayInput['customer'] = $customer->toArrayPublic();

        $response = $this->upiCore->callUpiGateway('upi_npci', 'GetToken', $gatewayInput);

        return $response;
    }

    public function updateUpiToken(Entity $device, string $upiToken)
    {
        $device->setUpiToken($upiToken);

        $device = $this->repo->saveOrFail($device);

        return $device;
    }
}
