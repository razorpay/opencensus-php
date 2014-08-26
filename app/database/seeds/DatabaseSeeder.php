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
			'id'			=>	'363e4efa820b0c06208ccd99',
			'name'			=>	'Abhishek Das',
			'email'			=>	'das@razorpay.com',
			'password'		=>	Hash::make('123456'),
			'created_at'	=>	time(),
			'updated_at'	=>	time()
			)
		);

		DB::table('merchant_details')->insert(array(
			'merchant_id'	=>	'363e4efa820b0c06208ccd99',
			)
		);

		DB::table('aggregations')->insert(array(
			'merchant_id'	=>	'363e4efa820b0c06208ccd99',
			'total_amount'	=>  12345,
			'txn_count'		=>	20,
			'successful_txn_count' => 12,
			'created_at'	=>	time(),
			'updated_at'	=>	time(),
			'mode'			=> 	'test'
			)
		);

		DB::table('aggregations')->insert(array(
			'merchant_id'	=>	'363e4efa820b0c06208ccd99',
			'total_amount'	=>  123456,
			'txn_count'		=>	25,
			'successful_txn_count' => 15,
			'created_at'	=>	time(),
			'updated_at'	=>	time(),
			'mode'			=> 	'live'
			)
		);

		DB::table('admins')->insert(
        	array(
	        	array(
	        	'name' => 'Harshil Mathur',
	        	'username' => 'harshil',
				'password'=> Hash::make('123456'),
				'email' => 'harshil@razorpay.com',
				'superadmin' => 1
				),
				array(
	        	'name' => 'Shashank Kumar',
	        	'username' => 'shk',
				'password'=> Hash::make('123456'),
				'email' => 'shashank@razorpay.com',
				'superadmin' => 1
				),
				array(
	        	'name' => 'Abhishek Das',
	        	'username' => 'das',
				'password'=> Hash::make('123456'),
				'email' => 'das@razorpay.com',
				'superadmin' => 0
				)
			)
		);
	}

}
