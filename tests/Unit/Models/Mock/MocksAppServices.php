<?php

namespace RZP\Tests\Unit\Models\Mock;

trait MocksAppServices
{
    public function mockBasicAuth()
    {
        $authMock = new MockBasicAuth($this->app);

        $this->app->instance('basicauth', $authMock);

        return $authMock;
    }
}
