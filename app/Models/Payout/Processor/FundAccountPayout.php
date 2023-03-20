<?php

namespace RZP\Models\Payout\Processor;

use Razorpay\Trace\Logger;

use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Models\Payout\Status;
use RZP\Models\Transaction;
use RZP\Models\Payout\Entity;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\Channel;
use RZP\Models\Payout\CounterHelper;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundTransfer\Mode as FundTransferMode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PayoutsStatusDetails\Core as PayoutsStatusDetailsCore;

class FundAccountPayout extends Base
{
    /**
     * {@inheritDoc}
     * After payout creation dispatches transaction.created event.
     */
    public function createPayout(array $input): Payout\Entity
    {
        if (isset($input[Balance\Entity::BALANCE_ID]))
        {
            /** @var Balance\Entity $balance */
            $balance = $this->repo->balance->findOrFailById($input[Balance\Entity::BALANCE_ID]);

            if ($balance->getType() === Balance\Type::BANKING)
            {
                $payout = $this->createBankingPayout($input, $balance);
            }
        }

        else
        {
            $payout = parent::createPayout($input);
        }

        //
        // In case of payouts with status=(queued, pending, scheduled, rejected, failed), we don't create the
        // transaction yet. This event will be dispatched later when we are actually processing the payout.
        //
        if ($payout->isStatusBeforeCreate() === false)
        {
            //
            // Ideally, this should be done as part of downstream processor,
            // but we do it here since, we do not want to dispatch this even if
            // payout creation flow fails for any reason after downstream processor runs.
            // Only doing this for type shared because transaction isn't created during payout flow for direct payouts.
            //

            if (($payout->isStatusBeforeCreate() === false) and
                ($payout->getBalanceAccountType() === AccountType::SHARED) and
                ($payout->merchant->isFeatureEnabled(Features::LEDGER_REVERSE_SHADOW) === false) and
                ($payout->getIsPayoutService() === false))
            {
                (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
            }
        }

        return $payout;
    }

    public function createPayoutForCompositePayoutFlow(array $input, Balance\Entity $balance = null, array $payoutMetadata = []): Entity
    {
        /** @var Balance\Entity $balance */
        if ($balance === null)
        {
            $balance = $this->repo->balance->findOrFailById($input[Balance\Entity::BALANCE_ID]);
        }

        $queuePayoutCreateRequest = $this->shouldDelayTransactionCreationForPayout();

        $input = array_merge($input, [Payout\Entity::QUEUE_PAYOUT_CREATE_REQUEST => $queuePayoutCreateRequest]);

        return parent::createPayoutForCompositePayoutFlow($input, $balance, $payoutMetadata);
    }

    public function createPayoutWithoutSaveForHighTpsCompositePayouts(array $input, Balance\Entity $balance = null)
    {
        $queuePayoutCreateRequest = $this->shouldDelayTransactionCreationForPayout();

        $input = array_merge($input, [Payout\Entity::QUEUE_PAYOUT_CREATE_REQUEST => $queuePayoutCreateRequest]);

        $this->setPayoutBalance($input, $balance);

        $this->preValidations();

        $payout =  $this->createPayoutEntityForNewCompositePayoutFlow($input, false);

        if ($payout->getQueuePayoutCreateRequest() === true)
        {
            $payout->setStatus(Status::CREATE_REQUEST_SUBMITTED);
        }

        return $payout;
    }

    /**
     * @param FundAccount\Entity $fundAccount
     *
     * @throws BadRequestException
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

        if ($this->isInternal === false)
        {
            $contactType = $fundAccount->source->getType();

            if (Contact\Type::isInInternal($contactType) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYOUT_TO_INTERNAL_FUND_ACCOUNT_NOT_PERMITTED,
                    null,
                    [
                        'contact_id'        => $fundAccount->source->getId(),
                        'fund_account_id'   => $fundAccount->getId(),
                    ]);
            }
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
        if (($payout->isStatusQueued() === true) || ($payout->isStatusOnHold() === true))
        {
            (new PayoutsStatusDetailsCore())->create($payout);

            $this->app->events->dispatch('api.payout.queued', [$payout]);
        }
        else
        {
            if ($payout->isStatusBeforeCreate() === false)
            {
                $this->app->events->dispatch('api.payout.initiated', [$payout]);
            }
        }
    }

    public function getAccountTypeForFundTransfer(Payout\Entity $payout)
    {
        if ($payout->isSubAccountPayout() === true)
        {
            return AccountType::DIRECT;
        }

        return $payout->balance->getAccountType() ?? AccountType::SHARED;
    }

    /**
     * TODO: Currently there is no proper way to decide the channel through
     * which the payout should be routed in case of shared accounts.
     * Till the time we achieve this by Dynamic routing, we are doing
     * a hack of using RazorX experiments to route certain merchants via a certain
     * channel (ICICI, CITI or YESBANK) based on mode of payout.
     *
     * However, for sub account payouts via master direct balance, the channel of fund transfer is
     * already determined.
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
        if ($payout->isSubAccountPayout() === true)
        {
            return $payout->getMasterBalance()->getChannel();
        }

        return $payout->balance->getChannel();
    }

    /*
     * One MID can't have more than one variant for same experiment, so there will be no clash.
     */
    protected function getChannelForSharedAccountFundTransfer(Payout\Entity $payout)
    {
        $merchant = $payout->merchant;

        $mode = $payout->getMode();

        if ($mode === FundTransferMode::CARD)
        {
            return Channel::M2P;
        }

        // this function gets called only if the source account type is shared, For it to be VA to VA transfer
        // here we just check destination account
        if (($payout->fundAccount->isAccountVirtualBankAccount() === true) and
            ($payout->getIsCreditTransferBasedPayout() === true))
        {
            return Channel::RZPX;
        }

        $razorxFeature = strtoupper(sprintf("%s_MODE_PAYOUT_FILTER", $mode));

        $variant = $this->app->razorx->getTreatment(
            $merchant->getId(),
            constant(RazorxTreatment::class . '::' . $razorxFeature),
            $this->mode,
            Payout\Entity::RAZORX_RETRY_COUNT
        );

        if (strtolower($variant) === 'control')
        {
            return Channel::YESBANK;
        }

        return constant(Channel::class . '::' . strtoupper($variant));
    }

    protected function validateModeChannelAndDestinationType(Payout\Entity $payout)
    {
        $accountType = $this->getAccountTypeForFundTransfer($payout);

        $channel = $this->getChannelForFundTransfer($accountType,$payout);

        $payout->setChannel($channel);

        $mode = $payout->getMode();

        $destinationType = $this->fundTransferDestination->getEntity();

        $merchantId = $payout->getMerchantId();

        if ($payout->isSubAccountPayout() === true)
        {
            $merchantId = $payout->getMasterBalance()->getMerchantId();
        }

        /** @var Payout\Validator $validator */
        $validator = $payout->getValidator();

        $valid = $validator->validateChannelAndModeForPayouts($merchantId, $channel, $destinationType, $mode, $accountType);

        if ($valid === false)
        {
            if ($accountType === AccountType::SHARED)
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

    protected function createBankingPayout(array $input, Balance\Entity $balance)
    {
        $queuePayoutCreateRequest = $this->shouldDelayTransactionCreationForPayout();

        $input = array_merge($input, [Payout\Entity::QUEUE_PAYOUT_CREATE_REQUEST => $queuePayoutCreateRequest]);

        $feeType = null;

        if ($queuePayoutCreateRequest === false)
        {
            $feeType = (new Payout\Core)->updateFreePayoutsConsumedAndGetFeeType($balance);

            $input = array_merge($input, [Payout\Entity::FEE_TYPE => $feeType]);
        }

        try
        {
            $payout = parent::createPayout($input);

            return $payout;
        }

        catch (\Throwable $throwable)
        {
            $balanceId = $input[Balance\Entity::BALANCE_ID];

            (new Payout\Core)->decreaseFreePayoutsConsumedInCaseOfTransactionFailureIfApplicable($balanceId, $feeType);

            $this->trace->traceException(
                $throwable,
                Logger::CRITICAL,
                TraceCode::CREATE_BANKING_PAYOUT_EXCEPTION,
                [
                    'balance_id'           => $balance->getId(),
                    'balance_channel'      => $balance->getChannel(),
                    'balance_type'         => $balance->getType(),
                    'account_account_type' => $balance->getAccountType(),
                ]
            );

            throw $throwable;
        }
    }

    protected function shouldDelayTransactionCreationForPayout()
    {
        if ($this->merchant->isAtLeastOneFeatureEnabled(
                    [
                        Features::PAYOUT_PROCESS_ASYNC_LP,
                        Features::PAYOUT_PROCESS_ASYNC
                    ]) === true)
        {
            return true;
        }

        return false;
    }
}
