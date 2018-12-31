<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $fetchHandlesRules;
    protected static $addRules;
    protected static $fetchAllRules;
    protected static $fetchRules;
    protected static $assignBankAccountRules;
    protected static $checkAvailabilityRules;
    protected static $deleteRules;

    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID            => 'string',
            Entity::HANDLE               => 'string',
            Entity::GATEWAY_DATA         => 'array',
            Entity::USERNAME             => 'string|regex:^[A-Za-z0-9\.\-]*$',
            Entity::BANK_ACCOUNT_ID      => 'string',
            Entity::BENEFICIARY_NAME     => 'string',
            Entity::PERMISSIONS          => 'string',
            Entity::FREQUENCY            => 'string',
            Entity::ACTIVE               => 'string',
            Entity::VALIDATED            => 'string',
            Entity::VERIFIED             => 'string',
            Entity::DEFAULT              => 'string',
        ];

        return $rules;
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::DEVICE_ID            => 'sometimes',
            Entity::HANDLE               => 'sometimes',
            Entity::GATEWAY_DATA         => 'sometimes',
            Entity::USERNAME             => 'sometimes',
            Entity::BANK_ACCOUNT_ID      => 'sometimes',
            Entity::BENEFICIARY_NAME     => 'sometimes',
            Entity::PERMISSIONS          => 'sometimes',
            Entity::FREQUENCY            => 'sometimes',
            Entity::ACTIVE               => 'sometimes',
            Entity::VALIDATED            => 'sometimes',
            Entity::VERIFIED             => 'sometimes',
            Entity::DEFAULT              => 'sometimes',
        ]);

        return $rules;
    }

    public function makeFetchHandlesRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeAddRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeFetchRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeAssignBankAccountRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeCheckAvailabilityRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeDeleteRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
