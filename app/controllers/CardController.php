<?php

use Models\Service\Token;
use Models\Service\BasicAuth;

class CardController extends BaseController 
{

    /**
     * To create a new card object. Retrieve one-time use card token.
     */
    public function postIndex()
    {
        $input = Input::all();

        $input['merchant_id'] = BasicAuth::getInstance()->MerchantId();
        
        $token_service = new Token();

        $token_data = $token_service->generate($input);

        return Response::json($token_data);
    }

    public function getRetrieve($token)
    {
        $token_service = new Token;

        $merchant_id = BasicAuth::getInstance()->MerchantId();

        list($token_data, $err) = $token_service->retrieve($token, $merchant_id);
        
        // NOTE: Error handling doesn't seem to work properly. Please have a look - Abhishek Kandoi<kandoi@razorpay.com>
        // if (($token_data === false) or
        //  ($err !== ERR::SUCCESS))
        // {
        //  return ERR::handle_error();
        // }

        return Response::json($token_data);
    }
}