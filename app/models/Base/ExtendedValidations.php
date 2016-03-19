<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;
use Symfony\Component\Translation\TranslatorInterface;

class ExtendedValidations extends \Razorpay\Spine\Validation\LaravelValidatorEx
{
    /**
     * Create a new Validator instance.
     *
     * @param  \Symfony\Component\Translation\TranslatorInterface  $translator
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function __construct(
        TranslatorInterface $translator,
        array $data,
        array $rules,
        array $messages = array(),
        array $customAttributes = array())
    {
        $this->addCustomMessages($messages);

        parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    }

    protected function addCustomMessages(& $messages)
    {
        $messages = array_merge($messages, \Razorpay\Spine\Validation\Messages::$messages);
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
        if (isset($notes) === false)
            return true;

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
            $code = $this->validateNotesArray($notes);
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

            if ($code !== null)
                break;
        }

        return $code;
    }

    /**
     * Create contact (mobile number) validation
     *
     * @param string $attribute
     * @param string $contact
     * @param array $parameters
     */
    protected function validateContact($attribute, $contact, $parameters)
    {
        $code = null;
        $message = null;

        $field = $attribute;

        if (is_string($contact) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_NOT_DIGITS,
                $field);
        }

        $origContact = $contact;

        // Except digits, only '+' symbol is allowed in the beginning
        if ($contact[0] === '+')
            $contact = substr($contact, 1);

        if (ctype_digit($contact) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_NOT_DIGITS,
                $field);
        }

        if (strlen($contact) < 10)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_MIN_TEN_DIGITS,
                $field);
        }

        if (strlen($contact) > 12)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_MAX_TWELVE_DIGITS,
                $field);
        }

        return true;
    }

    /**
     * Create requiredWithNested validation (Laravel 4.2 doesn't support nested array validations)
     * Nested array value should be required if any input is present
     * Usage: required_with_nested:key1,key2,key3,last_key
     * if arr['last_key'] is present then arr['key1']['key2']['key3'] will be validated
     *
     * @param string $attribute
     * @param string $value
     * @param array $parameters
     */
    protected function validateRequiredWithNested($attribute, $value, $parameters)
    {
        $requiredWith = [array_pop($parameters)];

        if ( ! $this->allFailingRequired($requiredWith))
        {
            return $this->validateRequiredNested($attribute, $value, $parameters);
        }

        return true;
    }

    /**
     * Create requiredNested validation (Laravel 4.2 doesn't support nested array validations)
     * Nested array value should be present
     *
     * @param string $attribute
     * @param string $value
     * @param array $keys
     */
    protected function validateRequiredNested($attribute, $value, $keys)
    {
        $data = $value;

        foreach ($keys as $key)
        {
            if (empty($data[$key]))
            {
                throw new Exception\BadRequestValidationFailureException('Nested required data is not there.');
            }

            $data = $data[$key];
        }

        return true;
    }
}