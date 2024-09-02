<?php

namespace RZP\Listeners;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Models\Transaction;
use RZP\Jobs\TransactionBalanceReadWriteLoggingJob;
use RZP\Trace\TraceCode;

class TransactionEventListener
{
    public function onRetrieved(Transaction\EventRetrieved $event)
    {
        if ((app()->runningUnitTests() === true) or (app()->runningInQueue() === true))
        {
            return;
        }

        try
        {
            if (app()->runningInQueue() === true)
            {
                $workerCtx = app('worker.ctx');

                $isDualWriteFlow = $workerCtx->getLedgerDualWriteFlow();
            }
            else
            {
                $requestCtx = app('request.ctx');

                $isDualWriteFlow = $requestCtx->getLedgerDualWriteFlow();
            }

            $input = [
                'event_name'                => 'onRetrieved',
                'event_entity'              => $event->entity,
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'transactions',
                'route'                     => app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName(),
            ];

            $mode = $this->getMode();

            TransactionBalanceReadWriteLoggingJob::dispatch($input, $mode);
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::TRANSACTIONS_BALANCE_DISPATCH_EXCEPTION, []);
        }
    }

    public function onSaved(Transaction\EventSaved $event)
    {
        if ((app()->runningUnitTests() === true) or (app()->runningInQueue() === true))
        {
            return;
        }

        try
        {
            if (app()->runningInQueue() === true)
            {
                $workerCtx = app('worker.ctx');

                $isDualWriteFlow = $workerCtx->getLedgerDualWriteFlow();
            }
            else
            {
                $requestCtx = app('request.ctx');

                $isDualWriteFlow = $requestCtx->getLedgerDualWriteFlow();
            }

            $input = [
                'event_name'                => 'onSaved',
                'event_entity'              => $event->entity,
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'transactions',
                'route'                     => app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName(),
            ];

            $mode = $this->getMode();

            TransactionBalanceReadWriteLoggingJob::dispatch($input, $mode);
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::TRANSACTIONS_BALANCE_DISPATCH_EXCEPTION, []);
        }
    }

    protected function getMode()
    {
        try
        {
            $mode = app('rzp.mode');
        }
        catch (\Throwable $e)
        {
            $mode = Mode::LIVE;
        }
        return $mode;
    }
}
