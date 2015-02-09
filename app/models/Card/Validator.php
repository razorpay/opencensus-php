<?php

namespace Models\Card;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'number'            => 'required|numeric|luhn|digits_between:12,19',
        'expiry_month'      => 'required|integer|digits_between:1,2|max:12|min:1',
        'expiry_year'       => 'required|integer|digits:4|non_past_year',
        'cvv'               => 'required|integer|digits_between:3,4',
        'name'              => 'required|alpha_space|max:100');

    protected static $createValidators = array('expiry_date');

    protected function validateExpiryDate($input)
    {
        $month = $input['expiry_month'];
        $year = $input['expiry_year'];

        $currentMonth = date('n');
        $currentYear = (int) date('Y');

        if (($month < $currentMonth) &&
            ($year <= $currentYear))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE);
        }
    }

    protected function validateAddress($input)
    {
        $addr_unset = array();
        $addr_set = array();

        foreach(self::$addressAttributes as $key)
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
            }
        }
    }
}
