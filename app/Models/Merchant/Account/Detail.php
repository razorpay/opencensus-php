<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class Detail
{
    protected static $detailMap = [
        'contact'       => [
            'name'                  => DetailEntity::CONTACT_NAME,
            'email'                 => DetailEntity::CONTACT_EMAIL,
            'mobile'                => DetailEntity::CONTACT_MOBILE,
        ],
        'business'      => [
            'type'                  => DetailEntity::BUSINESS_TYPE,
            'name'                  => DetailEntity::BUSINESS_NAME,
        ],
        'company'       => [
            'cin'                   => DetailEntity::COMPANY_CIN,
        ],
        'transaction'   => [
            'value'                 => DetailEntity::TRANSACTION_VALUE,
        ],
        'promoter'      => [
            'pan'                   => DetailEntity::PROMOTER_PAN,
        ],
        'bank'          => [
            // 'name'                  => DetailEntity::BANK_NAME,
            'account_number'        => DetailEntity::BANK_ACCOUNT_NUMBER,
            'account_name'          => DetailEntity::BANK_ACCOUNT_NAME,
            'account_type'          => DetailEntity::BANK_ACCOUNT_TYPE,
        ],
        'website'       => [

        ],
        'submit'                    => DetailEntity::SUBMIT,
        'can_submit'                => 'can_submit'
    ];

    // @todo: Refactor
    public static function flattenInputArray(array $input)
    {
        $flattened = [];

        foreach ($input as $key => $value)
        {
            if (is_array($value) === true)
            {
                foreach ($value as $nestedKey => $nestedValue)
                {
                    $mappedKey = self::$detailMap[$key][$nestedKey];

                    $flattened[$mappedKey] = $nestedValue;
                }
            }
            else
            {
                $flattened[$key] = $value;
            }
        }

        return $flattened;
    }

    // @todo: Refactor
    public static function expandNestedDetailArray($input)
    {
        $expanded = [];

        foreach (self::$detailMap as $key => $value)
        {
            if (is_array($value) === true)
            {
                foreach ($value as $nestedKey => $nestedValue)
                {
                    $mappedKey = self::$detailMap[$key][$nestedKey];

                    if ($input[$mappedKey] !== null)
                    {
                        $expanded[$key][$nestedKey] = $input[$mappedKey] ?? null;
                    }
                }

            }
            else
            {
                $expanded[$key] = $input[$key] ?? null;
            }
        }

        $expanded = array_filter($expanded, function($var) {
                        return is_null($var) === false;
                    });

        return $expanded;
    }
}
