<?php

namespace Models\Transaction\Refund;

use Models\Merchant;
use Models\Transaction;
use Models\Transaction\Action;
use Models\Transaction\Refund;
use Trace\TraceCode;

class Process extends Action
{
    /**
     * Refunds a transaction
     * @param  string   $id  Transaction Id
     *
     * @return Transaction\Entity
     */
    public function process($id, $input)
    {
        $txn = $this->retrieve($id);

        $refund = (new Refund\Entity)->build($input, $txn);

        $refund->merchant()->associate($this->merchant);

        $this->refund = $refund;

        $data = array(
                    'txn' => $txn->toArrayWithCard(),
                    'amount' => $refund->getAmount());

        try
        {
            $this->callGatewayFunction(Transaction\Action::REFUND, $data);

            $this->recordRefund();

            //
            // Analytics
            //
            $this->dashboardQueueRecord($txn);
        }
        catch(BaseException $e)
        {
            $this->traceTransactionFailed(
                    $e->getError(),
                    TraceCode::TRANSACTION_REFUND_FAILURE);

            throw $e;
        }

        return $txn;
    }

    protected function recordRefund()
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->txn->getKey());

            // (new Ledger\Core)->recordRefund($this->txn);

            $this->updateTransactionRefunded();

            $this->txn->save();
            $this->refund->save();
        });
    }

    protected function updateTransactionRefunded()
    {
        $this->txn->refundAmount($this->refund->getAmount());

        $this->trace(TraceCode::TRANSACTION_REFUND_SUCCESS);
    }
}
