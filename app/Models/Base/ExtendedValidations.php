<?php

namespace RZP\Models\Base;

use Lib\PhoneBook;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use libphonenumber\NumberParseException;
use RZP\Exception\BadRequestValidationFailureException;

class ExtendedValidations extends \Razorpay\Spine\Validation\LaravelValidatorEx
{
    const MYSQL_UNSIGNED_INT_MIN = 0;
    const MYSQL_UNSIGNED_INT_MAX = 4294967295;

    const MYSQL_SIGNED_INT_MIN   = -2147483648;
    const MYSQL_SIGNED_INT_MAX   = 2147483647;

    const EPOCH_DEFAULT_MIN      = 946684800;                  // Sat Jan  1 05:30:00 IST 2000
    const EPOCH_DEFAULT_MAX      = self::MYSQL_SIGNED_INT_MAX; // Tue Jan 19 08:44:07 IST 2038, *MySQL max for Signed Int

    protected $numericRules = [
        'Numeric',
        'Integer',
        'MysqlSignedInt',
        'MysqlUnsignedInt',
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

        $match = preg_match('/\b[a-z]{0,5}_[a-zA-Z0-9]{14}\b/', $id);

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
            else if (strlen($note) > 256)
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
        $this->validateInteger($attribute, $value);
        $this->validateBetween($attribute, $value, [self::MYSQL_SIGNED_INT_MIN, self::MYSQL_SIGNED_INT_MAX]);

        return true;
    }

    protected function validateMysqlUnsignedInt(string $attribute, $value)
    {
        $this->validateInteger($attribute, $value);
        $this->validateBetween($attribute, $value, [self::MYSQL_UNSIGNED_INT_MIN, self::MYSQL_UNSIGNED_INT_MAX]);

        return true;
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
}
