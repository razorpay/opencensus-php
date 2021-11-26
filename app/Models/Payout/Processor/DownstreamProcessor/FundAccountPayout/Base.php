<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

use RZP\Constants;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use Razorpay\Trace\Logger;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Purpose;
use RZP\Models\Merchant\Credits;
use RZP\Models\Payout\QueuedReasons;
use RZP\Models\Merchant\Balance\Type;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Models\Feature\Constants as Features;
use \RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\Payout\Processor\DownstreamProcessor\Base as DSBase;

class Base extends DSBase
{
    protected function setChannel(Entity $payout)
    {
        //
        // NOTE (for queued only): When the payout is being queued,
        // the payout might have channel A set. Once we start processing,
        // it's possible that we are selecting channel B (based on what
        // DR module returns at that point of time).
        // Hence, irrespective of the channel set at the time of queued,
        // the channel that will be actually used is of when the queued
        // payout is being processed.
        //

        $channel = snake_case(class_basename(get_called_class()));

        $payout->setChannel($channel);
    }

    protected function assignFreePayoutIfApplicable(Entity $payout)
    {
        /*
         * We don't want fee recovery payouts to go through the free payout flow, hence the check here.
         */
        if ($payout->getPurpose() === Purpose::RZP_FEES)
        {
            return;
        }

        $balance = $payout->balance;

        $freePayoutsSupportedModes = (new FreePayout)->getFreePayoutsSupportedModes($balance);

        if ((in_array($payout->getMode(), $freePayoutsSupportedModes, true) === true) and
            ($balance->getType() === Type::BANKING))
        {
            $expectedFeeType = $payout->getExpectedFeeType();

            $payout->setFeeType($expectedFeeType);
        }
    }

    // consuming reward fee credits here directly. Ideally there will be many
    // credits like amount, fee etc and there will also be a order here
    // to consume those rewards.

    protected function adjustMerchantFeesThroughRewardFeeCreditsForPayout(Entity $payout, & $fees, & $tax)
    {
        if ($payout->getFeeType() !== null)
        {
            return;
        }

        $creditsToBeConsumed = $fees - $tax;

        $this->trace->info(TraceCode::PAYOUT_REWARD_FEE_CREDITS_USED_REQUEST,
            [
                'payout_id'                 => $payout->getId(),
                'credits_to_be_consumed'    => $creditsToBeConsumed,
            ]);

        $creditsConsumed = (new Credits\Transaction\Core)->subtractAndGetMerchantCreditsConsumed(
                                                            $payout->merchant,
                                                            CreditType::REWARD_FEE,
                                                            Product::BANKING,
                                                            $creditsToBeConsumed,
                                                            $payout);

        if (($creditsConsumed != 0) and
            ($creditsConsumed === $creditsToBeConsumed))
        {
            $fees = $fees - $tax;

            $tax = 0;

            $this->trace->info(TraceCode::PAYOUT_REWARD_FEE_CREDITS_USED,
                [
                    'payout_id'         => $payout->getId(),
                    'fees'              => $fees,
                    'tax'               => $tax,
                    'credits_consumed'  => $creditsConsumed,
                ]);

            $payout->setFeeType(CreditType::REWARD_FEE);
        }
    }

    protected function holdPayoutIfApplicableAndBeneBankDown(Entity $payout)
    {
        if ($payout->fundAccount->getAccountType() !== FundAccountEntity::BANK_ACCOUNT)
        {
            return false;
        }

        if (($payout->isStatusOnHold() === false) and
            ($payout->isStatusQueued() === false))
        {
            $onHold = $this->checkIfPayoutToBeKeptOnHold($payout);

            if ($onHold === true)
            {
                $payout->setStatus(Status::ON_HOLD);

                $payout->setQueuedReason(QueuedReasons::BENE_BANK_DOWN);

                return true;
            }
        }

        return false;
    }

    //checks payout to be kept on_hold if the feature is enabled and bene bank is down
    protected function checkIfPayoutToBeKeptOnHold(Entity $payout): bool
    {
        try
        {
            if ($payout->merchant->isFeatureEnabled(Features::PAYOUTS_ON_HOLD) === true)
            {
                $isBeneBankDown = (new PayoutCore)->checkIfBeneBankIsDown($payout);

                if ($isBeneBankDown === true)
                {
                    $skipTestTransaction = $payout->merchant->isFeatureEnabled(Features::SKIP_TEST_TXN_FOR_DMT);

                    if ($skipTestTransaction == true)
                    {
                        $toHoldPayout = true;
                    }
                    else
                    {
                        $toHoldPayout = $this->generateRandomNumberAndCheckIfPayoutToHold();
                    }

                    if ($toHoldPayout === true)
                    {
                        $this->trace->info(
                            TraceCode::ON_HOLD_PAYOUT_CREATED,
                            [
                                'payout_id'     => $payout->getId(),
                                'ifsc'          => $payout->fundAccount->account->getIfscCode(),
                                'skip_test_txn' => $skipTestTransaction
                            ]);

                        return true;
                    }
                    else
                    {
                        $this->trace->info(
                            TraceCode::PAYOUT_SENT_TO_DETECT_BENE_UPTIME,
                            [
                                'ifsc'              => $payout->fundAccount->account->getIfscCode(),
                                'is_bene_bank_down' => $isBeneBankDown,
                                'payout_id'         => $payout->getId(),
                            ]);
                    }
                }
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::ON_HOLD_PAYOUT_CHECK_FAILED,
                [
                    'message'   => $e->getMessage(),
                    'payout_id' => $payout->getId(),
                ]);
        }

        return false;
    }

    protected function generateRandomNumberAndCheckIfPayoutToHold(): bool
    {
        if ($this->app->environment(Constants\Environment::TESTING))
        {
            return true;
        }

        $random = mt_rand(0, 100);

        if ($random <= 10)
        {
            return false;
        }

        return true;
    }
}
