<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $preCreateRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
    ];

    protected static $rblAvailabilityRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

    protected static $yesbankCreateRules = [
        Entity::ACCOUNT_NUMBER      => 'required|string|max:40',
        Entity::ACCOUNT_IFSC        => 'required|string|size:11',
        Entity::FTS_FUND_ACCOUNT_ID => 'sometimes|nullable|string|size:14',
    ];

    protected static $createRules = [
        Entity::CHANNEL             => 'required|string|in:rbl,yesbank',
        Entity::PINCODE             => 'required_if:channel,rbl',
        Entity::ACCOUNT_NUMBER      => 'sometimes|nullable|string|max:40',
        Entity::ACCOUNT_IFSC        => 'sometimes|nullable|string|size:11',
        Entity::BALANCE_ID          => 'sometimes|nullable|string|size:14',
        Entity::FTS_FUND_ACCOUNT_ID => 'sometimes|nullable|string|size:14',
    ];

    protected static $editRules = [
        Entity::ACCOUNT_NUMBER       => 'filled|string|max:40',
        Entity::ACCOUNT_IFSC         => 'filled|string|size:11',
        Entity::BANK_INTERNAL_STATUS => 'filled|string',
        Entity::STATUS               => 'filled|string|custom',
    ];

    protected static $rblUpdateRules = [
        Entity::ACCOUNT_NUMBER       => 'required_with:account_ifsc|max:40',
        Entity::ACCOUNT_IFSC         => 'required_with:account_number|size:11',
        Entity::STATUS               => 'filled|string|custom',
        Entity::BANK_INTERNAL_STATUS => 'required_if:status,processing,processed,cancelled|string',
    ];

    /**
     * @param string $attribute
     * @param string $status
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateStatus(string $attribute, string $status)
    {
        if (Status::isValidStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Banking account status is invalid',
                Entity::STATUS,
                [Entity::STATUS => $status]);
        }
    }
}
