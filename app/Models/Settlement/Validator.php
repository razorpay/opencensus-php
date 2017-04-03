<?php

namespace RZP\Models\Settlement;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT          => 'required',
        Entity::STATUS          => 'required|in:created,failed,processed',
        Entity::FEES            => 'sometimes',
        Entity::SERVICE_TAX     => 'sometimes',
        Entity::CHANNEL         => 'required|string|custom',
        Entity::ATTEMPTS        => 'sometimes|integer|min:1',
    ];

    protected static $batchFetchRules = [
        Entity::BATCH_FUND_TRANSFER_ID => 'required|alpha_num|size:14',
    ];

    protected static $nodalTransferRules = [
        Entity::AMOUNT => 'required|integer|min:100|max:1000000000',
    ];

    protected static $retryRules = [
        'settlement_ids'   => 'required|array',
        'settlement_ids.*' => 'required|alpha_dash|max:20',
    ];

    protected function validateChannel($attribute, $value)
    {
        if (in_array($value, Channel::getChannels()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Channel: ' . $value);
        }
    }
}