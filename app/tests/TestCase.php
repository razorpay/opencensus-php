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

        //setting up db
        Artisan::call('migrate');
        Eloquent::unguard();
        $key = Factory::create('Models\DAL\Key');
        $cardtoken= Factory::create('Models\DAL\CardToken');
        $transaction= Factory::create('Models\DAL\Transaction');
        Eloquent::reguard();

        //Enable filters
        Route::enableFilters();

        //Start DB transaction so as to rollback once done
        DB::beginTransaction();
        
        
    }

    public function tearDown()
    {
        //Undo DB Changes after test
        DB::rollback();
    }


}
