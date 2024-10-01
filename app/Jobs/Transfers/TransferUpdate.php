<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Models\Transfer\Entity;
use RZP\Trace\TraceCode;


class TransferUpdate extends Job
{
    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 3;

    protected $input;

    protected $transfer;


    public function __construct(Entity $transfer)
    {
        parent::__construct();

        $this->transfer = $transfer;
    }

    public function handle()
    {
        parent::handle();

        if ($this->transfer->isExternal() === false)
        {
            return;
        }

        $params = $this->repoManager->transfer->getUpdatableTransfersFields($this->transfer);

        $this->trace->info(
            TraceCode::SAVE_TRANSFER_VIA_ROUTE_SERVICE,
            [
                'transfer_id' => $this->transfer->getId(),
                'params'     => $params,
            ]
        );

        try
        {
            app('route')->saveApiTransfer($this->transfer->getId(), $params);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::SAVE_TRANSFER_VIA_ROUTE_SERVICE_FAILURE,
                [
                    'id' => $this->transfer->getId()
                ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        // check if retry is needed
    }

}

