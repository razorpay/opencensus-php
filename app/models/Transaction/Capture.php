<?php

namespace Models\Transaction;

use Models\Merchant;
use Models\Transaction;
use Trace\TraceCode;

class Capture extends Action
{
    /**
     * Capture a previous auth transaction
     *
     * @param  string              $id  Id of txn to be captured
     *
     * @return Transaction\Entity       Transaction\Entity object
     */
    public function process($id, array $input = array())
    {
        $txn = $this->retrieve($id);

        Transaction\Validator::captureValidate($txn, $input);

        $data = array(
                    'txn' => $txn->toArrayWithCard(),
                    'amount' => $input['amount']);

        $txn->setCaptureAmount($input['amount']);

        try
        {
            $this->callGatewayFunction(Transaction\Action::CAPTURE, $data);

            $this->recordCapture();

            //
            // Analytics
            //
            $this->dashboardQueueRecord($txn);
        }
        catch (BaseException $e)
        {
            $this->updateTransactionFailed(
                    $e->getError(),
                    TraceCode::TRANSACTION_CAPTURE_FAILURE);

            throw $e;
        }

        return $txn;
    }

    protected function recordCapture()
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->txn->getKey());

            // (new Ledger\Core)->recordCapture($this->$txn);

            $this->updateTransactionCaptured();

            $this->txn->save();
        });
    }


    protected function updateTransactionCaptured()
    {
        $this->txn->setStatus(Transaction\Status::CAPTURED);

        $this->trace(TraceCode::TRANSACTION_CAPTURE_SUCCESS);
    }
}
