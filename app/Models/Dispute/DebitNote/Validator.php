<?php

namespace RZP\Models\Dispute\DebitNote;

use RZP\Base;
use RZP\Models\Dispute;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Dispute\DebitNote\Detail;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $batchRules = [
        Constants::BATCH_MERCHANT_ID     => 'required|string|size:14',
        Constants::BATCH_PAYMENT_IDS     => 'required|string',
        Constants::BATCH_SKIP_VALIDATION => 'required|in:0',
    ];

    protected static $createRules = [
        Entity::MERCHANT_ID        => 'required|string|size:14|exists:merchants,id',
        Constants::PAYMENT_IDS     => 'required|array',
        Constants::SKIP_VALIDATION => 'required|boolean',
        Entity::BASE_AMOUNT        => 'required|min:0',
        Entity::ADMIN_ID           => 'required|string|size:14|exists:admins,id',
    ];

    protected static $createValidators = [
        'payment_relevant_status_for_debit_note_create',
        'is_not_duplicate',
    ];


    protected function validatePaymentRelevantStatusForDebitNoteCreate(array $input)
    {
        $paymentIds = $input[Constants::PAYMENT_IDS];

        $disputeRepo = (new Dispute\Repository);

        $balance = (new Merchant\Service)->getPrimaryBalance();

        foreach ($paymentIds as $paymentId)
        {
            $dispute = $disputeRepo->fetch([
                Dispute\Entity::STATUS          => Dispute\Status::LOST,
                Dispute\Entity::INTERNAL_STATUS => Dispute\InternalStatus::LOST_MERCHANT_NOT_DEBITED,
                Dispute\Entity::PAYMENT_ID      => $paymentId,
            ], $input[Entity::MERCHANT_ID]);

            if ($dispute->isEmpty() === true)
            {
                throw new BadRequestValidationFailureException("{$paymentId} in input not in relevant status to create debit note");
            }

            $this->validateBalanceInsufficientForDispute($dispute->firstOrFail(), $balance);
        }
    }

    protected function validateIsNotDuplicate(array $input)
    {

        $disputeRepo = (new Dispute\Repository);

        Payment\Entity::verifyIdAndSilentlyStripSignMultiple($input[Constants::PAYMENT_IDS]);

        $disputeIds = $disputeRepo->newQueryWithoutTimestamps()
            ->whereIn(Dispute\Entity::PAYMENT_ID, $input[Constants::PAYMENT_IDS])
            ->where(Entity::MERCHANT_ID, $input[Entity::MERCHANT_ID])
            ->get()
            ->pluck(Dispute\Entity::ID);

        $debitNoteIds = (new Detail\Repository)->newQueryWithoutTimestamps()
            ->where(Detail\Entity::DETAIL_TYPE, Detail\Type::DISPUTE)
            ->whereIn(Detail\Entity::DETAIL_ID, $disputeIds)
            ->get();

        if ($debitNoteIds->count() === 0)
        {
            return;
        }

        throw new BadRequestValidationFailureException('One of the payments already has a debit note against it');
    }

    protected function validateBalanceInsufficientForDispute(Dispute\Entity $dispute, $balance)
    {
        if ($balance[Merchant\Balance\Entity::BALANCE] <= $dispute->getBaseAmount())
        {
            return;
        }

        throw new BadRequestValidationFailureException("{$dispute->getPaymentId()} can directly be debited from merchant balance");
    }


}