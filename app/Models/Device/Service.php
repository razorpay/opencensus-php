<?php

namespace RZP\Models\Device;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Customer;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->device = $this->app['basicauth']->getDevice();

        $this->core = new Core();
    }

    public function create(array $input)
    {
        $device = $this->core->create($input, $this->merchant);

        return $device->toArrayPublic();
    }

    public function fetch($deviceId)
    {
        $device = $this->repo->device->findByPublicIdAndMerchant($deviceId, $this->merchant);

        return $device->toArrayPublic();
    }

    public function verify(array $input)
    {
        // TODO: Fix this
        \Database\DefaultConnection::set('test');
        list($verificationToken, $contact) = $this->getRelevantFieldsForVerify($input);

        $device = $this->repo->device->findByVerificationToken($verificationToken);

        // TODO: Fix this
        $this->app['basicauth']->setMode('test');

        $customer = (new Customer\Core)->createLocalCustomer([Customer\Entity::CONTACT => $contact], $device->merchant, false);

        $device = $this->core->verify($device, $customer);

        $this->device = $device;

        $response = [];

        if ($device->hasBeenRegistered() === false)
        {
            $response = $this->core->sendGetTokenRequestToGateway($device, $customer);
        }
    }

    public function refreshToken(array $input)
    {
        if ($this->device->hasBeenRegistered() === true)
        {
            $device = $this->core->updateChallenge($this->device, $input[Entity::CHALLENGE]);

            $response = $this->core->sendGetTokenRequestToGateway($device, $device->customer, 'rotate');
        }
    }

    public function updateUpiToken(string $deviceId, string $upiToken)
    {
        // TODO: Fix this
        \Database\DefaultConnection::set('test');
        $device = $this->repo->device->findOrFail($deviceId);

        $device = $this->core->updateUpiToken($device, $upiToken);

        return $device->toArrayPublic();
    }

    protected function getRelevantFieldsForVerify(array $input)
    {
        // Msg91 converts the keyword to lowercase

        $validator = new Validator;
        $validator->setStrictFalse();
        $validator->validateInput('verify', $input);

        $verificationToken = $input['message'];
        $contact = $input['number'];

        return [$verificationToken, $contact];
    }
}
