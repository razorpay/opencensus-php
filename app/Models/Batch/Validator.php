<?php

namespace RZP\Models\Batch;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FILE => 'required|file|mimes:xlsx|max:1024',
        Entity::TYPE => 'required|string|max:14|custom'
    ];

    protected function validateType(string $attribute, string $type)
    {
        if (Type::exists($type) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TYPE);
        }
    }

    /**
     * Throws error if batch is already processed.
     */
    public function validateNotProcessedAlready()
    {
        if ($this->entity->getStatus() === Status::PROCESSED)
        {
            throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_ALREADY_PROCESSED,
                        Entity::STATUS,
                        $this->entity->toArrayPublic());
        }
    }

    /**
     * Validates entries(array) of batch input file before
     * creating the batch entity.
     *
     * @param array $entries
     */
    public function validateEntries(array $entries)
    {
        $type = $this->entity->getType();

        Limit::validate($type, count($entries));

        Header::validate($type, array_keys(current($entries)));

        // Calls validate method of corresponding type.

        $validator = 'validate' .ucfirst(camel_case($type)) .'Entries';

        $this->$validator($entries);
    }

    protected function validateRefundEntries(array $entries)
    {
        $existingPaymentIds = [];

        foreach ($entries as $entry)
        {
            $amount    = $entry[Header::AMOUNT];
            $paymentId = $entry[Header::PAYMENT_ID];

            if (empty($paymentId) === true)
            {
                throw new BadRequestException(
                            ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_PAYMENT_ID);
            }

            if (empty($amount) === true)
            {
                throw new BadRequestException(
                            ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT);
            }

            if ((is_numeric($amount) === false) or ($amount <= 0))
            {
                throw new BadRequestException(
                            ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT);
            }

            // Batch File should not contain multiple entries for the same
            // payment id

            if (in_array($paymentId, $existingPaymentIds))
            {
                throw new BadRequestException(
                            ErrorCode::BAD_REQUEST_BATCH_FILE_DUPLICATE_PAYMENT_ID);
            }

            $existingPaymentIds[] = $paymentId;
        }
    }

    protected function validatePaymentLinkEntries(array $entries)
    {
    }
}
