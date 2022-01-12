<?php

namespace RZP\Models\FundTransfer\Yesbank;

use App;
use Config;
use Carbon\Carbon;


use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Card\Type;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\FundTransfer\Attempt\Constants;
use RZP\Models\FundTransfer\Yesbank\Request\Transfer;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Models\FundTransfer\Yesbank\Request\HealthCheck;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\StatusProcessor;

class NodalAccount extends NodalBase\NodalAccount
{
    const IFSC_IDENTIFIER = IFSC::YESB;

    protected $trace;

    protected $config;

    public function __construct(string $purpose = null)
    {
        parent::__construct($purpose);

        $this->initStats();
    }

    /**
     * Makes request to the bank for fund transfer for given attempts
     *
     * @param PublicCollection $attempts
     * @param $forceFlag
     * @return array
     * @throws LogicException
     */
    public function process(PublicCollection $attempts, bool $forceFlag = false): array
    {
        $processedCount = 0;

        foreach ($attempts as $attempt)
        {
            //
            // This is required only for Yesbank since the schedule sets
            // time during non-working days and non-working hours also
            // only for yesbank right now, for some merchants, based
            // on certain conditions.
            // Not required for other banks since the settled_at time will
            // never be set during non-working hours/days.
            // Also, for other banks, settlements itself won't be even
            // initiated on non-working days/hours
            //
            if ($forceFlag === false)
            {
                $isTransferAllowedToday = $this->isTransferAllowedToday($attempt);

                if ($isTransferAllowedToday === false)
                {
                    continue;
                }
            }
            //if BA is not present for attempt
            // marking FTA as failed, if source is settlement
            if($this->markFailedIfBANotExists($attempt) === true)
            {
                continue;
            }

            $gateway = $attempt->shouldUseGateway($attempt->getMode());

            $this->doRequiredChecks($gateway);

            $type = $this->getRequestType($attempt);

            $useCurrentAccount = $attempt->merchant->isFeatureEnabled(Feature\Constants::DUMMY);

            $transfer = new Transfer($this->purpose, $type, $useCurrentAccount);

            if ($attempt->hasCard() === true)
            {
                $iin = $attempt->getCardAttribute()->iinRelation;

                if (($iin !== null) and ($iin->getType() === Type::CREDIT))
                {
                    $transfer->disableLogs();
                }
                else
                {
                    $this->trace->info(TraceCode::UNSUPPORTED_CARD_TYPE_FOR_TRANSFER,
                        [
                            'channel'       => $this->channel,
                            'attempt_id'    => $attempt->getId(),
                        ]);

                    continue;
                }
            }
            try
            {
                $checkAttempt = clone $attempt;

                $checkAttempt->reload();

                if ($checkAttempt->getStatus() !== Attempt\Status::CREATED)
                {
                    $this->trace->info(TraceCode::NODAL_TRANSFER_REQUEST_DUPLICATE,
                        [
                            'channel'       => $this->channel,
                            'attempt_id'    => $attempt->getId(),
                        ]);

                    continue;
                }

                // Calling init will reset all the data of previous request
                $response = $transfer->setEntity($attempt)
                                     ->makeRequest($gateway);

                // will be true if there is any low balance alert
                $lowBalanceAlert = $response[self::LOW_BALANCE_ALERT] ?? false;
                $attempt->setMode($transfer->transferType);

                // We set attempt's status to `initiated` before calling this function, `process`.
                // Only if the request is executed successfully, we want to save the attempt's status.
                $this->repo->saveOrFail($attempt);

                $this->postFtaInitiateProcess($attempt);

                $this->trackAttemptsInitiatedSuccess($this->channel, $this->purpose, $attempt->getSourceType());
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_TRANSFER_REQUEST_FAILED,
                    [
                        'channel'       => $this->channel,
                        'attempt_id'    => $attempt->getId(),
                        'settlement_id' => $attempt->getSourceId(),
                    ]);

                $attempt->setMode($transfer->transferType);

                $this->repo->save($attempt);

                $this->trackAttemptsInitiatedFailure($this->channel, $this->purpose, $attempt->getSourceType());

                continue;
            }

            $processedCount++;

            try
            {
                (new StatusProcessor($response))->updateTransferStatus();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_TRANSFER_STATUS_UPDATE_FAILED,
                    $response
                );
            }

            if ($lowBalanceAlert === true)
            {
                $this->sendLowBalanceAlert([
                    'channel'        => $this->channel,
                    'account_number' => $transfer->getMaskedAccountNumber(),
                ]);
            }
        }

        $this->updateTransferStatus($processedCount);

        return $this->transferStatus;
    }

    public function healthCheck(array $input = []): array
    {
        $gateway = ((isset($input['gateway']) === true) and
                    ($input['gateway'] === true));

        $healthCheck = new HealthCheck(Attempt\Purpose::SETTLEMENT);

        return $healthCheck->makeRequest($gateway);
    }

    /**
     * @param bool $gateway
     * @throws LogicException
     */
    protected function doRequiredChecks(bool $gateway)
    {
        if ($gateway === false)
        {
            return;
        }

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData(Gateway::UPI_YESBANK);

        if ($terminal === null)
        {
            throw new LogicException(
                "Terminal not found.",
                null,
                [
                    'gateway' => Gateway::UPI_YESBANK
                ]);
        }
    }

    public function getPaymentModeForBankAccount(Attempt\Entity $attempt, $amount): string
    {
        if ($attempt->hasMode() === true)
        {
            return $attempt->getMode();
        }

        $ba = $attempt->bankAccount;

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);
        $ifscLastDigits = substr($ifsc, 4, strlen($ifsc)-4);

        $sameBankIfscCode = starts_with($ifscFirstFour, static::IFSC_IDENTIFIER);

        $source = $attempt->source;

        // if commission settlement
        // if same bank, then IFT else NEFT
        if ((empty($source) === false) and
            ($source->getEntityName() === EntityConstants::SETTLEMENT) and
            ($source->isBalanceTypeCommission() === true)) {

            if (($sameBankIfscCode === true) and (is_numeric($ifscLastDigits) === true))
            {
                return Mode::IFT;
            }
            else
            {
                return Mode::NEFT;
            }
        }
        else if ($sameBankIfscCode === true)
        {
            if (is_numeric($ifscLastDigits) === true)
            {
                return Mode::IFT;
            }
            else
            {
                return Mode::NEFT;
            }

        }
        else if ($amount < self::MAX_IMPS_AMOUNT)
        {
            return Mode::IMPS;
        }

        return $this->getTransferMode($amount, $ba->merchant);
    }

    /**
     * filters the attempts based on holiday and channel
     * for yesbank we allow settlements on holidays but it should only be IMPS
     * IMPS has amount limit of 2L.
     * So if any attempt of yesbank on holidays will be filtered based on amount
     *
     * @param Attempt\Entity $attempt
     * @return bool
     */
    protected function isTransferAllowedToday(Attempt\Entity $attempt):  bool
    {
        if (($attempt->hasVpa() === true) or
            ($attempt->hasCard() === true))
        {
            return true;
        }

        $amount = $attempt->source->getAmount() / 100;

        $mode = $this->getPaymentModeForBankAccount($attempt, $amount);

        $allowedModes = Mode::get24x7TransferModes();

        // For IMPS
        if (in_array($mode, $allowedModes, true) === true)
        {
            return true;
        }

        $currentDateTime = Carbon::now(Timezone::IST);

        // If Source Type is payout and its s Rx Payout, then
        // Use FTA bank holiday list to check working day.
        if (($attempt->getSourceType() === Attempt\Type::PAYOUT) and
            ($attempt->source->isBalanceTypeBanking() === true))
        {
            if (Holidays::isWorkingDay($currentDateTime) === false)
            {
                $this->trace->info(
                    TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED,
                    [
                        'attempt_id' => $attempt->getId(),
                        'reason' => 'Holiday today!',
                    ]);

                return false;
            }
            else
            {
                return $this->isNeftRtgsSupportedTimings($attempt, $mode, $amount);
            }
        }

        // This Checks the List of Holidays (Inside settlement holiday list)
        // If the source is other than RxPayout e.g: settlement, refunds etc
        if ($this->isWorkingDay === false)
        {
            $this->trace->info(
                TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED,
                [
                    'reason'     => 'Holiday today!',
                    'attempt_id' => $attempt->getId(),
                ]);

            return false;
        }

        return $this->isNeftRtgsSupportedTimings($attempt, $mode, $amount);
    }

    /**
     * To check if timings are supported for Given Mode i.e. NEFT, RTGS
     *
     * @param Attempt\Entity $attempt
     * @param $mode
     * @param $amount
     * @return bool
     */
    protected function isNeftRtgsSupportedTimings(Attempt\Entity $attempt, $mode, $amount): bool
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        // For RTGS
        if ($mode === Mode::RTGS)
        {
            if (($currentTime >= $this->bankingStartTimeRtgs) and
                ($currentTime <= $this->bankingEndTimeRtgs))
            {
                return true;
            }

            return false;
        }

        // For NEFT and IFT
        if (($currentTime >= $this->bankingStartTime) and
            ($currentTime <= $this->bankingEndTime))
        {
            return true;
        }

        $this->trace->info(
            TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED,
            [
                'attempt_id'            => $attempt->getId(),
                'amount'                => $amount,
                'mode'                  => $mode,
                'current_time'          => $currentTime,
                'banking_start_time'    => $this->bankingStartTime,
                'banking_ending_time'   => $this->bankingEndTime,
            ]);

        return false;
    }
}
