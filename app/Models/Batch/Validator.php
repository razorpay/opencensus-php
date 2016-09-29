<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const maxImageSize = 1024*1024;
    protected static $createRules = array(
        Entity::FILE => 'required|file',
        Entity::TYPE => 'required|string|max:100|custom'
    );

    const extensionMimeMap = array(
        "xlsx"  => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    );

    public function validateExtension($mimeType, $extension)
    {
        $acceptedMimeArray = self::extensionMimeMap;

        // Checks if extension is defined in the array and if the extension and mime type match.
        if ((!isset($acceptedMimeArray[$extension])) or
            ($acceptedMimeArray[$extension] !== $mimeType))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_NOT_EXCEL);
        }
    }

    public function validateSize($file)
    {
        if ($file->getClientSize() > self::maxImageSize)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_TOO_BIG);
        }
    }

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

        $validator = 'validate' .ucfirst($type) .'Entries';

        $this->$validator($entries);
    }

    protected function validateRefundEntries($entries)
    {
        $existingPaymentIds = array();

        foreach ($entries as $entry)
        {
            $amount = $entry['amount'];
            $paymentId = $entry['payment_id'];

            if (empty($paymentId) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }

            if (empty($amount) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FILE_VALIDATION);
            }

            if ((is_numeric($amount) === false) or ($amount <= 0))
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
