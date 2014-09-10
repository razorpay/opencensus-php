<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class Refund extends Service
{
    public function fetchListFromApi(array $input, $mode)
    {
        $data = array();

        list($error,$options) = Manager\Refund::createValidate($input, 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            
            $request = (new Request)->setCredentials($mode, $merchant_id);
            
            $response = $request->process('GET', 'refunds');

            $data = Manager\Refund::mapKeys($response);
        }

        return array($error, $data);
    }

    public function fetchRefundFromApi($id, $mode)
    {
        $data = array();

        list($error, $options) = Manager\Refund::createValidate(['id' => $id], 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            
            $request = (new Request)->setCredentials($mode, $merchant_id);
            
            $response = $request->process('GET', 'refunds/'.$id);

            if(isset($response['error']) and isset($response['error']['description']))
            {
                $error[] = 'Transaction not found.';
            }
            
            $data = array('count' => 1, 'data' => array($response));
        }

        return array($error, $data);
    }


 
}