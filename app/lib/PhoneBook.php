<?php

namespace Lib;

use App;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class PhoneBook
{
    const DEFAULT_COUNTRY_CODE = 'IN';

    protected $libphonenumber = null;

    protected $rawNumber = null;

    protected $phoneNumber = null;

    /*
     * Phone number formats
     *
     * E164             - Standardized format - +919987654321
     * INTERNATIONAL    - International format - +91 99876 54321
     * NATIONAL         - Gives national number - 099876 54321
     * RFC3966          - Format for using in html links - tel:+91-99876-54321
     */

    const E164            = 'e164';
    const INTERNATIONAL   = 'international';
    const NATIONAL        = 'national';
    const DOMESTIC        = 'domestic';
    const RFC3966         = 'rfc3966';
    const SPACE_SEPARATED = 'space_separated';

    const FORMATS = [
        self::E164          => PhoneNumberFormat::E164,
        self::INTERNATIONAL => PhoneNumberFormat::INTERNATIONAL,
        self::NATIONAL      => PhoneNumberFormat::NATIONAL,
        self::RFC3966       => PhoneNumberFormat::RFC3966,
    ];

    const SPECIAL_CHARS = ['-', '(', ')', ' '];

    const UNKNOWN_REGION = 'ZZ';

    /**
     * @param string  $phoneNumber
     * @param boolean $parseSilently Whether to throw exception or not
     */
    public function __construct($phoneNumber, $parseSilently = false, $countryCode = self::DEFAULT_COUNTRY_CODE)
    {
        $this->app = App::getFacadeRoot();

        $this->libphonenumber = $this->app['libphonenumber'];

        $phoneNumber = (string) $phoneNumber;
        $this->rawNumber = $phoneNumber;

        try
        {
            // Second argument is a default country code
            $this->phoneNumber = $this->libphonenumber->parse($phoneNumber, $countryCode);
        }
        catch (NumberParseException $e)
        {
            if ($parseSilently === false)
            {
                throw $e;
            }
        }
    }

    public function isValidNumber()
    {
        // For backward compatibility
        if ($this->phoneNumber === null)
        {
            return false;
        }

        return $this->libphonenumber->isValidNumber($this->phoneNumber);
    }

    public function isPossibleNumber()
    {
        return $this->libphonenumber->isPossibleNumber($this->phoneNumber);
    }

    public function getRawInput()
    {
        $normalizedRawNumber = $this->normalizeNumber($this->rawNumber);

        return $normalizedRawNumber;
    }

    /**
     * Removes spaces, special characters etc.,
     * gives back digits along with plus sign
     */
    public function normalizeNumber($number)
    {
        if (is_string($number) === false)
        {
            return $number;
        }

        $number = str_replace(self::SPECIAL_CHARS, '', $number);

        // Remove the 0 at the start
        if ((strlen($number) > 1) and
            ($number[0] === '0'))
        {
            $number = substr($number, 1);
        }

        return $number;
    }

    /**
     * Returns normalized number for phonebook library.
     * However, if the number is invalid, then it returns null.
     */
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

            case self::SPACE_SEPARATED:
                $countryCode = $number->getCountryCode();
                $nationalNumber = $number->getNationalNumber();
                $contact = '+' . $countryCode . ' ' . $nationalNumber;
                break;

            // Standardized format E164 - +919987654321
            default:
                $contact = $libphonenumber->format($number, PhoneNumberFormat::E164);
                break;
        }

        return $contact;
    }

    /**
     * Gets region code for the given number
     */
    public function getRegionCodeForNumber()
    {
        if ($this->phoneNumber !== null)
        {
            return $this->libphonenumber->getRegionCodeForNumber($this->phoneNumber);
        }

        return self::UNKNOWN_REGION;
    }

    public function isValidNumberForRegion(string $regionCode): bool
    {
        if ($this->phoneNumber !== null)
        {
            return $this->libphonenumber->isValidNumberForRegion($this->phoneNumber, $regionCode);
        }

        return false;
    }

    public function __toString()
    {
        if ($this->isValidNumber() === true)
        {
            return $this->format();
        }

        return $this->getRawInput();
    }

    /**
     * Gets underlying PhoneNumber object.
     * @return PhoneNumber|null
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * Get different mobile number formats
     * @return array
     */
    public function getMobileNumberFormats(): array
    {
        return [
            $this->format(PhoneBook::E164),
//            $this->format(PhoneBook::INTERNATIONAL),
            $this->format(PhoneBook::DOMESTIC),
//            $this->format(PhoneBook::NATIONAL),
            $this->format(PhoneBook::SPACE_SEPARATED),
        ];
    }
}
