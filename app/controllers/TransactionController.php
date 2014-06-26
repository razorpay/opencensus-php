<?php

use Models\Service\Transaction;
use Models\Service\BasicAuth;
use Constants\Field;
use Models\Manager\TransactionStatus;

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
        $merchantId = BasicAuth::getInstance()->MerchantId();

        $input = Input::all();

        switch($param)
        {
            case TransactionStatus::OPEN:
            case TransactionStatus::AUTH:
            case TransactionStatus::CAPTURED:
            case TransactionStatus::SETTLED:
            case TransactionStatus::FAILED:
                //
                // For all above set the status parameter
                //
                $input[Field\Transaction::STATUS] = $param;

            case null:
                //Common for all above
                BasicAuth::getInstance()->getMerchantIdInArray($input);

                $txnList = (new Transaction)->retrieveMultiple($input);
                return Response::json($txnList);

                break;

            //Case for checking if a valid UUID is given for transaction id
            case (preg_match("/^[0-9a-f]{8}[0-9a-f]{4}[1-5][0-9a-f]{3}[89ab][0-9a-f]{3}[0-9a-f]{12}$/i", $param) ? true : false ) :

                $txn = (new Transaction)->retrieve($param, $merchantId);

                if ($txn === null)
                {
                    return Response::view('error.404', array(), 404);
                }
                else
                {
                    return Response::json($txn);
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

        $input[Field\Common::MERCHANT_ID] = BasicAuth::getInstance()->MerchantId();

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
        unset($input['_']);

        try
        {
            $input['merchant_id'] = BasicAuth::getInstance()->MerchantId();

            $txn_data = (new Transaction)->process($input);

            return Response::json($txn_data)->setCallback(Input::get('callback'));
        }
        catch(Exception $e)
        {
            if(App::environment('dev'))
            {
                return Response::json([
                    'exception'=>$e->getMessage(),
                    'file'=>$e->getFile(),
                    'line'=>$e->getLine(),
                    'code'=>$e->getCode(),
                    'trace'=>$e->getTrace()
                ])->setCallback(Input::get('callback'));
            }
            else
            {
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
        $merchantId = BasicAuth::getInstance()->MerchantId();

        $txn = (new Transaction)->refund($id, $merchantId);

        if ($txn === null)
        {
            return Response::view('error.404', array(), 404);
        }
        else
        {
            return Response::json($txn);
        }
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
     * Captures a specific transaction which was
     * auth earlier
     */
    public function postCapture($id)
    {
        $merchantId = BasicAuth::getInstance()->MerchantId();

        $txn = (new Transaction)->capture($id, $merchantId);

        if ($txn === null)
        {
            return Response::view('error.404', array(), 404);
        }
        else
        {
            return Response::json($txn);
        }
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

