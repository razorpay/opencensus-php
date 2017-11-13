<?php

namespace RZP\Tests\Unit\Models\Mock;

use RZP\Http\BasicAuth\Type;
use RZP\Http\BasicAuth\BasicAuth;

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
