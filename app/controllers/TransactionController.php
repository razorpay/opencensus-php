<?php

use Models\Service\Transaction;
use Models\Service\BasicAuth;
use Constants\Field;

class TransactionController extends BaseController
{
    protected $basicAuth = null;
    protected $merchantId = null;

    public function __construct()
    {
        $this->basicAuth = BasicAuth::getInstance();
        $this->merchantId = $this->basicAuth->getMerchantId();
    }

    public function getTxnById($id)
    {
        $txn = (new Transaction)->retrieveById($id, $this->merchantId);

        return Response::json($txn);
    }

    /**
     * Retrieves transaction details
     */
    public function getMultipleTxn()
    {
        $input = Input::all();

        $input['merchant_id'] = $this->merchantId;

        $txns = (new Transaction)->retrieveMultiple($input);

        return Response::json($txns);
    }

    /**
    * Create a new transaction.
    */
    public function postIndex()
    {
        $input = Input::all();

        $input[Field\Common::MERCHANT_ID] = $this->merchantId;

        $txnData = Transaction::getNewInstance()->process($input);

        //
        // Check for call from API
        //
        if(Request::header('Razorpay-API') != 1 && isset($txnData['callbackUrl']))
        {
        	return View::make('hdfc.enrollResponse')
                ->with('data', $txnData['data'])
                ->with('callbackUrl',$txnData['callbackUrl']);
        }

        return Response::json($txnData);
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

        return Response::json($txn)->setCallback(Input::get('callback'));
    }

    /**
    * Refund a transaction.
    */
    public function postRefund($id)
    {
        $txn = (new Transaction)->refund($id, $this->merchantId);

        return Response::json($txn);
    }

    /**
     * Captures a specific transaction which was
     * auth earlier
     */
    public function postCapture($id)
    {
        $input = Input::get();

        $txn = (new Transaction)->capture($id, $this->merchantId, $input);

        return Response::json($txn);
    }

    /**
    * List previous refunds.
    */
    public function getRefund()
    {
        ;
    }

    /**
    * To list only successful transactions.
    */
    public function getProcess()
    {
        ;
    }

    public function postCallback($id)
    {
        $input = Input::all();

        $data = (new Transaction)->bankAcsCallback($id, $input);

        return View::make('gateway.callback')->with('data', $data);
    }
}
