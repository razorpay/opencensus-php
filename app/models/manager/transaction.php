<?php

namespace Models\Manager;

use \Utility;

class Transaction extends EntityManager
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|numeric',
        'amount'        =>  'required|numeric|max:10000',
        'currency'      =>  'required|max:3',
        'token'         =>  'required|alpha_num',
        'desc'          =>  'max:1000',
        'hold'       =>  'numeric|max:1|digits:1',
        'udf'           =>  'required'
        );

    protected static $udfRules = array(
        'email'         =>  'required|email|max:250',
        'contact'       =>  'required|numeric|digits_between:8:12');

    //TODO
    //Change it to refundRules, include amount as well
    protected static $idRules = array(
        'id'            =>  'required|alpha_num|max:32');

    protected static $generators = array('status');

    protected static $validators = array('currency', 'udf');

    private function validateUdf($input)
    {
        $udf = $input['udf'];

        if (!is_array($udf))
        {
            throw new \InvalidArgumentException('Not an array');
        }

        if (count($udf) > 15)
        {
            throw new \InvalidArgumentException('keys greater than 15');
        }

        foreach ($udf as $key => $value)
        {
            if (is_array($value))
                throw new \InvalidArgumentException('SHould not be an array');

            if (strlen($value) > 1024)
                throw new \InvalidArgumentException('Value too large!');

        }


    }

    private function validateCurrency($input)
    {
        $currency = $input['currency'];

        //
        // Right now only INR is supported.
        //

        if ($currency !== "INR")
        {
            throw new \InvalidCurrencyException($currency);
        }
    }

    public function generateStatus($input)
    {
    	$this->setField('status', 'open');
    }

    public function getStatus()
    {
        return $this->getField('status');
    }

    public function setStatus($status)
    {
        $this->setField('status', $status);
    }

    public static function separateTokenTxnInput($input)
    {
        return break_assoc_array(
                    $input,
                    CardToken::getCreateInputKeys(),
                    Transaction::getCreateInputKeys());
    }

    public static function validateTransactionId($id = NULL)
    {
        $validation = \Validator::make(array('id' => $id), static::$idRules);
        if ($validation->fails())
        {
            throw new \InvalidArgumentException($validation->messages());
        }
    }
}

