<?php

namespace Lib;

use App;
use libphonenumber\PhoneNumberFormat;

class PhoneBook
{
    const DEFAULT_COUNTRY_CODE = 'IN';

    /*
     * Phone number formats
     *
     */
    const E164          = 'e164';
    const INTERNATIONAL = 'international';
    const NATIONAL      = 'national';
    const DOMESTIC      = 'domestic';
    const RFC3966       = 'rfc3966';

    protected $specialChars = ['+', '-', '(', ')', ' '];

    public function __construct($phoneNumber)
    {
        $app = App::getFacadeRoot();

        $this->libphonenumber = $app['libphonenumber'];

        // Second argument is a default country code
        $this->phoneNumber = $this->libphonenumber->parse($phoneNumber, self::DEFAULT_COUNTRY_CODE);
    }

    public function isValidNumber()
    {
        $libphonenumber = $this->libphonenumber;
        $number = $this->phoneNumber;

        return $libphonenumber->isValidNumber($number);
    }

    public function isPossibleNumber()
    {
        $libphonenumber = $this->libphonenumber;
        $number = $this->phoneNumber;

        return $libphonenumber->isPossibleNumber($number);
    }

    public function format($format = self::E164)
    {
        $libphonenumber = $this->libphonenumber;
        $number = $this->phoneNumber;

        switch ($format)
        {
            case self::INTERNATIONAL:
                $contact = $libphonenumber->format($number, PhoneNumberFormat::E164);
                break;

            case self::NATIONAL:
                $contact = $libphonenumber->format($number, PhoneNumberFormat::NATIONAL);
                break;

            case self::DOMESTIC:
                $contact = $number->getNationalNumber();
                break;

            case self::RFC3966:
                $contact = $libphonenumber->format($number, PhoneNumberFormat::RFC3966);
                break;

            default:
                $contact = $libphonenumber->format($number, PhoneNumberFormat::E164);
                break;
        }

        return $contact;
    }
}