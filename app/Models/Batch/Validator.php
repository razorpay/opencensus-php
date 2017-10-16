<?php

namespace RZP\Models\Batch;

use RZP\Base;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Feature\Constants as Feature;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FILE                 => 'required|file|max:1024|mime_types:'
                                        . 'application/zip,'
                                        . 'application/vnd.ms-excel,'
                                        . 'application/vnd.oasis.opendocument.spreadsheet,'
                                        . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                                        . 'application/octet-stream,'
                                        . 'text/csv,'
                                        . 'text/plain',
        Entity::TYPE                 => 'required|string|max:25|custom',
        Entity::MERCHANT_ID          => 'sometimes|string',

        //
        // Type:payment_link specific input parameters
        // With current approach extra input would be ignored
        // but it's fine as this is proxy route.
        //

        // @todo:  We should enhance it to do per type input validations later.

        Invoice\Entity::DRAFT        => 'filled|in:0,1',
        Invoice\Entity::SMS_NOTIFY   => 'filled|in:0,1',
        Invoice\Entity::EMAIL_NOTIFY => 'filled|in:0,1',
    ];

    protected function validateType(string $attribute, string $type)
    {
        if (Type::exists($type) === false)
        {
            throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TYPE,
                        Entity::TYPE,
                        [
                            Entity::TYPE => $type,
                        ]);
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
                        $this->entity->toArray());
        }
    }

    /**
     * Validates entries(array) of batch input file before
     * creating the batch entity.
     *
     * @param array           $entries
     * @param array           $params
     * @param Merchant\Entity $merchant
     *
     * @throws BadRequestException
     */
    public function validateEntries(
        array & $entries,
        array $params,
        Merchant\Entity $merchant)
    {
        $type = $this->entity->getType();

        Limit::validate($type, count($entries));

        Header::validate($type, array_keys(current($entries)));

        // Calls validate method of corresponding type.

        $validator = 'validate' . studly_case($type) .'Entries';

        $this->$validator($entries, $params, $merchant);
    }

    protected function validateRefundEntries(
        array & $entries,
        array $params,
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
     *
     * @throws BadRequestException
     */
    protected function validatePaymentLinkEntries(
        array & $entries,
        array $params,
        Merchant\Entity $merchant)
    {
        // Associative array with index as input file's row index and values
        // as the error message.

        $errors = [];
        $errorEntries = [];

        foreach ($entries as $idx => $entry)
        {
            $input = Helpers\PaymentLink::getEntityInput($entry, $params);

            // Need to create dummy entity and associate merchant
            // for the validation around max allowed payment to happen.

            $rule = Invoice\Validator::CREATE_DRAFT;

            if ($input[Invoice\Entity::DRAFT] === '0')
            {
                $rule = Invoice\Validator::CREATE_ISSUED;
            }

            $invoice = new Invoice\Entity;

            $invoice->merchant()->associate($merchant);

            try
            {
                $invoice->getValidator()->validateInput($rule, $input);
            }
            catch (BaseException $e)
            {
                $idx++;

                $errors[$idx]       = $e->getError()->getDescription();
                $errorEntries[$idx] = $entry;
            }
            finally
            {
                unset($invoice);
            }
        }

        $errorsCount = count($errors);

        if ($errorsCount > 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_PAYMENT_LINK_FILE_ERRORS,
                Entity::FILE,
                [
                    'count'         => $errorsCount,
                    'errors'        => $errors,
                    'error_entries' => $errorEntries,
                    'merchant_id'   => $merchant->getId(),
                ]);
        }
    }

    protected function validateVirtualBankAccountEntries(array & $entries, array $params, Merchant\Entity $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature::VIRTUAL_ACCOUNTS) === false)
        {
            throw new BadRequestValidationFailureException(
                'Virtual accounts is not enabled for merchant',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }
    }

    protected function validateIrctcRefundEntries(array & $entries, array $params, Merchant\Entity $merchant)
    {

    }

    protected function validateIrctcSettlementEntries(array & $entries, array $params, Merchant\Entity $merchant)
    {

    }

    protected function validateLinkedAccountEntries(array & $entries, array $params, Merchant\Entity $merchant)
    {
        //
        // Batch creation for linked account should only be allowed for
        // marketplace merchant accounts.
        //
        if ($merchant->isMarketplace() === false)
        {
            throw new BadRequestValidationFailureException(
                'Linked account creation not allowed for merchant',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        //
        // TODO:
        // - Probably should rename these methods to validate<BatchType>Input() as
        //   it now does more than validating just the input entries.
        //
    }
}
