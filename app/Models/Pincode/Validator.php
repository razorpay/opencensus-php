<?php

namespace RZP\Models\Pincode;

class Validator
{
    protected $country;

    public function __construct($country)
    {
        $this->country = $country;
    }

    public function validate($pincode): bool
    {
        return ((preg_match(Pincode::REGEX[$this->country], $pincode) === 1) and
            ($pincode <= Pincode::MAX_PINCODE[$this->country]) and
            ($pincode >= Pincode::MIN_PINCODE[$this->country]));
    }
}
