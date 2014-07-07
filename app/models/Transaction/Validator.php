<?php

namespace Models\Transaction;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Utility;

class Validate extends Base\Validator
{
    protected static $createRules = array(
        'merchant_id'   =>  'required|numeric',
        'amount'        =>  'required|numeric|max:500000|min:0',
        'currency'      =>  'required|max:3',
        'description'   =>  'max:1000',
        'email'         =>  'required|email',
        'contact'       =>  'required',
        'udf'           =>  'array');

    protected static $captureRules = array(
        'amount'        => 'required|numeric|max:500000|min:0');

    protected static $createValidators = array('currency', 'contact', 'udf');

    protected function validateContact($input)
    {
        $contact = $input['contact'];
        if (is_string($contact) === false)
        {
            throw new Exception\FieldErrorException(
                'Contact number can only contain numbers and + symbol',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT);
        }

        $origContact = $contact;

        if ($contact[0] === '+')
            $contact = substr($contact, 1);

        if (is_numeric($contact) === false)
        {
            throw new Exception\FieldErrorException(
                'Contact number can only contain digits and + symbol',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT);
        }

        if (strlen($contact) < 10)
        {
            throw new Exception\FieldErrorException(
                'Contact number should have minimum 10 digits',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT);
        }

        if (strlen($contact) > 12)
        {
            throw new Exception\FieldErrorException(
                'Contact number should not be greater than 12 digits, including country code',
                ErrorCode::FIELD_ERROR_INVALID_CONTACT);
        }
    }

    /**
     * Validates Udf
     *
     * @param  array $input  input array
     * @return void
     */
    protected function validateUdf($input)
    {
        $udf = $input['udf'];

        if (!is_array($udf))
        {
            throw new Exception\BadRequestException(
                'Udf should be provided as an array');
        }

        if (count($udf) > 15)
        {
            throw new Exception\BadRequestException(
                'Number of fields in udf should be less than or equal to 15');
        }

        foreach ($udf as $key => $value)
        {
            if (is_array($value))
                throw new Exception\BadRequestException(
                    'Udf values themselves should not be an array');

            if (strlen($value) > 256)
                throw new Exception\BadRequestException(
                    'Udf value [' . $value .'] too large!');

            if (strlen($key) > 256)
                throw new Exception\BadRequestException(
                    'Udf value [' . $key .'] too large!');
        }
    }

    protected function validateCurrency($input)
    {
        $currency = $input['currency'];

        //
        // Right now only INR is supported.
        //

        if ($currency !== "INR")
        {
            throw new Exception\BadRequestException(
                'Invalid currency: '.$currency.'. Only INR supported.');
        }
    }

    public static function checkCardKeyExists($input)
    {
        if (array_key_exists('card', $input) === false)
        {
            throw new Exception\BadRequestException(
                'Transaction Exception: Card not provided');
        }
    }

    public function captureValidate($txn, $input)
    {
        //
        // Don't continue if already captured
        //
        if ($txn->isCaptured())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED);
        }

        try
        {
            $this->validateInput($input, 'capture');
        }
        catch (Exception\ValidationFailureException $e)
        {
            throw new BadRequestException($e->getMessageBag(), 0, $e);
        }

        if ($input['amount'] > $txn->getAttribute('amount'))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_CAPTURE_GREATER_THAN_AUTH);
        }
    }
}
