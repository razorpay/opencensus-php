<?php

namespace RZP\Gateway\Ebs\Mock;

use RZP\Base;
use RZP\Gateway\Ebs\RequestConstants as Request;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        Request::CHANNEL            => 'required|in:0,2',
        Request::ACCOUNT_ID         => 'required|alpha_num',
        Request::CALLBACK           => 'required|url',
        Request::REFERENCE_NO       => 'required|alpha_num',
        Request::AMOUNT             => 'required|numeric',
        Request::NAME               => 'required|alpha_num',
        Request::ADDRESS            => 'required|',
        Request::CITY               => 'required|alpha_num',
        Request::COUNTRY            => 'required|alpha_num',
        Request::POSTAL_CODE        => 'required|alpha_num',
        Request::PHONE              => 'required|contact_syntax',
        Request::EMAIL              => 'required|email',
        Request::DESCRIPTION        => 'required|',
        Request::CURRENCY           => 'required|alpha_num',
        Request::MODE               => 'required|in:TEST,LIVE',
        Request::NAME_ON_CARD       => 'required_if:channel,2',
        Request::CARD_NUMBER        => 'required_if:channel,2|numeric|digits_between:13,19',
        Request::CARD_EXPIRY        => 'required_if:channel,2|date_format:my',
        Request::PAYMENT_MODE       => 'required|alpha_num',
        Request::CARD_NETWORK       => 'required_if:channel,2',
        Request::CARD_CVV           => 'required_if:channel,2',
        Request::PAYMENT_OPTION     => 'sometimes|alpha_num',
        Request::SECURE_HASH        => 'required|alpha_num',
    );

    protected static $verifyRules = array(
        Request::API_ACTION         => 'required|in:statusByRef',
        Request::API_ACCOUNT_ID     => 'required|alpha_num',
        Request::API_SECRET_KEY     => 'required|alpha_num',
        Request::API_REFERENCE_NO   => 'required|alpha_num',
    );

    protected static $refundRules = array(
        Request::API_ACTION         => 'required|in:refund',
        Request::API_ACCOUNT_ID     => 'required|alpha_num',
        Request::API_SECRET_KEY     => 'required|alpha_num',
        Request::API_AMOUNT         => 'required|numeric',
        Request::API_PAYMENT_ID     => 'required|alpha_num',
    );
}
