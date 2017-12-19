<?php

namespace RZP\Tests\Unit\Models\Mock;

trait MocksAppServices
{
    public function mockBasicAuth()
    {
        $authMock = new BasicAuth($this->app);

        $this->app->instance('basicauth', $authMock);

        return $authMock;
    }
}
