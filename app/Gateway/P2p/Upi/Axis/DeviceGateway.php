<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Carbon\Carbon;

use RZP\Models\P2p\Device;
use RZP\Constants\Timezone;
use RZP\Models\Currency\Currency;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\Request;
use RZP\Models\P2p\Device\DeviceToken;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;

class DeviceGateway extends Gateway implements Contracts\DeviceGateway
{
    protected $actionMap = DeviceAction::MAP;

    public function initiateVerification(Response $response)
    {
        $deviceData = $this->input->get(Device\Entity::REGISTER_TOKEN)->get(Fields::DEVICE_DATA);

        $merchantCustomerId = $this->formatMerchantCustomerId($deviceData[Device\Entity::CUSTOMER_ID]);
        // Validate if DeviceData has SDK which has
        $request = $this->getSessionTokenRequest();

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID  => $merchantCustomerId,
        ]);

        $response->setRequest($request);
    }

    public function verification(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        $deviceData = $this->input->get(Device\Entity::REGISTER_TOKEN)->get(Fields::DEVICE_DATA);

        $merchantCustomerId = $this->formatMerchantCustomerId($deviceData[Device\Entity::CUSTOMER_ID]);

        $callack = $this->input->get(Fields::CALLBACK);

        switch ($callack->get(Fields::ACTION))
        {
            case DeviceAction::GET_SESSION_TOKEN:
                $this->handleGetSessionToken(
                    $response,
                    [
                        Fields::SIM_ID  => $deviceData[Fields::SDK][Fields::SIM_ID],
                    ],
                    [
                        Fields::CUSTOMER_MOBILE_NUMBER  => $sdk->get(Fields::CUSTOMER_MOBILE_NUMBER),
                        Fields::MERCHANT_CUSTOMER_ID    => $merchantCustomerId,
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

            default:
                // As verification callback can only handle GET_SESSION_TOKEN or BIND_DEVICE
                $this->throwP2pGatewayException();
        }

        if ($response->hasRequest() === false)
        {
            $response->setData([
                Fields::TOKEN       => $this->input->get(Device\Entity::REGISTER_TOKEN)->get(Fields::TOKEN),
                Fields::DEVICE_DATA => [
                    Device\Entity::CONTACT           => $sdk->get(Fields::CUSTOMER_MOBILE_NUMBER),
                    DeviceToken\Entity::GATEWAY_DATA => [
                        Fields::DEVICE_FINGERPRINT      => $sdk->get(Fields::DEVICE_FINGERPRINT),
                        Fields::MERCHANT_CUSTOMER_ID    => $merchantCustomerId,
                    ],
                ],
            ]);
        }
    }

    public function initiateGetToken(Response $response)
    {
        $device = $this->getContextDevice();
        $merchantCustomerId = $this->formatMerchantCustomerId($device->get(Device\Entity::CUSTOMER_ID));

        // Validate if DeviceData has SDK which has
        $request = $this->getSessionTokenRequest();

        $request->merge([
            Fields::MERCHANT_CUSTOMER_ID  => $merchantCustomerId,
        ]);

        $response->setRequest($request);
    }

    public function getToken(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        if (($this->isDeviceBound($sdk) === false) or ($this->isDeviceActivated($sdk) === false))
        {
            // the device binding is not present, sdk needs to reinitiates device binding
            // need to check if we want to throw exception or set error in data
            $this->throwP2pGatewayException();
        }

        $response->setData([
            Fields::GATEWAY_DATA => [
                Fields::DEVICE_FINGERPRINT  => $sdk[Fields::DEVICE_FINGERPRINT],
            ]
        ]);

        return $response;
    }

    public function deregister(Response $response)
    {

    }

    /*** PRIVATE METHODS ***/

    private function handleGetSessionToken(
        Response $response,
        array $bindRequest,
        array $activateBindingRequest)
    {
        $sdk = $this->input->get(Fields::SDK);

        if ($this->isSdkFailure())
        {
            $this->throwP2pGatewayException();
        }

        if (($this->isDeviceBound($sdk) === false))
        {
            $request = $this->bindDeviceRequest();

            $request->merge($bindRequest);

            $response->setRequest($request);
        }
        else if ($this->isDeviceActivated($sdk) === false)
        {
            $request = $this->activateDeviceBindingRequest();

            $request->merge($activateBindingRequest);

            $response->setRequest($request);
        }
    }

    private function handleBindDevice(
        Response $response,
        $activateBindingRequest)
    {
        $sdk = $this->input->get(Fields::SDK);

        if ($this->isSdkFailure())
        {
            $this->throwP2pGatewayException();
        }

        if (($this->isDeviceBound($sdk) === false))
        {
            // Should never come here as sdk can not be success for non bound device
            $this->throwP2pGatewayException();
        }
        else if ($this->isDeviceActivated($sdk) === false)
        {
            $request = $this->activateDeviceBindingRequest();

            $request->merge($activateBindingRequest);

            $response->setRequest($request);
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
            Fields::SHOULD_ACTIVATE         => 'true',
            Fields::TIMESTAMP               => $this->getTimeStamp(),
        ];

        $request->merge($attributes);

        $request->setCallback();

        return $request;
    }

    private function isSdkFailure(): bool
    {
        return $this->input->get(Fields::SDK)->get(Fields::STATUS) != 'SUCCESS';
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
