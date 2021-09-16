<?php


namespace RZP\Models\Dispute\Evidence;

use RZP\Base;
use RZP\Models\Dispute;
use RZP\Error\ErrorCode;
use Razorpay\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{

    protected static $createForDisputeRules = [
        Entity::AMOUNT                             => 'sometimes|integer',
        Entity::SUMMARY                            => 'sometimes|string',
        Constants::ACTION                          => 'sometimes|in:draft,submit,accept',
        Document\Types::SHIPPING_PROOF             => 'sometimes|array',
        Document\Types::BILLING_PROOF              => 'sometimes|array',
        Document\Types::CANCELLATION_PROOF         => 'sometimes|array',
        Document\Types::CUSTOMER_COMMUNICATION     => 'sometimes|array',
        Document\Types::EXPLANATION_LETTER         => 'sometimes|array',
        Document\Types::REFUND_CONFIRMATION        => 'sometimes|array',
        Document\Types::ACCESS_ACTIVITY_LOG        => 'sometimes|array',
        Document\Types::TERMS_AND_CONDITIONS       => 'sometimes|array',
        Document\Types::OTHERS                     => 'sometimes|array',
        Document\Types::REFUND_CANCELLATION_POLICY => 'sometimes|array',
        Document\Types::PROOF_OF_SERVICE           => 'sometimes|array',
    ];

    protected static $createRules = [
        Entity::DISPUTE_ID => 'required|size:14',
        Entity::SUMMARY    => 'sometimes|string',
        Entity::AMOUNT     => 'required|integer|min:0',
        Entity::CURRENCY   => 'required',
        Entity::SOURCE     => 'required',
        Constants::ACTION  => 'required',
    ];

    protected static $createValidators = [
        'empty_summary_on_accept',
    ];

    public function validateAmount(Dispute\Entity $dispute, array $createInput)
    {
        $amount = $createInput[Entity::AMOUNT];

        if ($amount <= $dispute->getAmount()) {
            return;
        }

        $exceptionData = [
            Entity::AMOUNT     => $amount,
            'dispute_amount'   => $dispute->getAmount(),
            'dispute_currency' => $dispute->getCurrency(),
        ];

        throw new BadRequestValidationFailureException('contest amount cannot be greater than dispute amount',
            Entity::AMOUNT,
            $exceptionData);
    }

    public function validateActionForDisputeStatus(Dispute\Entity $dispute, $action)
    {
        $status = $dispute->getStatus();

        if (Action::isValidActionForDisputeStatus($action, $status) === true)
        {
            return;
        }

        $exceptionData = [
            Dispute\Entity::STATUS => $status,
            Constants::ACTION      => $action,
        ];

        throw new BadRequestValidationFailureException("Action not allowed when dispute is in {$status} status.",

            Constants::ACTION,
            $exceptionData);
    }

    public function validateEmptySummaryOnAccept($input)
    {
        if ($input[Constants::ACTION] === Action::DRAFT)
        {
            return;
        }

        $summary = $input[Entity::SUMMARY] ?? '';

        if (strlen($summary) > 0)
        {
            return;
        }

        throw new BadRequestValidationFailureException('Summary cannot be empty');
    }

    public function validateDisputeHasNotExpired(Dispute\Entity $dispute)
    {
        $currentTimestamp = time();

        if ($dispute->getExpiresOn() > $currentTimestamp)
        {
            return;
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_DISPUTE_DEADLINE_ELAPSED, Dispute\Entity::EXPIRES_ON);
    }
}