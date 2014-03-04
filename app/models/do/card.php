<?php 

namespace Models\DO;

use \Validator;
use \ERR;

class Card extends DomainObject
{
    protected static $fields = array(
        'id',
        'number',
        'cardholder',
        'cvv',

        'expiry_month',
        'expiry_year',

        'last4',
        'type',
        'country',

        'address_line1',
        'address_line2',
        'address_state',
        'address_city',
        'address_zip',
        'address_country',

        'cvv_check',
        'address_line1_check',
        'address_zip_check',

        'created_at',
        'updated_at'
        );

    protected static $createRules = array(
        'number'        => 'required|digits',
        'expiry_month'  => 'required|numeric|digits:2',
        'expiry_year'   => 'required|numeric|digits_between:2,4',
        'cvv'           => 'required|numeric|size:3',
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

    protected static $appends = array('object');

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
    	$last4 = substr($input['number', -4]);

    	$this->setField('last4', $last4);
    }

    public function generateType($input)
    {
    	$this->setField('type', 'visa');
    }

    public function generateCountry('$input')
    {
    	$this->setField('country', 'IN');
    }

    public function setId($id)
    {
        $this->attr['id'] = $id;
    }

    public function getId()
    {
        return $this->attr['id'];
    }

    const FLAG_DEFAULT          = 0x0;
    const NO_CHECK_FIELDS       = 0x1;
    const WITH_OBJECT_FIELD     = 0x2;
    const ONLY_PUBLIC_FIELDS    = 0x4;
    
    public function getCardData($flag = 0x0)
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
