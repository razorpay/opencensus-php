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

    public function initiateVerification(array $input): array
    {
        $this->initialize(Action::INITIATE_VERIFICATION, $input, true);

        $customer = $this->core->getDeviceCustomer($input[Entity::CUSTOMER_ID]);

        $this->context()->validateMerchant($customer->merchant);

        $this->input->put(Entity::CUSTOMER_ID, $customer->getId());

        $registerToken = (new RegisterToken\Core)->createWithDeviceData($this->input->toArray());

        $this->gatewayInput->put(Entity::REGISTER_TOKEN, $registerToken);

        $this->callbackInput->push($registerToken->getToken());

        return $this->callGateway();
    }

    protected function initiateVerificationSuccess(array $input): array
    {
        $this->initialize(Action::INITIATE_VERIFICATION_SUCCESS, $input, true);

        $token = $this->input->get(RegisterToken\Entity::TOKEN);
        $registerToken = (new RegisterToken\Core)->retrieveById($token);

        // Gateway might return device data to be saved in device_token
        $deviceData = array_merge_recursive(
            $this->input->get(RegisterToken\Entity::DEVICE_DATA, []),
            $registerToken->getDeviceData());

        $registerToken->setDeviceData($deviceData);
        $registerToken->setStatus(RegisterToken\Status::PENDING);

        $this->repo()->saveOrFail($registerToken);

        return $registerToken->toArray();
    }

    public function verification(array $input): array
    {
        $this->initialize(Action::VERIFICATION, $input, true);

        $token = $this->input->get(RegisterToken\Entity::TOKEN);
        $registerToken = (new RegisterToken\Core)->retrieveById($token);

        $this->gatewayInput->put(Entity::REGISTER_TOKEN, $registerToken);
        $this->callbackInput->push($registerToken->getToken());

        return $this->callGateway();
    }

    protected function verificationSuccess(array $input): array
    {
        $this->initialize(Action::VERIFICATION_SUCCESS, $input, true);

        $token = $this->input->get(RegisterToken\Entity::TOKEN);
        $registerToken = (new RegisterToken\Core)->retrieveById($token);

        // Gateway might return device data to be saved in device_token
        $deviceData = array_merge_recursive(
            $this->input->get(RegisterToken\Entity::DEVICE_DATA, []),
            $registerToken->getDeviceData());

        // Since this is success response, first, we will check for device status
        $deviceInput = array_except($deviceData, [
            DeviceToken\Entity::GATEWAY_DATA,
        ]);
        $device = $this->core->createOrUpdate($deviceInput);

        // We now can put device in context, which will be used in device token
        $this->context()->setDevice($device);

        // Now we will create the deviceToken, which will have gateway and CL data
        $deviceTokenInput = array_only($deviceData, [
            DeviceToken\Entity::GATEWAY_DATA,
        ]);

        $deviceToken = (new DeviceToken\Core)->create($deviceTokenInput);

        // Now we can update the register token
        (new RegisterToken\Core)->updateTokenCompleted($registerToken);

        return [
            Entity::ID                      => $device->getPublicId(),
            DeviceToken\Entity::STATUS      => $deviceToken->getStatus(),
            Entity::AUTH_TOKEN              => $device->getAuthToken(),
        ];
    }

    public function initiateGetToken(array $input): array
    {
        $this->initialize(Action::INITIATE_GET_TOKEN, $input, true);

        return $this->callGateway();
    }

    protected function initiateGetTokenSuccess(array $input): array
    {
        $this->initialize(Action::INITIATE_GET_TOKEN_SUCCESS, $input, true);

        return $this->callGateway();
    }

    public function getToken(array $input): array
    {
        $this->initialize(Action::GET_TOKEN, $input, true);

        return $this->callGateway();
    }

    protected function getTokenSuccess(array $input): array
    {
        $this->initialize(Action::GET_TOKEN_SUCCESS, $input, true);

        $deviceToken = $this->context()->getDeviceToken();

        $deviceToken->mergeGatewayData($this->input->get(DeviceToken\Entity::GATEWAY_DATA, []));
        $deviceToken->generateRefreshedAt();

        $this->repo()->saveOrFail($deviceToken);

        return [
            Entity::ID                      => $deviceToken->device->getPublicId(),
            DeviceToken\Entity::STATUS      => $deviceToken->getStatus(),
            Entity::AUTH_TOKEN              => $deviceToken->device->getAuthToken(),
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
