<?php

namespace RZP\Tests\Unit\Request\Helpers;

use RZP\Http\Throttle\Throttler as BaseThrottler;

/**
 * Helper class that assists in unit testing protected/private
 * methods of actual Throttler class.
 */
class Throttler extends BaseThrottler
{
    public function __call(string $name, array $args)
    {
        return $this->$name(...$args);
    }
}
