<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class Payment extends Service
{
    public function fetchListFromApi(array $input, $mode)
    {
        $data = array();

        list($error,$options) = Manager\Payment::createValidate($input, 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            $this->setApiCredentials($merchant_id, $mode);

            $response = $this->api->transaction->all($options)->toArray();

            $data = Manager\Payment::mapKeys($response);
        }

        return array($error, $data);
    }

    public function fetchFromApi($id, $mode)
    {
        $data = array();

        list($error, $options) = Manager\Payment::createValidate(['id' => $id], 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            $this->setApiCredentials($merchant_id, $mode);

            $id = $options['id'];
            try
            {
                $data = $this->api->transaction->fetch($id)->toArray();

                $data = array('count' => 1, 'data' => array($data));
            }
            catch(\Exception $e)
            {
                $error[] = 'Payment not found.';
            }
        }

        return array($error, $data);
    }

    public function fetchRefundsFromApi($id, $mode)
    {
        $data = array();

        list($error, $options) = Manager\Payment::createValidate(['id' => $id], 'fetch')->getData();

        if (empty($error))
        {
            $merchant_id = \Auth::merchant()->id();
            $this->setApiCredentials($merchant_id, $mode);

            $id = $options['id'];
            try
            {
                $data = $this->api->transaction
                                        ->fetch($id)
                                        ->refunds()
                                        ->all()
                                        ->toArray();

                $data = $data['data'];

            }
            catch(\Exception $e)
            {
                $error[] = 'Request Failed';
            }
        }

        return array($error, $data);
    }

    public function capture($id, $amount, $mode)
    {
        $error = array();

        $merchant_id = \Auth::merchant()->id();

        $this->setApiCredentials($merchant_id, $mode);

        try
        {
            $data = $this->api->transaction
                                ->fetch($id)
                                ->capture(array('amount' => $amount))
                                ->toArray();
        }
        catch(\Exception $e)
        {
            $error[] = "Capture Failed";
            return $error;
        }

        if(isset($data['error']) === true or isset($data['status']) === false or $data['status'] !== "captured")
            $error[] = "Capture Failed";

        return $error;
    }

    public function refund($id, $amount, $mode)
    {   
        $error = array();

        $merchant_id = \Auth::merchant()->id();
        $this->setApiCredentials($merchant_id, $mode);

        try
        {
            $data = $this->api->transaction
                            ->fetch($id)
                            ->refund(array('amount' => $amount))
                            ->toArray();  
            
        }
        catch(\Exception $e)
        {
            $error[] = "Refund Failed";
            return $error;
        }

        if($data['entity'] !== "refund" or $data['amount'] != $amount)
            $error[] = "Refund Failed";
        
        return $error;
    }
}