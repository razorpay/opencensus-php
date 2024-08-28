<?php

namespace RZP\Listeners;

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
                'event'                     => $event,
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'transactions'
            ];

            TransactionBalanceReadWriteLoggingJob::dispatch($input, $this->getMode());
        }
        catch (\Throwable $e)
        {
            app('trace')->info(TraceCode::TRANSACTIONS_BALANCE_DISPATCH_EXCEPTION,
                [
                    'exception' => $e,
                ]
            );
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
                'event'                     => $event,
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'transactions'
            ];

            TransactionBalanceReadWriteLoggingJob::dispatch($input, $this->getMode());
        }
        catch (\Throwable $e)
        {
            app('trace')->info(TraceCode::TRANSACTIONS_BALANCE_DISPATCH_EXCEPTION,
                [
                    'exception' => $e,
                ]
            );
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
