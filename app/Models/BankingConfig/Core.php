<?php

namespace RZP\Models\BankingConfig;

use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\NetbankingConfig;
use RZP\Services\Dcs\Configurations\Constants as DcsConfigConst;
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

    public function fetchPaymentsNotesKeys($userId, $merchantId): array {

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($merchant->isFeatureEnabled(Feature::CUSTOM_TXN_TAB_VIEW) === false) {
            throw new  Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $dcsConfigService = app(Constants::DCS_CONFIG_SERVICE);

        $userColumnsDCS = $dcsConfigService->fetchConfiguration(DcsConfigConst::PaymentNotesKeyColumns, $userId, [DcsConfigConst::PaymentNotesKeyColumns], $this->mode);

        $userTotalColumns = [];

        if(empty($userColumnsDCS) === false and
            $userColumnsDCS[DcsConfigConst::PaymentNotesKeyColumns] !== "")
        {
            $userTotalColumns = json_decode($userColumnsDCS[DcsConfigConst::PaymentNotesKeyColumns], true);
        }

        $data[Constants::PAYMENT_OPTIONAL_KEYS_COLUMNS] = $userTotalColumns[Constants::PAYMENT_OPTIONAL_KEYS_COLUMNS];

        $data[Constants::USER_NOTES_KEYS_COLUMNS] = $userTotalColumns[Constants::USER_NOTES_KEYS_COLUMNS];

        $response['data'] = $data;

        $response['success'] = true;

        return $response;
    }

    public function upsertPaymentsNotesKeys($input, $userId, $merchantId): array {

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($merchant->isFeatureEnabled(Feature::CUSTOM_TXN_TAB_VIEW) === false) {
            throw new  Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $dcsConfigService = app(Constants::DCS_CONFIG_SERVICE);

        $userTotalColumns = $dcsConfigService->fetchConfiguration(DcsConfigConst::PaymentNotesKeyColumns, $userId, [DcsConfigConst::PaymentNotesKeyColumns], $this->mode);

        $requestBody = [
            Constants::PAYMENT_OPTIONAL_KEYS_COLUMNS => $input["data"][Constants::PAYMENT_OPTIONAL_KEYS_COLUMNS],
            Constants::USER_NOTES_KEYS_COLUMNS  => $input["data"][Constants::USER_NOTES_KEYS_COLUMNS],
        ];

        $jsonString = json_encode($requestBody);

        if(empty($userTotalColumns) === true or
            $userTotalColumns[DcsConfigConst::PaymentNotesKeyColumns] === "")
        {
            $dcsResponse = $dcsConfigService->createConfiguration(DcsConfigConst::PaymentNotesKeyColumns, $userId, [DcsConfigConst::PaymentNotesKeyColumns => $jsonString], $this->mode);
        }
        else
        {
            $dcsResponse =  $dcsConfigService->editConfiguration(DcsConfigConst::PaymentNotesKeyColumns, $userId, [DcsConfigConst::PaymentNotesKeyColumns => $jsonString], $this->mode);
        }

        $response['data'] = json_decode($dcsResponse[DcsConfigConst::PaymentNotesKeyColumns]);

        $response['success'] = true;

        return $response;
    }
}
