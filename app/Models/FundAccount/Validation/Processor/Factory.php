<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Models\FundAccount\Entity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundAccount\Validation as FundAccountValidation;

class Factory
{
    protected static $validTypes = [
        Entity::BANK_ACCOUNT,
        Entity::VPA
    ];

    /**
     * @param FundAccountValidation\Entity $fundAccountValidation
     *
     * @return Base
     * @throws BadRequestValidationFailureException
     */
    public static function get(FundAccountValidation\Entity $fundAccountValidation): Base
    {
        $type = $fundAccountValidation->fundAccount->account->getEntityName();

        self::validate($type);

        $processor = __NAMESPACE__ . '\\' . studly_case($type);

        return new $processor($fundAccountValidation);
    }

    public static function getVpaProcessor(FundAccountValidation\Entity $fundAccountValidation): Base
    {
        // Set the processor specifically for VPA
        $processor = __NAMESPACE__ . '\\' . studly_case(Entity::VPA);
        return new $processor($fundAccountValidation);
    }

    public static function getBankAccountProcessor(FundAccountValidation\Entity $fundAccountValidation): Base
    {
        // Set the processor specifically for BankAccount
        $processor = __NAMESPACE__ . '\\' . studly_case(Entity::BANK_ACCOUNT);
        return new $processor($fundAccountValidation);
    }

    /**
     * @param string $type
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Invalid fund account type: ' . $type);
        }
    }

    protected static function isValid(string $type)
    {
        return in_array($type, self::$validTypes, true);
    }
}
