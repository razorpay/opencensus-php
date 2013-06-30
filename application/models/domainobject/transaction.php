<?php

namespace DomainObject;

use \Validator;
use \Err;
use \Utility;

class Transaction
{
    private static $input_build_attributes = array(
        'amount', 
        'currency', 
        'token', 
        'desc',
        'process'
        );

    private static $rules_build_attributes = array(
        'amount'    =>  'required|integer|max:10000',
        'currency'  =>  'required|max:3',
        'desc'      =>  'max:1000',
        'process'   =>  'integer|max:1',
        );

    private $txn = array(
        'id'            =>  null,

        'txn_id'        =>  null,
        'merchant_id'   =>  null,
        'token_id'      =>  null,

        'amount'        =>  null,
        'currency'      =>  null,
        'processed'     =>  0,
        'desc'          =>  null,

        'refund'        =>  0,

        'created_at'    =>  0,
        'updated_at'    =>  0

        );

    private $process_now    = true;

    private $token_do       = null;

    private $input          = null;

    private $data           = null;

    private $input_verified = false;

    public function verify_build_input($input = null)
    {
        if ($this->input === null)
            $this->input = $input;

        $err = $this->verify_build_input_keys();

        if ($err !== ERR::SUCCESS)
            return $err;

        $validation = Validator::make($this->input, self::$rules_build_attributes);

        if ($validation->fails()) 
        {
            var_dump($validation->errors);
            return ERR::INVALID_PARAMETERS;
        }
        
        $err = $this->verify_currency();

        if ($err !== ERR::SUCCESS)
            return $err;

        $err = $this->verify_amount();

        if ($err !== ERR::SUCCESS)
            return $err;

        $this->input_verified = true;

        return ERR::SUCCESS;
    }

    private function verify_amount()
    {
        if (!isset($this->input['currency']))
            return ERR::INVALID_PARAMETERS;

        $amount = (int) $this->input['amount'];

        if ($amount == 0)
            return ERR::INVALID_AMOUNT;
        else if ($amount > 12345678)    // some large pre-decided number.
            return ERR::INVALID_AMOUNT;

        return ERR::SUCCESS;
        // Put any syntactical or logical constraints on amount 
        // before proceeding with the transaction.

    }

    private function verify_currency()
    {
        if (!isset($this->input['currency']))
            return ERR::INVALID_PARAMETERS;

        $currency = $this->input['currency'];

        // Right now only INR is supported.

        if ($currency !== "INR")
            return ERR::INVALID_CURRENCY;

        return ERR::SUCCESS;
    }

    private function verify_build_input_keys()
    {
        $input_new_token_attributes = CardToken::input_keys_for_new_token();

        $card_attr = 0;

        if (isset($input['card']))
        {
            $card_attr = 1;
        }

        foreach ($this->input as $key => $value)
        {
            if (!in_array($key, self::$input_build_attributes))
            {
                if (!in_array($key, $input_new_token_attributes))
                {
                    return ERR::INVALID_PARAMETERS;
                }
                else if ($card_attr === 1)
                {
                    return ERR::INVALID_PARAMETERS;
                }
                else $card_attr = 2;
            }
        }

        if ($card_attr === 0)
        {
            return ERR::INVALID_PARAMETERS;
        }

        return ERR::SUCCESS;
    }

    public function build(array $data, array $input = null)
    {
        if ($this->input_verified === false)
        {
            if ($input === null)
            {
                return ERR::INVALID_PARAMETERS;
            }
            
            $this->input = $input;
            $err = $this->verify_build_input();

            if ($err !== ERR::SUCCESS)
                return $err;
        }

        $this->data = array_merge($this->input, $data);;

        $this->data['txn_id'] = Utility::generate_token(16);

        return ERR::SUCCESS;
    }

    public function set($data = null)
    {
        if ($data === null)
        {
            if ($this->data === null)
            {
                return ERR::INVALID_PARAMETERS;
            }
            else $data = $this->data;
        }
        else 
            $this->data = $data;

        foreach ($this->data as $key=>$value)
        {
            if (array_key_exists($key, $this->txn))
                $this->txn[$key] = $value;
            else
            {
                if ($key === 'process')
                {
                    $this->txn['processed'] = $value;
                    $this->process_now = (bool) $value;
                }
                throw new \InvalidArgumentException($key . " is not a valid key.");
            }
        }

        return ERR::SUCCESS;
    }

    public function set_token_do(CardToken $card_token_do)
    {
        $this->card_token_do = $card_token_do;

        if (empty($this->txn['token']))
            $this->txn['token'] = $card_token_do->get_token();
    }

    public function process_now()
    {
        return $this->process_now;
    }

    public functino get_processed()
    {
        return $this->txn['processed'];
    }

    public function set_processed($processed)
    {
        $this->txn['processed'] = $processed;
    }
}

