<?php

namespace RZP\Models\State\Reason;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Models\State\Entity as StateEntity;
use RZP\Models\Merchant\Detail\RejectionReasons as MerchantDetailRejectionReasons;
use RZP\Models\Merchant\Request\RejectionReasons as MerchantRequestRejectionReasons;

class Validator extends Base\Validator
{
    const INVALID_REASON_TYPE_MESSAGE          = 'Invalid reason type';
    const INVALID_REASON_CATEGORY_MESSAGE      = 'Invalid reason category';
    const INVALID_REASON_CODE_MESSAGE          = 'Invalid reason code';
    const INVALID_REASON_CATEGORY_CODE_MESSAGE = 'Invalid reason code for given reason category';

    protected static $createRules = [
        Entity::REASON_TYPE     => 'required|string|max:255',
        Entity::REASON_CATEGORY => 'required|string|max:255',
        Entity::REASON_CODE     => 'required|string|max:255',
    ];

    protected static $createValidators = [
        Entity::REASON_TYPE,
    ];

    public function validateReasonType(array $input)
    {
        $reasonType = $input[Entity::REASON_TYPE];

        if (in_array($reasonType, ReasonType::ALLOWED_REASON_TYPES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REASON_TYPE_MESSAGE);
        }
    }

    public function checkValidRejectionReason(array $input, StateEntity $state)
    {
        $reasonType = $input[Entity::REASON_TYPE];

        if ($reasonType !== ReasonType::REJECTION)
        {
            return;
        }

        $rejectionReasonsMapping = MerchantDetailRejectionReasons::REJECTION_REASONS_MAPPING;

        if ($state->entity->getEntity() === E::MERCHANT_REQUEST)
        {
            $rejectionReasonsMapping = MerchantRequestRejectionReasons::REJECTION_REASONS_MAPPING;
        }

        $allowedRejectionReasonCategories = array_keys($rejectionReasonsMapping);

        $rejectionReasonCategory = $input[Entity::REASON_CATEGORY];

        $rejectionReasonCode = $input[Entity::REASON_CODE];

        if (in_array($rejectionReasonCategory, $allowedRejectionReasonCategories, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REASON_CATEGORY_MESSAGE);
        }

        $allowedRejectionReasonCodes = array_keys(MerchantDetailRejectionReasons::REASON_CODES_DESCRIPTIONS_MAPPING);

        if ($state->entity->getEntity() === E::MERCHANT_REQUEST)
        {
            $allowedRejectionReasonCodes = array_keys
            (MerchantRequestRejectionReasons::REASON_CODES_DESCRIPTIONS_MAPPING);
        }

        if (in_array($rejectionReasonCode, $allowedRejectionReasonCodes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REASON_CODE_MESSAGE);
        }

        $rejectionReasonCategoryCodes = $rejectionReasonsMapping[$rejectionReasonCategory];

        $validRejectionReasonCodesForCategory = [];

        foreach ($rejectionReasonCategoryCodes as $rejectionReasonCategoryCode)
        {
            $validRejectionReasonCodesForCategory[] =
                $rejectionReasonCategoryCode[MerchantDetailRejectionReasons::CODE];
        }

        if (in_array($rejectionReasonCode, $validRejectionReasonCodesForCategory, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REASON_CATEGORY_CODE_MESSAGE);
        }
    }
}
