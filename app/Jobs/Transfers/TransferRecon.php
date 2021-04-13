<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Service as Transfers;

class TransferRecon extends Job
{
    protected $settlementIds;

    protected $merchantId;

    protected $queueConfigKey = 'transfer_settlement';

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 900;

    public function __construct($settlementIds, string $mode)
    {
        parent::__construct($mode);

        $this->settlementIds      = $settlementIds;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::MESSAGE_RECEIVED_FROM_TRANSFER_SETTLEMENT_QUEUE,
            [
                'settlement_id' => $this->settlementIds,
                'current_time'  => Carbon::now()->getTimestamp(),
            ]
        );

        try
        {
           (new Transfers)->UpdateTransfersWithSettlementId($this->settlementIds);
        }
        catch (\Throwable $e)
        {
            $this->trace->critical(
                TraceCode::UPDATE_SETTLEMENT_TRANSFER_FAILED,
                [
                    'message'    => $e->getMessage(),
                ]);
        }
        finally
        {
            $this->delete();
        }
    }
}
