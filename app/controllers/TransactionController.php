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
    public function getIndex ($id = null)
    {
        $merchant_id = BasicAuth::getInstance()->MerchantId();

        $m = BasicAuth::getInstance()->Merchant();
        
        if ($id === null) 
        {
            $t = $m->transactions();

            if (empty($t))
            {
                return Response::json(array());
            }
            else
            {
                return Response::json($t);
            }
        }
        else 
        {
            $t = Transaction::where('id', '=', $id)->first();
        
            if ($t === null)
            {
                return Response::error('404');
            }
            else
            {
                if ($t->get_merchant() === $merchant_id)
                    return Response::eloquent($t);
                else
                    return Response::error('401');
            }
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

        if(isset($txn_data['callbackUrl']))
        {	
        	return View::make('hdfc.enrollResponse')
        					->with('data', $txn_data['data'])
        					->with('callbackUrl',$txn_data['callbackUrl']);
        }
        else return Response::json($txn_data);

    }

    /**
     * Retrieve previous transactions.
     */
    public function getRetrieve()
    {
        $txn_service = new Transaction();

        $input = Input::all();

        list($txn_data, $err) = $txn_service->retrieveMultiple($input);

        if ($err !== ERR::SUCCESS)
        {
            return ERR::handle_error();
        }

        return Response::json($txn_data);
    }

    /**
    * Refund a transaction.
    */
    public function postRefund()
    {
        $id= Input::get('transaction_id');

        $txn_service = new Transaction();

        $txn_data = $txn_service->retrieve($id);

        $merchant_id = BasicAuth::getInstance()->MerchantId();

        if ($merchant_id !== $txn_data->getMerchantId())
            die("Jhootbolegasaale");

        $txn_service->refund($txn_data);
        
        return Response::json($txn_data);
    }

    /**
     * Captures transactions from the past 1 day
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
        return $txn_service->bankAcsCallback($input);
    }
}