<?php

namespace RZP\Models\P2p\Base\Libraries;

class Card
{
    const CARD              = 'card';
    const EXPIRY_MONTH      = 'expiry_month';
    const EXPIRY_YEAR       = 'expiry_year';
    const LAST6             = 'last6';

    public static function rules(): Rules
    {
        return new Rules([
            self::EXPIRY_YEAR   => 'integer|min:18|max:99',
            self::EXPIRY_MONTH  => 'integer|min:1|max:12',
            self::LAST6         => 'string|size:6' .
                '',
        ]);
    }
}
