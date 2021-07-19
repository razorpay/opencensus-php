<?php

namespace RZP\Models\Transaction;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Settlement\Channel;

class Validator extends Base\Validator
{
    protected static $uniqueEntityIdRules = array(
        Entity::ENTITY_ID       => 'required|alpha_num|size:14|unique:transactions');

    protected static $bulkUpdateRules = [
        Entity::CHANNEL         => 'required|string|max:32|custom',
        'merchant_ids'          => 'required|array',
        'merchant_ids.*'        => 'required|alpha_dash|max:20',
        'old_settled_at'        => 'sometimes|array',
        'old_settled_at.start'  => 'required_with:old_settled_at|epoch|date_format:U|before:old_settled_at.end',
        'old_settled_at.end'    => 'required_with:old_settled_at|epoch|date_format:U|after:old_settled_at.start',
    ];

    protected static $unsettledTxnsChannelUpdateRules = [
        Entity::CHANNEL         => 'required|string|max:32|custom',
        'merchant_id'           => 'required|string'
    ];

    protected static $toggleTransactionHoldRules = [
        'transaction_ids'     =>  'required|array',
        'transaction_ids.*'   =>  'required|alpha_num|size:14',
        'reason'              =>  'required|alpha_dash_space',
    ];

    protected static $toggleTransactionReleaseRules = [
        'transaction_ids'     => 'required|array',
        'transaction_ids.*'   => 'required|alpha_num|size:14',
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
