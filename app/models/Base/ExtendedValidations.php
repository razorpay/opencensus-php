<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;
use Symfony\Component\Translation\TranslatorInterface;

class ExtendedValidations extends \Razorpay\Spine\Validation\LaravelValidatorEx
{
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
     * Validate merchant_order_id should be present if signature is present in root
     *
     * @param string $attribute
     * @param array $value
     * @param array $parameters
     */
    protected function validateContainsMerchantorderidIfSignature($attribute, $value, $parameters)
    {
        $requiredWith = ['signature'];

        if ( ! $this->allFailingRequired($requiredWith))
        {
            if (empty($value['merchant_order_id']))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'merchant_order_id should be defined when signature is present');
            }
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