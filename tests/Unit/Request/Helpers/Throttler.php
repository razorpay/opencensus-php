<?php

namespace RZP\Tests\Unit\Request\Helpers;

/**
 * Helper class that assists in unit testing protected/private
 * methods of actual Throttler class.
 */
class Throttler extends \RZP\Http\Throttle\Throttler
{
    public function __get(string $attribute)
    {
        return $this->$attribute;
    }

    public function __call(string $name, array $args)
    {
        return $this->$name(...$args);
    }
}
