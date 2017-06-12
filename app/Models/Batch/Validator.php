<?php

namespace RZP\Models\Batch;

use RZP\Base;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
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
            throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TYPE);
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
    public function validateEntries(array $entries, Merchant\Entity $merchant)
    {
        $type = $this->entity->getType();

        Limit::validate($type, count($entries));

        Header::validate($type, array_keys(current($entries)));

        // Calls validate method of corresponding type.

        $validator = 'validate' .ucfirst(camel_case($type)) .'Entries';

        $this->$validator($entries, $merchant);
    }

    protected function validateRefundEntries(
        array & $entries,
        Merchant\Entity $merchant)
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

    /**
     * Validates payment link entries.
     * - Creates dummy invoice object and validates them as it happens
     *   otherwise in creation by API flow. This approach let us re-use code.
     *
     * @param array           $entries
     * @param Merchant\Entity $merchant
     */
    protected function validatePaymentLinkEntries(
        array & $entries,
        Merchant\Entity $merchant)
    {
        $validator = (new Invoice\Entity)->getValidator();

        // Associative array with index as input file's row index and values
        // as the error message.

        $errors = [];

        foreach ($entries as $idx => $entry)
        {
            try
            {
                $input = Helper\PaymentLink::getEntityInput($entry);

                $invoice = new Invoice\Entity;

                $invoice->merchant()->associate($merchant);

                $invoice->build($input);

                $invoice->getValidator()->validateInvoiceIssue();

                unset($invoice);
            }
            catch (BaseException $e)
            {
                $errors[$idx + 1] = $e->getError()->getDescription();
            }
        }

        if (count($errors) > 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_VALIDATION,
                Entity::FILE,
                $errors);
        }
    }
}
