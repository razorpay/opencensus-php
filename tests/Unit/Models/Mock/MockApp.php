<?php

namespace RZP\Tests\Unit\Models\Mock;

use App;

class MockApp
{
    public static function mockAuth()
    {
        $app = App::getFacadeRoot();

        $auth = new MockBasicAuth($app);

        $app->singleton('basicauth', function() use ($auth){
            return $auth;
        });

        return $auth;
    }
}
