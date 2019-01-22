<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    protected $entity = 'p2p_device';

    public function startVerification(array $input): array
    {
        $this->initialize(Action::START_VERIFICATION, $input, true);

        $registerToken = (new RegisterToken\Core)->createWithDeviceData($this->input->toArray());

        $this->gatewayInput->put(Entity::REGISTER_TOKEN, $registerToken->toArrayBag());

        return $this->callGateway();
    }

    protected function startVerificationSuccess(array $input): array
    {
        $this->initialize(Action::START_VERIFICATION_SUCCESS, $input, true);

        $token = $this->input->get(RegisterToken\Entity::TOKEN);
        $registerToken = (new RegisterToken\Core)->retrieveById($token);

        // Gateway might return device data to be saved in device_token
        $deviceData = array_merge_recursive(
            $this->input->get(RegisterToken\Entity::DEVICE_DATA, []),
            $registerToken->getDeviceData());

        $registerToken->setDeviceData($deviceData);
        $registerToken->setStatus(RegisterToken\Status::PENDING);

        $this->repo()->saveOrFail($registerToken);

        $response = array_merge(
            $this->input->get(Entity::RESPONSE),
            $registerToken->only(RegisterToken\Entity::HANDLE, RegisterToken\Entity::TOKEN));

        return $response;
    }

    public function getVerificationStatus(array $input): array
    {
        $this->initialize(Action::GET_VERIFICATION_STATUS, $input, true);

        $token = $this->input->get(RegisterToken\Entity::TOKEN);
        $registerToken = (new RegisterToken\Core)->retrieveById($token);

        // We will hit gateway to verify the register token only if it is in pending state
//        if ($registerToken->getStatus() !== RegisterToken\Status::PENDING)
//        {
//            // Throw Exception
//        }

        $this->gatewayInput->put(Entity::REGISTER_TOKEN, $registerToken->toArrayBag());

        return $this->callGateway();
    }

    protected function getVerificationStatusSuccess(array $input): array
    {
        $this->initialize(Action::GET_VERIFICATION_STATUS_SUCCESS, $input, true);

        $token = $this->input->get(RegisterToken\Entity::TOKEN);
        $registerToken = (new RegisterToken\Core)->retrieveById($token);

        // Gateway might return device data to be saved in device_token
        $deviceData = array_merge_recursive(
            $this->input->get(RegisterToken\Entity::DEVICE_DATA, []),
            $registerToken->getDeviceData());

        // Since this is success response, first, we will check for device status
        $deviceInput = array_except($deviceData, [
            DeviceToken\Entity::GATEWAY_DATA,
            Base\Upi\ClientLibrary::CL,
        ]);
        $device = $this->core->createOrUpdate($deviceInput);

        // We now can put device in context, which will be used in device token
        $this->context()->setDevice($device);

        // Now we will create the deviceToken, which will have gateway and CL data
        $deviceTokenInput = array_only($deviceData, [
            DeviceToken\Entity::GATEWAY_DATA,
            Base\Upi\ClientLibrary::CL,
        ]);

        (new DeviceToken\Core)->create($deviceTokenInput);

        // Now we can update the register token
        $registerToken = (new RegisterToken\Core)->updateTokenCompleted($registerToken);

        return [
            RegisterToken\Entity::STATUS    => $registerToken->getStatus(),
            Entity::AUTH_TOKEN              => $device->getAuthToken(),
        ];
    }

    public function refreshClToken(array $input): array
    {
        $this->initialize(Action::REFRESH_CL_TOKEN, $input, true);

        return $this->callGateway();
    }

    protected function refreshClTokenSuccess(array $input): array
    {
        $this->initialize(Action::REFRESH_CL_TOKEN_SUCCESS, $input, true);

        $deviceToken = $this->context()->getDeviceToken();

        $deviceToken->mergeCl($this->input->get(DeviceToken\Entity::CL, []));
        $deviceToken->generateRefreshedAt();

        $this->repo()->saveOrFail($deviceToken);

        return [
            Entity::CONTACT                    => $deviceToken->device->getContact(),
            DeviceToken\Entity::HANDLE         => $deviceToken->getHandle(),
            DeviceToken\Entity::CL             => $deviceToken->getCl(),
            DeviceToken\Entity::REFRESHED_AT   => $deviceToken->getRefreshedAt(),
        ];
    }

    public function deregister(array $input): array
    {
        $this->initialize(Action::DEREGISTER, $input, true);

        return $this->callGateway();
    }

    protected function deregisterSuccess(array $input): array
    {
        $this->initialize(Action::DEREGISTER_SUCCESS, $input, true);

        (new DeviceToken\Core)->expire();

        return [
            Entity::SUCCESS => true,
        ];
    }
}
