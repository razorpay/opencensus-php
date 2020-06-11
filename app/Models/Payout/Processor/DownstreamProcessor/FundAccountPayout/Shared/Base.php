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

            $this->createTransaction($payout);

            //
            // Create a fund transfer entity where the fund transfers will be processed.
            // NOTE: Ensure that this is created after transaction creation, so that if
            // the transaction creation fails because of insufficient funds and we want
            // to queue the payout instead of failing the complete DB transaction, this
            // FTA does not get created.
            //
            $this->createFundTransferAttempt($payout, $ftaAccount);

            $this->sendEmailHackForLowBalance($payout);
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
                if ($payout->toBeQueued() === false)
                {
                    throw $ex;
                }

                $payout->setStatus(Status::QUEUED);

                $this->sendEmailHackForLowBalance($payout);
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
     * TODO: This is a hack and is temporary (Maximum: Until Dec 2019)
     * No feature requests on this are to be entertained.
     *
     * @param Entity $payout
     *
     * @return void|null
     */
    public function sendEmailHackForLowBalance(Entity $payout)
    {
        $adminService = new Admin\Service;

        $merchantId = $payout->getMerchantId();

        try
        {
            $lowBalanceEmailMerchantsConfig = $adminService->getConfigKey(
                [
                    'key' => Admin\ConfigKey::LOW_BALANCE_RX_EMAIL
                ]);

            if (empty($lowBalanceEmailMerchantsConfig) === true)
            {
                return;
            }

            // This is done just for the test cases. '10000000000000' is converted to integer 10000000000000
            $configuredMerchantIds = array_map('strval', array_keys($lowBalanceEmailMerchantsConfig));

            if (in_array($merchantId, $configuredMerchantIds, true) === false)
            {
                return;
            }

            $merchantConfig = $lowBalanceEmailMerchantsConfig[$merchantId];

            $notifyAt = $merchantConfig['notify_at'] ?? 0;

            $currentTime = Carbon::now()->getTimestamp();

            $lowBalanceThreshold = $merchantConfig['low_balance_threshold'] ?? 0;

            $balance = $payout->balance->getBalanceWithLockedBalance();

            if ($balance > $lowBalanceThreshold)
            {
                //
                // This is required in the following case:
                // - Balance went below the threshold.
                // - We updated notify_at to next 8 hours.
                // - Balance is now above threshold (within the 8hrs).
                // - Balance is now again below the threshold (within the 8hrs).
                // - We will not send the notification since notify_at is set for the next 8hrs
                //
                // The below line will ensure that whenever the balance is above the threshold, we
                // update the notify_at to 0 so that if it goes below the threshold again, we notify.
                //
                $this->modifyNotifyAtForLowBalanceEmail($lowBalanceEmailMerchantsConfig, $merchantId, 0);

                return;
            }

            if ($notifyAt > $currentTime)
            {
                return;
            }

            $emails = $merchantConfig['email_ids'] ?? ['ankit.nahata@razorpay.com'];

            $accountNumber = $payout->balance->getAccountNumber();

            $data = [
                'emails'                => $emails,
                'masked_account_number' => mask_except_last4($accountNumber),
                'available_balance'     => (float) $balance / 100,
                'threshold'             => (float) $lowBalanceThreshold / 100,
            ];

            $this->trace->info(
                TraceCode::RX_LOW_BALANCE_EMAIL_ALERT_DATA,
                [
                    'data'              => $data,
                    'payout_id'         => $payout->getId(),
                    'payout_amount'     => $payout->getAmount(),
                    'merchant_id'       => $merchantId,
                    'merchant_config'   => $merchantConfig,
                ]);

            $lowBalanceEmail = new Banking\LowBalanceAlert($payout->merchant, $data);

            Mail::queue($lowBalanceEmail);

            // 28800: 8 hours in seconds
            $nextNotifyAt = $currentTime + 28800;

            $this->modifyNotifyAtForLowBalanceEmail($lowBalanceEmailMerchantsConfig, $merchantId, $nextNotifyAt);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::RX_LOW_BALANCE_EMAIL_ALERT_FAILED,
                [
                    'payout_id'     => $payout->getId(),
                    'merchant_id'   => $merchantId,
                ]);
        }
    }

    protected function modifyNotifyAtForLowBalanceEmail($lowBalanceEmailMerchantsConfig, $merchantId, $notifyAt)
    {
        $merchantConfig = $lowBalanceEmailMerchantsConfig[$merchantId];

        $merchantConfig['notify_at'] = $notifyAt;

        $lowBalanceEmailMerchantsConfig[$merchantId] = $merchantConfig;

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::LOW_BALANCE_RX_EMAIL => $lowBalanceEmailMerchantsConfig
            ]);
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
