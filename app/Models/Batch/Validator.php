<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::FILE => 'requried|file',
        Entity::TYPE => 'required|string|max:100|custom'
    );

    protected function validateType($attribute, $type)
    {
        if (Batch\Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid batch type');
        }
    }

    protected function validateEntries($entries)
    {
        $totalEntries = count($entries);

        if ($totalEntries > 1000)
        {
           throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_EXCEED_LIMIT);
        }

        // $validator = 'validate' .ucfirst($this->entity->getType());

        // $this->$validator($entries);
    }

    protected function validateRefund($entries)
    {
        $headers = array('payment_id', 'refund_amount');

        foreach ($entries as $entry)
        {
            $entryMap = array_combine($headers, $entry);

            $amount = $entryMap['refund_amount'];
            $paymentId = $entryMap['payment_id'];

            if (!isset($paymentId))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }
            elseif (!isset($amount))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }
        }
    }
}
