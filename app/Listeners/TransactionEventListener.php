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
        if (app()->runningUnitTests() === true)
        {
            return;
        }

        try
        {
            $requestCtx = app('request.ctx');

            $workerCtx = app('worker.ctx');

            $isDualWriteFlow = $requestCtx->getLedgerDualWriteFlow() OR $workerCtx->getLedgerDualWriteFlow();

            $input = [
                'event_name'                => 'onRetrieved',
                'event_entity'              => $event->entity,
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'transactions',
                'route'                     => app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName(),
            ];

            TransactionBalanceReadWriteLoggingJob::dispatch($input, $this->getMode());
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::TRANSACTIONS_BALANCE_DISPATCH_EXCEPTION, []);
        }
    }

    public function onSaved(Transaction\EventSaved $event)
    {
        if (app()->runningUnitTests() === true)
        {
            return;
        }

        try
        {
            $requestCtx = app('request.ctx');

            $workerCtx = app('worker.ctx');

            $isDualWriteFlow = $requestCtx->getLedgerDualWriteFlow() OR $workerCtx->getLedgerDualWriteFlow();

            $input = [
                'event_name'                => 'onSaved',
                'event_entity'              => $event->entity,
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'transactions',
                'route'                     => app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName(),
            ];

            TransactionBalanceReadWriteLoggingJob::dispatch($input, $this->getMode());
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
