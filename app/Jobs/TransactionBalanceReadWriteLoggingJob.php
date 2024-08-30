<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use Razorpay\Trace\Logger as Trace;

class TransactionBalanceReadWriteLoggingJob extends Job
{
    protected $input;

    protected $queueConfigKey = 'transaction_balance_read_write_logging';

    public function __construct(array $input, $mode)
    {
        parent::__construct($mode);

        $this->input = $input;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            app('trace')->info(TraceCode::TRANSACTIONS_EVENT_INPUT,
                [
                    'input' => $this->input,
                ]);

            if ((isset($this->input['event_entity']) === true) and ($this->input['event_name'] === 'onRetrieved') and ($this->input['entity'] === 'transactions'))
            {
                $entity = $this->input['event_entity'];

                $route = $this->input['route'];

                $isLedgerDualWriteFlow = $this->input['is_ledger_dual_write_flow'];

                (new Transaction\Service())->logTransactionReads($entity, $isLedgerDualWriteFlow, $route);
            }
            else if ((isset($this->input['event_entity']) === true) and ($this->input['event_name'] === 'onSaved') and ($this->input['entity'] === 'transactions'))
            {
                $entity = $this->input['event_entity'];

                $route = $this->input['route'];

                $isLedgerDualWriteFlow = $this->input['is_ledger_dual_write_flow'];

                (new Transaction\Service())->logTransactionWrites($entity, $isLedgerDualWriteFlow, $route);
            }
            else if ((isset($this->input['event_entity']) === true) and ($this->input['event_name'] === 'onRetrieved') and ($this->input['entity'] === 'balance'))
            {
                $entity = $this->input['event_entity'];

                $route = $this->input['route'];

                $isLedgerDualWriteFlow = $this->input['is_ledger_dual_write_flow'];

                (new Balance\Service())->logBalanceReads($entity, $isLedgerDualWriteFlow, $route);
            }
            else if ((isset($this->input['event_entity']) === true) and ($this->input['event_name'] === 'onSaved') and ($this->input['entity'] === 'balance'))
            {
                $entity = $this->input['event_entity'];

                $route = $this->input['route'];

                $isLedgerDualWriteFlow = $this->input['is_ledger_dual_write_flow'];

                (new Balance\Service())->logBalanceWrites($entity, $isLedgerDualWriteFlow, $route);
            }
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException($e, Trace::ERROR, TraceCode::TRANSACTIONS_BALANCE_EVENT_EXCEPTION, []);
        }
    }
}
