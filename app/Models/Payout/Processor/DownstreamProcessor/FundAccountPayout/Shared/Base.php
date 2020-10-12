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
use RZP\Constants\Product;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Merchant\Credits;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Settlement\Channel;
use RZP\Models\Payout\CounterHelper;
use RZP\Models\Transaction\CreditType;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

class Base extends FundAccountPayout\Base
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        try
        {
            $this->setChannel($payout);

            $this->validateModeForChannelAndFundAccount($payout, $ftaAccount);

            // We are overriding only for live mode for now. To check for test mode later.
            if (($payout->getChannel() === Channel::YESBANK) and
                ($this->isLiveMode() === true))
            {
                $payout->setChannel(Channel::ICICI);
            }

            $this->checkAllowModeOnIcici($payout, $ftaAccount);

            $this->assignFreePayoutIfApplicable($payout);

            // The below code calculates payouts fees and tax and uses
            // reward_fee credits if available. The fees and tax of
            // transaction are updated accordingly. We

            if ($payout->merchant->isFeatureEnabled(Entity::PAYOUT_CREDITS_NEW_FLOW) === true)
            {
                $this->setFeeAndTaxForPayout($payout);
            }

            $this->createTransaction($payout);

            //
            // Create a fund transfer entity where the fund transfers will be processed.
            // NOTE: Ensure that this is created after transaction creation, so that if
            // the transaction creation fails because of insufficient funds and we want
            // to queue the payout instead of failing the complete DB transaction, this
            // FTA does not get created.
            //
            $this->createFundTransferAttempt($payout, $ftaAccount);
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
                if ($payout->merchant->isFeatureEnabled(Entity::PAYOUT_CREDITS_NEW_FLOW) === true)
                {
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
                }

                if ($payout->toBeQueued() === false)
                {
                    throw $ex;
                }

                $payout->setStatus(Status::QUEUED);
            }
            else
            {
                throw $ex;
            }
        }
    }

    /**
     * @param Entity $payout
     *
     * @throws BadRequestException
     */
    protected function checkAllowModeOnIcici(Entity $payout, $ftaAccount)
    {
        $channel = $payout->getChannel();

        $mode = $payout->getMode();

        $destination = $ftaAccount->getEntity();

        $treatmentName = 'RAZORPAY_X_ALLOW_' . strtoupper($mode) .'_PAYOUTS_VIA_ICICI_TO_' . strtoupper($destination);

        $key = Merchant\RazorxTreatment::class . '::' . strtoupper($treatmentName);

        $treatmentExists = ((defined($key) === true) and
                            (constant($key) === strtolower($treatmentName)));

        // If we don't have a treatment defined, we will allow the mode to go through.
        if ($treatmentExists === false)
        {
            return;
        }

        //
        // If we have the treatment defined AND the channel is ICICI, we check RazorX.
        // If RazorX fails, we assume that the mode is not supported and fail it.
        // We don't have mode check based on experiment for other channels.
        //
        if ($channel === Channel::ICICI)
        {
            $variant = $this->app->razorx->getTreatment(
                $payout->getMerchantId(),
                constant(Merchant\RazorxTreatment::class . '::' . $treatmentName),
                $this->mode
            );

            if ($variant === 'on')
            {
                return;
            }
            else
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
                    null,
                    [
                        'channel'       => $channel,
                        'mode'          => $mode,
                        'payout_id'     => $payout->getId()
                    ],
                    $mode . ' is not supported'
                );
            }
        }
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

        $valid = $validator->validateChannelAndModeForPayouts($merchantId, $channel, $destinationType, $mode);

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

        $this->adjustMerchantFeesThroughRewardFeeCreditsForPayout($payout, $fees, $tax);

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
