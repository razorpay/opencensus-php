<?php

namespace Models\DO;

use \Validator;
use \Err;
use \Utility;

class Transaction extends DomainObject
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|numeric',
        'amount'        =>  'required|numeric|max:10000',
        'currency'      =>  'required|max:3',
        'token'         =>  'sometimes',			
        'desc'          =>  'max:1000',
        'process'       =>  'numeric|max:1|digits:1');

    protected $fields = array(
        'id',
        'uid',
        'merchant_id',
        'token',
        'amount',
        'currency',
        'processed',
        'desc',
//        'refund',
        'created_at',
        'updated_at');

    protected static $generate = array('uid');

    protected static $validators = array('currency');

    protected static $do = array(
    	'one' => array('card', 'token'),
    	'many' => array());

    private $process_now    = true;

    private $card_token_do  = null;

    private $card_do        = null;

    const int $UID_LEN = 16;

    private function validateCurrency($input)
    {
        $currency = $input['currency'];

        // Right now only INR is supported.

        if ($currency !== "INR")
        {
            return throw new \InvalidCurrencyException($currency);
        }
    }

    public function generateUidField($data)
    {
    	return Utility::generateToken(self::$UID_LEN);
    }

    public function setProcessField($value)
    {
    	$this->process_now - (bool) $value;
    	$this->setField('process', (bool) $value);
    }

    public function setToken($token)
    {
    	if (is_string($token))
    	{
    		$this->setField('token', $token);
    	}
    	else if ($token instanceof CardToken)
    	{
    		$this->setObject('token', $token);
    	}
    }

    public function setCard(Card $card)
    {
        $this->setObject('Card', $card);
    }

    public function getProcessNowField()
    {
        return $this->process_now;
    }

    public function getProcessed()
    {
        return $this->getField('processed');
    }

    public function setProcessed($processed)
    {
        $this->setField('processed', $processed)
    }

    public function setId(/* int */ $id)
    {
        $this->set('id', $id);
    }

    public function getObjectField()
    {
    	return 'transaction';
    }

    const WITH_CARD             = 0x256;

    public function toArray($flag = 0x0)
    {
    	$array = parent::toArray($flag);

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            $array['id'] = $array['uid'];
            unset($array['uid']);
        }

        if ($flag & self::WITH_CARD)
        {
            $card_do = null;

            if ((($this->card_token_do === null) or
                 ($this->card_token_do->get_card_do() === null)) and
                ($this->card_do === null))
            {
                throw new \InvalidArgumentException('No card present');
            }

            if ($this->card_token_do !== null)
            {
                $card_do = $this->card_token_do->get_card_do();
            }
            
            if (($card_do === null) and 
                ($this->card_do !== null))
            {
                $card_do = $this->card_do;
            }

            if ($card_do === null)
            {
                throw new \UnexpectedValueException('No card do present to fetch card data');
            }

            $card_data = $card_do->toArray($flag);

            $data['card'] = $card_data;
        }

        return $data;
    }
}

