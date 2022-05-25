<?php


namespace RZP\Models\CreditTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT             => 'required|integer',
        Entity::CHANNEL            => 'sometimes|string|max:32',
        Entity::CURRENCY           => 'required|in:INR',
        Entity::DESCRIPTION        => 'sometimes|nullable|string',
        Entity::MODE               => 'sometimes|string|max:32',
        Entity::ENTITY_ID          => 'required|alpha_num|size:14',
        Entity::ENTITY_TYPE        => 'required|in:payout',
        Entity::TRANSACTION_ID     => 'sometimes|alpha_num|size:14',
        Entity::PAYER_ACCOUNT      => 'nullable|string',
        Entity::PAYER_NAME         => 'nullable|string',
        Entity::PAYER_IFSC         => 'nullable|string',
        Entity::PAYEE_ACCOUNT_ID   => 'required|alpha_num|size:14',
        Entity::PAYEE_ACCOUNT_TYPE => 'required|string',
        Entity::FAILED_AT          => 'sometimes|integer',
        Entity::PROCESSED_AT       => 'sometimes|integer'
    ];

    public function validateCreditTransferForReversal()
    {
        /** @var Entity $creditTransfer */
        $creditTransfer = $this->entity;

        if ($creditTransfer->isStatusProcessed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_CREDIT_TRANSFER_ALREADY_PROCESSED,
                null,
                [
                    'credit_transfer_id'     => $creditTransfer->getId(),
                    'credit_transfer_status' => $creditTransfer->getStatus()
                ]);
        }

        if ($creditTransfer->isStatusFailed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VA_TO_VA_CREDIT_TRANSFER_ALREADY_FAILED,
                null,
                [
                    'credit_transfer_id'     => $creditTransfer->getId(),
                    'credit_transfer_status' => $creditTransfer->getStatus()
                ]);
        }
    }
}
