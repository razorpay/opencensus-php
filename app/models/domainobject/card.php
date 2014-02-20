<?php 

namespace DomainObject;

use \Validator;
use \ERR;

class Card extends DomainObject
{
    private $input = null;
    /**
     * dump of input data provided goes here.
     */

    private $data = null;

    private $attr = array(
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
        'number'        => 'required|regex:/[0-9]/',
        'expiry_month'  => 'required|regex:/[0-9]{2}/|min:1|max:2',
        'expiry_year'   => 'required|regex:/[0-9]/|min:2|max:4',
        'cvv'           => 'required|regex:/[0-9]/|size:3',
        'cardholder'    => 'required|regex:/[a-zA-Z]*/|max:100',
        'address_line1'     => 'regex:/[a-zA-Z,1-9.]*/|size:100',
        'address_line2'     => 'regex:/[a-zA-Z,1-9. ]*/|size:100',
        'address_city'      => 'regex:/[a-zA-Z,1-9. ]*/|size:100',
        'address_state'     => 'regex:/[a-zA-Z,1-9. ]*/|size:100',
        'address_country'   => 'regex:/[a-zA-Z]*/|size:50',
        'address_zip'       => 'regex:/[a-zA-Z1-9]*/|max:10'
        );

    private static $input_keys = array(
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
        $validation = Validator::make($this->input, self::$rules);

        if ($validation->fails()) 
        {
            return ERR::invalid_parameters($validation->errors->all());
        }
        
        $err = $this->verify_address_parameters();

        return $err;
    }

    private function verify_input_keys()
    {
        $input = $this->input;

        $keys = array_keys($this->input);
        $invalid_keys = array_diff($keys, self::$input_keys);

        if (count($invalid_keys) !== 0)
        {
            return ERR::invalid_keys($invalid_keys);
        }
        
        return ERR::SUCCESS;
    }

    private function verify_address_parameters()
    {
        $addr_unset = array();
        $addr_set = array();
        
        foreach(self::$address_attributes as $key)
        {
            if ((!isset($this->input[$key])) or
                (empty($this->input[$key])))
            {
                array_push($addr_unset, $key);
            }
            else
            {
                array_push($addr_set, $key);
            }
        }

        if (count($addr_set) > 0)
        {
            $addr_unset_count = count($addr_unset);
            if (($addr_unset_count > 1) or
                (($addr_unset_count === 1) and 
                 ($addr_unset_count[0] !== 'address_line2')))
            {
                $msg = implode(',', $addr_unset) . ' address values are not set.';
                return ERR::invalid_parameters($msg);
            }
        }

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

    public function build(array $input = null)
    {
        $this->input = $input;

        $err = $this->verify_input_keys();

        if ($err !== ERR::SUCCESS)
            return $err;

        $err = $this->verify_input_values();
        if ($err !== ERR::SUCCESS)
            return $err;

        $this->data = $input;

        $err = $this->build_meta();
        if ($err !== ERR::SUCCESS)
            return $err;
        
        $err = $this->set();
        if ($err !== ERR::SUCCESS)
            return $err;
        
        return $err;
    }

    public function set(array $data = null)
    {
        if ($data === null)
        {
            if ($this->data === null)
            {
                throw new \InvalidArgumentException("$data not provided");
            }
            else $data = $this->data;
        }
        else 
            $this->data = $data;

        // Essential attributes
        $this->set_essential();
        
        // Generated attributes
        $this->set_generated();

        // Address atributes
        $this->set_address();

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
        $this->data['last4'] = substr($this->data['number'], -4);
        $this->data['type'] = $this->type($this->data['number']);
        $this->data['country'] = $this->country($this->data['number']);

        return ERR::SUCCESS;
    }

    private function type($number)
    {
        return 'visa';

    }

    private function country($number)
    {

        return 'IN';
    }

    public function set_id($id)
    {
        $this->attr['id'] = $id;
    }

    public function get_id()
    {
        return $this->attr['id'];
    }

    private function set_attr($key)
    {
        if ((array_key_exists($key, $this->data)) and
            (array_key_exists($key, $this->attr)))
        {
            $this->attr[$key] = $this->data[$key];
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
                $msg = $key . ' is not a valid key.';
                throw new \InvalidArgumentException($msg);
            }
        }
    }

    public static function input_keys()
    {
        return self::$input_keys;
    }

    const FLAG_DEFAULT          = 0x0;
    const NO_CHECK_FIELDS       = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;
    
    public function get_card_data($flag = 0x0)
    {
        $data = $this->attr;

        unset($data['created_at']);
        unset($data['updated_at']);

        if ($flag & self::WITH_OBJECT_FIELD)
            $data['object'] = 'card';

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            unset($data['id']);
            unset($data['number']);
        }


        if ($flag & self::NO_CHECK_FIELDS)
        {
            unset(
                $data['cvv_check'],
                $data['address_line1_check'],
                $data['address_zip_check']);
        }

        return $data;
    }
}
