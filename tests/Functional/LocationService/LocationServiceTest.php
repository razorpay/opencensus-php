<?php

namespace Functional\LocationService;

use RZP\Tests\TestCase;
use RZP\Services\LocationService;

class LocationServiceTest extends TestCase
{
    public function testGetStatesByCountry()
    {
        $states = (new LocationService($this->app))->getStatesByCountry("jp");
        $this->assertNotEmpty($states);
        $this->assertContains(["name" => "Aichi Prefecture", "state_code" => 23], $states);
    }
}
