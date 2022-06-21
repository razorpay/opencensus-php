<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Events\P2p;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Beneficiary;
use RZP\Models\P2p\Transaction;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    use MerchantTrait;

    protected $entity = 'p2p_device';

    public function initiateVerification(array $input): array
    {
        $this->initialize(Action::INITIATE_VERIFICATION, $input, true);

        $customer = $this->core->getDeviceCustomer($input[Entity::CUSTOMER_ID]);

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
        $registerToken = (new RegisterToken\Core)->fetch($token);

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
        $registerToken = (new RegisterToken\Core)->fetch($token);

        $this->gatewayInput->put(Entity::REGISTER_TOKEN, $registerToken);
        $this->callbackInput->push($registerToken->getToken());

        return $this->callGateway();
    }

    protected function verificationSuccess(array $input): array
    {
        $this->initialize(Action::VERIFICATION_SUCCESS, $input, true);

        $token = $this->input->get(RegisterToken\Entity::TOKEN);
        $registerToken = (new RegisterToken\Core)->fetch($token);

        // If the register token was marked completed by another request/callback
        if ($registerToken->isCompleted())
        {
            $device = (new Core)->fetch($registerToken->getDeviceId());

            $deviceId = array_get($this->input->get(Entity::DEVICE), Entity::ID);

            // Gateway must acknowledge that RT was completed and send device block
            // TODO: Handle race condition where RT was marked completed in between of request
            if ($device->getId() !== $deviceId)
            {
                $response = [
                    'expected'  => $device->getId(),
                    'actual'    => $deviceId,
                ];

                $this->setException(new Exception\LogicException('Device id mismatch', $response));

                return $response;
            }

            // Device token must be there if RT is completed
            $deviceToken = $device->deviceToken($this->context()->getHandle());

            if (empty($deviceToken) === true)
            {
                $response = [
                    'device_id' => $device->getId(),
                    'handle'    => $this->context()->handleCode(),
                ];

                $this->setException(new Exception\LogicException('Device token is missing', $response));

                return $response;
            }

            // Now set the device/and token in context
            $this->context()->setDevice($device, true);
            $this->context()->setDeviceToken($deviceToken);

            return $this->makeTokenResponse($deviceToken);
        }

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
        $this->context()->setDevice($device, true);

        // Now we can fire the event of device verification completed
        $this->app['events']->dispatch(new P2p\DeviceVerificationCompleted($this->context(), $device));

        // Now we will create the deviceToken, which will have gateway and CL data
        $deviceTokenInput = array_only($deviceData, [
            DeviceToken\Entity::GATEWAY_DATA,
        ]);

        $deviceToken = (new DeviceToken\Core)->create($deviceTokenInput);

        // Now we can inject Device Token
        $this->context()->setDeviceToken($deviceToken);

        // Now we can update the register token
        (new RegisterToken\Core)->updateTokenCompleted($registerToken);

        return $this->makeTokenResponse($deviceToken);
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

        (new DeviceToken\Core)->update($deviceToken, $this->input->get(Entity::DEVICE_TOKEN));

        return $this->makeTokenResponse($deviceToken);
    }

    public function deregister(array $input): array
    {
        $this->initialize(Action::DEREGISTER, $input, true);

        // TODO: Make sure this is done on either of Private/AppAuth
        if ($this->input->get('force'))
        {
            return $this->deregisterSuccess([
                Entity::SUCCESS => true
            ]);
        }

        return $this->callGateway();
    }

    public function deviceCooldownCompleted(array $input)
    {
        $this->initialize(Action::DEVICE_COOLDOWN_COMPLETED, $input);

        $this->app['events']->dispatch(
            new P2p\DeviceCooldownCompleted($this->context(), $this->context()->getDevice())
        );

        return [
            Entity::SUCCESS => true,
        ];
    }

    protected function deregisterSuccess(array $input): array
    {
        $this->initialize(Action::DEREGISTER_SUCCESS, $input, true);

        $this->repo()->transaction(
            function()
            {
                (new Vpa\Core)->deregister();
                (new BankAccount\Core)->deregister();
                (new DeviceToken\Core)->deregister();
                (new Beneficiary\Core)->deregister();
            });

        $device = $this->context()->getDevice();

        $this->app['events']->dispatch(new P2p\DeviceDeregistrationCompleted($this->context(), $device));

        return [
            Entity::SUCCESS => true,
        ];
    }

    protected function makeTokenResponse(DeviceToken\Entity $deviceToken)
    {
        $defaultVpa = (new Vpa\Core)->getDefaultVpa();

        return [
            Entity::ID                      => $deviceToken->device->getPublicId(),
            DeviceToken\Entity::STATUS      => $deviceToken->getStatus(),
            Entity::AUTH_TOKEN              => $deviceToken->device->getAuthToken(),
            DeviceToken\Entity::EXPIRE_AT   => $deviceToken->getExpireAt(),
            Vpa\Entity::VPA                 => $defaultVpa ? $defaultVpa->toArrayPublic() : null,
        ];
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input, true);

        $entities = $this->core->fetchAll($input);

        return $entities->toArrayPublic();
    }
}
