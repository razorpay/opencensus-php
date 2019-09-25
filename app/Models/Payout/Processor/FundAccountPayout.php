<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\Contact\Entity;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Exception\BadRequestValidationFailureException;

class FundAccountPayout extends Base
{
    /**
     * {@inheritDoc}
     * After payout creation dispatches transaction.created event.
     */
    public function createPayout(array $input): Payout\Entity
    {
        $payout = parent::createPayout($input);

        //
        // In case of payouts with status=(queued, payouts), we don't create the transaction yet.
        // This event will be dispatched later when we are actually processing the payout.
        //
        if ($payout->isStatusBeforeCreate() === false)
        {
            //
            // Ideally, this should be done as part of downstream processor,
            // but we do it here since, we do not want to dispatch this even if
            // payout creation flow fails for any reason after downstream processor runs.
            //

            if (($payout->isStatusBeforeCreate() === false) and
                ($payout->balance->getAccountType() !== AccountType::DIRECT))
            {
                (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
            }
        }

        return $payout;
    }

    /**
     * @param FundAccount\Entity $fundAccount
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateFundAccountContact(FundAccount\Entity $fundAccount)
    {
        if ($fundAccount->getSourceType() !== Entity::CONTACT)
        {
            throw new BadRequestValidationFailureException(
                'Payouts cannot be created for fund account without contact.',
                Payout\Entity::FUND_ACCOUNT_ID,
                [
                    'fund_account_id' => $fundAccount->getId()
                ]);
        }
    }
}
