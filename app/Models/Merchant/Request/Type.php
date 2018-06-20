<?php

namespace RZP\Models\Merchant\Request;

class Type
{
    /*
     * Enum values used for request type
     */
    const PARTNER  = 'partner';
    const PRODUCT  = 'product';
    const INTERNAL = 'internal';

    /**
     * Request types which are valid only in the live mode.
     *
     * Eg:
     * product activations - in test mode, the merchant does not fill up any activation form
     * partner activations - merchants is a synced entity. requests should be restricted to only one mode.
     *
     * @var array
     */
    public static $liveModeRequestTypes = [
        self::PRODUCT,
        self::PARTNER,
    ];
}
