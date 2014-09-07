<?php

namespace Models\Transaction;

use Models\Merchant;
use Models\Transaction;
use Trace\TraceCode;

class Refund extends Action
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

        Transaction\Validator::refundValidate($txn, $input);

        $data = array(
                    'txn' => $txn->toArrayWithCard(),
                    'amount' => $txn['amount']);

        try
        {
            $this->callGatewayFunction(Transaction\Action::REFUND, $data);

            $this->recordRefund($input);

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

    protected function recordRefund($input)
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->txn->getKey());

            // (new Ledger\Core)->recordRefund($this->txn);

            $this->updateTransactionRefunded($input);

            $this->txn->save();
        });
    }

    protected function updateTransactionRefunded($input)
    {
        $amountRefunded = $txn->getAmountRefunded();
        $amountCaptured = $txn->getAmount();
        $amountToRefund = $input['input'];

        $refundStatus = RefundStatus::PARTIAL;

        if ($amountToRefund === ($amountCaptured + $amountRefunded))
        {
            $refundStatus = RefundStatus::FULL;
        }

        $amountRefunded += $amountToRefund;

        $this->txn->setRefundStatus($refundStatus);
        $this->txn->setAmountRefunded($amountRefunded);

        $this->createRefundEntity($input);

        $this->trace(TraceCode::TRANSACTION_REFUND_SUCCESS);
    }
}
