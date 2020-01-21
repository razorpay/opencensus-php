<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Admin\Service as AdminService;
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

    /**
     * run entity validations
     *
     * @param Payout\Entity $payout
     * @param array         $input
     *
     * @throws BadRequestException
     */
    protected function runEntityValidations(Payout\Entity $payout, array $input)
    {
        /** @var Payout\Validator $validator */
        $validator = $payout->getValidator();

        $validator->validateFundAccountMode($input);

        $this->validateModeChannelAndDestinationType($payout);
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

    public function getAccountTypeForFundTransfer(Payout\Entity $payout)
    {
        return $payout->balance->getAccountType() ?? AccountType::SHARED;
    }

    /**
     * TODO: Currently there is no proper way to decide the channel through
     * which the payout should be routed in case of shared accounts.
     * Till the time we achieve this by Dynamic routing, we are doing
     * a hack of using config key to store the MIDs for which
     * channel for processing the payout should be CITI and ICICI.
     * The precedence between ICICI and CITI is ICICI.
     *
     * @param $accountType
     * @return string
     */
    protected function getChannelForFundTransfer($accountType, Payout\Entity $payout): string
    {
        if ($accountType === AccountType::DIRECT)
        {
            return $this->getChannelForDirectAccountFundTransfer($payout);
        }

        return $this->getChannelForSharedAccountFundTransfer($payout);
    }

    protected function getChannelForDirectAccountFundTransfer(Payout\Entity $payout)
    {
        return $payout->balance->getChannel();
    }

    protected function getChannelForSharedAccountFundTransfer(Payout\Entity $payout)
    {
        $merchant = $payout->merchant;

        if ($this->checkIfChannelShouldBeIcici($merchant) === true)
        {
            return Channel::ICICI;
        }

        if ($this->checkIfChannelShouldBeCiti($merchant) === true)
        {
            return Channel::CITI;
        }

        return $payout->balance->getChannel() ?? Channel::YESBANK;
    }

    protected function checkIfChannelShouldBeIcici(Merchant\Entity $merchant): bool
    {
        $mid = $merchant->getId();

        $iciciMids = (new AdminService)->getConfigKey(['key' => ConfigKey::ICICI_CHANNEL_PAYOUT_MIDS]);

        return (in_array($mid, $iciciMids, true) === true);
    }

    protected function checkIfChannelShouldBeCiti(Merchant\Entity $merchant): bool
    {
        $mid = $merchant->getId();

        $citiMids = (new AdminService)->getConfigKey(['key' => ConfigKey::CITI_CHANNEL_PAYOUT_MIDS]);

        return (in_array($mid, $citiMids, true) === true);
    }

    protected function validateModeChannelAndDestinationType(Payout\Entity $payout)
    {
        $accountType = $this->getAccountTypeForFundTransfer($payout);

        $channel = $this->getChannelForFundTransfer($accountType,$payout);

        $payout->setChannel($channel);

        $mode = $payout->getMode();

        $destinationType = $this->fundTransferDestination->getEntity();

        $valid = Channel::validateChannelAndMode($channel, $destinationType, $mode);

        if ($valid === false)
        {
            if ($this->getAccountTypeForFundTransfer($payout) === AccountType::SHARED)
            {
                $errorMsg =  $mode . ' is not supported';
            }

            else
            {
                $errorMsg = strtoupper($channel) . ' does not support ' . $mode . ' payouts to ' . strtoupper($destinationType);
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
                null,
                [
                    'channel'           => $channel,
                    'mode'              => $mode,
                    'destination_type'  => $destinationType
                ],
                $errorMsg
            );
        }
    }
}
