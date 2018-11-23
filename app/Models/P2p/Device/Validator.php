<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device\RegisterToken;

class Validator extends Base\Validator
{
    const RESPONSE  = 'response';

    protected static $startVerificationRules;
    protected static $startVerificationSuccessRules;
    protected static $getVerificationStatusRules;
    protected static $getVerificationStatusSuccessRules;
    protected static $refreshClTokenRules;
    protected static $refreshClTokenSuccessRules;
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

            // Register Token Rules
            RegisterToken\Entity::TOKEN         => 'string',
            RegisterToken\Entity::DEVICE_DATA   => 'array',
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

    public function makeStartVerificationRules()
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

        $rules->merge((new DeviceToken\Validator)->makeClRules()->toArray());

        return $rules;
    }

    public function makeStartVerificationSuccessRules()
    {
        $rules = $this->makeRules([
            RegisterToken\Entity::TOKEN         => 'required',
            RegisterToken\Entity::RESPONSE      => 'required',
            RegisterToken\Entity::DEVICE_DATA   => 'sometimes',
        ]);

        return $rules;
    }

    public function makeGetVerificationStatusRules()
    {
        $rules = $this->makeRules([
            RegisterToken\Entity::TOKEN  => 'required',
        ]);

        return $rules;
    }

    public function makeGetVerificationStatusSuccessRules()
    {
        $rules = $this->makeRules([
            RegisterToken\Entity::TOKEN  => 'required',
            RegisterToken\Entity::DEVICE_DATA   => 'sometimes',
        ]);

        return $rules;
    }

    public function makeRefreshClTokenRules()
    {
        $rules = $this->makeRules([]);

        $rules->merge((new DeviceToken\Validator)->makeClRules()->toArray());

        return $rules;
    }

    public function makeRefreshClTokenSuccessRules()
    {
        $rules = $this->makeRules([]);

        $rules->merge((new DeviceToken\Validator)->makeClSuccessRules()->toArray());

        return $rules;
    }

    public function makeDeregisterRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeDeregisterSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::SUCCESS     => 'required|bool|in:1',
        ]);

        return $rules;
    }
}
