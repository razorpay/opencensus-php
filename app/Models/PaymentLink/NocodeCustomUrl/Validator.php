<?php

namespace RZP\Models\PaymentLink\NocodeCustomUrl;

use RZP\Base;
use RZP\Models\PaymentLink\ViewType;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    /**
     * @var string[]
     */
    protected static $upsertRules = [
        Entity::SLUG            => 'required|min:3|max:' . Entity::SLUG_LEN,
        Entity::DOMAIN          => 'required|min:3|max:' . Entity::DOMAIN_LEN . '|custom',
        Entity::PRODUCT         => 'required|custom',
        Entity::META_DATA       => 'nullable|array',
    ];

    /**
     * @param string $attribute
     * @param string $value
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateProduct(string $attribute, string $value)
    {
        ViewType::checkViewType($value);
    }

    /**
     * @param string $attribute
     * @param string $value
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateDomain(string $attribute, string $value)
    {
        // source https://websolutionstuff.com/post/how-to-validate-url-in-php-with-regex
        
        $regex = "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
        $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})";
        $regex .= "(\:[0-9]{2,5})?";

        if (preg_match("/^$regex$/i", $value) != 1)
        {
            throw new BadRequestValidationFailureException('Invalid domain format: ' . $value);
        }
    }
}
