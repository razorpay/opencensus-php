<?php


namespace RZP\Models\BankingAccount\Activation\MIS;


use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Repository;
use RZP\Exception\BadRequestValidationFailureException;

class Factory
{
    // Types
    const LEADS             = 'leads';
    const LEADS_REPORT      = 'leads_report';
    const EXTERNAL_COMMENTS = 'external_comments';

    public static function getProcessor(string $misType, array $input, string $entity = 'banking_account')
    {
        switch ($misType)
        {
            case self::LEADS:
                return new Leads($input, $entity);

            case self::LEADS_REPORT:
                return new LeadsReport($input, $entity);

            case self::EXTERNAL_COMMENTS:
                return new ExternalComments($input);

        }

        throw new BadRequestValidationFailureException("Invalid MIS Type ". $misType);
    }
}
