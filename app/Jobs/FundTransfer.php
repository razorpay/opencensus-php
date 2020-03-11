<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\FundTransfer as FTA;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\BankAccount\Beneficiary;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Initiator;
use RZP\Models\FundAccount\Type as FundAccountType;
use RZP\Models\NodalBeneficiary\Status as BeneficiaryStatus;

class FundTransfer extends Job
{
    const MUTEX_LOCK_TTL        = 45;

    const MAX_ALLOWED_ATTEMPTS  = 10;

    const RELEASE_WAIT_SECS     = 30;

    /**
     * @var string
     */
    protected $queueConfigKey = 'instant_fund_transfer';

    /**
     * @var string
     */
    protected $ftaId;

    //
    // Yesbank upi request timeout is 180 sec
    // so job timeout should be more than that
    //
    public $timeout = 200;

    /**
     * @bool forceFlag
     *
     * adding default value for backward comparability at the time out roll out
     */
    protected $forceFlag = false;

    public function __construct(string $mode, string $ftaId, bool $forceFlag = false)
    {
        parent::__construct($mode);

        $this->ftaId = $ftaId;

        $this->forceFlag = $forceFlag;
    }

    public function handle()
    {
        $ftaInitiator = new Initiator;

        $data = [
            'fta_id' => $this->ftaId
        ];

        try
        {
            parent::handle();

            $fta = $this->repoManager
                        ->fund_transfer_attempt
                        ->findByIdWithStatus($this->ftaId, Status::CREATED);

            if ($fta === null)
            {
                $this->logAndDelete(['fta_id' => $this->ftaId], TraceCode::FTA_NOT_FOUND);

                return;
            }

            $channel     = $fta->getChannel();

            $accountEntity = null;

            $accountType = FundAccountType::BANK_ACCOUNT;

            if ($fta->hasCard() === true)
            {
                $accountEntity = $fta->card;

                $accountType = FundAccountType::CARD;
            }
            else if($fta->hasVpa() === true)
            {
                $accountEntity = $fta->vpa;

                $accountType = FundAccountType::VPA;
            }
            else if($fta->hasBankAccount() === true)
            {
                $accountEntity = $fta->bankAccount;
            }
            else
            {
                $this->trace->info(
                    TraceCode::ACCOUNT_NOT_FOUND_FOR_FUND_TRANSFER,
                    [
                        'fta_id' => $this->ftaId,
                    ]);

                return;
            }

            $data = [
                'fta_id'  => $fta->getId(),
                'source'  => $fta->getSourceId(),
                'channel' => $channel,
            ];

            $allowedChannels = Settlement\Channel::getInstantPayoutChannels();

            if (in_array($channel, $allowedChannels, true) === false)
            {
                $this->logAndDelete($data, TraceCode::FTA_CHANNEL_NOT_SUPPORTED);

                return;
            }

            $shouldReturn = false;

            if (in_array($accountType, [FundAccountType::BANK_ACCOUNT, FundAccountType::CARD], true) === true)
            {
                $shouldReturn = $this->checkBeneficiaryRegistrationAndVerification($fta, $channel, $data, $accountEntity, $accountType);
            }

            if ($shouldReturn === true)
            {
                return;
            }

            $this->setModeOfFtaInitiator($ftaInitiator);

            $ftaInitiator->initFundTransferOnChannel($fta, $channel, $this->forceFlag);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::FTA_PROCESSING_FOR_MERCHANT_FAILED,
                [
                    'fta_id' => $this->ftaId
                ]);

            (new SlackNotification)->send('FundTransfer processing failed', $data, $e);

            $this->logAndDelete($data);

            return;
        }
    }

    /**
     * @param array $data
     * @param string $traceCode
     * @param Attempt\Entity $fta
     */
    public function checkRetryOrDelete(array $data, $traceCode, Attempt\Entity $fta)
    {
        // Functional test cases gets failed due to checkRetryOrDelete
        // gets called in sync hence returning false in test mode
        if ($this->mode === Mode::TEST)
        {
            return false;
        }

        if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->logAndDelete($data, $traceCode, true);
        }
        else
        {
            $this->logAndDelete($data, $traceCode);

            return $this->isWithInFtaSla($fta);
        }

        return true;
    }

    protected function logAndDelete(
        array $data,
        string $traceCode = TraceCode::FTA_DISPATCH_FOR_MERCHANT_DELETED,
        bool $soft = false)
    {
        $this->trace->info($traceCode, $data);

        if ($soft === true)
        {
            $this->release(self::RELEASE_WAIT_SECS);
        }
        else
        {
            $this->delete();
        }
    }

    /**
     * Checks if Beneficiary Registration or Verification is required
     * then dispatch it for the same and wait for the RELEASE_WAIT_SECS.
     *
     * @param       $fta
     * @param       $channel
     * @param array $data
     * @param $accountEntity
     * @return bool
     */
    public function checkBeneficiaryRegistrationAndVerification($fta, $channel, array $data, $accountEntity, $accountType)
    {
        // Checks if registration is required based on product and account type
        $isBeneRegistrationRequired = $fta->isBeneRegistrationRequired();

        if ($isBeneRegistrationRequired === true)
        {
            $beneficiaryStatus = (new Beneficiary)->getBeneficiaryStatus($channel, $accountEntity, $accountType);

            if ($beneficiaryStatus !== BeneficiaryStatus::VERIFIED and
                $beneficiaryStatus !== BeneficiaryStatus::REGISTERED)
            {
                (new Beneficiary)->dispatchBankAccountForBeneficiaryRegistration($accountEntity, $channel, $accountType);

                return $this->checkRetryOrDelete($data, TraceCode::FTA_BENEFICIARY_NOT_REGISTERED, $fta);
            }

            if ($beneficiaryStatus !== BeneficiaryStatus::VERIFIED)
            {
                (new Beneficiary)->dispatchBankAccountForBeneficiaryVerification($accountEntity, $channel, $accountType);

                return $this->checkRetryOrDelete($data, TraceCode::FTA_BENEFICIARY_NOT_VERIFIED, $fta);
            }
        }

        return false;
    }

    /**
     * rzp.mode is set by basicAuth. Since an instance of initiator is being created from job
     * so, any method or sub-method calls within initiator will have $app[rzp.mode] = null
     * Hence , the following.
     *
     * @param Initiator $ftaInitiator
     */
    public function setModeOfFtaInitiator(Initiator $ftaInitiator)
    {
        if ($this->mode !== null)
        {
            $this->trace->info(
                TraceCode::FTA_MODE_SET,
                [
                    'fta_id' => $this->ftaId,
                    'mode' => $this->mode
                ]);

            $ftaInitiator->setModeAndDefaultConnection($this->mode);
        }
        else
        {
            $this->trace->info(
                TraceCode::FTA_MODE_NOT_FOUND,
                [
                    'fta_id' => $this->ftaId,
                    'mode' => $this->mode
                ]);
        }
    }

    private function isWithInFtaSla(Attempt\Entity $fta): bool
    {
        // SLA in seconds
        $sla = (new Admin\Service)->getConfigKey(['key' => ConfigKey::RX_SLA_FOR_IMPS_PAYOUT]);

        $currentTime = Carbon::now()->getTimestamp();

        $duration = $currentTime - $fta->getCreatedAt();

        if ((empty($sla) === false) and
            (((int) $sla) <= $duration))
        {
            $this->trace->info(
                TraceCode::FTA_SLA_EXPIRED,
                [
                    'fta_id'   => $this->ftaId,
                    'mode'     => $this->mode,
                    'sla'      => $sla,
                    'duration' => $duration,
                ]);

            $this->trace->count(
                Attempt\Metric::FTA_SLA_EXPIRED,
                [
                    Attempt\Metric::SLA => $sla,
                ]);

            return false;
        }

        return true;
    }
}
