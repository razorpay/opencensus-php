<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Carbon\Carbon;

use RZP\Models\P2p\Device;
use RZP\Constants\Timezone;
use RZP\Models\P2p\Device\Entity;
use RZP\Models\Currency\Currency;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\Sdk;
use RZP\Models\P2p\Device\DeviceToken;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;

class DeviceGateway extends Gateway implements Contracts\DeviceGateway
{
    // 9 minutes in seconds
    const DEFAULT_TOKEN_EXPIRY_TIME = 540;

    protected $actionMap = DeviceAction::MAP;

    public function initiateVerification(Response $response)
    {
        $deviceData = $this->input->get(Entity::REGISTER_TOKEN)->get(Fields::DEVICE_DATA);

        $merchantCustomerId = $this->formatMerchantCustomerId($deviceData[Entity::CUSTOMER_ID]);

        // Validate if DeviceData has SDK which has
        $request = $this->getSessionTokenRequest();

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID  => $merchantCustomerId,
            Fields::SIM_ID                => $deviceData[Entity::SDK][Fields::SIM_ID],
        ]);

        $response->setRequest($request);
    }

    public function verification(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        $deviceData = $this->input->get(Entity::REGISTER_TOKEN)->get(Fields::DEVICE_DATA);

        $merchantCustomerId = $this->formatMerchantCustomerId($deviceData[Entity::CUSTOMER_ID]);

        $callback = $this->input->get(Fields::CALLBACK);

        switch ($callback->get(Fields::ACTION))
        {
            case DeviceAction::GET_SESSION_TOKEN:
                $this->handleGetSessionToken(
                    $response,
                    [
                        Fields::SIM_ID  => $deviceData[Fields::SDK][Fields::SIM_ID],
                    ]);

                break;

            case DeviceAction::BIND_DEVICE:
                $this->handleBindDevice(
                    $response,
                    [
                        Fields::CUSTOMER_MOBILE_NUMBER  => $sdk->get(Fields::CUSTOMER_MOBILE_NUMBER),
                        Fields::MERCHANT_CUSTOMER_ID    => $merchantCustomerId,
                    ]);

                break;

            case DeviceAction::ACTIVATE_DEVICE_BINDING:
                $this->handleActivateDeviceBinding($response,
                    [
                        Fields::CUSTOMER_MOBILE_NUMBER  => $sdk->get(Fields::CUSTOMER_MOBILE_NUMBER),
                        Fields::MERCHANT_CUSTOMER_ID    => $merchantCustomerId,
                    ]);

                break;

            default:
                // As verification callback can only handle GET_SESSION_TOKEN or BIND_DEVICE
                throw $this->p2pGatewayException(ErrorMap::INVALID_CALLBACK, [
                    Entity::CALLBACK => $callback
                ]);
        }

        if ($response->hasRequest() === false)
        {
            $deviceTokenExpireAt = $this->getCurrentTimestamp() + self::DEFAULT_TOKEN_EXPIRY_TIME;

            $response->setData([
                Fields::TOKEN       => $this->input->get(Entity::REGISTER_TOKEN)->get(Fields::TOKEN),
                Fields::DEVICE_DATA => [
                    Entity::CONTACT           => $sdk->get(Fields::CUSTOMER_MOBILE_NUMBER),
                    DeviceToken\Entity::GATEWAY_DATA => [
                        Fields::DEVICE_FINGERPRINT      => $sdk->get(Fields::DEVICE_FINGERPRINT),
                        Fields::MERCHANT_CUSTOMER_ID    => $merchantCustomerId,
                        Fields::SIM_ID                  => $deviceData[Fields::SDK][Fields::SIM_ID],
                        DeviceToken\Entity::EXPIRE_AT   => $deviceTokenExpireAt,
                    ],
                ],
            ]);
        }
    }

    public function initiateGetToken(Response $response)
    {
        $device = $this->getContextDevice();
        $deviceToken = $this->getContextDeviceToken();

        // Merchant Customer Id needs to be picked from Gateway Data
        $merchantCustomerId = $deviceToken->get(Entity::GATEWAY_DATA)[Fields::MERCHANT_CUSTOMER_ID] ??
                              $this->formatMerchantCustomerId($device->get(Device\Entity::CUSTOMER_ID));

        // Sim Id is required and must be available in gateway data
        $simId = $deviceToken->get(Entity::GATEWAY_DATA)[Fields::SIM_ID] ?? '0';

        // Validate if DeviceData has SDK
        $request = $this->getSessionTokenRequest();

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID  => $merchantCustomerId,
            Fields::SIM_ID                => $simId,
        ]);

        $response->setRequest($request);
    }

    public function getToken(Response $response)
    {
        $sdk = $this->handleInputSdk();

        if (($this->isDeviceBound($sdk) === false) or ($this->isDeviceActivated($sdk) === false))
        {
            // the device binding is not present, sdk needs to reinitiates device binding
            // need to check if we want to throw exception or set error in data
            throw $this->p2pGatewayException(ErrorMap::INACTIVE_DEVICE, [Entity::SDK => $sdk]);
        }

        $deviceTokenExpireAt = $this->getCurrentTimestamp() + self::DEFAULT_TOKEN_EXPIRY_TIME;

        $response->setData([
            Entity::DEVICE_TOKEN => [
                Entity::ID              => $this->getContextDeviceToken()->get(Entity::ID),
                Entity::GATEWAY_DATA => [
                    Fields::DEVICE_FINGERPRINT      => $sdk[Fields::DEVICE_FINGERPRINT],
                    DeviceToken\Entity::EXPIRE_AT   => $deviceTokenExpireAt,
                ]
            ]
        ]);

        return $response;
    }

    public function deregister(Response $response)
    {
        $device = $this->getContextDevice();
        $deviceToken = $this->getContextDeviceToken();

        // Merchant Customer Id needs to be picked from Gateway Data
        $merchantCustomerId = $deviceToken->get(Entity::GATEWAY_DATA)[Fields::MERCHANT_CUSTOMER_ID] ??
                              $this->formatMerchantCustomerId($device->get(Device\Entity::CUSTOMER_ID));

        $request = $this->initiateS2sRequest(DeviceAction::DEREGISTER);

        $request->merge([
            Fields::CUSTOMER_MOBILE_NUMBER  => $device->get(Entity::CONTACT),
        ]);

        $s2s = $this->sendS2sRequest($request);

        $response->setData([
            'success' => true,
        ]);

        return $response;
    }

    /*** PRIVATE METHODS ***/

    private function handleGetSessionToken(
        Response $response,
        array $bindRequest)
    {
        $sdk = $this->handleInputSdk();

        // we are intentionally calling bind device and not giving the control to session token api
        // to activate device binding. This is being done to avoid cases where token can expire and we are
        // in the middle of activation. Bind device is the call that can take maximum time so
        // we want that bind device and activate bind device happen in one go.
        if ($this->isDeviceActivated($sdk) === false)
        {
            $request = $this->bindDeviceRequest();

            $request->merge($bindRequest);

            $response->setRequest($request);
        }
    }

    private function handleBindDevice(
        Response $response,
        $activateBindingRequest)
    {
        $sdk = $this->handleInputSdk();

        $this->validateFields($activateBindingRequest, [
            Device\Entity::CONTACT => Fields::CUSTOMER_MOBILE_NUMBER,
        ]);

        if (($this->isDeviceBound($sdk) === false))
        {
            // Should never come here as sdk can not be success for non bound device
            throw $this->p2pGatewayException(ErrorMap::NOT_AVAILABLE, [Entity::SDK => $sdk]);
        }
        else if ($this->isDeviceActivated($sdk) === false)
        {
            $request = $this->activateDeviceBindingRequest();

            $request->merge($activateBindingRequest);

            $hash = $this->generateHash([
                $activateBindingRequest[Fields::CUSTOMER_MOBILE_NUMBER],
                $activateBindingRequest[Fields::MERCHANT_CUSTOMER_ID],
                $this->input->get(Entity::REGISTER_TOKEN)->get(Fields::TOKEN),
                DeviceAction::ACTIVATE_DEVICE_BINDING,
            ]);

            $request->mergeUdf([
                Fields::RSH => $hash,
            ]);

            $response->setRequest($request);
        }
    }

    private function handleActivateDeviceBinding(
        Response $response,
        $activateBindingRequest)
    {
        $sdk = $this->handleInputSdk();

        $this->validateFields($activateBindingRequest, [
            Device\Entity::CONTACT => Fields::CUSTOMER_MOBILE_NUMBER,
        ]);

        if (($this->isDeviceActivated($sdk) === false))
        {
            // Should never come here as sdk can not be success for non activated device
            throw $this->p2pGatewayException(ErrorMap::NOT_AVAILABLE, [Entity::SDK => $sdk->toArray()]);
        }

        $udf = json_decode($sdk->get(Fields::UDF_PARAMETERS), true);

        $actualHash = array_get($udf, Fields::RSH);

        if (is_string($actualHash) === false)
        {
            // We do not want to auto initiate the verification
            throw $this->p2pGatewayException(ErrorMap::SDK_HASH_MISSING, [Entity::SDK => $sdk->toArray()]);
        }

        $expectedHash = $this->generateHash([
            $activateBindingRequest[Fields::CUSTOMER_MOBILE_NUMBER],
            $activateBindingRequest[Fields::MERCHANT_CUSTOMER_ID],
            $this->input->get(Entity::REGISTER_TOKEN)->get(Fields::TOKEN),
            DeviceAction::ACTIVATE_DEVICE_BINDING,
        ]);

        if (hash_equals($expectedHash, $actualHash) === false)
        {
            // For rolling out this will make sure that customer retries
            throw $this->p2pGatewayException(ErrorMap::SDK_HASH_MISMATCH, [Entity::SDK => $sdk->toArray()]);
        }
    }

    private function getSessionTokenRequest()
    {
        $request = $this->initiateSdkRequest(DeviceAction::GET_SESSION_TOKEN);

        $request->merge([
            Fields::MERCHANT_ID           => $this->getMerchantId(),
            Fields::MERCHANT_CHANNEL_ID   => $this->getMerchantChannelId(),
            Fields::MCC                   => $this->getMerchantCategoryCode(),
            Fields::TIMESTAMP             => $this->getTimeStamp(),
            Fields::CURRENCY              => Currency::INR,
        ]);

        $request->setCallback();

        return $request;
    }

    private function bindDeviceRequest()
    {
        $request = $this->initiateSdkRequest(DeviceAction::BIND_DEVICE);

        $request->setCallback();

        return $request;
    }

    private function activateDeviceBindingRequest()
    {
        $request = $this->initiateSdkRequest(DeviceAction::ACTIVATE_DEVICE_BINDING);

        $attributes = [
            Fields::SHOULD_ACTIVATE         => 'true', // ToDo to handle these string conversions at one place
            Fields::TIMESTAMP               => $this->getTimeStamp(),
        ];

        $request->merge($attributes);

        $request->setCallback();

        return $request;
    }

    private function isDeviceBound(ArrayBag $sdk): bool
    {
        return $sdk->get(Fields::IS_DEVICE_BOUND) === 'true';
    }

    private function isDeviceActivated(ArrayBag $sdk): bool
    {
        return $sdk->get(Fields::IS_DEVICE_ACTIVATED) === 'true';
    }
}
