<?php

namespace RZP\Constants;

use RZP\Exception\LogicException;

class Mode
{
    const TEST = 'test';
    const LIVE = 'live';

    public static function exists(string $mode = null): bool
    {
        $key = __CLASS__ . '::' . strtoupper($mode);

        return ((defined($key) === true) and (constant($key) === $mode));
    }

    /**
     * Throws LogicException instead of BadRequestException. Reason being this
     * enum class unline others is used internally only and so throwing former
     * exception is more correct.
     *
     * @param string $mode
     *
     * @throws LogicException
     */
    public static function validate(string $mode)
    {
        if (self::exists($mode) === false)
        {
            throw new LogicException("Invalid mode: $mode");
        }
    }
}
