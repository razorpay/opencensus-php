<?php 

namespace Models\Manager;

use \Validator;
use Models\DAL;

class Card extends EntityManager
{
    protected static $createRules = array(
        'number'        => 'required|numeric',
        'expiry_month'  => 'required|numeric|digits:2',
        'expiry_year'   => 'required|numeric|digits_between:2,4',
        'cvv'           => 'required|numeric|digits:3',
        'cardholder'    => 'required|regex:/[a-zA-Z ]*/|max:100',
        'address_line1'     => 'regex:/[a-zA-Z,1-9. ]*/|size:100',
        'address_line2'     => 'regex:/[a-zA-Z,1-9. ]*/|size:100',
        'address_city'      => 'regex:/[a-zA-Z,1-9. ]*/|size:100',
        'address_state'     => 'regex:/[a-zA-Z,1-9. ]*/|size:100',
        'address_country'   => 'regex:/[a-zA-Z]*/|size:50',
        'address_zip'       => 'numeric|max:10'
        );

    protected static $address_attributes = array(
        'address_line1',
        'address_line2',
        'address_city',
        'address_state',
        'address_country',
        'address_zip'
        );

    protected static $validators = array('address');

    protected static $generators = array('last4', 'country', 'type');

    private function validateAddress($input)
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

        $this->setField('number', $last4);
    }

    public function generateType($input)
    {
    	$this->setField('type', 'visa');
    }

    public function generateCountry($input)
    {
    	$this->setField('country', 'IN');
    }


}
