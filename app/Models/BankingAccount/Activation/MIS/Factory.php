<?php


namespace RZP\Models\BankingAccount\Activation\MIS;


use RZP\Exception\BadRequestValidationFailureException;

class Factory
{
    // Types
    const LEADS = 'leads';
    const EXTERNAL_COMMENTS = 'external_comments';

    public static function getProcessor(string $misType, array $input)
    {
        switch ($misType)
        {
            case self::LEADS:
                return new Leads($input);
                break;
            case self::EXTERNAL_COMMENTS:
                return new ExternalComments($input);
                break;
        }

        throw new BadRequestValidationFailureException("Invalid MIS Type ". $misType);
    }
}
