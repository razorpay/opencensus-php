<?php

namespace Models\Manager;

use \Utility;

class Transaction extends EntityManager
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|numeric',
        'amount'        =>  'required|numeric|max:10000',
        'currency'      =>  'required|max:3',
        //'token'         =>  'required',
        'desc'          =>  'max:1000',
        'process'       =>  'numeric|max:1|digits:1',
        'udf'           =>  'required');

    protected static $udfRules = array(
    	'email'         =>  'required|email|max:250',
    	'contact'       =>  'required|numeric|digits_between:8:12')

    protected static $generators = array('process');

    protected static $validators = array('currency', 'udf');

    private $process_now = true;

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

        // Right now only INR is supported.

        if ($currency !== "INR")
        {
            throw new \InvalidCurrencyException($currency);
        }
    }

    public function generateProcess($input)
    {
    	$this->process_now = (bool) $input['process'];
    	$this->setField('processed', 0);
    }

    public function processNow()
    {
        return $this->process_now;
    }

    public function getProcessed()
    {
        return $this->getField('processed');
    }

    public function setProcessed($processed)
    {
        $this->setField('processed', $processed);
    }

    public static function separateTokenTxnInput($input)
    {
    	return break_assoc_array(
					$input,
					CardToken::getCreateInputKeys(),
					Transaction::getCreateInputKeys());
    }
}

