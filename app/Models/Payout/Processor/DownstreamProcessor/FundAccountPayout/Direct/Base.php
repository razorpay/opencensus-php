<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Direct;

use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Method;
use RZP\Models\Payout\Purpose;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

class Base extends FundAccountPayout\Base
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        $this->validateModeForChannelAndFundAccount($payout, $ftaAccount);

        $queued = $this->queueIfLowBalance($payout);

        if ($queued === false)
        {
            $this->setFeeAndTaxForPayout($payout);

            $this->createFundTransferAttempt($payout, $ftaAccount);
        }
    }

    protected function queueIfLowBalance(Entity $payout) : bool
    {
        if ($payout->toBeQueued() === false)
        {
            return false;
        }

        $payoutAmount = $payout->getAmount();

        $merchantBalance = $payout->balance->getBalance();

        $hasBalance = ($merchantBalance >= $payoutAmount);

        if ($hasBalance === false)
        {
            $payout->setStatus(Status::QUEUED);

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

        $valid = Mode::validateChannelAndModeForPayouts($channel, $destinationType, $mode);

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

    protected function setFeeAndTaxForPayout($payout)
    {
        list($fees, $tax, $pricingRuleId) = $this->calculateFeesAndTaxForPayouts($payout);

        if (empty($pricingRuleId) === true)
        {
            throw new LogicException('No Pricing Rule ID set for payout: ' . $payout->getId());
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
