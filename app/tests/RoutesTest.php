<?php

class RoutesTest extends Tests\TestCase
{
    public function setUp()
    {
        parent::setUp();

        //
        // Setting up db
        //
        Artisan::call('migrate');

        //
        // Enable filters
        //
        Route::enableFilters();
    }

    public function testJSONPRoute()
    {
        $this->markTestIncomplete('will fix it in morning');

        //Should return 401
        $response = $this->action('GET', 'TransactionController@getJSONP');

        $this->assertTrue($response->getStatusCode() == 401);
    }
}
