<?php

use Constants\Table;

class DatabaseSeeder extends Seeder
{

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
        DB::transaction(function()
        {
            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  '1cXSLlUU8V9sXl',
                    'name'          =>  'Razorpay',
                    'email'         =>  'shashank@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  '1cXSLlUU8V9sXl',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  '10000000000000',
                    'name'          =>  'Harshil',
                    'email'         =>  'das@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  '10000000000000',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::MERCHANT)->insert(
                array(
                    'id'            =>  '1MABTZjIwgLtRZ',
                    'name'          =>  'testname',
                    'email'         =>  'shk@razorpay.com',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::BALANCE)->insert(
                array(
                    'id'            =>  '1MABTZjIwgLtRZ',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::TERMINAL)->insert(
                array(
                    'id'                    => '1n25f6uN5S1Z5a',
                    'merchant_id'           => '10000000000000',
                    'gateway'               => 'hdfc',
                    'gateway_merchant_id'   => 'merch123',
                    'gateway_terminal_id'   => '123456',
                    'gateway_terminal_password' => Crypt::encrypt('encryptpass'),
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    )
                );

            DB::table(Table::TERMINAL)->insert(
                array(
                    'id'                    => '1BjhC5CJAqNF7R',
                    'merchant_id'           => '10000000000000',
                    'gateway'               => 'atom',
                    'gateway_merchant_id'   => 'merch1234',
                    'gateway_terminal_id'   => '',
                    'gateway_terminal_password' => Crypt::encrypt('encryptpass'),
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    )
                );

            DB::table(Table::CARD)->insert(
                array(
                    'id'            =>  '1qGr3alAuTlB74',
                    'iin'           =>  '123456',
                    'name'          =>  'shk',
                    'expiry_month'  =>  '01',
                    'expiry_year'   =>  '99',
                    'network'       =>  'visa',
                    'country'       =>  'IN',
                    'iin'           =>  '401200',
                    'last4'         =>  '7890',
                    'length'        =>  '16',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::CARD)->insert(
                array(
                    'id'            =>  '1qIPa5EJYzzfq0',
                    'iin'           =>  '123456',
                    'name'          =>  'shk',
                    'expiry_month'  =>  '01',
                    'expiry_year'   =>  '12',
                    'network'       =>  'visa',
                    'country'       =>  'IN',
                    'iin'           =>  '401200',
                    'last4'         =>  '7891',
                    'length'        =>  '16',
                    'created_at'    =>  time(),
                    'updated_at'    =>  time(),
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  '1DP5mmOlF5G5ag',
                    'merchant_id'   =>  '10000000000000',
                    'secret'        =>  Crypt::encrypt('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::KEY)->insert(
                array(
                    'id'            =>  '0wFRWIZnH65uny',
                    'merchant_id'   =>  '1MABTZjIwgLtRZ',
                    'secret'        =>  Crypt::encrypt('thisissupersecret'),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                    )
                );

            DB::table(Table::PRICING)->insert(
                array(
                    'id'            => '1GuENK6Hl2BWGx',
                    'plan_id'       => '1YXludj60w4pSp',
                    'plan_name'     => 'Education',
                    'gateway'       => 'hdfc',
                    'payment_method'  => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'=> 'DICL',
                    'payment_issuer' => 'HDFC',
                    'percent_rate'  => '289',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => 'e5484d12aafacc2023608c79',
                    'plan_id'       => '1YXludj60w4pSp',
                    'plan_name'     => 'Education',
                    'gateway'       => 'hdfc',
                    'payment_method'  => 'card',
                    'payment_method_type' => 'credit',
                    'percent_rate'  => '300',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1GuENK6Hl2BWGx',
                    'plan_id'       => '1YXludj60w4pSp',
                    'plan_name'     => 'Education',
                    'gateway'       => 'hdfc',
                    'payment_method'  => 'card',
                    'payment_method_type' => 'debit',
                    'percent_rate'  => '250',
                    'fixed_rate'    => '0',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    ),

                array(
                    'id'            => '1qPS1eB4Q3UBQX',
                    'plan_id'       => '1YXludj60w4pSp',
                    'plan_name'     => 'Education',
                    'gateway'       => 'icici',
                    'payment_method'  => 'nb',
                    'percent_rate'  => '0',
                    'fixed_rate'    => '3000',
                    'expired_at'    => null,
                    'created_at'    => time(),
                    'updated_at'    => time()
                    )
                );

            DB::table(Table::PRICING)->insert(
                array(
                    'id' => '1hDYlICobzOCYt',
                    'plan_id' => '1hDYlICobzOCYt',
                    'plan_name' => 'defaultPlan',
                    'payment_method' => 'card',
                    'payment_method_type' => null,
                    'payment_network' => null,
                    'payment_issuer' => null,
                    'percent_rate' => '200',
                    'fixed_rate' => 0,
                    'created_at'    => time(),
                    'updated_at'    => time()
                ),
                array(
                    'id' => '1OwH8rTI0ejFxS',
                    'plan_id' => '1hDYlICobzOCYt',
                    'plan_name' => 'defaultPlan',
                    'payment_method' => 'card',
                    'payment_method_type' => null,
                    'payment_network' => 'AMEX',
                    'payment_issuer' => null,
                    'percent_rate' => 300,
                    'fixed_rate' => 0,
                    'created_at'    => time(),
                    'updated_at'    => time()
                ),
                array(
                    'id' => '1fq0OXpgeyafQq',
                    'plan_id' => '1hDYlICobzOCYt',
                    'plan_name' => 'defaultPlan',
                    'payment_method' => 'card',
                    'payment_method_type' => null,
                    'payment_network' => 'DICL',
                    'payment_issuer' => null,
                    'percent_rate' => 300,
                    'fixed_rate' => 0,
                    'created_at'    => time(),
                    'updated_at'    => time()
                )
            );
        });
    }
}
