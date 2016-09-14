<?php

namespace RZP\Models\Card;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::NUMBER             => 'required|numeric|luhn|digits_between:12,19',
        Entity::EXPIRY_MONTH       => 'required|integer|digits_between:1,2|max:12|min:1',
        Entity::EXPIRY_YEAR        => 'required|integer|digits:4|non_past_year',
        Entity::CVV                => 'sometimes|numeric|digits_between:3,4',
        Entity::NAME               => 'required|alpha_space|max:100',
        Entity::VAULT_TOKEN        => 'sometimes|string',
        Entity::VAULT              => 'required_with:vault_token|in:tokenex'
    );

    protected static $editRules = array(
        Entity::NUMBER             => 'required|numeric|luhn|digits_between:12,19',
        Entity::CVV                => 'sometimes|numeric|digits_between:3,4',
        Entity::NAME               => 'sometimes|alpha_space|max:100',
        Entity::VAULT_TOKEN        => 'sometimes|string',
        Entity::VAULT              => 'required_with:vault_token|in:tokenex'
    );

    protected static $createValidators = array(
        'expiry_date'
    );

    protected function validateExpiryDate($input)
    {
        $month = $input['expiry_month'];
        $year = $input['expiry_year'];

        $currentMonth = date('n');
        $currentYear = (int) date('Y');

        if (($month < $currentMonth) and
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
