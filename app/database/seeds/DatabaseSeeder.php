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

		$this->call('IinsTableSeeder');
	}

	private function seed()
	{
		DB::table('merchants')->insert(
			array(
				'id'			=>	1,
				'created_at'	=>	time(),
				'updated_at'	=>	time()
				)
			);

		DB::table('merchants')->insert(
			array(
				'id'	=>	2,
				'created_at'	=>	time(),
				'updated_at'	=>	time()
				)
			);

		DB::table('cards')->insert(
			array(
				'name'	        =>	'shk',
				'expiry_month'	=>	'01',
				'expiry_year'	=>	'99',
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
				'secret'		=>	Hash::make('thisissupersecret')
				)
			);

		DB::table('keys')->insert(
			array(
				'id'			=>	'd9c6bf091a1a64cb5678d8c1d5e7360f',
				'merchant_id'	=>	1,
				'live'			=>	0,
				'active'		=>	1,
				'secret'		=>	Hash::make('thisissupersecret')
				)
			);

		DB::table('keys')->insert(
			array(
				'id'			=>	'd9c6bf091a1a64cb5678d8c1d5e7360g',
				'merchant_id'	=>	2,
				'live'			=>	1,
				'active'		=>	1,
				'secret'		=>	Hash::make('thisissupersecret')
				)
			);

		DB::table('keys')->insert(
			array(
				'id'			=>	'd9c6bf091a1a64cb5678d8c1d5e7360h',
				'merchant_id'	=>	2,
				'live'			=>	0,
				'active'		=>	1,
				'secret'		=>	Hash::make('thisissupersecret')
				)
			);
	}

}
