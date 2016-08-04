<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;
use RZP\Models\Merchant\Credits;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::CAMPAIGN                    => 'required|alpha_dash|max:255',
        Entity::VALUE                     => 'required|integer',
        Entity::MERCHANT_ID                 => 'sometimes|alpha_num',
        Entity::NOTES                       => 'sometimes|notes',
    );

    protected static $editRules = array(
        Entity::VALUE                     => 'sometimes|integer',
    );

    public static function validateCreditsForDeduction($creditsLog, $balance, $credits)
    {
        if ($balance->getCredits() < $credits)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Credits to deduct is more than total credits available');
        }
        else if ($creditsLog->getValue() < $credits)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Credits to deduct is more than credits assigned to merchant in campaign');
        }
    }

    public static function validateNewCreditLog($campaign, $merchant)
    {
        // Check if the log already exists, API is meant to use for creation only.
        $creditsLogExists = (new Credits\Core)->checkIfCreditsLogExists(
            $merchant, $campaign);
        if ($creditsLogExists)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The record already exists for given campaign and merchant.');
        }
    }
}
