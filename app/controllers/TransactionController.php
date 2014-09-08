<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Transaction;

class TransactionController extends BaseController
{
    protected $merchantId = null;

    protected $merchant = null;

    protected $transaction;

    public function __construct()
    {
        $this->merchant = BasicAuth::getMerchant();

        $this->merchantId = $this->merchant->getKey();

        $this->transaction = new Transaction\Service();
    }

    public function getTransaction($id)
    {
        $txn = $this->transaction->retrieveByIdAndMerchantId(
                                        $id, $this->merchant->getKey());

        return ApiResponse::json($txn);
    }

    /**
     * Retrieves transaction details
     */
    public function getTransactions()
    {
        $input = Input::all();

        $input['merchant_id'] = $this->merchantId;

        $txns = $this->transaction->retrieveMultiple($input);

        return ApiResponse::json($txns);
    }

    /**
     * Create a new transaction
     */
    public function postCreateTransaction()
    {
        $input = Input::all();

        $input['merchant_id'] = $this->merchantId;

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

        $input['merchant_id'] = $this->merchantId;

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

        App::forgetMiddleware('Illuminate\Http\FrameGuard');

        try
        {
            $data = $this->transaction->bankAcsCallback($id, $input);
        }
        catch (RecoverableException $exception)
        {
            if (App::runningUnitTests())
                throw $exception;

            $error = $exception->getError();

            $data = $error->toPublicArray();
            $data['http_status_code'] = $error->getHttpStatusCode();
        }
        finally
        {
            if ($data !== null)
                return View::make('gateway.callback')->with('data', $data);
        }
    }

    public function getRefundsForTransaction($txnId)
    {
        $refunds = $this->transaction->retrieveRefundsForTransaction($txnId);

        return ApiResponse::json($refunds);
    }
}
