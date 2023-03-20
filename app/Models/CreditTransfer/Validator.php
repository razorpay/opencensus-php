<?php


namespace RZP\Models\CreditTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT             => 'required|integer',
        Entity::CHANNEL            => 'required|string|max:32|in:rzpx',
        Entity::CURRENCY           => 'required|in:INR',
        Entity::DESCRIPTION        => 'sometimes|nullable|string',
        Entity::MODE               => 'required|string|max:32|in:IFT',
        Entity::ENTITY_ID          => 'nullable|alpha_num|size:14',
        Entity::ENTITY_TYPE        => 'nullable|in:payout',
        Entity::TRANSACTION_ID     => 'sometimes|alpha_num|size:14',
        Entity::PAYER_ACCOUNT      => 'nullable|string',
        Entity::PAYER_MERCHANT_ID  => 'sometimes|nullable|string|size:14',
        Entity::PAYER_NAME         => 'nullable|string',
        Entity::PAYER_IFSC         => 'nullable|string',
        Entity::PAYEE_ACCOUNT_ID   => 'nullable|alpha_num|size:14',
        Entity::PAYEE_ACCOUNT_TYPE => 'nullable|string',
        Entity::FAILED_AT          => 'sometimes|integer',
        Entity::PROCESSED_AT       => 'sometimes|integer'
    ];

    protected static $createInputRules = [
        Entity::AMOUNT                => 'required|integer',
        Entity::CHANNEL               => 'required|string|max:32|in:rzpx',
        Entity::CURRENCY              => 'required|in:INR',
        Entity::DESCRIPTION           => 'sometimes|nullable|string',
        Entity::MODE                  => 'required|string|max:32|in:IFT',
        Constants::SOURCE_ENTITY_ID   => 'required|alpha_num|size:14',
        Constants::SOURCE_ENTITY_TYPE => 'required|in:payout',
        Entity::PAYER_ACCOUNT         => 'nullable|string',
        Entity::PAYER_NAME            => 'nullable|string',
        Entity::PAYER_IFSC            => 'nullable|string',
        Constants::PAYEE_DETAILS      => 'required|array',
        Entity::PAYEE_ACCOUNT_TYPE    => 'required|string'
    ];

    public function validateCreditTransferForFailure()
    {
        /** @var Entity $creditTransfer */
        $creditTransfer = $this->entity;

        if (($creditTransfer->isStatusProcessed() === true) or
            ($creditTransfer->getTransactionId() !== null) or
            ($creditTransfer->getUtr() !== null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CREDIT_TRANSFER_ALREADY_PROCESSED,
                null,
                [
                    'credit_transfer_id'     => $creditTransfer->getId(),
                    'credit_transfer_status' => $creditTransfer->getStatus()
                ]);
        }
    }
}
