<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::FILE => 'required|file|mimes:xlsx|max:1024',
        Entity::TYPE => 'required|string|max:100|custom'
    );

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TYPE);
        }
    }

    public function validateEntries($entries, $type)
    {
        $totalEntries = count($entries);

        if ($totalEntries > 1000)
        {
           throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT);
        }

        $validator = 'validate' .ucfirst($type) .'Entries';

        $this->$validator($entries);
    }

    protected function validateRefundEntries($entries)
    {
        $existingPaymentIds = array();

        foreach ($entries as $entry)
        {
            $amount = $entry[Header::AMOUNT];
            $paymentId = $entry[Header::PAYMENT_ID];

            if (empty($paymentId) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_PAYMENT_ID);
            }

            if (empty($amount) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT);
            }

            if ((is_numeric($amount) === false) or ($amount <= 0))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT);
            }

            // Batch File should not contain multiple entries for the same payment id
            if(in_array($paymentId, $existingPaymentIds))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_DUPLICATE_PAYMENT_ID);
            }

            array_push($existingPaymentIds, $paymentId);
        }
    }
}
