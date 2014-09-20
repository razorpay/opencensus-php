<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Transaction;

class TransactionController extends BaseController
{
    protected $transaction;

    public function __construct()
    {
        $this->transaction = new Transaction\Service();
    }

    public function getTransaction($id)
    {
        $txn = $this->transaction->retrieveTransaction($id);

        return ApiResponse::json($txn);
    }

    /**
     * Retrieves transaction details
     */
    public function getTransactions()
    {
        $input = Input::all();

        $txns = $this->transaction->retrieveMultiple($input);

        return ApiResponse::json($txns);
    }

    /**
     * Create a new transaction
     */
    public function postCreateTransaction()
    {
        $input = Input::all();

        $data = $this->transaction->process($input);

        //
        // Check for call from API
        //
        if (isset($data['callbackUrl']))
        {
        	return View::make('hdfc.enrollResponse')
                ->with('data', $data['data'])
                ->with('callbackUrl',$data['callbackUrl']);
        }

        return ApiResponse::json($data);
    }

    /**
     * Creates a new transaction on a JSONP Request
     */
    public function getJSONP()
    {
        $input = Input::all();

        unset($input['callback']);
        unset($input['_']);

        $txn = $this->transaction->process($input);

        return ApiResponse::json($txn);
    }

    /**
    * Refund a transaction.
    */
    public function postRefund($id)
    {
        $input = Input::all();

        $txn = $this->transaction->refund($id, $input);

        return ApiResponse::json($txn);
    }

    /**
     * Captures a specific transaction which was
     * auth earlier
     */
    public function postCapture($id)
    {
        $input = Input::all();

        $txn = $this->transaction->capture($id, $input);

        return ApiResponse::json($txn);
    }

    public function postCallback($id)
    {
        $input = Input::all();

        $data = null;

        try
        {
            $data = $this->transaction->bankAcsCallback($id, $input);
        }
        catch (RecoverableException $exception)
        {
            if (App::runningUnitTests())
            {
                throw $exception;
            }

            $error = $exception->getError();

            $data = $error->toPublicArray();
            $data['http_status_code'] = $error->getHttpStatusCode();
        }
        finally
        {
            if ($data !== null)
            {
                return View::make('gateway.callback')->with('data', $data);
            }
        }
    }

    public function getRefundsForTransaction($txnId)
    {
        $refunds = $this->transaction->retrieveRefundsForTransaction($txnId);

        return ApiResponse::json($refunds);
    }

    public function getRefund($id)
    {
        $refunds = $this->transaction->retrieveRefund($id);

        return ApiResponse::json($refunds);
    }

    public function getRefunds()
    {
        $input = Input::all();

        $refunds = $this->transaction->retrieveMultipleRefunds($input);

        return ApiResponse::json($refunds);
    }

    public function getRefundByRefundAndTransactionId($txnId, $rfndId)
    {
        $refunds = $this->transaction->retrieveRefundByIdAndTransactionId($txnId, $rfndId);

        return ApiResponse::json($refunds);
    }
}
