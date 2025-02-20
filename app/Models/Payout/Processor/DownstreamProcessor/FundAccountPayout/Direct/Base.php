<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Direct;

use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Purpose;
use RZP\Models\Payout\Service;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Credits;
use RZP\Models\Payout\BankingAccount;
use RZP\Models\Transaction\CreditType;
use RZP\Exception\BadRequestException;
use RZP\Constants\Mode as ConstantMode;
use RZP\Constants\Entity as ConstantEntity;
use RZP\Models\Payout\Processor\Base as ProcessorBase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

class Base extends FundAccountPayout\Base
{
    // while creating payouts we fetch balance from gateway at a frequency decided in SLA. For now have hardcoded this
    // to 50 minutes . So if last fetched at was while ago (more than 50 minutes) only then we will fetch.
    const DEFAULT_GATEWAY_BALANCE_LAST_FETCHED_AT_RATE_LIMITING = 50; //in minutes

    const NON_ZERO_PRICING_ERROR_MESSAGE = "Payout failed. Contact support for help.";

    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $payoutEntityProcessStartTime = millitime();

        $this->setChannel($payout);

        $this->validateModeForChannelAndFundAccount($payout, $ftaAccount);

        $holdPayout = $this->holdPayoutIfPartnerBankDown($payout) || $this->holdPayoutIfApplicableAndBeneBankDown($payout);

        $payoutEntityBankHealthCheckEndTime = millitime();

        if ($holdPayout === true)
        {
            return;
        }

        $queued = $this->queueIfLowBalance($payout);

        $payoutEntityAvailableBalanceCheckEndTime = millitime();

        if ($queued === true)
        {
            return;
        }

        $this->assignFreePayoutIfApplicable($payout);

        $this->setFeeAndTaxForPayout($payout);

        $payoutEntityProcessEndTime = millitime();

        $this->trace->info(
            TraceCode::DIRECT_ACCOUNT_PAYOUT_ENTITY_PROCESS_DURATION,
            [
                'payout_id'                    => $payout->getId(),
                'merchant_id'                  => $payout->getMerchantId(),
                'channel'                      => $payout->getChannel(),
                'bank_health_check_time'       => $payoutEntityBankHealthCheckEndTime - $payoutEntityProcessStartTime,
                'available_balance_check_time' => $payoutEntityAvailableBalanceCheckEndTime - $payoutEntityBankHealthCheckEndTime,
                'process_duration'             => $payoutEntityProcessEndTime - $payoutEntityProcessStartTime,
            ]);

        $this->createFundTransferAttempt($payout, $ftaAccount);
    }

    protected function queueIfLowBalance(Entity $payout) : bool
    {
        if (($payout->getPurpose() != Payout\Purpose::RZP_FEES) and
            ($payout->getPurpose() != Payout\Purpose::RZP_CHARGE_COLLECTIONS))
        {
            $queueDueToFeeRecovery = (new Payout\Processor\Base)->getQueuedFeeRecoveryPayoutsFlag($payout);

            if ($queueDueToFeeRecovery === true)
            {
                $payout->setStatus(Status::QUEUED);

                $payout->setQueuedReason(Payout\QueuedReasons::FEE_RECOVERY_PENDING);

                $this->trace->info(
                    TraceCode::PAYOUT_QUEUED,
                    [
                        'payout_id'     => $payout->getId(),
                        'payout_amount' => $payout->getAmount(),
                        'queue_flag'    => $payout->toBeQueued(),
                        'batch_id'      => $payout->getBatchId(),
                        'balance_id'    => $payout->getBalanceId()
                    ]);

                return true;
            }
        }

        if ($payout->toBeQueued() === false)
        {
            return false;
        }

        $payoutAmount = $payout->getAmount();

        $balanceResponse = $this->getMerchantBalanceToCheckForQueued($payout);

        $merchantBalance = $balanceResponse[ConstantEntity::BALANCE];

        $isStale = $balanceResponse['isStaleAndDispatched'];

        $hasBalance = (($isStale === false) and ($merchantBalance >= $payoutAmount));

        if ($hasBalance === false)
        {
            $payout->setStatus(Status::QUEUED);

            if ($isStale === true)
            {
                $payout->setQueuedReason(Payout\QueuedReasons::SYNCING_BALANCE);
            }
            else
            {
                $payout->setQueuedReason(Payout\QueuedReasons::LOW_BALANCE);
            }

            $this->trace->info(
                TraceCode::PAYOUT_QUEUED,
                [
                    'payout_id'         => $payout->getId(),
                    'payout_amount'     => $payout->getAmount(),
                    'balance'           => $merchantBalance,
                    'queue_flag'        => $payout->toBeQueued(),
                    'batch_id'          => $payout->getBatchId()
                ]);

            return true;
        }

        return false;
    }

    protected function getMerchantBalanceToCheckForQueued(Entity $payout)
    {
        $requestPayload = [
            "id" =>  $payout->getMerchantId(),
            "experiment_name" => RazorxTreatment::CA_PAYOUT_SKIP_BALANCE_FETCH,
            'request_data'  => json_encode(['id' =>  $payout->getMerchantId()])
        ];

        $isExperimentEnabled = (new Merchant\Core)->isSplitzExperimentEnable($requestPayload,RazorxTreatment::VARIANT_ENABLE);

        $isPayoutCreateFlow = false;

        if ($isExperimentEnabled === true)
        {
            $isPayoutCreateFlow = true;
        }

        return (new Payout\Core)->getLatestBalanceForDirectAccount($payout->balance, $isPayoutCreateFlow);
    }

    /**
     * ADDING for BACKWARD COMPATIBILITY
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
                strtoupper($channel) . ' does not support ' . $mode . ' payouts to ' . strtoupper($destinationType)
            );
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function setFeeAndTaxForPayout($payout)
    {
        list($fees, $tax, $pricingRuleId) = $this->calculateFeesAndTaxForPayouts($payout);

        if (empty($pricingRuleId) === true)
        {
            throw new LogicException('No Pricing Rule ID set for payout: ' . $payout->getId());
        }

        // Zero Pricing for Fee Recovery Payout.
        if (($payout->getPurpose() === Purpose::RZP_FEES) and
            (($fees != 0) or ($tax != 0)))
        {
            $this->trace->count(
                Payout\Metric::FEES_NON_ZERO_FOR_RZP_FEES_PAYOUT,
                [
                    'payout_id' => $payout->getId(),
                    'fees' => $fees,
                    'tax' => $tax,
                    'pricing_rule' => $pricingRuleId,
                ]);

            $this->trace->info(
                TraceCode::FEES_NON_ZERO_FOR_RZP_FEES_PAYOUT,
                [
                    'payout_id'     => $payout->getId(),
                    'fees'          => $fees,
                    'tax'           => $tax,
                    'pricing_rule'  => $pricingRuleId,
                ]);
        }

        // Zero pricing for charge collections payout.
        if (($payout->getPurpose() === Purpose::RZP_CHARGE_COLLECTIONS) and
            (($fees !== 0) or ($tax !== 0)))
        {
            throw new BadRequestValidationFailureException(self::NON_ZERO_PRICING_ERROR_MESSAGE);
        }

        if (!((new BankingAccount\Core())->merchantMigratedToPayoutServiceByMerchantIdAndBalanceId($payout->merchant->getMerchantId(), $payout->balance->getId())))
        {
            $this->adjustMerchantFeesThroughRewardFeeCreditsForPayout($payout, $fees, $tax);
        }

        $payout->setFees($fees);

        $payout->setTax($tax);

        $payout->setPricingRuleId($pricingRuleId);

        $this->trace->info(
            TraceCode::DIRECT_ACCOUNT_PAYOUT_FEES_TAXES,
            [
                'payout_id'     => $payout->getId(),
                'fees'          => $payout->getFees(),
                'tax'           => $payout->getTax(),
                'pricing_rule'  => $payout->getPricingRuleId(),
            ]);

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

    // consuming reward fee credits here directly. Ideally there will be many
    // credits like amount, fee etc and there will also be a order here
    // to consume those rewards.

    protected function adjustMerchantFeesThroughCreditsForPayout(Entity $payout, & $fees, & $tax)
    {
        $merchantCredits = $this->repo->credits->getTypeAggregatedMerchantCreditsForProduct($payout->merchant->getId(), Product::BANKING);

        $rewardFeeCredits = $merchantCredits[CreditType::REWARD_FEE] ?? 0;

        if (($rewardFeeCredits < $fees - $tax) or
            ($fees === 0))
        {
            return;
        }

        if ($rewardFeeCredits !== 0)
        {
            $rewardUsed = $fees = $fees - $tax;

            $tax = 0;

            (new Credits\Transaction\Core)->subtractMerchantCreditBalanceAndCreateTransactions(
                                                            $payout->merchant,
                                                            CreditType::REWARD_FEE,
                                                            Product::BANKING,
                                                            $rewardUsed,
                                                            $payout);

            $payout->setFeeType(CreditType::REWARD_FEE);
        }
    }

    public function adjustFeeAndTaxesIfCreditsAvailable($payout)
    {
        $fees = $payout->getFees();

        $tax = $payout->getTax();

        if ($payout->merchant->isFeatureEnabled(Feature\Constants::HIGH_TPS_COMPOSITE_PAYOUT) === false)
        {
            $this->adjustMerchantFeesThroughRewardFeeCreditsForPayout($payout, $fees, $tax);
        }

        $payout->setFees($fees);

        $payout->setTax($tax);
    }
}
