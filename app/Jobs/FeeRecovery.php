<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FeeRecovery\Core as FeeRecoveryCore;

class FeeRecovery extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const DELAY = 300;

    // Overriding timeout with 300 for the time being, since we don't know how much time the process will take.
    public $timeout = 300;

    protected $trace;

    protected $balanceId;

    protected $startTimeStamp;

    protected $endTimeStamp;

    public function __construct(string $mode, string $balanceId, int $startTimeStamp, int $endTimeStamp)
    {
        parent::__construct($mode);

        $this->balanceId = $balanceId;

        $this->startTimeStamp = $startTimeStamp;

        $this->endTimeStamp = $endTimeStamp;
    }

    public function handle()
    {
        parent::handle();

        $data = [
            'balance_id'    => $this->balanceId,
            'from'          => $this->startTimeStamp,
            'to'            => $this->endTimeStamp,
        ];

        $this->trace->info(
            TraceCode::FEE_RECOVERY_CRON_PROCESS,
            $data);

        try
        {
            $payout = (new FeeRecoveryCore)->createFeeRecoveryPayout($data);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_CRON_SUCCESS,
                [
                    'recovery_payout'   => $payout->toArrayPublic(),
                    'balance_id'        => $this->balanceId,
                ]
            );

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_CRON_FAILURE_DELETE_JOB,
                    $data);

                $operation = 'Fee Recovery job failed thrice';

                (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_ca_rbl_alerts');
            }
            else
            {
                $this->release(self::DELAY);

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_CRON_FAILURE,
                    $data);
            }
        }
    }
}
