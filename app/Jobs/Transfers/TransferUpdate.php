<?php

namespace RZP\Jobs\Transfers;

use App;
use RZP\Constants\Metric;
use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Models\Transfer\Entity;
use RZP\Trace\TraceCode;


class TransferUpdate extends Job
{
    const RETRY_INTERVAL    = 30;
    const MAX_RETRY_ATTEMPT = 15;

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
            $startTime = millitime();

            app('route')->saveApiTransfer($this->transfer->getId(), $params);

            $this->traceSuccessMetrics('transferUpdateJob', millitime()-$startTime);
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

            $this->traceFailureMetrics('transferUpdateJob', millitime()-$startTime);

            $this->checkRetry();
        }
    }
    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_REPO_FETCH_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_REPO_FETCH_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS, [
            'caller'      => $functionName,

        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}

