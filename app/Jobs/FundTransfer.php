<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Beneficiary;
use RZP\Models\FundTransfer\Attempt\Status;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Initiator;
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

    public function __construct(string $mode, string $ftaId)
    {
        parent::__construct($mode);

        $this->ftaId = $ftaId;
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

            $bankAccount = $fta->bankAccount;

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

            $shouldReturn = $this->checkBeneficiaryRegistrationAndVerification($fta, $bankAccount, $channel, $data);

            if ($shouldReturn === true)
            {
                return;
            }

            /**
             * rzp.mode is set by basicAuth. Since an instance of initiator is being created from job
             * so, any method or sub-method calls within initiator will have $app[rzp.mode] = null
             * Hence , the following.
             */
            if ($this->mode !== null)
            {
                $this->trace->info(
                    TraceCode::FTA_MODE_SET,
                    [
                        'fta_id' => $this->ftaId,
                        'mode'   => $this->mode
                    ]);

                $ftaInitiator->setModeAndDefaultConnection($this->mode);
            }
            else
            {
                $this->trace->info(
                    TraceCode::FTA_MODE_NOT_FOUND,
                    [
                        'fta_id' => $this->ftaId,
                        'mode'   => $this->mode
                    ]);
            }

            $ftaInitiator->initFundTransferOnChannel($fta, $channel);
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
     */
    public function checkRetryOrDelete(array $data)
    {
        $traceCode = TraceCode::FTA_BENEFICIARY_NOT_REGISTERED;

        if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->logAndDelete($data, $traceCode, true);
        }
        else
        {
            (new SlackNotification)->send('Fund transfer not initiated due to beneficiary registration failure', $data, null, 1);

            $this->logAndDelete($data, $traceCode);
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
     * @param $fta
     * @param $bankAccount
     * @param $channel
     * @param array $data
     */
    public function checkBeneficiaryRegistrationAndVerification($fta, $bankAccount, $channel, array $data)
    {
        // Checks if registration is required based on product and account type
        $isBeneRegistrationRequired = $fta->isBeneRegistrationRequired();

        if ($isBeneRegistrationRequired === true) {
            $beneficiaryStatus = (new Beneficiary)->getBeneficiaryStatus($bankAccount,
                $channel);

            if ($beneficiaryStatus !== BeneficiaryStatus::VERIFIED and
                $beneficiaryStatus !== BeneficiaryStatus::REGISTERED) {
                (new Beneficiary)->dispatchBankAccountForBeneficiaryRegistration($bankAccount, $channel);

                return $this->checkRetryOrDelete($data);
            }

            if ($beneficiaryStatus !== BeneficiaryStatus::VERIFIED) {
                (new Beneficiary)->dispatchBankAccountForBeneficiaryVerification($bankAccount, $channel);

                return $this->checkRetryOrDelete($data);
            }
        }

        return false;
    }
}
