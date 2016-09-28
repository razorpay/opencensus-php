<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::FILE => 'required|file',
        Entity::TYPE => 'required|string|max:100|custom'
    );

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid batch type');
        }
    }

    public function validateEntries($entries, $type)
    {
        $totalEntries = count($entries);

        if ($totalEntries > 1000)
        {
           throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_EXCEED_LIMIT);
        }

        $validator = 'validate' . ucfirst($type);

        $this->$validator($entries);
    }

    protected function validateRefund($entries)
    {
        $headers = array('payment_id', 'refund_amount');

        // Skipping the first row: This would be templatized headers
        $headerValues = $entries[0];
        $headerMap = array_combine($headers, $headerValues);

        if($headerMap['payment_id'] !== 'Payment Id' ||
            $headerMap['refund_amount'] !== 'Amount')
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
        }

        array_shift($entries);

        $existingPaymentIds = array();

        foreach ($entries as $entry)
        {
            if (count($headers) !== count($entry))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }

            $entryMap = array_combine($headers, $entry);

            $amount = $entryMap['refund_amount'];
            $paymentId = $entryMap['payment_id'];

            if (isset($paymentId) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }
            elseif (isset($amount) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }

            // Batch File should not contain multiple entries for the same payment id
            if(in_array($paymentId, $existingPaymentIds))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }

            array_push($existingPaymentIds, $paymentId);
        }
    }
}
