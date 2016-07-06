<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class AdminSeeder extends Seeder
{
	/**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seed();
    }

    private function seed()
    {
        DB::table('admins')->insert(
            array(
                array(
                'name' => 'Harshil Mathur',
                'username' => 'harshil',
                'password'=> Hash::make('123456'),
                'email' => 'harshil@razorpay.com',
                'superadmin' => 1,
                'created_at'    =>  time(),
                'updated_at'    =>  time()
                ),
                array(
                'name' => 'Shashank Kumar',
                'username' => 'shk',
                'password'=> Hash::make('123456'),
                'email' => 'shashank@razorpay.com',
                'superadmin' => 1,
                'created_at'    =>  time(),
                'updated_at'    =>  time()
                ),
                array(
                'name' => 'Abhay Rana',
                'username' => 'nemo',
                'password'=> Hash::make('123456'),
                'email' => 'nemo@razorpay.com',
                'superadmin' => 0,
                'created_at'    =>  time(),
                'updated_at'    =>  time()
                )
            )
        );
    }

}
