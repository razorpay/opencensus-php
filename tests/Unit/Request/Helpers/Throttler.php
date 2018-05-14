<?php

namespace RZP\Tests\Unit\Request\Helpers;

use RZP\Http\Throttle\Throttler as BaseThrottler;

/**
 * Helper class that assists in unit testing protected/private
 * methods of actual Throttler class.
 */
class Throttler extends BaseThrottler
{
    public function __construct()
    {
        parent::__construct();

        //
        // Generally Throttler class gets used in middleware and there reqctx is initialized.
        // For the purpose of Unit testing we initialize reqctx manually.
        //
        $this->reqctx->init();
    }

    public function __call(string $name, array $args)
    {
        return $this->$name(...$args);
    }
}
