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
            
            $this->setApiCredentials($merchant_id, $mode);
            
            $response = $this->api->refund->all($options);

            $data = Manager\Refund::mapKeys($response);
        }

        return array($error, $data);
    }

    public function fetchFromApi($id, $mode)
    {
        $data = array();

        list($error, $options) = Manager\Refund::createValidate(['id' => $id], 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            
            $this->setApiCredentials($merchant_id, $mode);

            $response =  array();
            
            try
            {
                $response = $this->api->refund->fetch($id)->toArray();
            }
            catch(\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getCode();
            }
            
            $data = array('count' => 1, 'data' => array($response));
        }

        return array($error, $data);
    }


 
}