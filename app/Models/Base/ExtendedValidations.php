<?php

namespace RZP\Models\Base;

use Lib\PhoneBook;
use libphonenumber\NumberParseException;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class ExtendedValidations extends \Razorpay\Spine\Validation\LaravelValidatorEx
{
    /**
     * Create basic contact validate
     *
     * @param  string   $attribute     Attrbute name
     * @param  string   $contact       Contact number
     * @param  array    $parameters    Parameter list
     * @return boolean
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
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE,
                        $attribute);

                // This generally indicates the string passed in had less than 3 digits in it. More
                // specifically, the number failed to match the regular expression VALID_PHONE_NUMBER in
                // PhoneNumberUtil.
                // Example: +91 322-23-43b
                case NumberParseException::NOT_A_NUMBER:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT,
                        $attribute);

                // This indicates the string started with an international dialing prefix, but after this was
                // stripped from the number, had less digits than any valid phone number (including country
                // code) could have.
                // Example: +91 998765432
                case NumberParseException::TOO_SHORT_AFTER_IDD:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
                        $attribute);

                // This indicates the string, after any country code has been stripped, had less digits than any
                // valid phone number could have.
                // Example: +91 9
                case NumberParseException::TOO_SHORT_NSN:
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
                        $attribute);

                // Example: +1 234-234-234-234-234-23
                case NumberParseException::TOO_LONG:
                    throw new Exception\BadRequestException(
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
            throw new Exception\BadRequestException(
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
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG,
                $attribute);
        }

        return true;
    }

    /**
     * Create notes validation
     *
     * @param string $attribute
     * @param array $notes
     * @param array $parameters
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
            throw new Exception\BadRequestException($code, 'notes');
        }

        return true;
    }

    /**
     * Check notes array is flat
     *
     * @param array $notes
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
}
