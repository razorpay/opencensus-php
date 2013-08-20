<?php

namespace DomainObject;

use \Validator;
use \Err;
use \Utility;

class Transaction extends DomainObject
{
    private static $input_keys = array(
        'merchant_id',
        'amount', 
        'currency', 
        'token', 
        'desc',
        'process'
        );

    private static $rules_build_attributes = array(
        'merchant_id'   =>  'required|integer',
        'amount'        =>  'required|integer|max:10000',
        'currency'      =>  'required|max:3',
        'desc'          =>  'max:1000',
        'process'       =>  'integer|max:1'
        );

    private $attr = array(
        'id'            =>  null,

        'uid'           =>  null,
        'merchant_id'   =>  null,
        'token'         =>  null,

        'amount'        =>  null,
        'currency'      =>  null,
        'processed'     =>  0,
        'desc'          =>  null,

//        'refund'        =>  0,

        'created_at'    =>  0,
        'updated_at'    =>  0
        );

    private $process_now    = true;

    private $card_token_do  = null;

    private $card_do        = null;

    private $input          = null;

    private $data           = null;

    private $input_verified = false;

    private static $UID_LEN = 16;

    public function verify_build_input($input = null)
    {
        if ($this->input === null)
            $this->input = $input;

        $err = $this->verify_build_input_keys();

        if ($err !== ERR::SUCCESS)
            return $err;

        $validation = Validator::make(
                        $this->input, 
                        self::$rules_build_attributes);

        if ($validation->fails()) 
        {
            return ERR::invalid_parameters($validation->errors->all());
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

    private function verify_build_input_keys()
    {
        $user_keys = array_keys($this->input);
        $invalid_keys = array_diff($user_keys, self::$input_keys);

        if (count($invalid_keys) == 0)
        {
            return ERR::SUCCESS;
        }
        else 
        {
            return ERR::invalid_keys($invalid_keys);
        }
    }

    private function verify_amount()
    {
        $amount = (int) $this->input['amount'];

        if (($amount == 0) or
            ($amount > 12345678))    // some large pre-decided number.
            return ERR::invalid_parameters('Amount provided is not valid.');

        $this->input['amount'] = $amount;
        return ERR::SUCCESS;
        // Put any syntactical or logical constraints on amount 
        // before proceeding with the transaction.

    }

    private function verify_currency()
    {
        $currency = $this->input['currency'];

        // Right now only INR is supported.

        if ($currency !== "INR")
            return ERR::invalid_currency($currency . ' provided.');

        return ERR::SUCCESS;
    }

    public function build(array $input = null)
    {
        if ($this->input_verified === false)
        {
            if ($input === null)
            {
                return ERR::invalid_parameters('No input provided');
            }
            
            $this->input = $input;
            $err = $this->verify_build_input();

            if ($err !== ERR::SUCCESS)
                return $err;
        }

        $this->data = $this->input;

        $this->data['uid'] = Utility::generate_token(self::$UID_LEN);

        $err = $this->set();

        if ($err !== ERR::SUCCESS)
            return $err;

        return ERR::SUCCESS;
    }

    public function set(array $data = null)
    {
        if ($data === null)
        {
            if ($this->data === null)
            {
                throw new \InvalidArgumentException('parameter $data not provided.');
            }
            else $data = $this->data;
        }
        else 
            $this->data = $data;

        foreach ($this->data as $key=>$value)
        {
            if (array_key_exists($key, $this->attr))
            {
                $this->attr[$key] = $value;
            }
            else if ($key === 'process')
            {
                $this->attr['processed'] = $value;
                $this->process_now = (bool) $value;
            }
            else
            {
                throw new \InvalidArgumentException($key . " is not a valid key.");
            }
        }

        return ERR::SUCCESS;
    }

    public function set_token_do(CardToken $card_token_do)
    {
        $this->card_token_do = $card_token_do;

        if (empty($this->attr['token']))
            $this->attr['token'] = $card_token_do->get_token();
    }

    public function set_card_do(Card $card_do)
    {
        $this->card_do = $card_do;
    }

    public function process_now()
    {
        return $this->process_now;
    }

    public function get_processed()
    {
        return $this->attr['processed'];
    }

    public function set_processed($processed)
    {
        $this->attr['processed'] = $processed;
    }

    public function set_id(/* int */ $id)
    {
        $this->attr['id'] = $id;
    }


    const FLAG_DEFAULT          = 0x0;
    const WITH_CARD             = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;
    public function get_transaction_data($flag = 0x0)
    {
        $data = $this->attr;

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            unset(
                $data['id'],
                $data['merchant_id'],
                $data['token']
                );
        }

        if ($flag &self::WITH_OBJECT_FIELD)
        {
            $data['object'] = 'transaction';
        }

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            $data['id'] = $data['uid'];
            unset($data['uid']);
        }

        if ($flag & self::WITH_CARD)
        {
            $card_do_flag = 0x0;
            $card_do = null;

            if ($flag & self::WITH_OBJECT_FIELD)
                $card_do_flag |= Card::WITH_OBJECT_FIELD;
            if ($flag & self::ONLY_PUBLIC_FIELDS)
                $card_do_flag |= Card::ONLY_PUBLIC_FIELDS;

            if ((($this->card_token_do === null) or
                 ($this->card_token_do->get_card_do() === null)) and
                ($this->card_do === null))
                throw new \InvalidArgumentException('No card present');

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
                throw new \UnexpectedValueException('No card do present to fetch card data');

            $card_data = $card_do->get_card_data($card_do_flag);

            $data['card'] = $card_data;
        }

        return $data;
    }
}

