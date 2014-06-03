<?php 

namespace Models\Manager;

use \Validator;
use Models\DAL;
use Models\Service;

class Card extends EntityManager
{
    protected static $createRules = array(
        'number'        => 'required|numeric|luhn',
        'expiry_month'  => 'required|month',
        'expiry_year'   => 'required|expiry_year',
        'cvv'           => 'required|numeric|digits:3',
        'type'          => 'card_type',
        'bank'          => 'regex:/[a-zA-Z,0-9.&\- ]*/|max:100',
        'country'       => 'alpha|max:2',
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

    protected static $generators = array('last4', 'network', 'type', 'bank', 'country');

    public function build(array $input)
    {
        // retrieve card details
        $iin = intval(substr($input['number'], 0, 6));

        $cardDetails = Service\Card::getNewInstance()->retrieveDetails($iin);

        if($cardDetails)
        {
            $input['type'] = $cardDetails['card_type'];
            $input['bank'] = $cardDetails['bank'];
            $input['country'] = $cardDetails['brand'];
        }
        else
        {
            $input['type'] = '';
            $input['bank'] = '';
            $input['country'] = '';
        }

        parent::build($input);
    }

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
        // shift this code to a common place in future
        $network = false;

        $maestroFirstFour = array('5018', '5020', '5038', '5612', '5893', '6304', '6759', '6761', '6762', '6763', '0604', '6390');

        if(substr($input['number'], 0, 1) == '4')
        {
            $network = 'VISA';
        }
        elseif(intval(substr($input['number'], 0, 2)) >= 51
                and
                intval(substr($input['number'], 0, 2)) <= 55
                )
        {
            $network = 'MASTERCARD';
        }
        elseif(in_array(substr($input['number'], 0, 4), $maestroFirstFour))
        {
            $network = 'MAESTRO';
        }

        $this->setField('network', $network);
    }

    public function generateType($input)
    {
        $type = $input['type'];

        $this->setField('type', $type);
    }

    public function generateBank($input)
    {
        $bank = $input['bank'];

        $this->setField('bank', $bank);
    }

    public function generateCountry($input)
    {
        $country = $input['country'];

        $this->setField('country', $country);
    }


}
