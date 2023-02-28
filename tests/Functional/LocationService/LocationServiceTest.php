<?php

namespace Functional\LocationService;

use RZP\Tests\TestCase;
use RZP\Error\ErrorCode;
use RZP\Services\LocationService;

class LocationServiceTest extends TestCase
{
    public function testGetStatesByCountry()
    {
        $states = (new LocationService($this->app))->getStatesByCountry("jp");
        $this->assertNotEmpty($states);
        $this->assertContains(["name" => "Aichi Prefecture", "state_code" => "23"], $states);
    }

    public function testGetAddressSuggestions()
    {
        $suggestions = (new LocationService($this->app))->getAddressSuggestions(['input' => 'aus']);
        $this->assertNotEmpty($suggestions);
        $this->assertEquals(["predictions" => [], "status" => "OK"], $suggestions);
    }

    public function testGetAddressSuggestionsWithInvalidInput()
    {
        $ex = null;
        try
        {
            $suggestions = (new LocationService($this->app))->getAddressSuggestions(['input' => '']);

        }
        catch (\Throwable $e)
        {
            $ex = $e;
        }
        $this->assertEquals(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, $ex->getError()->getInternalErrorCode());
        $this->assertEmpty($suggestions);
    }
}
