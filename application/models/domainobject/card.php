<?php 

namespace DomainObject;

use \Validator;
use \Err;

class Card
{
    /**
     * dump of input data provided goes here.
     */
    private $data = null;

    private $card = array(
        'id'        => null,
        'number'    => null,
        'cardholder'=> null,
        'cvv'       => null,

        'expiry_month' => null, 
        'expiry_year'  => null,

        'last4'   => null,
        'type'    => null,
        'country' => null,


        'address_line1'     => null, 
        'address_line2'     => null,
        'address_state'     => null,
        'address_city'      => null,
        'address_zip'       => null,
        'address_country'   => null,

        'cvv_check'           => 0,
        'address_line1_check' => 0,
        'address_zip_check'   => 0,

        'created_at'    => null,
        'updated_at'    => null
        );

    private static $rules = array(
        'number'        => 'required|match:/[0-9]/',
        'expiry_month'  => 'required|match:/[0-9]{2}/',
        'expiry_year'   => 'required|match:/[0-9]/|min:2|max:4',
        'cvv'           => 'required|match:/[0-9]/|size:3',
        'cardholder'    => 'required|match:/[a-zA-Z]*/|max:100',
        'address_line1'     => 'match:/[a-zA-Z,1-9.]*/|size:100',
        'address_line2'     => 'match:/[a-zA-Z,1-9. ]*/|size:100',
        'address_city'      => 'match:/[a-zA-Z,1-9. ]*/|size:100',
        'address_state'     => 'match:/[a-zA-Z,1-9. ]*/|size:100',
        'address_country'   => 'match:/[a-zA-Z]*/|size:50',
        'address_zip'       => 'match:/[a-zA-Z1-9]*/|max:10'
        );

    private static $input_attributes = array(
        'number', 
        'expiry_month', 
        'expiry_year', 
        'cvv',
        'cardholder',
        'address_line1',
        'address_line2',
        'address_city',
        'address_state',
        'address_country',
        'address_zip'
        );

    private static $address_attributes = array(
        'address_line1',
        'address_line2',
        'address_city',
        'address_state',
        'address_country',
        'address_zip'
        );

    /**
     * Verify credit card attributes syntax
     * @param  arrray $data [description]
     * @return int ERR::SUCCESS on success or error code on error.
     */
    private function verify_input_values()
    {
        $validation = Validator::make($this->data, self::$rules);

        if ($validation->fails()) 
        {
            var_dump($validation->errors);
            return ERR::INVALID_PARAMETERS;
        }
        
        $err = $this->check_address_values();

        return $err;
    }

    private function verify_input_keys()
    {
        foreach($this->data as $key => $value)
        {
            if (! in_array($key, static::$input_attributes, true))
            {
                echo $key . " should not be in this list." + __FILE__ + " " + __LINE__;
                return ERR::INVALID_PARAMETERS;
            }
        }
        return ERR::SUCCESS;
    }

    /**
     * Sets generated card data and verifies validity of card data etc
     * @return int ERR::SUCCESS on success or error code on error
     */
    public function build($data = null)
    {
        $this->data = $data;

        $err = $this->verify_input_keys();

        if ($err !== ERR::SUCCESS)
            return $err;

        $err = $this->verify_input_values();
        if ($err !== ERR::SUCCESS)
            return $err;

        $err = $this->build_meta();
        if ($err !== ERR::SUCCESS)
            return $err;
        
        $err = $this->set();
        if ($err !== ERR::SUCCESS)
            return $err;
        
        return $err;
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

        try
        {
            // Essential attributes
            $this->set_essential();
            
            // Generated attributes
            $this->set_generated();

            // Address atributes
            $this->set_address();
        }
        catch (\Exception $e)
        {
            var_dump($e);
            return ERR::INTERNAL_SERVER_ERROR;
        }

        return ERR::SUCCESS;
    }

    private function set_essential()
    {
        $this->set_attr('id');
        $this->set_attr('number');
        $this->set_attr('cardholder');
        $this->set_attr('expiry_month');
        $this->set_attr('expiry_year');
        $this->set_attr('cvv');
    }

    private function set_generated()
    {
        // generated attributes
        $this->set_attr('last4');
        $this->set_attr('type');
        $this->set_attr('country');
    }

    private function check_address_values()
    {
        $addr_set = false;
        $addr_unset = false;

        foreach(static::$address_attributes as $key)
        {
            if($key === 'address_line2')
                continue;
            if(empty($this->data[$key]))
            {
                $addr_unset = true;
            }
            else
            {
                $addr_set = true;
            }
        }

        if (($addr_set === false) and
            ($addr_unset === true))
            return ERR::SUCCESS;
        else if (($addr_set === true) and
                 ($addr_unset === true))
            return ERR::INVALID_PARAMETERS;

/*
        $country = upper($this->data['address_country']);
        $zip = $this->data['zip'];

        if (($country == 'INDIA') or
            ($country == 'IN'))
        {
            if ((strlen($zip) !== 5) or
                (!is_numeric($zip)))
                return ERR::INVALID_PARAMETERS;
        }
*/

        return ERR::SUCCESS;
    }

    private function set_address()
    {
        if(empty($this->data['address_line1']))
            return;
        
        $this->set_attr('address_line1');
        $this->set_attr('address_line2');
        $this->set_attr('address_city');
        $this->set_attr('address_state');
        $this->set_attr('address_zip');
    }
   
    private function build_meta()
    {
        $err = ERR::SUCCESS;

        try
        {
            $this->data['last4'] = substr($this->data['number'], -4);
            $this->data['type'] = $this->type($this->data['number']);
            $this->data['country'] = $this->country($this->data['number']);
        }
        catch (InvalidArgumentException $e)
        {
            $err = ERR::INVALID_PARAMETERS;
        }

        return $err;
    }

    private function type($number)
    {
        return 'visa';

    }

    private function country($number)
    {

        return 'IN';
    }

    public function verify_cvv()
    {
        $card = $this->$card;
        if ((!isset($card['cvv'])) or
            (!isset($card['cardholder'])) or
            (!isset($this['id'])))
            return ERR::INVALID_PARAMETERS;

        if (isset($card['cvv']) and
           (($this->card['cvv'] == '0') or
            ($this->card['cvv'] == '1')))
        {   
            return ERR::CVV_ALREADY_VERIFIED;
        }

        return ERR::SUCCESS;
    }

    public function set_id($id)
    {
        $this->card['id'] = $id;
    }

    public function get_id()
    {
        return $this->card['id'];
    }

    private function set_attr($key)
    {
        if ((array_key_exists($key, $this->data)) and
            (array_key_exists($key, $this->card)))
        {
            $this->card[$key] = $this->data[$key];
        }
        else
        {
            if ($key === 'id')
            {
                ;
            }
            else if ($key === 'card_id')
            {
                $this->set_id($this->data[$key]);
            }
            else
            {
                echo $key . ' is not a valid key.';
                throw new \InvalidArgumentException($key);
            }
        }
    }

    public static function input_keys_for_new_card()
    {
        return self::$input_attributes;
    }

    const FLAG_DEFAULT          = 0x0;
    const NO_CHECK_FIELDS       = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;
    
    public function get_card_data($flag = 0x0)
    {
        $card = $this->card;

        unset($card['created_at']);
        unset($card['updated_at']);

        if ($flag & self::WITH_OBJECT_FIELD)
            $card['object'] = 'card';

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            unset($card['id']);
            unset($card['number']);
        }


        if ($flag & self::NO_CHECK_FIELDS)
        {
            unset(
                $card['cvv_check'],
                $card['address_line1_check'],
                $card['address_zip_check']);
        }

        return $card;
    }
}
