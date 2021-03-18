<?php

namespace RZP\Models\State\Reason;

use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Request;

class Constants
{
    const ENTITY_REJECTION_REASONS_MAPPING = [
        E::MERCHANT_REQUEST => Request\RejectionReasons::REJECTION_REASONS_MAPPING,
        E::MERCHANT_DETAIL  => Detail\RejectionReasons::REJECTION_REASONS_MAPPING,
        E::PARTNER_ACTIVATION  => Detail\RejectionReasons::REJECTION_REASONS_MAPPING,
    ];

    const ENTITY_REASON_CODES_DESCRIPTIONS_MAPPING = [
        E::MERCHANT_REQUEST => Request\RejectionReasons::REASON_CODES_DESCRIPTIONS_MAPPING,
        E::MERCHANT_DETAIL  => Detail\RejectionReasons::REASON_CODES_DESCRIPTIONS_MAPPING,
        E::PARTNER_ACTIVATION  => Detail\RejectionReasons::REASON_CODES_DESCRIPTIONS_MAPPING,
    ];

    public static function getValidRejectionReasonsMappingForEntity(string $entity)
    {
        if (isset(self::ENTITY_REJECTION_REASONS_MAPPING[$entity]) === true)
        {
            return self::ENTITY_REJECTION_REASONS_MAPPING[$entity];
        }

        throw new Exception\BadRequestValidationFailureException(
        Validator::INVALID_ENTITY_FOR_REJECTION_REASONS);
    }

    public static function getValidRejectionReasonsCodesDescriptionsMappingForEntity(string $entity)
    {
        if (isset(self::ENTITY_REASON_CODES_DESCRIPTIONS_MAPPING[$entity]) === true)
        {
            return self::ENTITY_REASON_CODES_DESCRIPTIONS_MAPPING[$entity];
        }

        throw new Exception\BadRequestValidationFailureException(
            Validator::INVALID_ENTITY_FOR_REJECTION_REASONS);
    }
}
