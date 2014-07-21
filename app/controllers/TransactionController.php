<?php

use Http\ApiResponse;
use Models\Transaction\Service as Transaction;

class TransactionController extends BaseController
{
    protected $merchantId = null;

    public function __construct()
    {
        $this->merchantId = BasicAuth::getMerchantId();
    }

    public function getTxnById($id)
    {
        $txn = (new Transaction)->retrieveById($id, $this->merchantId);

        return ApiResponse::json($txn);
    }

    /**
     * Retrieves transaction details
     */
    public function getMultipleTxn()
    {
        $input = Input::all();

        $input['merchant_id'] = $this->merchantId;

        $txns = (new Transaction)->retrieveMultiple($input);

        return ApiResponse::json($txns);
    }

    /**
     * Create a new transaction.
     */
    public function postIndex()
    {
        $input = Input::all();

        $input['merchant_id'] = $this->merchantId;

        $txnData = (new Transaction)->process($input);

        //
        // Check for call from API
        //
        if (Request::header('Razorpay-API') != 1 && isset($txnData['callbackUrl']))
        {
        	return View::make('hdfc.enrollResponse')
                ->with('data', $txnData['data'])
                ->with('callbackUrl',$txnData['callbackUrl']);
        }

        return ApiResponse::json($txnData);
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

        $txn = (new Transaction)->process($input);

        return ApiResponse::json($txn);
    }

    /**
    * Refund a transaction.
    */
    public function postRefund($id)
    {
        $txn = (new Transaction)->refund($id, $this->merchantId);

        return ApiResponse::json($txn);
    }

    /**
     * Captures a specific transaction which was
     * auth earlier
     */
    public function postCapture($id)
    {
        $input = Input::get();

        $txn = (new Transaction)->capture($id, $this->merchantId, $input);

        return ApiResponse::json($txn);
    }

    public function postCallback($id)
    {
        $input = Input::get();

        $data = null;

        \App::foregetMiddleware('Illuminate\Http\FrameGuard');

        try
        {
            $data = (new Transaction)->bankAcsCallback($id, $this->merchantId, $input);
        }
        catch (\EE\Exception\RecoverableException $exception)
        {
            $error = $exception->getPublicError();

            $data = $error->toArray();
            $data['http_status_code'] = $error->getHttpStatusCode();
        }
        finally
        {
            return View::make('gateway.callback')->with('data', $data);
        }
    }
}
