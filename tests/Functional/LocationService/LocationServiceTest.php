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

    public function testGetAddressSuggestions()
    {
        $suggestions = (new LocationService($this->app))->getAddressSuggestions("input=Australia&types=geocode");
        $this->assertNotEmpty($suggestions);
        $this->assertEquals(["predictions" => [], "status" => "OK"], $suggestions);
    }
}
