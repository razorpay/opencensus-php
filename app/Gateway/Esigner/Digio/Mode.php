<?php

namespace RZP\Gateway\Esigner\Digio;

use RZP\Constants;

class Mode
{
    const PRODUCTION = 'production';
    const UAT        = 'stage';

    public static function map($mode)
    {
        if ($mode === Constants\Mode::LIVE)
        {
            return self::PRODUCTION;
        }

        return self::UAT;
    }
}
