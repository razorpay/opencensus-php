<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device\RegisterToken;

class Validator extends Base\Validator
{
    protected static $initiateVerificationRules;
    protected static $initiateVerificationSuccessRules;
    protected static $verificationRules;
    protected static $verificationSuccessRules;
    protected static $initiateGetTokenRules;
    protected static $initiateGetTokenSuccessRules;
    protected static $getTokenRules;
    protected static $getTokenSuccessRules;
    protected static $deregisterRules;
    protected static $deregisterSuccessRules;

    public function rules()
    {
        $rules = [
            Entity::CUSTOMER_ID         => 'string|min:14|max:19',
            Entity::MERCHANT_ID         => 'string',
            Entity::CONTACT             => 'string|regex:/91(\d*){10}/',
            Entity::SIMID               => 'string',
            Entity::UUID                => 'string',
            Entity::TYPE                => 'string',
            Entity::OS                  => 'string',
            Entity::OS_VERSION          => 'string',
            Entity::APP_NAME            => 'string',
            Entity::IP                  => 'string',
            Entity::GEOCODE             => 'string',
            Entity::AUTH_TOKEN          => 'string',
            Entity::RESPONSE            => 'array',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::CUSTOMER_ID     => 'required',
            Entity::CONTACT         => 'required',
            Entity::SIMID           => 'required',
            Entity::UUID            => 'required',
            Entity::TYPE            => 'required',
            Entity::OS              => 'required',
            Entity::OS_VERSION      => 'required',
            Entity::APP_NAME        => 'required',
            Entity::IP              => 'required',
            Entity::GEOCODE         => 'required',
        ]);

        return $rules;
    }

    public function makeInitiateVerificationRules()
    {
        $rules = $this->makeRules([
            Entity::CUSTOMER_ID    => 'required|min:19',
            Entity::SIMID          => 'required',
            Entity::UUID           => 'required',
            Entity::TYPE           => 'required',
            Entity::OS             => 'required',
            Entity::OS_VERSION     => 'required',
            Entity::APP_NAME       => 'required',
            Entity::IP             => 'required',
            Entity::GEOCODE        => 'required',
        ]);

        return $rules;
    }

    public function makeInitiateVerificationSuccessRules()
    {
        $rules = $this->makeRules();

        $rules->merge((new RegisterToken\Validator)->makeVerificationSuccessRules());

        return $rules;
    }

    public function makeVerificationRules()
    {
        $rules = $this->makeRules();

        $rules->merge((new RegisterToken\Validator)->makeVerificationRules());

        return $rules;
    }

    public function makeVerificationSuccessRules()
    {
        $rules = $this->makeRules([
            RegisterToken\Entity::TOKEN         => 'required',
            RegisterToken\Entity::DEVICE_DATA   => 'sometimes',
        ]);

        return $rules;
    }

    public function makeInitiateGetTokenRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeInitiateGetTokenSuccessRules()
    {
        $rules = $this->makeRules();

        return $rules;
    }

    public function makeGetTokenRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeGetTokenSuccessRules()
    {
        $rules = $this->makeRules([
            DeviceToken\Entity::GATEWAY_DATA         => 'required',
        ]);

        return $rules;
    }

    public function makeDeregisterRules()
    {
        $rules = $this->makeRules();

        return $rules;
    }

    public function makeDeregisterSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::SUCCESS     => 'required|bool|in:1',
        ]);

        return $rules;
    }

    public function validateDeviceData()
    {
        (new RegisterToken\Validator)->validateDeviceData();
    }
}
