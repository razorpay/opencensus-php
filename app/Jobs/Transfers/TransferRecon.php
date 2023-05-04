<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Service as Transfers;
use RZP\Exception\SettlementIdUpdateException;

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

    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 3;

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

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->critical(
                TraceCode::UPDATE_SETTLEMENT_TRANSFER_FAILED,
                [
                    'message'    => $e->getMessage(),
                ]);

            if (($e instanceof SettlementIdUpdateException) and ($e->shouldRetrySameJob() === true))
            {
                $this->checkRetry();
            }
            else if(($e instanceof SettlementIdUpdateException) and ($e->shouldRetrySameJob() === false))
            {
                $this->delete();

                $input['transaction_ids'] = $e->getFailedTransactionIds();

                $this->trace->info(
                    TraceCode::TRANSFER_RECON_JOB_RETRY_DISPATCH,
                    [
                        'transaction_ids'    => $input['transaction_ids']
                    ]);

                TransferRecon::dispatch($input, $this->mode);
            }
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::TRANSFER_RECON_JOB_RETRY_EXHAUSTED,
                [
                    'transaction_ids'    => $this->txnIds
                ]);

            $this->delete();
        }
        else
        {
            $this->trace->info(
                TraceCode::TRANSFER_RECON_JOB_RETRY_DISPATCH,
                [
                    'transaction_ids'    => $this->txnIds
                ]);

            $this->release(self::RETRY_INTERVAL);
        }
    }
}
