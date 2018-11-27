<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $startVerificationRules;
    protected static $getVerificationStatusRules;
    protected static $refreshClTokenRules;
    protected static $deregisterRules;

    protected function rules()
    {
        $rules = [
            Entity::CUSTOMER_ID         => 'string|min:14|max:19',
            Entity::MERCHANT_ID         => 'string',
            Entity::CONTACT             => 'string|regex:^\+91(\d*){10}$',
            Entity::SIMID               => 'string',
            Entity::UUID                => 'string',
            Entity::TYPE                => 'string',
            Entity::OS                  => 'string',
            Entity::OS_VERSION          => 'string',
            Entity::APP_NAME            => 'string',
            Entity::IP                  => 'string',
            Entity::GEOCODE             => 'string',
            Entity::AUTH_TOKEN          => 'string',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::CUSTOMER_ID,
            Entity::MERCHANT_ID,
            Entity::CONTACT,
            Entity::SIMID,
            Entity::UUID,
            Entity::OS,
            Entity::OS_VERSION,
            Entity::APP_NAME,
            Entity::IP,
            Entity::GEOCODE,
            Entity::AUTH_TOKEN,
        ]);

        return $rules;
    }

    protected function getStartVerificationRules()
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

        $rules->arrayRules(ClientLibrary::CL, [
            ClientLibrary::CAPABILITY   => 'required|string',
            ClientLibrary::CHALLENGE    => 'required|string',
        ]);

        return $rules;
    }

    protected function getGetVerificationStatusRules()
    {
        $rules = $this->makeRules([
            Entity::AUTH_TOKEN  => 'required',
        ]);

        return $rules;
    }

    protected function getRefreshClTokenRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getDeregisterRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
