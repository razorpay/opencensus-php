<?php

namespace RZP\Tests\Unit\Services;

use RZP\Exception;
use RZP\Tests\TestCase;

class PincodeSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('applications.pincodesearcher.mock', true);
    }

    public function testPincodeDetails()
    {
        $pincode = '110020';

        $client = $this->getPincodeSearcherClient();

        $result = $client->fetchCityAndStateFromPincode($pincode);

        $this->assertSame([
            'city'       => "South West Delhi",
            'state'      => "Delhi",
            'state_code' => "DL",
        ], $result);
    }

    public function testInternationalPincodeDetails()
    {
        $pincode = "99501";
        $country = "us";

        $result = $this->getPincodeSearcherClient()->fetchCityAndStateFromPincode($pincode, false, false, $country);

        $this->assertSame([
            "city"       => "anchorage",
            "state"      => "alaska",
            "state_code" => "ak",
        ], $result);
    }

    public function testInvalidPincodeDetails()
    {
        $pincode = '1100';

        $client = $this->getPincodeSearcherClient();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $client->fetchCityAndStateFromPincode($pincode);
    }

    /*
     * Helpers
     */

    protected function getPincodeSearcherClient()
    {
        return $this->app['pincodesearch'];
    }
}
