<?php

namespace RZP\Models\Transaction;

use RZP\Base;
use RZP\Models\Settlement\Channel;

class Validator extends Base\Validator
{
    protected static $uniqueEntityIdRules = array(
        Entity::ENTITY_ID       => 'required|alpha_num|size:14|unique:transactions');

    protected static $updateRules = [
        Entity::CHANNEL         => 'sometimes|string|max:32|custom',
        Entity::SETTLED_AT      => 'sometimes|array',
        'merchant_ids'          => 'required|array',
        'merchant_ids.*'        => 'required|alpha_dash|max:20',
        'transaction_ids'       => 'sometimes|array',
        'transaction_ids.*'     => 'required_with:transaction_ids|alpha_dash|max:20',
        'old_settled_at.start'  => 'required_with:old_settled_at|epoch|date_format:U|before:old_settled_at.end',
        'old_settled_at.end'    => 'required_with:old_settled_at|epoch|date_format:U|after:old_settled_at:start',
        'settled_at'        => 'sometimes|epoch|date_format:U',
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
