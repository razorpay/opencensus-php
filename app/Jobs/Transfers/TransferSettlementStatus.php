<?php


namespace RZP\Jobs\Transfers;


use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Transfer\Core;
use RZP\Exception\SettlementStatusUpdateException;

class TransferSettlementStatus extends Job
{
    const DISPATCH_DELAY_SECONDS = 300;

    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 3;

    /**
     * This job will be terminated after <$timeout> seconds.
     * @var int
     */
    public $timeout = 900;

    protected $queueConfigKey = 'transfer_settlement';

    protected $settlementId;

    public function __construct(string $mode, string $settlementId)
    {
        parent::__construct($mode);

        $this->settlementId = $settlementId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::SETTLEMENT_STATUS_UPDATE_IN_TRANSFERS_REQUEST,
            [
                'settlement_id' => $this->settlementId,
            ]
        );

        try
        {
            (new Core())->updateSettlementStatusInTransfers($this->settlementId);

            $this->delete();
        }
        catch (\Exception $ex)
        {
            if ($ex instanceof SettlementStatusUpdateException)
            {
                $this->checkRetry();
            }
            else
            {
                $this->trace->traceException(
                    $ex,
                    Logger::ERROR,
                    TraceCode::SETTLEMENT_STATUS_UPDATE_IN_TRANSFER_FAILED,
                    [
                        'settlement_id' => $this->settlementId,
                    ]
                );
            }
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::SETTLEMENT_STATUS_UPDATE_RETRY_EXHAUSTED,
                [
                    'settlement_id' => $this->settlementId,
                ]
            );

            $this->delete();
        }
        else
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_STATUS_UPDATE_RETRY_DISPATCH,
                [
                    'settlement_id' => $this->settlementId,
                ]
            );

            $this->release(self::RETRY_INTERVAL);
        }
    }
}
