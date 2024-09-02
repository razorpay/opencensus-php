<?php

namespace RZP\Listeners;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Balance;
use RZP\Jobs\TransactionBalanceReadWriteLoggingJob;
use RZP\Trace\TraceCode;

class BalanceEventListener
{
    public function onRetrieved(Balance\EventRetrieved $event)
    {
        if ((app()->runningUnitTests() === true) or (app()->runningInQueue() === true))
        {
            return;
        }

        $mode = $this->getMode();

        if ($mode === Mode::TEST)
        {
            return;
        }

        try
        {
            $rand = rand(1,500000);

            if ($rand > 100000)
            {
                return;
            }

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
                'event_entity'              => $event->entity->attributesToArray(),
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'balance',
                'route'                     => app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName(),
            ];

            TransactionBalanceReadWriteLoggingJob::dispatch($input, $mode);
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::TRANSACTIONS_BALANCE_DISPATCH_EXCEPTION, []);
        }
    }

    public function onSaved(Balance\EventSaved $event)
    {
        if ((app()->runningUnitTests() === true) or (app()->runningInQueue() === true))
        {
            return;
        }

        $mode = $this->getMode();

        if ($mode === Mode::TEST)
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
                'event_entity'              => $event->entity->attributesToArray(),
                'is_ledger_dual_write_flow' => $isDualWriteFlow,
                'entity'                    => 'balance',
                'route'                     => app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName(),
            ];

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
