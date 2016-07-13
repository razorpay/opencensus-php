<?php

namespace RZP\Gateway\Cybersource\Mock;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'merchantID'              => 'required|string',
        'merchantReferenceCode'   => 'required|alpha_num',
        'clientLibrary'           => 'required|string',
        'clientLibraryVersion'    => 'required|string',
        'clientEnvironment'       => 'required|string',
        'ccAuthService'           => 'required',
        'billTo'                  => 'required',
        'card'                    => 'required',
        'purchaseTotals'          => 'required',
        'item'                    => 'required',
        'ucaf'                    => 'sometimes',
    );

    protected static $enrollRules = array(
        'payerAuthEnrollService'    => 'required',
        'card'                      => 'required',
        'purchaseTotals'            => 'required',
        'item'                      => 'required',
        'merchantID'                => 'required|string',
        'merchantReferenceCode'     => 'required|alpha_num',
        'clientLibrary'             => 'required|string',
        'clientLibraryVersion'      => 'required|string',
        'clientEnvironment'         => 'required|string'
    );

    protected static $authenticateRules = array(
        'TermUrl'          => 'required|url',
        'MD'               => 'required|alpha_num',
        'PaReq'            => 'required|alpha_num',
    );

    protected static $authValidators = array(
        'bill_to',
        'card',
        'purchase_totals',
        'item',
        'cc_auth_service'
    );

    protected static $enrollValidators = array(
        'card',
        'purchase_totals',
        'item',
        'payer_auth_enroll_service'
    );

    protected function validateBillTo($input)
    {
        if ((array_key_exists('firstName', $input['billTo']) === false) or
            ($input['billTo']['firstName'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('lastName', $input['billTo']) === false) or
            ($input['billTo']['lastName'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('street1', $input['billTo']) === false) or
            ($input['billTo']['street1'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('city', $input['billTo']) === false) or
            ($input['billTo']['city'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('state', $input['billTo']) === false) or
            ($input['billTo']['state'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('postalCode', $input['billTo']) === false) or
            ($input['billTo']['postalCode'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('country', $input['billTo']) === false) or
            ($input['billTo']['country'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('email', $input['billTo']) === false) or
            ($input['billTo']['email'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
    }

    protected function validateCard($input)
    {
        if ((array_key_exists('accountNumber', $input['card']) === false) or
            ($input['card']['accountNumber'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('expirationMonth', $input['card']) === false) or
            ($input['card']['expirationMonth'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('expirationYear', $input['card']) === false) or
            ($input['card']['expirationYear'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
    }

    protected function validatePurchaseTotals($input)
    {
        if ((array_key_exists('currency', $input['purchaseTotals']) === false) or
            ($input['purchaseTotals']['currency'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
    }

    protected function validateItem($input)
    {
        if ((array_key_exists('unitPrice', $input['item'][0]) === false) or
            ($input['item'][0]['unitPrice'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('id', $input['item'][0]) === false) or
            ($input['item'][0]['id'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
    }

    protected function validateCcAuthService($input)
    {
        if ((array_key_exists('run', $input['ccAuthService']) === false) or
            ($input['ccAuthService']['run'] === null))
        {
                    throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('commerceIndicator', $input['ccAuthService']) === false) or
            ($input['ccAuthService']['commerceIndicator'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
        if ((array_key_exists('reconciliationID', $input['ccAuthService']) === false) or
            ($input['ccAuthService']['reconciliationID'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
    }

    protected function validatePayerAuthEnrollService($input)
    {
        if ((array_key_exists('run', $input['payerAuthEnrollService']) === false) or
            ($input['payerAuthEnrollService']['run'] === null))
        {
            throw new Exception\BadRequestException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA);
        }
    }
}