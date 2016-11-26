<?php

namespace RZP\Models\Device;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Upi;
use RZP\Models\Customer;
use RZP\Models\Merchant;
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

    public function create(array $input, Merchant\Entity $merchant)
    {
        $device = new Entity();

        $device->build($input);

        $device->merchant()->associate($merchant);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function verify(Entity $device, Customer\Entity $customer)
    {
        if ($device->hasBeenVerified() === true)
        {
            return $device;
        }

        $device->setStatus(Status::VERIFIED);

        $device->customer()->associate($customer);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function sendGetTokenRequestToGateway(Entity $device, Customer\Entity $customer)
    {
        $gatewayInput['device'] = $device->toArray();
        $gatewayInput['customer'] = $customer->toArrayPublic();

        $response = $this->upiCore->callUpiGateway('upi_npci', 'GetToken', $gatewayInput);

        return $response;
    }

    public function updateUpiToken(Entity $device, string $upiToken)
    {
        $device->setUpiToken($upiToken);

        if ($device->hasBeenRegistered() === false)
        {
            $device->setStatus(Status::REGISTERED);
        }

        $this->repo->saveOrFail($device);

        return $device;
    }
}
