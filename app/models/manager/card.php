<?php

namespace Models\Manager;

use \Validator;
use Models\DAL;
use Models\Service;

use EE\Exception;
use EE\Exception\CardErrorException;
use EE\Error\ErrorCode;

class Card extends EntityManager
{
    protected static $createRules = array(
        'number'            => 'required|numeric|luhn|digits_between:12,19',
        'expiry_month'      => 'required|numeric|digits_between:1,2|max:12',
        'expiry_year'       => 'required|numeric|digits:4|year_length',
        'cvv'               => 'required|numeric|digits_between:3,4',
        'name'              => 'required|alpha_space|max:100',
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
        'address_zip');

    protected static $createValidators = array('address', 'expiry_date');

    protected static $modifiers = array('expiry_year');

    protected static $generators = array('last4');

    public function build(array $input)
    {
        try
        {
            parent::build($input);
        }
        catch (Exception\CardErrorException $e)
        {
            throw $e;
        }
        catch (Exception\ValidationFailureException $e)
        {
            throw new CardErrorException($e->getMessageBag(), 0, $e);
        }
        catch (Exception\ExtraFieldsException $e)
        {
            throw new CardErrorException($e->getMessageBag(), 0, $e);
        }
    }

    protected function validateExpiryDate($input)
    {
        $month = $input['expiry_month'];
        $year = $input['expiry_year'];

        $currentMonth = date('M');
        $currentYear = date('Y');

        if (($month < $currentMonth) &&
            ($year < $currentYear))
        {
            throw new CardErrorException(
                'Expiry date should not be in the past',
                ErrorCode::CARD_ERROR_INVALID_EXPIRY_DATE);
        }
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

    public static function modifyNumber($number)
    {
        $number = str_replace(' ', '', $number);
        $number = str_replace('-', '', $number);

        return $number;
    }
}
