<?php

namespace RZP\Tests\Functional\Location;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class LocationTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/LocationTestData.php';

        parent::setUp();
    }

    public function testGetCountries()
    {
        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertTrue(array_key_exists('countryName', $response[0]) === true);

        $this->assertTrue(array_key_exists('countryAlpha2Code', $response[0]) === true);

        $this->assertTrue(array_key_exists('countryAlpha3Code', $response[0]) === true);
    }

    public function testGetStates()
    {
        $this->ba->publicAuth();

        $response = $this->startTest();

        $this->assertTrue(array_key_exists('countryName', $response) === true);

        $this->assertTrue(array_key_exists('countryAlpha2Code', $response) === true);

        $this->assertTrue(array_key_exists('countryAlpha3Code', $response) === true);

        $this->assertTrue(array_key_exists('stateName', $response['states'][0]) === true);

        $this->assertTrue(array_key_exists('stateCode', $response['states'][0]) === true);
    }

    public function testGetStatesForIncorrectCountryCode()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }
}
