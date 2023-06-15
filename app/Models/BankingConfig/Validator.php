<?php

namespace RZP\Models\BankingConfig;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\Org;


class Validator extends Base\Validator
{
    protected static $upsertRules = [
        Constants::KEY => 'required|string',
        Constants::FIELD_NAME => 'required|string',
        Constants::SHORT_KEY => 'required|string',
        Constants::FIELD_VALUE => 'required',
        Constants::ENTITY_ID => 'required|alpha_num|size:14',
    ];

    protected static $getRules = [
        Constants::KEY => 'required|string',
        Constants::SHORT_KEY => 'required|string',
        Constants::FIELDS => 'required|array',
        Constants::ENTITY_ID => 'required|alpha_num|size:14',
    ];

    public function isAdminAuthorized($sessionOrgId, $entityOrgId)
    {
        // RZP org should be able to update any entity
        if ($sessionOrgId === Constants::RZP_ORG)
        {
            return;
        }

        // other orgs can only update entities belonging to their orgs
        if ($sessionOrgId !== $entityOrgId)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }

    public function validateConfigOwnership($key, $fieldName)
    {
        $bankingConfigs = Constants::BANKING_CONFIGS;

        if (isset($bankingConfigs[$key]) === false)
        {
            throw new  Exception\BadRequestValidationFailureException(
                'key '. $key. ' isn\'t owned by banking');
        }

         if (isset($bankingConfigs[$key][$fieldName]) === false)
        {
            throw new  Exception\BadRequestValidationFailureException(
                'field '. $fieldName. ' isn\'t owned by banking');
        }
    }
}
