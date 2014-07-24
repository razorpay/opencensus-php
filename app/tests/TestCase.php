<?php

namespace Tests;

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;

class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
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
        sd(
            $_ENV['WERCKER_MYSQL_HOST'],
            $_ENV['WERCKER_MYSQL_PORT'],
            $_ENV['DB_MYSQL_HOST'],
            $_ENV['DB_MYSQL_PORT']);

        parent::setUp();
    }
}
