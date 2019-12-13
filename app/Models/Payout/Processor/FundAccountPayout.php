<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
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
        if ($fundAccount->getSourceType() !== Contact\Entity::CONTACT)
        {
            throw new BadRequestValidationFailureException(
                'Payouts cannot be created for fund account without contact.',
                Payout\Entity::FUND_ACCOUNT_ID,
                [
                    'fund_account_id' => $fundAccount->getId()
                ]);
        }
    }

    protected function runEntityValidations(Payout\Entity $payout, array $input)
    {
        /** @var Payout\Validator $validator */
        $validator = $payout->getValidator();

        $validator->validateFundAccountMode($input);
    }

    protected function fireEventForPayoutStatus(Payout\Entity $payout)
    {
        if ($payout->isStatusQueued() === true)
        {
            $this->app->events->fire('api.payout.queued', [$payout]);
        }
        else if ($payout->isStatusPending() === true)
        {
            // TODO:: Add pending webhook trigger here
        }
        else
        {
            $shouldFirePayoutCreatedWebhook = $this->shouldFirePayoutCreatedWebhook($payout);

            // TODO: Remove this after a week or two. JIRA: https://razorpay.atlassian.net/browse/RX-853
            if ($shouldFirePayoutCreatedWebhook === true)
            {
                $this->app->events->fire('api.payout.created', [$payout]);
            }

            $this->app->events->fire('api.payout.initiated', [$payout]);
        }
    }

    protected function shouldFirePayoutCreatedWebhook(Payout\Entity $payout)
    {
        $variant = $this->app->razorx->getTreatment($payout->getMerchantId(),
                                                    Merchant\RazorxTreatment::PAYOUTS_CREATED_WEBHOOK,
                                                    $this->mode);

        return (strtolower($variant) === 'on');
    }
}
