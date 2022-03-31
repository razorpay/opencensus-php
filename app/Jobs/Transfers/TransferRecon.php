<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Service as Transfers;

class TransferRecon extends Job
{
    protected $txnIds;

    protected $merchantId;

    protected $queueConfigKey = 'transfer_settlement';

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 900;

    public function __construct($txnIds, string $mode)
    {
        parent::__construct($mode);

        $this->txnIds = $txnIds;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        try
        {
            if (isset($this->txnIds['transaction_ids']) === true)
            {
                (new Transfers())->updateTransfersWithSettlementId($this->txnIds['transaction_ids']);
            }
            else if (isset($this->txnIds['settlement_id']) === true)
            {
                (new Transfers())->triggerTransferSettledWebhook($this->txnIds['settlement_id']);
            }
            else
            {
                $this->trace->info(
                TraceCode::TRANSFER_RECON_JOB_UNEXPECTED,
                [
                    'transaction_ids'    => $this->txnIds,
                ]);
            }
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
