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
        'token.protocolVersion'                   => 'required|in:ECv1',
        'token.signedMessage'                     => 'required',
        'token.signedMessage.tag'                 => 'required',
        'token.signedMessage.ephemeralPublicKey'  => 'required',
        'token.signedMessage.encryptedMessage'    => 'required',
        'token.signature'                         => 'required',
        RequestFields::PG_BUNDLE                  => 'sometimes',
    ];
}
