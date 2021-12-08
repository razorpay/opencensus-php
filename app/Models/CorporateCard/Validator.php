<?php

namespace RZP\Models\CorporateCard;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NUMBER             => 'required|numeric|luhn|digits_between:12,19',
        Entity::EXPIRY_MONTH       => 'required|integer|digits_between:1,2|max:12|min:1',
        Entity::EXPIRY_YEAR        => 'required|integer|digits:4|non_past_year',
        Entity::NAME               => 'sometimes|regex:(^[a-zA-Z.\- 0-9\']+$)|max:100',
        Entity::HOLDER_NAME        => 'sometimes|regex:(^[a-zA-Z.\- 0-9\']+$)|max:100',
        Entity::BILLING_CYCLE      => 'sometimes|string|max:100',
    ];

    protected static $editRules = [
        Entity::IS_ACTIVE  => 'sometimes|boolean',
        Entity::NAME       => 'sometimes|regex:(^[a-zA-Z.\- 0-9\']+$)|max:100',
    ];

    protected static $createValidators = [
            'expiry_date'
    ];

    protected static $tokenRules = [
        Entity::TOKEN => 'required|max:255|alpha_num',
    ];

    protected function validateExpiryDate($input)
        {
            $month = $input[Entity::EXPIRY_MONTH];
            $year = $input[Entity::EXPIRY_YEAR];

            $currentMonth = date('n');
            $currentYear = (int) date('Y');

            if (($month < $currentMonth) and
                ($year <= $currentYear))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CORPORATE_CARD_INVALID_EXPIRY_DATE);
            }
        }
}
