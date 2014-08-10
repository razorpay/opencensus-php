<?php

namespace Models\Transaction;

use Models\Merchant;
use Models\Transaction;
use Trace\TraceCode;

class Refund extends Action
{
    /**
     * Refunds a transaction
     * @param  string              $id  Transaction Id
     *
     * @return Transaction\Entity
     */
    public function process($id)
    {
        $txn = $this->retrieve($id);

        Transaction\Validator::refundValidate($txn);

        $data = array(
                    'txn' => $txn->toArrayWithCard(),
                    'amount' => $txn['amount']);

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
        });
    }

    protected function updateTransactionRefunded()
    {
        $this->txn->setStatus(Transaction\Status::REFUNDED);

        $this->trace(TraceCode::TRANSACTION_REFUND_SUCCESS);
    }
}
