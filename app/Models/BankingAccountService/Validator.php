<?php

namespace RZP\Models\BankingAccountService;

use RZP\Base;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class Validator extends Base\Validator
{
    const BVS_INITIATE_VALIDATION = 'bvs_initiate_validation';

    const SUPPORTED_ARTEFACT_TYPE = [
      Constant::BUSINESS_PAN,
      Constant::PERSONAL_PAN
    ];

    protected static $createRules = [
        Constants::CHANNEL        => 'required|string|custom',
        Constants::ACCOUNT_NUMBER => 'required|string',
    ];

    protected static $businessRules = [
        Constants::BUSINESS_ID => 'required|string|max:14',
    ];

    protected static $bvsInitiateValidationRules = [
        Constant::ARTEFACT_TYPE     => 'required|string|custom:artefactType',
        Constant::OWNER_TYPE        => 'required|string|in:bas_document',
        Constant::OWNER_ID          => 'required|string',
        Constant::DETAILS           => 'required|array',
    ];

    protected function validateArtefactType($attribute, $artefactType): bool
    {
        return in_array($artefactType, self::SUPPORTED_ARTEFACT_TYPE);
    }

    protected function validateChannel($attribute, $channel)
    {
        Channel::isValidDirectTypeChannel($channel);
    }
}
