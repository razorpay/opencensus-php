<?php

namespace RZP\Models\Merchant\Product\Util;

use Lib\PhoneBook;
use RZP\Models\Merchant;

class OtpRequestHandler
{
    public function formatContactNumber (string $contactNumber, Merchant\Entity $merchant) :string
    {
        $number = new PhoneBook($contactNumber, true, $merchant->getCountry());

        return ($number->isValidNumber() === true) ? $number->format() :  $number->getRawInput();
    }
}
