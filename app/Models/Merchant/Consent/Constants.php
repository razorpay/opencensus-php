<?php


namespace RZP\Models\Merchant\Consent;


class Constants
{
    const INPUT       = 'input';
    const IP          = 'ip';
    const USER_AGENT  = 'user_agent';
    const BASIC_AUTH  = 'basicauth';
    const REQUEST     = 'request';
    const REQUEST_CTX = 'request.ctx';
    const CONTACT_US  = 'contact_us';
    const TERMS       = 'terms';
    const REFUND      = 'refund';
    const PRIVACY     = 'privacy';
    const SHIPPING    = 'shipping';

    const WEBSITE      = 'website';
    const CONSENT_KEYS = self::WEBSITE .'_'. self::CONTACT_US . ',' .
                         self::WEBSITE .'_'. self::TERMS . ',' .
                         self::WEBSITE .'_'. self::REFUND . ',' .
                         self::WEBSITE .'_'. self::PRIVACY . ',' .
                         self::WEBSITE .'_'. self::SHIPPING;
}
