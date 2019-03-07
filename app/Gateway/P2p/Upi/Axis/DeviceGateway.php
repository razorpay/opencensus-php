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
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Gateway\P2p\Upi\Axis\Actions\DeviceAction;

class DeviceGateway extends Gateway implements Contracts\DeviceGateway
{
    protected $actionMap = DeviceAction::MAP;

    public function startVerification(Response $response)
    {

    }

    public function getVerificationStatus(Response $response)
    {

    }

    public function refreshClToken(Response $response)
    {

    }

    public function deregister(Response $response)
    {

    }

    public function initiateVerification(Response $response)
    {
        $udfParameters = '';

        $request = $this->getSessionToken($this->input, $udfParameters);

        $response->setRequest($request);
    }

    public function verification(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        $gateway = $this->input->get(Fields::GATEWAY);

        switch ($gateway->get('action'))
        {
            case DeviceAction::GET_SESSION_TOKEN:
                return $this->handleGetSessionToken();

            case DeviceAction::BIND_DEVICE:
                return $this->handleBindDevice();

            default:
                throw \Exception('why');
        }

        $registerToken = $this->input->get('register_token');

        $isDeviceBound = $this->toBoolean($sdk[Fields::IS_DEVICE_BOUND]);

        $isDeviceActivated = $this->toBoolean($sdk[Fields::IS_DEVICE_ACTIVATED]);
        
        if (($isDeviceBound === false) and ($isDeviceActivated === false))
        {
            $this->bindDevice($registerToken, $udfParameters);
        }

        //             $this->terminateDeviceBinding($response);
        if (($isDeviceBound === true) and ($isDeviceActivated === false))
        {
            return $this->initiateActivateDeviceBinding($response);
        }

        if (($isDeviceBound === true) and ($isDeviceActivated === true))
        {
            $response->setData([
                Fields::TOKEN => $registerToken,
                Fields::DEVICE_DATA => [
                    Device\Entity::CONTACT      => $sdk[Fields::CUSTOMER_MOBILE_NUMBER],
                ],
                Fields::GATEWAY_DATA => [
                    Fields::IS_DEVICE_BOUND     => $isDeviceBound,
                    Fields::IS_DEVICE_ACTIVATED => $isDeviceActivated,
                    Fields::DEVICE_FINGERPRINT  => $sdk[Fields::DEVICE_FINGERPRINT],
                ]
            ]);
            
            return $response;
        }
    }

    public function initiateGetToken(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        $device = $this->context->getDevice();

        $udfParameters = '';

        $attributes = [
          Fields::MERCHANT_ID           => $this->getMerchantId(),
          Fields::MERCHANT_CHANNEL_ID   => $this->getMerchantChannelId(),
          Fields::MERCHANT_CUSTOMER_ID  => $device->getCustomerId(),
          Fields::MCC                   => $this->getMerchantCategoryCode(),
          Fields::SIM_ID                => $device->getSimid(),
          Fields::TIMESTAMP             => $this->getTimeStamp(),
          Fields::CURRENCY              => Currency::INR,
          Fields::UDF_PARAMETERS        => $udfParameters
        ];

        $request = $this->initiateSdkRequest(DeviceAction::GET_SESSION_TOKEN);

        $request->merge($attributes);

        $request->setCallback();

        $request->finish();

        $response->setRequest($request);

        return $response;
    }

    public function getToken(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        $isDeviceBound = $this->toBoolean($sdk[Fields::IS_DEVICE_BOUND]);

        $isDeviceActivated = $this->toBoolean($sdk[Fields::IS_DEVICE_ACTIVATED]);

        if (($isDeviceBound === false) or ($isDeviceActivated === false))
        {
            // the device binding is not present, sdk needs to reinitiates device binding
            // need to check if we want to throw exception or set error in data
        }

        $response->setData([
            Fields::DEVICE_DATA => [
                Device\Entity::CONTACT      => $sdk[Fields::CUSTOMER_MOBILE_NUMBER],
            ],
            Fields::SDK => [
                Fields::IS_DEVICE_BOUND     => $isDeviceBound,
                Fields::IS_DEVICE_ACTIVATED => $isDeviceActivated,
                Fields::DEVICE_FINGERPRINT  => $sdk[Fields::DEVICE_FINGERPRINT],
            ]
        ]);

        return $response;
    }

    private function initiateActivateDeviceBinding(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        $registerToken = $this->input->get('register_token');

        $request = $this->initiateSdkRequest(DeviceAction::ACTIVATE_DEVICE_BINDING);

        $deviceData = $registerToken->get(RegisterToken\Entity::DEVICE_DATA);

        $udfParameters = '';

        $attributes = [
            Fields::SHOULD_ACTIVATE         => 'true',
            Fields::CUSTOMER_MOBILE_NUMBER  => $sdk[Fields::CUSTOMER_MOBILE_NUMBER],
            Fields::MERCHANT_CUSTOMER_ID    => $deviceData[Device\Entity::CUSTOMER_ID],
            Fields::TIMESTAMP               => $this->getTimeStamp(),
            Fields::UDF_PARAMETERS          => $udfParameters
        ];

        $request->merge($attributes);

        $request->setCallback();

        $request->finish();

        $response->setRequest($request);

        return $response;
    }

    private function terminateDeviceBinding(Response $response)
    {
        $sdk = $this->input->get(Fields::SDK);

        $response->setData([
            Fields::SDK_DATA => [
                Fields::IS_DEVICE_BOUND     => $sdk(Fields::IS_DEVICE_BOUND),
                Fields::IS_DEVICE_ACTIVATED => $sdk[Fields::IS_DEVICE_ACTIVATED],
            ],
        ]);
    }

    private function getSessionToken($input, $udfParameters)
    {
        $attributes = [
            Fields::MERCHANT_ID           => $this->getMerchantId(),
            Fields::MERCHANT_CHANNEL_ID   => $this->getMerchantChannelId(),
            Fields::MERCHANT_CUSTOMER_ID  => $input[Device\Entity::CUSTOMER_ID],
            Fields::MCC                   => $this->getMerchantCategoryCode(),
            Fields::SIM_ID                => $input[Device\Entity::SIMID],
            Fields::TIMESTAMP             => $this->getTimeStamp(),
            Fields::CURRENCY              => Currency::INR,
            Fields::UDF_PARAMETERS        => $udfParameters
        ];

        $request = $this->initiateSdkRequest(DeviceAction::GET_SESSION_TOKEN);

        $request->merge($attributes);

        $request->setCallback();

        $request->finish();

        return $request;
    }

    private function bindDevice($token, $udfParameters)
    {
        $attributes = [
            Fields::SIM_ID          => $token[Device\Entity::SIMID],
            Fields::UDF_PARAMETERS  => $udfParameters,
        ];

        $request = $this->initiateSdkRequest(DeviceAction::BIND_DEVICE);

        $request->merge($attributes);

        $request->setCallback();

        $request->finish();

        return $request;
    }

    private function handleGetSessionToken()
    {
        if ($this->isSdkFailure())
        {
            // Handle Error
        }


    }

    private function isSdkFailure()
    {
        return $this->input->get(Fields::SDK)->get('status') != 'SUCCESS';
    }
}
