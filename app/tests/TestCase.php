<?php

/**
 * Base test case class provided by laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;


class TestCase extends Illuminate\Foundation\Testing\TestCase {

	/**
	 * Creates the application.
	 *
	 * @return \Symfony\Component\HttpKernel\HttpKernelInterface
	 */
	public function createApplication()
	{
		$unitTesting = true;

		$testEnvironment = 'testing';

		return require __DIR__.'/../../bootstrap/start.php';
	}

    public function setUp()
    {
        parent::setUp();

        //setting up db
        Artisan::call('migrate');

        //Enable filters
        Route::enableFilters();

        //Auth
        $_SERVER['PHP_AUTH_USER'] = 'd9c6bf091a1a64cb5678d8c1d5e7360f';
        $_SERVER['PHP_AUTH_PW'] = 'thisissupersecret';
    }

}
