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

    protected static $createRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

    protected static $editRules = [
        Entity::ACCOUNT_NUMBER       => 'sometimes|string|max:40',
        Entity::ACCOUNT_IFSC         => 'sometimes|string|size:11',
        Entity::BANK_INTERNAL_STATUS => 'sometimes|string',
        Entity::STATUS               => 'sometimes|string|in:created,initiated,processing,processed,cancelled,unserviceable'
    ];

    protected static $rblUpdateRules = [
      Entity::ACCOUNT_NUMBER        => 'required_with:account_ifsc|max:40',
      Entity::ACCOUNT_IFSC          => 'required_with:account_number|size:11',
      Entity::STATUS                => 'sometimes|string|custom',
      Entity::BANK_INTERNAL_STATUS  => 'required_if:status,processing,processed,cancelled|string',
    ];

    // this validates the RZP status
    protected function validateStatus(string $attribute, string $status)
    {
        if (Status::isValidStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Razorpay Status is invalid',
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }
}
