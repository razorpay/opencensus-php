<?php

namespace RZP\Models\Base;

use Lib\Gstin;
use Lib\PhoneBook;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use libphonenumber\NumberParseException;
use Egulias\EmailValidator\EmailValidator;
use RZP\Models\Customer\Token\RecurringStatus;
use RZP\Models\Base\Traits\CustomReplacesAttributes;
use RZP\Exception\BadRequestValidationFailureException;
use Illuminate\Validation\Concerns\FilterEmailValidation;
use Egulias\EmailValidator\Validation as EmailValidation;

class ExtendedValidations extends \Razorpay\Spine\Validation\LaravelValidatorEx
{
    use CustomReplacesAttributes;

    const MYSQL_UNSIGNED_INT_MIN        = 0;
    const MYSQL_UNSIGNED_INT_MAX        = 4294967295;

    const MYSQL_SIGNED_INT_MIN          = -2147483648;
    const MYSQL_SIGNED_INT_MAX          = 2147483647;

    const MYSQL_SIGNED_BIGINT_MIN       = -9223372036854775808;
    const MYSQL_SIGNED_BIGINT_MAX       = 9223372036854775807;

    const MYSQL_UNSIGNED_BIGINT_MIN     = 0;
    const MYSQL_UNSIGNED_BIGINT_MAX     = 18446744073709551615;

    const INT_PERCENTAGE_MIN            = 0;
    const INT_PERCENTAGE_MAX            = 10000;

    const EPOCH_DEFAULT_MIN             = 946684800;                  // Sat Jan  1 05:30:00 IST 2000
    const EPOCH_DEFAULT_MAX             = 4765046400;                 // Tuesday, 31 December 05:30:00 IST 2120, as it is bigInt

    const PAN_NUMBER_REGEX          = '/^[A-Za-z]{5}\d{4}[A-Za-z]{1}$/';
    const PERSONAL_PAN_NUMBER_REGEX = '/^[A-Za-z]{3}[Pp][A-Za-z]{1}\d{4}[A-Za-z]{1}$/';
    const COMPANY_PAN_NUMBER_REGEX  = '/^[A-Za-z]{3}[CcHhFfAaTtBbLlJjGg][A-Za-z]{1}\d{4}[A-Za-z]{1}$/';
    const COMPANY_CIN_REGEX         = '/^([A-Z|a-z]{3}-\d{4}|[F|f]\w{3}-\d{4}|[ulUL]\d{5}[A-Z|a-z]{2}\d{4}[A-Z|a-z]{3}\d{6})$/';

    /**
     * Overridden from \Illuminate\Validation\Validator because we have added
     * custom rules for integer data type. This list is used by framework for
     * various operations on integer data type attributes under validations,
     * e.g. getSize() method etc.
     *
     * @var array
     */
    protected $numericRules = [
        'Numeric',
        'Integer',
        'MysqlSignedInt',
        'MysqlUnsignedInt',
        'IntPercentage',
        'Epoch',
    ];

    protected function validatePublicId($attribute, $id)
    {
        //
        // This is required because even if the validation
        // rules have `string`, this might get executed first.
        // If an array is sent, a server error is thrown
        // because of preg_match
        //
        if (is_string($id) === false)
        {
            throw new BadRequestValidationFailureException("The $attribute must be a string");
        }

        $match = preg_match(PublicEntity::SIGNED_PUBLIC_ID_REGEX, $id);

        //
        // This should be compared against 1 and not 0 because
        // preg_match returns either 0 or false in case of failure.
        //
        if ($match !== 1)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return true;
    }

    protected function validateUnsignedId($attribute, $id)
    {
        if (is_string($id) === false)
        {
            throw new BadRequestValidationFailureException("The $attribute must be a string");
        }

        $match = preg_match(UniqueIdEntity::UNSIGNED_ID_REGEX, $id);

        //
        // This should be compared against 1 and not 0 because
        // preg_match returns either 0 or false in case of failure.
        //
        if ($match !== 1)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return true;
    }

    protected function validateSequentialArray($attribute, $value)
    {
        //
        // `is_array` check is required because even if the validation
        // rules have `array`, sequential_array might get executed first.
        //
        if ((is_array($value) === false) or
            (is_sequential_array($value) === false))
        {
            throw new BadRequestValidationFailureException("$attribute must be an array");
        }

        return true;
    }

    protected function validateAssociativeArray($attribute, $value)
    {
        //
        // `is_array` check is required because even if the validation
        // rules have `array`, associative_array might get executed first.
        //
        if ((is_array($value) === false) or
            (is_associative_array($value) === false))
        {
            throw new BadRequestValidationFailureException("$attribute must be an object");
        }

        return true;
    }

    /**
     * Create basic contact validate
     *
     * @param  string $attribute  Attribute name
     * @param  string $contact    Contact number
     * @param  array  $parameters Parameter list
     *
     * @return bool
     * @throws BadRequestException
     */
    protected function validateContactSyntax($attribute, $contact, $parameters)
    {
        $code = null;
        $message = null;

        try
        {
            $number = new PhoneBook($contact);
        }
        catch (NumberParseException $e)
        {
            switch ($e->getErrorType())
            {
                // Example: +697 87654321323
                case NumberParseException::INVALID_COUNTRY_CODE:
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE,
                        $attribute);

                // This generally indicates the string passed in had less than 3 digits in it. More
                // specifically, the number failed to match the regular expression VALID_PHONE_NUMBER in
                // PhoneNumberUtil.
                // Example: +91 322-23-43b
                case NumberParseException::NOT_A_NUMBER:
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT,
                        $attribute);

                // This indicates the string started with an international dialing prefix, but after this was
                // stripped from the number, had less digits than any valid phone number (including country
                // code) could have.
                // Example: +91 998765432
                case NumberParseException::TOO_SHORT_AFTER_IDD:
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
                        $attribute);

                // This indicates the string, after any country code has been stripped, had less digits than any
                // valid phone number could have.
                // Example: +91 9
                case NumberParseException::TOO_SHORT_NSN:
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
                        $attribute);

                // Example: +1 234-234-234-234-234-23
                case NumberParseException::TOO_LONG:
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG,
                        $attribute);
            }
        }

        $formattedContact = (string) $number;

        /**
         * The minimum contact number length including international
         * prefix (country code) is theoritically 8 digits.
         *
         * See http://stackoverflow.com/a/17814276/368328
         *
         * libphonenumber only matches NSN (National significant number)
         * we are checking for the length of mobile and fixed_line
         */
        if (strlen($formattedContact) < 8)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
                $attribute);
        }

        /**
         * See https://en.wikipedia.org/wiki/Telephone_numbering_plan#International_numbering_plan
         * for why 15
         * libphonenumber takes 17 as limit, because german number can be longer. However, we are
         * are sticking to the ITU standard for now.
         */
        if (strlen($formattedContact) > 15)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG,
                $attribute);
        }

        return true;
    }
    /**
     * Validate that an attribute does not exist.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $parameters
     * @return bool
     */
    public function validateProhibited($attribute, $value)
    {
        return false;
    }

    /**
     * Validates notes input for fetch requests. We expect a string value
     * which is searched against the whole notes object.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     * @throws BadRequestValidationFailureException
     */
    protected function validateNotesFetch(string $attribute, $value)
    {
        $error = null;

        if (is_string($value) === false)
        {
            $error = 'notes should be a string value';
        }

        $len = strlen($value);

        if (($len < 2) or ($len > 256))
        {
            $error = 'notes value length should be between 2 and 256';
        }

        if ($error !== null)
        {
            throw new BadRequestValidationFailureException($error, $attribute, $value);
        }

        return true;
    }

    /**
     * Validates notes input for create/put requests.
     *
     * @param string $attribute
     * @param array  $notes
     * @param array  $parameters
     *
     * @return bool
     * @throws BadRequestException
     */
    protected function validateNotes($attribute, $notes, $parameters)
    {
        $code = null;

        if (is_array($notes) === false)
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY;
        }
        else if (count($notes) > 15)
        {
            $code = ErrorCode::BAD_REQUEST_NOTES_TOO_MANY_KEYS;
        }
        else
        {
            $code = $this->validateNotesKeyValue($notes);
        }

        if ($code !== null)
        {
            throw new BadRequestException($code, 'notes');
        }

        return true;
    }

    /**
     * Check notes array is flat
     *
     * @param array $notes
     *
     * @return null|string
     */
    protected function validateNotesKeyValue(array $notes)
    {
        $code = null;
        $notify = false;

        foreach ($notes as $key => $note)
        {
            if (is_array($note))
            {
                $code = ErrorCode::BAD_REQUEST_NOTES_VALUE_CANNOT_BE_ARRAY;
            }
            else if (strlen($note) > 512)
            {
                $code = ErrorCode::BAD_REQUEST_NOTES_VALUE_TOO_LARGE;
            }
            else if (strlen($key) > 256)
            {
                $code = ErrorCode::BAD_REQUEST_NOTES_KEY_TOO_LARGE;
            }
            else if (is_numeric($key))
            {
                $notify = true;
            }

            if ($code !== null)
                break;
        }

        // Collecting data for notes with integer keys
        if ($notify === true)
        {
            $app = \App::getFacadeRoot();

            $app['trace']->info(
               TraceCode::PAYMENT_NOTES_INVALID,
               $notes);
        }

        return $code;
    }

    /**
     * Validates if the value is epoch.
     * By default it checks if the value is in between Jan 2000 - Jan 2100.
     * Parameters(min and max value) can be passed when using this rule.
     *
     * Eg usage:
     * epoch:946684800,946684801
     * epoch
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return boolean
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateEpoch(string $attribute, $value, array $parameters)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false)
        {
            throw new BadRequestValidationFailureException("$attribute must be an integer.");
        }

        array_walk(
            $parameters,
            function (& $v, $i)
            {
                $v = intval($v);
            });

        $min = $parameters[0] ?? self::EPOCH_DEFAULT_MIN;
        $max = $parameters[1] ?? self::EPOCH_DEFAULT_MAX;

        $isValid = (($value >= $min) and ($value <= $max));

        if ($isValid === false)
        {
            throw new BadRequestValidationFailureException("$attribute must be between $min and $max");
        }

        return true;
    }

    protected function validateMysqlSignedInt(string $attribute, $value)
    {
        $isInteger = $this->validateInteger($attribute, $value);

        $isInRange = $this->validateBetween(
                                $attribute,
                                $value,
                                [
                                    self::MYSQL_SIGNED_INT_MIN,
                                    self::MYSQL_SIGNED_INT_MAX,
                                ]);

        return ($isInteger and $isInRange);
    }

    protected function validateMysqlUnsignedInt(string $attribute, $value)
    {
        $isInteger = $this->validateInteger($attribute, $value);

        $isInRange = $this->validateBetween(
                                $attribute,
                                $value,
                                [
                                    self::MYSQL_UNSIGNED_INT_MIN,
                                    self::MYSQL_UNSIGNED_INT_MAX,
                                ]);

        return ($isInteger and $isInRange);
    }

    protected function validateMysqlSignedBigInt(string $attribute, $value)
    {
        $isFloat = (filter_var($value, FILTER_VALIDATE_FLOAT) !== false);

        $isInRange = $this->validateBetween(
            $attribute,
            $value,
            [
                self::MYSQL_SIGNED_BIGINT_MIN,
                self::MYSQL_SIGNED_BIGINT_MAX,
            ]);

        return ($isFloat and $isInRange);
    }

    protected function validateMysqlUnsignedBigInt(string $attribute, $value)
    {
        $isFloat = (filter_var($value, FILTER_VALIDATE_FLOAT) !== false);

        $isInRange = $this->validateBetween(
            $attribute,
            $value,
            [
                self::MYSQL_UNSIGNED_BIGINT_MIN,
                self::MYSQL_UNSIGNED_BIGINT_MAX,
            ]);

        return ($isFloat and $isInRange);
    }

    /**
     * Checks if the value is a supported utf8 encoded string
     * Currently we don't support utf8mb4 encoding and this method checks the same
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool validation result
     * @throws BadRequestValidationFailureException
     */
    protected function validateUtf8(string $attribute, $value)
    {
        if ((empty($value) === false) and (is_string($value) === true))
        {
            if (is_valid_utf8($value) === false)
            {
                throw new BadRequestValidationFailureException(
                    "$attribute contains invalid characters");
            }
        }

        return true;
    }

    /**
     * Validates percentage value depicted as integer
     * For ex: 18.5% as 1850
     *
     * @param $attribute
     * @param $value
     *
     * @return bool
     */
    protected function validateIntPercentage($attribute, $value)
    {
        $isInteger = $this->validateInteger($attribute, $value);

        $isInRange = $this->validateBetween(
            $attribute,
            $value,
            [
                self::INT_PERCENTAGE_MIN,
                self::INT_PERCENTAGE_MAX,
            ]);

        return (($isInteger === true) and ($isInRange === true));
    }

    protected function validateGstin($attribute, $value)
    {
        $isString = $this->validateString($attribute, $value);

        return (($isString === true) and (Gstin::isValid($value) === true));
    }

    protected function validatePan($attribute, $value)
    {
        $isAlphaNum = $this->validateAlphaNum($attribute, $value);

        return (($isAlphaNum === true) and
                (preg_match(self::PAN_NUMBER_REGEX, $value) === 1));
    }

    protected function validateCompanyPan($attribute, $value)
    {
        return (preg_match(self::COMPANY_PAN_NUMBER_REGEX, $value) === 1);
    }

    protected function validatePersonalPan($attribute, $value)
    {
        return (preg_match(self::PERSONAL_PAN_NUMBER_REGEX, $value) === 1);
    }

    protected function validateCompanyCin($attribute, $value)
    {
        return (preg_match(self::COMPANY_CIN_REGEX, $value) === 1);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dashes,
     * underscores, and spaces (only space, no tabs, returns etc)
     *
     * @param  string  $attribute
     * @param  mixed   $value
     *
     * @return bool
     */
    public function validateAlphaDashSpace($attribute, $value)
    {
        if ((is_string($value) === false) and
            (is_numeric($value) === false))
        {
            return false;
        }

        return preg_match('/^[ \pL\pM\pN_-]+$/u', $value) > 0;
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters and underscores
     *
     * @param  string  $attribute
     * @param  mixed   $value
     *
     * @return bool
     */
    public function validateAlphaNumUnderscore($attribute, $value)
    {
        if ((is_string($value) === false) and
            (is_numeric($value) === false))
        {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_]+$/u', $value) > 0;
    }

    public function validateMinAmount($attribute, $amount, $parameters)
    {
        $currencyKey = $parameters[0] ?? 'currency';

        $currency = array_get($this->getData(), $currencyKey, Currency::INR);

        $minAmount = Currency::getMinAmount($currency);

        return ($amount >= $minAmount);
    }

    /**
     * Validate that the given currency is supported or not
     * @param $attribute
     * @param $currency
     * @return mixed
     */
    public function validateCurrency($attribute, $currency)
    {
        return Currency::isSupportedCurrency($currency);
    }

    /**
     * Validate that an attribute is a valid e-mail address.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateEmail($attribute, $value, $parameters)
    {
        if (! is_string($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
            return false;
        }

        $validations = collect($parameters)
            ->unique()
            ->map(function ($validation) {
                if ($validation === 'rfc') {
                    return new EmailValidation\RFCValidation();
                } elseif ($validation === 'strict') {
                    return new EmailValidation\NoRFCWarningsValidation();
                } elseif ($validation === 'dns') {
                    return new EmailValidation\DNSCheckValidation();
                } elseif ($validation === 'spoof') {
                    return new EmailValidation\SpoofCheckValidation();
                } elseif ($validation === 'filter') {
                    return new FilterEmailValidation();
                }
            })
            ->values()
            ->all() ?: [new FilterEmailValidation()];

        return (new EmailValidator)->isValid($value, new EmailValidation\MultipleValidationWithAnd($validations));
    }

    /**
     * Validate that an attribute is a valid token recurring status.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function validateRecurringStatus(string $attribute, $value)
    {
        return RecurringStatus::isRecurringStatusValid($value);
    }

}
