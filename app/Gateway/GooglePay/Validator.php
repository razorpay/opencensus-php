<?php

namespace RZP\Gateway\GooglePay;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use Illuminate\Support\Facades\Validator as LaravelValidator;

class Validator extends JitValidator
{
    protected static $googlePayCardVerificationRules = [
        RequestFields::PAYMENT_ID => 'required',
    ];

    protected static $googlePayCardAuthorizationRules = [
        RequestFields::PAYMENT_ID                 => 'required|filled',
        RequestFields::CARD_TYPE                  => 'required|in:DEBIT,CREDIT,UNKNOWN',
        RequestFields::CARD_NETWORK               => 'required|in:VISA,MASTERCARD',
        RequestFields::AMOUNT                     => 'required|filled',
        RequestFields::TOKEN                      => 'required',
        'token.signature'                         => 'required',
        'token.signedMessage'                     => 'required',
        'token.protocolVersion'                   => 'required|in:ECv2',
        'token.intermediateSigningKey.signedKey'  => 'required',
        'token.intermediateSigningKey.signatures' => 'required',
        RequestFields::PG_BUNDLE                  => 'sometimes',
    ];

    protected static $googlePayDecryptedMessageRules = [
        '_raw'                                                       => 'sometimes',
        'decryptedMessage'                                           => 'required',
        'decryptedMessage.gatewayMerchantId'                         => 'required|filled',
        'decryptedMessage.messageExpiration'                         => 'required|filled',
        'decryptedMessage.messageId'                                 => 'required|filled',
        'decryptedMessage.paymentMethod'                             => 'required',
        'decryptedMessage.paymentMethodDetails.3dsCryptogram'        => 'required|filled',
        'decryptedMessage.paymentMethodDetails.3dsEciIndicator'      => 'sometimes',
        'decryptedMessage.paymentMethodDetails.authMethod'           => 'required|filled',
        'decryptedMessage.paymentMethodDetails.expirationMonth'      => 'required|filled',
        'decryptedMessage.paymentMethodDetails.expirationYear'       => 'required|filled',
        'decryptedMessage.paymentMethodDetails.pan'                  => 'required|numeric|luhn',
        'decryptedMessage.signingKeyExpiration'                      => 'required|filled',
    ];

    public function internalInputValidation($operation, $input)
    {
        $rulesVar = $this->getRulesVariableName($operation);

        $invalidKeys = array_keys(array_diff_key($input, static::$$rulesVar));

        if (count($invalidKeys) > 0)
        {
            throw new Exception\ExtraFieldsException($invalidKeys,
                ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
                null,
                [
                    'method'      =>'card',
                    'application' => 'google_pay'
                ]);
        }

        $validator = LaravelValidator::make($input, static::$$rulesVar);

        if ($validator->fails() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE,
                null,
                [
                    'method'      => 'card',
                    'application' => 'google_pay'
                ]);
        }
    }
}
