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
        ;
    }
}
