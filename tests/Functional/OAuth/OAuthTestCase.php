<?php

namespace RZP\Tests\Functional\OAuth;

use Illuminate\Database\Eloquent\Factory;

use RZP\Tests\Functional\TestCase;

class OAuthTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }
}
