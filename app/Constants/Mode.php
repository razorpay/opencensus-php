<?php

namespace RZP\Constants;

use RZP\Exception\BadRequestValidationFailureException;

class Mode
{
    const TEST = 'test';
    const LIVE = 'live';

    public static function exists(string $mode = null): bool
    {
        return (($mode === self::TEST) or ($mode === self::LIVE));
    }

    public static function validateModeOrFailPublic(string $mode)
    {
        if (!self::exists($mode))
        {
            throw new BadRequestValidationFailureException(
                'invalid mode '.$mode.' was sent');
        }
    }
}
