<?php

namespace RZP\Models\Merchant\Detail;

class ActivationFlow
{
    const WHITELIST                     = 'whitelist';
    const BLACKLIST                     = 'blacklist';
    const GREYLIST                      = 'greylist';

    public static function isValid($type): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }
}
