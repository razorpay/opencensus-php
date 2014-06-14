<?php

class DatabaseSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		Eloquent::unguard();

		$this->seed();
	}

	private function seed()
	{
		DB::table('merchants')->insert(array(
			'id'			=>	1,
			'name'			=>	'Abhishek Das',
			'email'			=>	'das@razorpay.com',
			'password'		=>	Hash::make('123456'),
			'created_at'	=>	time(),
			'updated_at'	=>	time()
			)
		);

		DB::table('aggregations')->insert(array(
			'merchant_id'	=>	1,
			'txn_count'		=>	0,
			'created_at'	=>	time(),
			'updated_at'	=>	time()
			)
		);
	}

}
