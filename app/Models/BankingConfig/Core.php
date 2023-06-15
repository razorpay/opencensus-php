<?php

namespace RZP\Models\BankingConfig;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\NetbankingConfig;
use RZP\Trace\TraceCode;
use function PHPUnit\Framework\isNull;


class Core extends Base\Core
{
    // returns true if the config is not present in DCS
    // fetches all fields and checks if their value is equal to default
    public function createNewConfig(array $input): bool
    {
        $shortKey = $input[Constants::SHORT_KEY];

        $key = $input[Constants::KEY];

        $entityId = $input[Constants::ENTITY_ID];

        $allFields = [];

        $allFieldMap = Constants::BANKING_CONFIGS[$key];

        foreach ($allFieldMap as $fieldName => $v)
        {
            array_push($allFields, $fieldName);
        }

        $dcsConfigService = app('dcs_config_service');

        $res = $dcsConfigService->fetchConfiguration($shortKey, $entityId, $allFields, $this->mode);

        // iterate through all fields and check if their value is same as default value

        foreach ($res as $field => $value)
        {
            $dataType = $allFieldMap[$field]["type"];

            $defaultValue = Constants::DEFAULT_VALUES[$dataType];

            if ($defaultValue !== $value)
            {
                return false;
            }

        }
        

        return true;
    }

    public function upsertBankingConfigs($input, $sessionOrgId)
    {
        (new Validator)->validateInput(
            'upsert',
             $input
        );

        $key = $input[Constants::KEY];

        $shortKey = $input[Constants::SHORT_KEY];

        $entityId = $input[Constants::ENTITY_ID];

        $fieldName = $input[Constants::FIELD_NAME];

        $fieldValue = $input[Constants::FIELD_VALUE];

        $entityOrgId = $this->getEntityOrgId($key, $entityId);

        (new Validator())->isAdminAuthorized($sessionOrgId, $entityOrgId);

        (new Validator())->validateConfigOwnership($key, $fieldName);

        $dcsConfigService = app('dcs_config_service');

        $key = $input[Constants::SHORT_KEY];

        $entityId = $input[Constants::ENTITY_ID];

        $fieldName = $input[Constants::FIELD_NAME];

        $fieldValue = $input[Constants::FIELD_VALUE];

        $createNewConfig = $this->createNewConfig($input);

        if ($createNewConfig === true)
        {
            return $dcsConfigService->createConfiguration($key, $entityId, [$fieldName => $fieldValue], $this->mode);
        }

        return $dcsConfigService->editConfiguration($key, $entityId, [$fieldName => $fieldValue], $this->mode);
    }

    // returns the orgid of entity
    private function getEntityOrgId($key, $entity_id)
    {
        $keyArr = explode("/", $key);

        if (count($keyArr) < 4)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Key '.$key. ' is invalid');
        }

        $entity = $keyArr[2];

        if ($entity === "org")
        {
            return $entity_id;
        }
        else if ($entity === "merchant")
        {
            $merchant = $this->repo->merchant->findOrFail($entity_id);

            return $merchant->org->getId();
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Entity '.$entity. ' is not supported');
        }
    }

}
