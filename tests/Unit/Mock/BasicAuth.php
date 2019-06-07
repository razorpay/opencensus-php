<?php

namespace RZP\Tests\Unit\Mock;

use RZP\Http\BasicAuth\Type;
use RZP\Http\BasicAuth\BasicAuth as BaseBasicAuth;

/**
 * Mocked instance in place of BasicAuth for unit tests. We have
 * overridden definitions of some methods that gets triggered from Core
 * logic in some places. We couldn't use Mockery because that way it becomes
 * difficult (but is possible) to change instance states(e.g. other variables etc.).
 * Mocker is well suited for mocking methods in run time in tests and setting
 * parameter and return expectations.
 *
 * @package RZP\Tests\Unit\Models\Mock
 */
class BasicAuth extends BaseBasicAuth
{
    public function privateAuth()
    {
        $this->setType(Type::PRIVATE_AUTH);
    }

    public function proxyAuth()
    {
        $this->setType(Type::PROXY_AUTH);

        $this->setProxyTrue();
    }

    public function appAuth()
    {
        $this->setType(Type::PRIVILEGE_AUTH);

        $this->setAppAuth(true);
    }

    public function adminAuth()
    {
        $this->appAuth();

        $this->setAdminTrue();
    }
}
