<?php

namespace RZP\Gateway\GooglePay;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Base\JitValidator;
use Illuminate\Support\Facades\Validator as LaravelValidator;

class Validator extends JitValidator
{
    protected static $googlePayCardVerificationRules = [
        RequestFields::PAYMENT_ID => 'required|public_id',
    ];

    protected static $googlePayCardAuthorizationRules = [
        RequestFields::PAYMENT_ID                 => 'required|public_id',
        RequestFields::CARD_TYPE                  => 'required|in:DEBIT,CREDIT',
        RequestFields::CARD_NETWORK               => 'required|in:VISA,MASTERCARD',
        RequestFields::AMOUNT                     => 'required',
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
        'decryptedMessage.gatewayMerchantId'                         => 'required',
        'decryptedMessage.messageExpiration'                         => 'required',
        'decryptedMessage.messageId'                                 => 'required',
        'decryptedMessage.paymentMethod'                             => 'required',
        'decryptedMessage.paymentMethodDetails.3dsCryptogram'        => 'required',
        'decryptedMessage.paymentMethodDetails.3dsEciIndicator'      => 'required',
        'decryptedMessage.paymentMethodDetails.authMethod'           => 'required',
        'decryptedMessage.paymentMethodDetails.expirationMonth'      => 'required',
        'decryptedMessage.paymentMethodDetails.expirationYear'       => 'required',
        'decryptedMessage.paymentMethodDetails.pan'                  => 'required',
        'decryptedMessage.signingKeyExpiration'                      => 'required',
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
