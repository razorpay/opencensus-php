<?php

namespace RZP\Models\Vpa;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const MIN_LENGTH = '3';

    const MAX_LENGTH = '100';

    const VPA_REGEX = '/^[-\.a-zA-Z0-9][a-zA-Z0-9\.-]*@[a-zA-Z]+(?:[0-9]*\.ifsc\.npci)?$/';

    /*
     * RZP\Models\FundAccount\Core::REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS_FROM_VPA_USERNAME
     * needs to be updated accordingly if the validation regex for address field is changed in
     * $createRules (in the validateAddress method).
     */
    protected static $createRules = [
        Entity::ADDRESS => 'required|string|custom',
    ];

    protected static $createVirtualVpaRules = [
        Entity::USERNAME => 'required|string|max:40',
        Entity::ADDRESS  => 'required|string|between:3,100|regex:"[a-zA-Z0-9][a-zA-Z0-9\.-]{2,}@[a-zA-Z]+"|custom',
    ];

    public function validateAddress(string $attribute, string $address)
    {
        if (((bool) preg_match(self::VPA_REGEX, $address) === false) or
            (strlen($address) > self::MAX_LENGTH) or
            (strlen($address) < self::MIN_LENGTH))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                $attribute,
                [
                    'vpa' => $address
                ]);
        }
    }
}
