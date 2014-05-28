<?php 

namespace Models\Manager;

use \Validator;
use Models\DAL;

class Card extends EntityManager
{
    protected static $createRules = array(
        'number'        => 'required|numeric|luhn',
        'expiry_month'  => 'required|month',
        'expiry_year'   => 'required|expiry_year',
        'cvv'           => 'required|numeric|digits:3',
        'name'          => 'required|alpha_space|max:100',
        'address_line1'     => 'regex:/[a-zA-Z,1-9. ]*/|max:100',
        'address_line2'     => 'regex:/[a-zA-Z,1-9. ]*/|max:100',
        'address_city'      => 'regex:/[a-zA-Z,1-9. ]*/|max:100',
        'address_state'     => 'regex:/[a-zA-Z,1-9. ]*/|max:100',
        'address_country'   => 'regex:/[a-zA-Z]*/|max:50',
        'address_zip'       => 'numeric|digits_between:0,10');

    protected static $address_attributes = array(
        'address_line1',
        'address_line2',
        'address_city',
        'address_state',
        'address_country',
        'address_zip'
        );

    protected static $createValidators = array('address');

    protected static $generators = array('last4', 'country', 'network');

    protected function validateAddress($input)
    {
        $addr_unset = array();
        $addr_set = array();
        
        foreach(self::$address_attributes as $key)
        {
            if ((!isset($input[$key])) or
                (empty($input[$key])))
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
                throw new \InvalidArgumentException($msg);
            }
        }
    }

    public function generateLast4($input)
    {
        $last4 = substr($input['number'], -4);

        $this->setField('last4', $last4);
    }

    public function generateNetwork($input)
    {
        $this->setField('network', 'visa');
    }

    public function generateCountry($input)
    {
        $this->setField('country', 'IN');
    }


}
