<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device\DeviceToken;

class Validator extends Base\Validator
{
    protected static $beneficiaryRules;
    protected static $initiateAddRules;
    protected static $addRules;
    protected static $addSuccessRules;
    protected static $assignBankAccountRules;
    protected static $assignBankAccountSuccessRules;
    protected static $checkAvailabilityRules;
    protected static $checkAvailabilitySuccessRules;
    protected static $deleteRules;
    protected static $deleteSuccessRules;
    protected static $setDefaultRules;
    protected static $setDefaultSuccessRules;
    protected static $initiateCheckAvailabilityRules;

    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID            => 'string',
            Entity::HANDLE               => 'string|regex:/^[a-z0-9\.]{3,50}$/',
            Entity::GATEWAY_DATA         => 'array',
            Entity::USERNAME             => 'string|regex:/^[A-Za-z0-9\.\-]{3,200}$/',
            Entity::BANK_ACCOUNT_ID      => 'string',
            Entity::BENEFICIARY_NAME     => 'string',
            Entity::PERMISSIONS          => 'string',
            Entity::FREQUENCY            => 'string',
            Entity::ACTIVE               => 'string',
            Entity::VALIDATED            => 'boolean',
            Entity::VERIFIED             => 'boolean',
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

    public function makeBeneficiaryRules()
    {
        $rules = $this->makeRules([
            Entity::HANDLE               => 'required',
            Entity::USERNAME             => 'required',
            Entity::BENEFICIARY_NAME     => 'required',
            Entity::GATEWAY_DATA         => 'sometimes',
            Entity::VERIFIED             => 'sometimes',
        ]);

        return $rules;
    }

    public function makeVpaSuccessRules()
    {
        return $this->makeRules([
            Entity::USERNAME        => 'required',
            Entity::HANDLE          => 'required',
        ]);
    }

    public function makeInitiateAddRules()
    {
        return $this->makeRules([
            Entity::USERNAME        => 'sometimes',
            Entity::BANK_ACCOUNT_ID => 'required',
        ]);
    }

    public function makeAddRules()
    {
        $rules = $this->makeRules([
            Entity::USERNAME        => 'required',
            Entity::BANK_ACCOUNT_ID => 'required',
        ]);

        return $rules;
    }

    public function makeAddSuccessRules()
    {
        $rules = $this->makeRules();

        $rules->arrayRules(Entity::VPA, $this->makeVpaSuccessRules()->toArray());

        $rules->arrayRules(Entity::BANK_ACCOUNT, $this->makeEntityIdRules()->toArray());

        $deviceTokenRules = [
            DeviceToken\Entity::ID              => 'sometimes|string',
            DeviceToken\Entity::GATEWAY_DATA    => 'sometimes|array',
        ];

        $rules->arrayRules(DeviceToken\Entity::DEVICE_TOKEN, $this->makeRules($deviceTokenRules)->toArray());

        return $rules;
    }

    public function makeAssignBankAccountRules()
    {
        $rules = $this->makePublicIdRules();

        $rules->merge($this->makeRules([
            Entity::BANK_ACCOUNT_ID => 'required',
        ]));

        return $rules;
    }

    public function makeAssignBankAccountSuccessRules()
    {
        $rules = $this->makeRules();

        $rules->arrayRules(Entity::VPA, $this->makeEntityIdRules()->toArray());
        $rules->arrayRules(Entity::BANK_ACCOUNT, $this->makeEntityIdRules()->toArray());

        $deviceTokenRules = [
            DeviceToken\Entity::ID              => 'sometimes|string',
            DeviceToken\Entity::GATEWAY_DATA    => 'sometimes|array',
        ];

        $rules->arrayRules(DeviceToken\Entity::DEVICE_TOKEN, $this->makeRules($deviceTokenRules)->toArray());

        return $rules;
    }

    public function makeCheckAvailabilityRules()
    {
        $rules = $this->makeRules([
            Entity::USERNAME    => 'required',
        ]);

        return $rules;
    }

    public function makeCheckAvailabilitySuccessRules()
    {
        $rules = $this->makeRules([
            Entity::USERNAME        => 'required',
            Entity::HANDLE          => 'required',
            Entity::AVAILABLE       => 'required',
            Entity::SUGGESTIONS     => 'sometimes',
        ]);

        return $rules;
    }

    public function makeDeleteRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeDeleteSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::SUCCESS     => 'required|boolean|in:1'
        ]);

        $rules->arrayRules(Entity::VPA, $this->makeEntityIdRules()->toArray());

        return $rules;
    }

    public function makeInitiateCheckAvailabilityRules()
    {
        $rules = $this->makeRules([
            Entity::USERNAME    => 'required',
        ]);

        return $rules;
    }

    public function makeSetDefaultRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeSetDefaultSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::SUCCESS     => 'required|boolean|in:1'
        ]);

        $rules->arrayRules(Entity::VPA, $this->makeEntityIdRules()->toArray());

        return $rules;
    }

    public function makeFetchAllRules()
    {
        // restrict fetch all via only device id in merchant context
        if($this->context === Base\Libraries\Context::MERCHANT)
        {
            return  Parent::makeDeviceIdRules();
        }
        else
        {
            return $this->makeRules([]);
        }
    }
}
