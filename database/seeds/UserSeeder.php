<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'id' => '20000000000000',
            'name' => 'Test User Account',
            'email' => 'test@razorpay.com',
            'password'=> Hash::make('123456'),
            'contact_mobile' => '9999999999',
            'created_at'    =>  time(),
            'updated_at'    =>  time()
        ]);

        DB::table('users')->insert([
            'id' => '20000000000001',
            'name' => 'Test User Account2',
            'email' => 'test2@razorpay.com',
            'password'=> Hash::make('123456'),
            'contact_mobile' => '9999999999',
            'created_at'    =>  time(),
            'updated_at'    =>  time()
        ]);

        DB::table('merchant_users')->insert([
            [
                'merchant_id' => '10000000000000',
                'user_id'     => '20000000000000',
                'role'        => 'owner'
            ],
            [
                'merchant_id' => '10000000000000',
                'user_id'     => '20000000000001',
                'role'        => 'manager'
            ],
        ]);
    }
}
