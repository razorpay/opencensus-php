<?php

namespace RZP\Gateway\GooglePay;

use RZP\Base\JitValidator;

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
}
