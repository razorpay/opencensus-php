<?php
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
        Artisan::call('migrate');
        Route::enableFilters();
        DB::beginTransaction();
        Eloquent::unguard();
        $key = Factory::create('Models\DAL\Key');
        $cardtoken= Factory::create('Models\DAL\CardToken');
        $transaction= Factory::create('Models\DAL\Transaction');
        Eloquent::reguard();
        
    }

    public function tearDown()
    {
       DB::rollback();
    }


}
