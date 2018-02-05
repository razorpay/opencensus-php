<?php

namespace RZP\Tests\Unit;

use RZP\Tests\Unit\Mock;

trait MocksAppServices
{
    public function mockBasicAuth()
    {
        $authMock = new Mock\BasicAuth($this->app);

        $this->app->instance('basicauth', $authMock);

        return $authMock;
    }
}
