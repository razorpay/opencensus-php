<?php 

namespace Models\Manager;

use \Validator;
use Models\DAL;
use Models\Service;

class Card extends EntityManager
{
    protected static $createRules = array(
        'number'        => 'required|numeric|luhn|digits_between:12,19',
        'expiry_month'  => 'required|month',
        'expiry_year'   => 'required|expiry_year',
        'cvv'           => 'required|numeric|digits_between:3,4',
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

    protected static $modifiers = array('number', 'expiry_year');

    protected static $generators = array('last4');

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

    public function modifyNumber(& $input)
    {
        $input['number'] = str_replace(' ', '', $input['number']);
        $input['number'] = str_replace('-', '', $input['number']);
    }

    public function modifyExpiryYear(& $input)
    {
        if(strlen($input['expiry_year']) == 2)
        {
            $input['expiry_year'] = '20'.$input['expiry_year'];
        }
    }

    public function fillNetworkDetails($details)
    {
        $number = $this->getField('number');

        $network = CardNetwork::detectNetwork($number);

        $this->setField('network', $network);

        if ($details)
        {
            $arr = array(
                'type' => $details['card_type'],
                'bank' => $details['bank'],
                'country' => $details['country_code']);

            $this->fill($arr);
        }
    }
}
