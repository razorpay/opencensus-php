<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Base;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $editRules = [
        Entity::STATUS           => 'sometimes|string|custom',
        Entity::FAILURE_REASON   => 'sometimes|string|max:100',
        Entity::REMARKS          => 'sometimes|string|max:100',
        Entity::BANK_STATUS_CODE => 'sometimes|string|max:30',
    ];

    protected static $initiateFundTransferRules = [
        Entity::PURPOSE         => 'required|filled|string|max:30|in:refund,settlement',
        Entity::SOURCE_TYPE     => 'sometimes|filled|string|max:32|in:refund,payout',
        // This will be used while generating response while mock. Only used in api based settlements
        'failed_response'       => 'sometimes|int'
    ];

    protected static $bulkReconcileRules = [
        'from' => 'required_with:to|epoch|date_format:U',
        'to'   => 'required_with:from|epoch|date_format:U',
    ];

    protected static $retryBeamFileUploadRules = [
        'file_id'           =>  'required|filled|string|alpha_num|size:14',
        Entity::CHANNEL     =>  'required|filled|string',
        Entity::FILE_TYPE   =>  'required|filled|string',
    ];

    protected function validateStatus($attribute, $value)
    {
        if (Status::isValidForBulkUpdate($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid status',
                $attribute,
                $value);
        }
    }

    /**
     * @param string $attribute
     * @param string $value
     * @throws BadRequestValidationFailureException
     */
    public function validateChannel(string $attribute, string $value)
    {
        $channels = [Channel::AXIS, Channel::ICICI];

        if (in_array($value, $channels, true) !== true)
        {
            throw new BadRequestValidationFailureException('Invalid channel value : ' . $value);
        }
    }

    /**
     * @param string $attribute
     * @param string $value
     * @throws BadRequestValidationFailureException
     */
    public function validateFileType(string $attribute, string $value)
    {
        $fileType = [Entity::BENEFICIARY, Entity::SETTLEMENT];

        if (in_array($value, $fileType, true) !== true)
        {
            throw new BadRequestValidationFailureException('Invalid file type : ' . $value);
        }
    }
}
