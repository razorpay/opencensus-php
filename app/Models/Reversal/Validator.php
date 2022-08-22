<?php

namespace RZP\Models\Reversal;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Transfer;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Models\Feature\Constants as Feature;

class Validator extends Base\Validator
{
    const PAYOUT_SERVICE_REVERSAL_CREATE = 'payout_service_reversal_create';

    const REVERSE_CREDITS_VIA_PAYOUT_SERVICE = 'reverse_credits_via_payout_service';

    protected static $createRules = [
        Entity::AMOUNT               => 'required|integer|min:0',
        Entity::FEE                  => 'sometimes|integer|min:0',
        Entity::TAX                  => 'sometimes|integer|min:0',
        Entity::CHANNEL              => 'sometimes|string|max:30',
        Entity::CURRENCY             => 'required|string|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::LINKED_ACCOUNT_NOTES => 'sometimes|array',
        Entity::REFUND_TO_CUSTOMER   => 'sometimes|boolean',
        Entity::UTR                  => 'sometimes|nullable|string',
    ];

    protected static $payoutServiceReversalCreateRules = [
        Entity::ID                   => 'required|string|size:14',
        Entity::PAYOUT_ID            => 'required|string|size:14',
    ];

    protected static $reverseCreditsViaPayoutServiceRules = [
        Entity::REVERSAL_ID            => 'string|size:14|required_if:entity_type,reversal',
        Entity::ENTITY_TYPE            => 'required|string|in:payout,reversal',
        Entity::PAYOUT_ID              => 'required|string|size:14',
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::BALANCE_ID             => 'required|string|size:14',
        Entity::FEE_TYPE               => 'present|string',
    ];

    public function validateReversalAmount(Transfer\Entity $transfer, array $input)
    {
        if (isset($input['amount']) === false)
        {
            return;
        }

        $amount = $input['amount'];

        if (empty($amount) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount cannot be blank',
                'amount');
        }

        if ((ctype_digit($amount) === false) and
            (is_int($amount) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount should be in paise and only have digits',
                Entity::AMOUNT);
        }

        $transferAmount = $transfer->getAmount();

        if ($amount > $transferAmount)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_TRANSFERRED,
                'amount',
                ['transfer_id' => $transfer->getId()]);
        }

        if ($amount > $transfer->getAmountUnreversed())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_AMOUNT_GREATER_THAN_UNREVERSED,
                'amount',
                ['transfer_id' => $transfer->getId()]);
        }
    }

    public function validateInitiatorForReversal(Transfer\Entity $transfer, Merchant\Entity $initiator)
    {
        if ($initiator->isLinkedAccount() === true)
        {
            if (($transfer->getSourceType() !== E::PAYMENT) and ($transfer->getSourceType() !== E::ORDER))
            {
                throw new Exception\LogicException(
                    'Refund to customer attempted by Linked Account ' . $initiator->getId() . ' on invalid transfer source_type - ' . $transfer->getSourceType(),
                    null,
                    $transfer
                );
            }

            // check if LA has the required feature to perform reversals + customer refunds
            $allowReversals = $initiator->isFeatureEnabled(Feature::ALLOW_REVERSALS_FROM_LA);

            if ($allowReversals === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_LA_TRANSFER_REVERSAL_PERMISSION_MISSING,
                    null,
                    [
                        'transfer_id' => $transfer->getId()
                    ]);
            }
        }
    }
}
