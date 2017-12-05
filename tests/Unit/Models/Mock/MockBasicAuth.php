<?php

namespace RZP\Tests\Unit\Models\Mock;

use RZP\Http\BasicAuth\Type;
use RZP\Http\BasicAuth\BasicAuth;

/**
 * Class works as mocked instance to BasicAuth, we have
 * mocked the basic setters in class which required
 * user and request information.
 * Note: We are not using Mockery for this because it is
 * complicated, hard to understand and less flexible.
 *
 * Class MockBasicAuth
 * @package RZP\Tests\Unit\Models\Mock
 */
class MockBasicAuth extends BasicAuth
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

        $this->setAppTrue();
    }

    public function adminAuth()
    {
        $this->appAuth();

        $this->setAdminTrue();
    }
}
