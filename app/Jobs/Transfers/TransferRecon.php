<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Service as Transfers;

class TransferRecon extends Job
{
    protected $settlementIds;

    protected $merchantId;

    protected $queueConfigKey = 'transfer_settlement';

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
    }
}
