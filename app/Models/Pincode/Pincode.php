<?php

namespace RZP\Models\Pincode;

class Pincode
{
    const IN = 'IN';

    const LENGTH = [
        self::IN => 6,
    ];

    const REGEX = [
        self::IN => '/^[1-9][0-9]{5}$/',
    ];

    const MAX_PINCODE = [
        self::IN => 859999,
    ];

    const MIN_PINCODE = [
        self::IN => 110000,
    ];
}
