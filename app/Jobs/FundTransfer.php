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

class FundTransfer extends Job
{
    const MUTEX_LOCK_TTL        = 45;

    const MAX_ALLOWED_ATTEMPTS  = 10;

    const RELEASE_WAIT_SECS     = 60;

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

        $delayTransfer = false;

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

            $isBeneRegistrationRequired = $fta->isBeneRegistrationRequired();

            if ($isBeneRegistrationRequired === true)
            {
                $beneficiaryRegistered = (new Beneficiary)->registerBeneficiaryOnChannelAndGetStatus(
                                                                $channel,
                                                                $bankAccount);

                if ($beneficiaryRegistered === false)
                {
                    $this->checkRetryOrDelete($data);

                    return;
                }

                //
                // delaying the transfer only if bene registration is done in this flow
                //
                $delayTransfer = true;
            }

            //
            // Bene registration form YB requires some time (Max observed is 45 sec)
            // Because of this we are adding delay of 60 sec, in case we do bene registration in this flow.
            // TODO: remove this code once verify bene feature is in place
            //
            if ($delayTransfer === true)
            {
                $this->logAndDelete($data, TraceCode::FTA_TRANSFER_JOB_DELAYED, true);
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

            return;
        }
        else
        {
            (new SlackNotification)->send('Fund transfer not initiated due to beneficiary registration failure', $data, null, 1);

            $this->logAndDelete($data, $traceCode);
        }
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
}
