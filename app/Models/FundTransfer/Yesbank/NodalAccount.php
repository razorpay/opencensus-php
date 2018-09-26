<?php

namespace RZP\Models\FundTransfer\Yesbank;

use App;
use Carbon\Carbon;
use Config;

use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\BankAccount;
use RZP\Models\FundTransfer\Mode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Attempt\Lock;
use RZP\Models\FundTransfer\Yesbank\Request\Transfer;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
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
     * @return array
     */
    public function process(PublicCollection $attempts): array
    {
        $transfer = new Transfer($this->purpose);

        $processedCount = 0;

        $lock = (new Lock($this->channel));

        $attempts = $lock->lockAttempts($attempts);

        foreach ($attempts as $entity)
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
            $isTransferAllowedToday = $this->isTransferAllowedToday($entity);

            if ($isTransferAllowedToday === false)
            {
                continue;
            }

            try
            {
                // Calling init will reset all the data of previous request
                $response = $transfer->init()
                                     ->setEntity($entity)
                                     ->makeRequest();

                $this->repo->saveOrFail($entity);

                $this->repo->saveOrFail($entity->source);

                $this->trackAttemptsInitiatedSuccess($this->channel, $this->purpose, $entity->getSourceType());
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_TRANSFER_REQUEST_FAILED,
                    [
                        'channel'       => $this->channel,
                        'entity_id'     => $entity->getId(),
                        'settlement_id' => $entity->getSourceId(),
                    ]);

                $lock->releaseAttempt($entity);

                $this->trackAttemptsInitiatedFailure($this->channel, $this->purpose, $entity->getSourceType());

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
            finally
            {
                $lock->releaseAttempt($entity);
            }
        }

        $this->updateTransferStatus($processedCount);

        return $this->transferStatus;
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
        $amount = $attempt->source->getAmount() / 100;

        $mode = $this->getPaymentMode($attempt->bankAccount, $amount);

        $allowedModes = Mode::get24x7TransferModes();

        if (in_array($mode, $allowedModes, true) === true)
        {
            return true;
        }

        if ($this->isWorkingDay === false)
        {
            $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED, [
                'attempt_id'=> $attempt->getId(),
                'reason'    => 'Holiday today!',
            ]);

            return false;
        }

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if (($currentTime >= $this->bankingStartTime) and
            ($currentTime <= $this->bankingEndTime))
        {
            return true;
        }

        $this->trace->info(TraceCode::FUND_TRANSFER_ATTEMPT_INITIATE_SKIPPED, [
            'attempt_id'            => $attempt->getId(),
            'amount'                => $amount,
            'mode'                  => $mode,
            'banking_start_time'    => $this->bankingStartTime,
            'banking_ending_time'   => $this->bankingEndTime,
        ]);

        return false;
    }

    protected function getPaymentMode(BankAccount\Entity $ba, $amount): string
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if (starts_with($ifscFirstFour, static::IFSC_IDENTIFIER) === true)
        {
            return Mode::IFT;
        }
        else if ($amount < self::MAX_IMPS_AMOUNT)
        {
            return Mode::IMPS;
        }

        return $this->getTransferMode($amount, $ba->merchant);
    }
}
