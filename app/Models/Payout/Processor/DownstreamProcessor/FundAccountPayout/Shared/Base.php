<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Shared;

use Mail;

use Carbon\Carbon;
use RZP\Mail\Banking;
use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Payout\Mode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Settlement\Channel;
use RZP\Models\Payout\CounterHelper;
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
                $mode . ' is not supported'
            );
        }
    }
}
