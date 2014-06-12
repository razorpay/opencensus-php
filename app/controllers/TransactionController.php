<?php

use Models\Service\Transaction;
use Models\Service\BasicAuth;

class TransactionController extends BaseController
{
    /**
    * Retrieves transaction details by `id`
    * Lists previous transactions if `id` not provided
    *
    * @param token (optional)
    *
    */
    public function getIndex($param = null)
    {
        $merchant_id = BasicAuth::getInstance()->MerchantId();
        $merchant = BasicAuth::getInstance()->Merchant();

        $input = Input::all();
        $input['merchant_id'] = $merchant_id;

        switch($param)
        {
            case 'uncaptured':
                //uncaptured is same as auth
                $param = 'auth';
            case 'open':
            case 'auth':
            case 'captured':
            case 'settled':
                //For all above set the status parameter
                $input['status'] = $param;
            case NULL:
                //Common for all above
                $txn_service = new Transaction();

                $txn_data = $txn_service->retrieveMultiple($input);

                //dd($txn_data);

                return Response::json($txn_data);
            break;

            //Case for checking if a valid UUID is given for transaction id
            case (preg_match("/^[0-9a-f]{8}[0-9a-f]{4}[1-5][0-9a-f]{3}[89ab][0-9a-f]{3}[0-9a-f]{12}$/i", $param) ? true : false ) :
                $transactionObject = new Transaction;
                $transaction=$transactionObject->retrieve($param);

                if ($transaction === null)
                {
                    return Response::view('error.404', array(), 404);
                }
                else
                {
                    if ((int)$transaction->merchant->id === $merchant_id)
                    {
                        return Response::json($transaction);
                    }
                    else
                    {
                        return Response::view('error.401', array(), 401);
                    }
                }
                break;

            default:
               return Response::view('error.401', array(), 401);
               break;

        }

    }

    /**
    * Create a new transaction.
    */
    public function postIndex()
    {
        $input = Input::all();

        $input['merchant_id'] = BasicAuth::getInstance()->MerchantId();

        $txn_data = Transaction::getNewInstance()->process($input);

        //Check for call from API
        if(Request::header('Razorpay-API') != 1 && isset($txn_data['callbackUrl']))
        {
        	return View::make('hdfc.enrollResponse')
                ->with('data', $txn_data['data'])
                ->with('callbackUrl',$txn_data['callbackUrl']);
        }
        return Response::json($txn_data);

    }

    /**
     * Creates a new transaction on a JSONP Request
     */
    public function getJSONP()
    {
        $input = Input::all();
        unset($input['callback']);
        unset($input['key']);
        unset($input['_']);
        try{
            $input['merchant_id'] = BasicAuth::getInstance()->MerchantId();        
            $txn_data = Transaction::getNewInstance()->process($input);
            return Response::json($txn_data)->setCallback(Input::get('callback'));
        }
        catch(Exception $e){
            if('dev' === app()->env){
                return Response::json([
                    'exception'=>$e->getMessage(),
                    'file'=>$e->getFile(),
                    'line'=>$e->getLine(),
                    'code'=>$e->getCode(),
                    'trace'=>$e->getTrace()
                ])->setCallback(Input::get('callback'));
            }
            else{
                return Response::json([
                    'exception'=>'An error occured'
                ])->setCallback(Input::get('callback'));
            }
        }
    }

    /**
    * Refund a transaction.
    */
    public function postRefund($id)
    {

        $txn_service = new Transaction();

        $txn_data = $txn_service->retrieve($id);

        $merchant_id = BasicAuth::getInstance()->MerchantId();

        if ($merchant_id !== $txn_data->getMerchantId())
            return Response::view('error.404', array(), 404);

        $txn_service->refund($txn_data);

        return Response::json($txn_data);
    }

    /**
     * Captures transactions from the past 1 day
     *
     */

    public function capture()
    {
        $txn_service = new Transaction();

        $txn_array = $txn_service->retrieveUncaptured();

        foreach ($txn_array as $txn)
        {
            $txn_service->capture($txn);
        }

        return Response::json($txn_array);
    }

    /**
     * Captures a specific transaction which was put on hold earlier
     *
     */
    public function postCapture($id)
    {

        $txnService = new Transaction();

        $txnData = $txnService->capture($id);

        return Response::json($txnData);
    }

    /**
    * List previous refunds.
    */
    public function getRefund()
    {
        ;
    }

    /**
    * To process a transaction and make payments.
    */
    public function postProcess($token = NULL )
    {
        echo 'process: ' . $token;
    }

    /**
    * To list only successful transactions.
    */
    public function getProcess()
    {
        ;
    }

    public function postCallback()
    {
        $input = Input::all();
        $txn_service = new Transaction();
        $data = $txn_service->bankAcsCallback($input);
        return View::make('gateway.callback')->with('data', $data);
    }
}

