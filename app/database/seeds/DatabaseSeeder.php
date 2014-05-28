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
		DB::table('merchants')->insert(
			array(
				'email'	=>	'hm@gmail.com',
				'pwd'	=>	'password',
				'hash'	=>	'$2y$10$nXJFmdCSrpImMZMs5RX1TenKOP44pDsYDFsP1rvail3hjKbM.2VOO'
				)
			);

		DB::table('merchants')->insert(
			array(
				'email'	=>	'shk@gmail.com',
				'pwd'	=>	'password',
				'hash'	=>	'$2y$10$nXJFmdCSrpImMZMs5RX1TenKOP44pDsYDFsP1rvail3hjKbM.2VOO'
				)
			);
	
		DB::table('merchants')->insert(
			array(
				'email'	=>	'abd@gmail.com',
				'pwd'	=>	'password',
				'hash'	=>	'$2y$10$nXJFmdCSrpImMZMs5RX1TenKOP44pDsYDFsP1rvail3hjKbM.2VOO'
				)
			);

		DB::table('cards')->insert(
			array(
				'name'	        =>	'shk',
				'expiry_month'	=>	'01',
				'expiry_year'	=>	'99',
				'cvv'			=>	'000',
				'network'		=>	'visa',
				'country'		=>	'IN',
				'last4'			=>	'7890',
				)
			);

		DB::table('cards')->insert(
			array(
				'name'	        =>	'shk',
				'expiry_month'	=>	'01',
				'expiry_year'	=>	'12',
				'cvv'			=>	'000',
				'network'		=>	'visa',
				'country'		=>	'IN',
				'last4'			=>	'7891',
				)
			);

		DB::table('cardtokens')->insert(
			array(
				'card_id'		=>	2,
				'merchant_id'	=>	1,
				'token'			=>	'174bdd3e456c8f6f'
				)
			);

		DB::table('keys')->insert(
			array(
				'id'			=>	'd9c6bf091a1a64cb5678d8c1d5e7360e',
				'merchant_id'	=>	1,
				'live'			=>	1,
				'active'		=>	1,
				'secret'		=>	0
				)
			);
		
		DB::table('keys')->insert(
			array(
				'id'			=>	'd9c6bf091a1a64cb5678d8c1d5e7360f',
				'merchant_id'	=>	1,
				'live'			=>	1,
				'active'		=>	1,
				'secret'		=>	1
				)
			);

		DB::table('keys')->insert(
			array(
				'id'			=>	'd9c6bf091a1a64cb5678d8c1d5e7360g',
				'merchant_id'	=>	2,
				'live'			=>	1,
				'active'		=>	1,
				'secret'		=>	0
				)
			);

		DB::table('keys')->insert(
			array(
				'id'			=>	'd9c6bf091a1a64cb5678d8c1d5e7360h',
				'merchant_id'	=>	2,
				'live'			=>	1,
				'active'		=>	1,
				'secret'		=>	1
				)
			);
	}

}