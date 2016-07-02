<?php

namespace Lib;

use App;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseExeption;

class PhoneBook
{
    const DEFAULT_COUNTRY_CODE = 'IN';

    protected $libphonenumber = null;

    protected $rawNumber = null;

    protected $phoneNumber = null;

    /*
     * Phone number formats
     *
     */
    const E164          = 'e164';
    const INTERNATIONAL = 'international';
    const NATIONAL      = 'national';
    const DOMESTIC      = 'domestic';
    const RFC3966       = 'rfc3966';

    const FORMATS = [
        self::E164          => PhoneNumberFormat::E164,
        self::INTERNATIONAL => PhoneNumberFormat::INTERNATIONAL,
        self::NATIONAL      => PhoneNumberFormat::NATIONAL,
        self::RFC3966       => PhoneNumberFormat::RFC3966,
    ];

    protected $specialChars = ['-', '(', ')', ' '];

    public function __construct($phoneNumber, $parseSilently = false)
    {
        $app = App::getFacadeRoot();

        $this->libphonenumber = $app['libphonenumber'];

        $this->rawNumber = $phoneNumber;

        try
        {
            // Second argument is a default country code
            $this->phoneNumber = $this->libphonenumber->parse($phoneNumber, self::DEFAULT_COUNTRY_CODE);
        }
        catch (NumberParseExeption $e)
        {
            if ($parseSilently)
            {
                throw $e;
            }
        }
    }

    public function isValidNumber()
    {
        $libphonenumber = $this->libphonenumber;
        $number = $this->phoneNumber;

        // For backward compatibility
        if ($number === null)
        {
            return false;
        }

        return $libphonenumber->isValidNumber($number);
    }

    public function isPossibleNumber()
    {
        $libphonenumber = $this->libphonenumber;
        $number = $this->phoneNumber;

        return $libphonenumber->isPossibleNumber($number);
    }

    public function getRawInput()
    {
        return $this->normalizeNumber($this->rawNumber);
    }

    public function normalizeNumber($number)
    {
        if (is_string($number) === false)
        {
            return $number;
        }

        $number = str_replace($this->specialChars, '', $number);

        // Remove the 0 at the start
        if ((strlen($number) > 1) and
            ($number[0] === '0'))
        {
            $number = substr($number, 1);
        }

        return $number;
    }

    public function getNormalizedNumber()
    {
        return $this->phoneNumber->getNationalNumber();
    }

    public function format($format = self::E164)
    {
        $libphonenumber = $this->libphonenumber;
        $number = $this->phoneNumber;

        if ($number === null)
        {
            return $this->getRawInput();
        }

        switch ($format)
        {
            // Standardized format - +919987654321
            case self::E164:
            // International format - +91 99876 54321
            case self::INTERNATIONAL:
            // Gives national number - 099876 54321
            case self::NATIONAL:
            // RFC3966 format for using in html links - tel:+91-99876-54321
            case self::RFC3966:
                $contact = $libphonenumber->format($number, self::FORMATS[$format]);
                break;

            // Gives national number without zero and space - 9987654321
            case self::DOMESTIC:
                $contact = $number->getNationalNumber();
                break;

            // Standardized format E164 - +919987654321
            default:
                $contact = $libphonenumber->format($number, PhoneNumberFormat::E164);
                break;
        }

        return $contact;
    }

    public function __toString()
    {
        if ($this->isValidNumber() === true)
        {
            return $this->format();
        }

        return $this->getRawInput();
    }
}