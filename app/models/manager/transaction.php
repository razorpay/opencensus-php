<?php

namespace Models\Manager;

use \Utility;

class Transaction extends EntityManager
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|numeric',
        'amount'        =>  'required|numeric|max:10000',
        'currency'      =>  'required|max:3',
        'token'         =>  'required',
        'desc'          =>  'max:1000',
        'process'       =>  'numeric|max:1|digits:1');

    protected static $generators = array('uid', 'process');

    protected static $validators = array('currency');

    private $process_now    = true;

    const UID_LEN = 16;

    private function validateCurrency($input)
    {
        $currency = $input['currency'];

        // Right now only INR is supported.

        if ($currency !== "INR")
        {
            throw new \InvalidCurrencyException($currency);
        }
    }

    public function generateUid()
    {
    	$this->setField('uid', Utility::generate_token(self::UID_LEN));
    }

    public function generateProcess($input)
    {
    	$this->process_now = (bool) $input['process'];
    	$this->setField('processed', 0);
    }

    public function isProcessNow()
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

