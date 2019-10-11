<?php

namespace RZP\Models\Order;

class ExtraParams
{
    const TOKEN     = 'token';
    const TRANSFERS = 'transfers';

    const allExtraParams = [
        self::TOKEN,
        self::TRANSFERS
    ];
}
