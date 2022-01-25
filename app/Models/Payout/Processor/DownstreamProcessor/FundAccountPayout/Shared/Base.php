<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Shared;

use Mail;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Mail\Banking;
use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Pricing;
use RZP\Models\Feature;
use RZP\Constants\Product;
use RZP\Jobs\Transactions;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Core;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Merchant\Credits;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\CounterHelper;
use RZP\Models\Payout\QueuedReasons;
use RZP\Models\Transaction\CreditType;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Transaction\Core as TxnCore;
use RZP\Constants\Entity as EntityConstant;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Transaction\Processor\Payout as PayoutTxnProcessor;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

class Base extends FundAccountPayout\Base
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        try
        {
            $this->setChannel($payout);

            $this->validateModeForChannelAndFundAccount($payout, $ftaAccount);

            $holdPayout = $this->holdPayoutIfApplicableAndBeneBankDown($payout);

            if ($holdPayout === true)
            {
                return ;
            }

            // We are overriding only for live mode for now. To check for test mode later.
            if (($payout->getChannel() === Channel::YESBANK) and
                ($this->isLiveMode() === true))
            {
                $payout->setChannel(Channel::ICICI);
            }

            $this->assignFreePayoutIfApplicable($payout);

            // The below code calculates payouts fees and tax and uses
            // reward_fee credits if available. The fees and tax of
            // transaction are updated accordingly. We

            if (!(($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === true) and
                ($payout->merchant->isAtLeastOneFeatureEnabled([Feature\Constants::PAYOUT_PROCESS_ASYNC_LP, Feature\Constants::PAYOUT_PROCESS_ASYNC]) === true)))
            {
                $this->setFeeAndTaxForPayout($payout);
            }

            $this->trace->info(
                TraceCode::LEDGER_REVERSE_SHADOW_CONDITION_CHECKLIST,
                [
                    'feature_flag' => $payout->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW),
                    'fee_type' => $payout->getFeeType(),
                    'payout_service_flag' => $payout->getIsPayoutService(),
                    'payout_balance_type' => $payout->getBalanceType(),
                    'payout_balance_account_type' => $payout->getBalanceAccountType(),
                ]
            );

            if (Core::shouldPayoutGoThroughLedgerReverseShadowFlow($payout) === true)
            {
                return $payout;
            }

            $this->createTransaction($payout);

            //
            // Create a fund transfer entity where the fund transfers will be processed.
            // NOTE: Ensure that this is created after transaction creation, so that if
            // the transaction creation fails because of insufficient funds and we want
            // to queue the payout instead of failing the complete DB transaction, this
            // FTA does not get created.
            // Skip for payout service payout because we are breaking these in new flow
            // as ledger will be a separate service
            //
            if ($payout->getIsPayoutService() === false)
            {
                $this->createFundTransferAttempt($payout, $ftaAccount);
            }
        }
        catch (BadRequestException $ex)
        {
            //
            // This needs to be done since while creating a transaction we also associate
            // the source (payout) with the transaction and then we fail the transaction
            // creation due to insufficient balance and then later attempt to save the payout.
            // Payout save fails because we associated the failed transaction with the payout
            // but we had not actually saved the transaction in the DB.
            //
            $payout->transaction()->dissociate();

            $insufficientFundsErrorCode = ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING;

            if ($ex->getError()->getInternalErrorCode() === $insufficientFundsErrorCode)
            {
                $shouldUnsetFeeTypeAndExpectedFeeType =
                    (new CounterHelper)->decreaseFreePayoutsConsumedIfApplicable($payout,
                                                                                 CounterHelper::INSUFFICIENT_BALANCE);

                if ($shouldUnsetFeeTypeAndExpectedFeeType === true)
                {
                    $payout->setFeeType(null);

                    $payout->setExpectedFeeType(null);
                }

                // since the banking balance of merchant was not sufficient, the payout went to queued state
                // we don't want to have a payout in the system which is in queued state and has fees and tax
                // set, so rolling back the changes.

                $payout->setFees(0);

                $payout->setTax(0);

                unset($payout[Entity::PRICING_RULE_ID]);

                // we need to reverse the credits consumed by the payouts
                if ($payout->getFeeType() === CreditType::REWARD_FEE)
                {
                    $this->trace->info(TraceCode::CREDITS_REVERSE_FOR_QUEUED_PAYOUT,
                        [
                            'payout_id' => $payout->getId()
                        ]);

                    (new Credits\Transaction\Core)->reverseCreditsForSource(
                        $payout->getId(),
                        Constants\Entity::PAYOUT,
                        $payout);

                    unset($payout[Entity::FEE_TYPE]);
                }

                if ($payout->toBeQueued() === false)
                {
                    throw $ex;
                }

                $payout->setStatus(Status::QUEUED);

                $payout->setQueuedReason(QueuedReasons::LOW_BALANCE);
            }
            else
            {
                throw $ex;
            }
        }
    }

    /**
     * Don't call this method if the payout is before created state.
     * @param Entity       $payout
     * @param PublicEntity $ftaAccount
     *
     * @return Entity
     * @throws BadRequestException
     * @throws GatewayTimeoutException
     * @throws LogicException
     * @throws \RZP\Exception\InvalidArgumentException
     * @throws \Throwable
     */
    public function processPayoutThroughLedger(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->trace->info(
            TraceCode::PAYOUT_PROCESSING_THROUGH_LEDGER_BEGINS,
            [
                'payout_id' => $payout->getPublicId(),
            ]
        );

        try
        {
            $ledgerResponse = (new Ledger\Payout())->processPayoutAndCreateJournalEntry($payout);

            if ($payout->getIsPayoutService() === false)
            {
                $this->createFundTransferAttempt($payout, $ftaAccount);
            }

            // If it is a success, dispatch to queue for transactions creation
            try
            {
                Transactions::dispatch($this->mode,
                                       $payout->getId(),
                                       EntityConstant::PAYOUT,
                                       $ledgerResponse);
            }
            catch (\Throwable $ex)
            {
                // TODO: Set an alert. Check how to handle this failure.
                // Will probably need to give a route to retry creation
                $this->trace->info(
                    TraceCode::LEDGER_TRANSACTIONS_QUEUE_JOB_PUSH_FAILED,
                    [
                        'payout_id'      => $payout->getId(),
                        'entity_name'    => EntityConstant::PAYOUT,
                        'ledgerResponse' => $ledgerResponse,
                    ]);
            }

            return $payout;
        }
        catch (GatewayTimeoutException $e)
        {
            // Timeout case
            // This is an ambiguous situation, need to manually check if the ledger entry was created.
            // TODO: An alert here is absolutely essential
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                null,
                [
                    'payout_id' => $payout->getId(),
                ]
            );

            throw $e;
        }
        catch (BadRequestException $ex)
        {
            $insufficientFundsErrorCode = ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING;

            if ($ex->getError()->getInternalErrorCode() === $insufficientFundsErrorCode)
            {
                $shouldUnsetFeeTypeAndExpectedFeeType =
                    (new CounterHelper)->decreaseFreePayoutsConsumedIfApplicable($payout,
                                                                                 CounterHelper::INSUFFICIENT_BALANCE);

                if ($shouldUnsetFeeTypeAndExpectedFeeType === true)
                {
                    $payout->setFeeType(null);

                    $payout->setExpectedFeeType(null);
                }

                // since the banking balance of merchant was not sufficient, the payout went to queued state
                // we don't want to have a payout in the system which is in queued state and has fees and tax
                // set, so rolling back the changes.

                $payout->setFees(0);

                $payout->setTax(0);

                unset($payout[Entity::PRICING_RULE_ID]);

                // we need to reverse the credits consumed by the payouts
                // although the flow shouldn't reach here right now in partial reverse shadow
                // keeping this for safety
                if ($payout->getFeeType() === CreditType::REWARD_FEE)
                {
                    $this->trace->info(TraceCode::CREDITS_REVERSE_FOR_QUEUED_PAYOUT,
                                       [
                                           'payout_id' => $payout->getId()
                                       ]);

                    (new Credits\Transaction\Core)->reverseCreditsForSource(
                        $payout->getId(),
                        Constants\Entity::PAYOUT,
                        $payout);

                    unset($payout[Entity::FEE_TYPE]);
                }

                if ($payout->toBeQueued() === false)
                {
                    $payout->setStatus(Status::FAILED);

                    $payout->setFailureReason('Insufficient balance to process payout');

                    $payout->setStatusCode($insufficientFundsErrorCode);

                    $this->repo->saveOrFail($payout);

                    $this->trace->info(
                        TraceCode::PAYOUT_FAILED_IN_LEDGER_FLOW,
                        [
                            'payout_id'      => $payout->getId(),
                            'transaction_id' => $payout->getTransactionId(),
                            'payout_status'  => $payout->getStatus(),
                            'failure_reason' => $payout->getFailureReason(),
                        ]);

                    throw $ex;
                }

                $payout->setStatus(Status::QUEUED);

                $payout->setQueuedReason(QueuedReasons::LOW_BALANCE);

                $this->repo->saveOrFail($payout);
            }
            else
            {
                throw $ex;
            }
        }
    }

    public function createTransactionForLedgerReverseShadow($payout, $ledgerResponse)
    {
        $this->trace->info(
            TraceCode::TRANSACTION_CREATE_FOR_LEDGER_REVERSE_SHADOW_BEGINS,
            [
                'entity_id' => $payout->getPublicId(),
            ]
        );

        $txnId = $ledgerResponse[Entity::ID];
        $newBalance = Ledger\Payout::getMerchantBalanceFromLedgerResponse($ledgerResponse);

        list($txn, $feeSplit) = (new PayoutTxnProcessor($payout))->createTransactionForLedger($txnId, $newBalance);

        $this->repo->saveOrFail($txn);

        // if fee split is null, it may mean that a txn is already created.
        // thus a dispatch may have already happened
        // which means a dispatch is not needed if fee split is null
        if ($feeSplit !== null)
        {
            (new TxnCore)->saveFeeDetails($txn, $feeSplit);

            (new TxnCore())->dispatchEventForTransactionCreated($txn);
        }

        $this->trace->info(
            TraceCode::TRANSACTION_FOR_LEDGER_REVERSE_SHADOW_CREATED,
            [
                'entity_id' => $payout->getPublicId(),
            ]
        );

        return $txn;
    }

    /**
     * This function makes sure that we don't queue something that will fail when picked up for processing.
     * Ideally, this logic should stay with FTS, but in that case merchants get a bad experience.
     * TODO: Need to keep this check at FTS level itself
     *
     * @param $payout
     * @param $ftaAccount
     * @throws BadRequestException
     */
    protected function validateModeForChannelAndFundAccount($payout, $ftaAccount)
    {
        $destinationType = $ftaAccount->getEntity();

        $channel = $payout->getChannel();

        $mode = $payout->getMode();

        $merchantId = $payout->getMerchantId();

        /** @var Payout\Validator $validator */
        $validator = $payout->getValidator();

        $accountType = $payout->balance->getAccountType();

        $valid = $validator->validateChannelAndModeForPayouts($merchantId, $channel, $destinationType, $mode, $accountType);

        if ($valid === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
                null,
                [
                    'channel'           => $channel,
                    'mode'              => $mode,
                    'destination_type'  => $destinationType
                ],
                $mode . ' is not supported'
            );
        }
    }

    protected function setFeeAndTaxForPayout($payout)
    {
        list($fees, $tax, $pricingRuleId) = $this->calculateFeesAndTaxForPayouts($payout);

        if (empty($pricingRuleId) === true)
        {
            throw new LogicException('No Pricing Rule ID set for payout: ' . $payout->getId());
        }

        if ($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === false)
        {
            $this->adjustMerchantFeesThroughRewardFeeCreditsForPayout($payout, $fees, $tax);
        }

        $payout->setFees($fees);

        $payout->setTax($tax);

        $payout->setPricingRuleId($pricingRuleId);
    }

    protected function calculateFeesAndTaxForPayouts(Entity $payout)
    {
        list($fees, $tax, $feesSplit) = (new Pricing\PayoutFee)->calculateMerchantFees($payout);

        $feesSplitData = $feesSplit->toArray();

        foreach ($feesSplitData as $feesSplit)
        {
            // Set pricingRuleId from the feesSplit (there are two entries and at least one has pricingRuleId)
            if (empty($feesSplit[Entity::PRICING_RULE_ID]) === false)
            {
                $pricingRuleId = $feesSplit[Entity::PRICING_RULE_ID];
            }
        }

        return [$fees, $tax, $pricingRuleId];
    }
}
