<?php

use Models\Service\Transaction;
use Models\Service\BasicAuth;
use Constants\Field;
use Models\Manager\TransactionStatus;

class TransactionController extends BaseController
{
    public function getTxnById($id)
    {
        $merchantId = BasicAuth::getInstance()->MerchantId();

        $txn = (new Transaction)->retrieveById($id, $merchantId);

        return Response::json($txn);
    }

    /**
     * Retrieves transaction details
     */
    public function getMultipleTxn()
    {
        $input = Input::all();

        BasicAuth::getInstance()->getMerchantIdInArray($input);

        $txns = (new Transaction)->retrieveMultiple($input);

        return Response::json($txns);
    }

    /**
    * Create a new transaction.
    */
    public function postIndex()
    {
        $input = Input::all();

        $input[Field\Common::MERCHANT_ID] = BasicAuth::getInstance()->MerchantId();

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

        $input['merchant_id'] = BasicAuth::getInstance()->MerchantId();

        $txn = (new Transaction)->process($input);

        return Response::json($txn)->setCallback(Input::get('callback'));
    }

    /**
    * Refund a transaction.
    */
    public function postRefund($id)
    {
        $merchantId = BasicAuth::getInstance()->MerchantId();

        $txn = (new Transaction)->refund($id, $merchantId);

        return Response::json($txn);
    }

    /**
     * Captures transactions from the past 1 day
     *
     */
    // public function capture()
    // {
    //     $txn_service = new Transaction();
    //
    //     $txn_array = $txn_service->retrieveUncaptured();
    //
    //     foreach ($txn_array as $txn)
    //     {
    //         $txn_service->capture($txn);
    //     }
    //
    //     return Response::json($txn_array);
    // }

    /**
     * Captures a specific transaction which was
     * auth earlier
     */
    public function postCapture($id)
    {
        $merchantId = BasicAuth::getInstance()->MerchantId();

        $txn = (new Transaction)->capture($id, $merchantId);

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
