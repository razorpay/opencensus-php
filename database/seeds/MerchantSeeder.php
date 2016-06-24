<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class MerchantSeeder extends Seeder
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

    protected function setupMerchant($merchantId, $name, $email, $attributes = [])
    {
        DB::table('merchants')->insert(array(
            'id'            =>  $merchantId,
            'name'          =>  $name,
            'email'         =>  $email,
            'password'      =>  Hash::make('123456'),
            'created_at'    =>  time(),
            'updated_at'    =>  time()
            ) + $attributes
        );

        DB::table('merchant_details')->insert(array(
            'merchant_id'   => $merchantId,
            'locked'        => 1,
            'created_at'    => time(),
            'updated_at'    => time(),
            )
        );

        DB::table('aggregations')->insert(array(
            'merchant_id'   =>  $merchantId,
            'total_amount'  =>  239871,
            'txn_count'     =>  21,
            'successful_txn_count' => 13,
            'resource'      => 'payment',
            'created_at'    =>  time(),
            'updated_at'    =>  time(),
            'mode'          =>  'test'
            )
        );

        DB::table('aggregations')->insert(array(
            'merchant_id'   =>  $merchantId,
            'total_amount'  =>  12345,
            'txn_count'     =>  3,
            'successful_txn_count' => 3,
            'resource'      => 'refund',
            'created_at'    =>  time(),
            'updated_at'    =>  time(),
            'mode'          =>  'test'
            )
        );

        DB::table('aggregations')->insert(array(
            'merchant_id'   =>  $merchantId,
            'total_amount'  =>  12345,
            'txn_count'     =>  7,
            'successful_txn_count' => 7,
            'resource'      => 'settlement',
            'created_at'    =>  time(),
            'updated_at'    =>  time(),
            'mode'          =>  'test'
            )
        );


        DB::table('payment_aggregations')->insert(array(
            'merchant_id'   =>  $merchantId,
            'CARD'  =>  100,
            'NETBANKING'     =>  21,
            'VISA' => 26,
            'MC'      => 33,
            'MAES'    =>  30,
            'RUPAY'   => 11,
            'created_at'    =>  time(),
            'updated_at'    =>  time(),
            'mode'          =>  'test'
            )
        );

        $types = array('day', 'week', 'month', 'year');

        foreach($types as $type)
        {
            $data = array();

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  5000,
                'count' => 13,
                'mode'      => 'test',
                'created_at'    =>  time(),
                'updated_at'    =>  time()
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  4500,
                'count' => 9,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-1 ' . $type, time()),
                'updated_at'    =>  strtotime('-1 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  4200,
                'count' => 10,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-2 ' . $type, time()),
                'updated_at'    =>  strtotime('-2 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  3600,
                'count' => 8,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-3 ' . $type, time()),
                'updated_at'    =>  strtotime('-3 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  4500,
                'count' => 6,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-4 ' . $type, time()),
                'updated_at'    =>  strtotime('-4 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  2500,
                'count' => 10,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-6 ' . $type, time()),
                'updated_at'    =>  strtotime('-6 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  3300,
                'count' => 23,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-7 ' . $type, time()),
                'updated_at'    =>  strtotime('-7 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  6500,
                'count' => 25,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-8 ' . $type, time()),
                'updated_at'    =>  strtotime('-8 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  8000,
                'count' => 29,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-9 ' . $type, time()),
                'updated_at'    =>  strtotime('-9 ' . $type, time())
            );


            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  7900,
                'count' => 18,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-10 ' . $type, time()),
                'updated_at'    =>  strtotime('-10 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  8200,
                'count' => 25,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-11 ' . $type, time()),
                'updated_at'    =>  strtotime('-11 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  8400,
                'count' => 20,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-12 ' . $type, time()),
                'updated_at'    =>  strtotime('-12 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  6100,
                'count' => 16,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-13 ' . $type, time()),
                'updated_at'    =>  strtotime('-13 ' . $type, time())
            );

            $data[] = array(
                'merchant_id'   =>  $merchantId,
                'type'  =>  $type,
                'amount'     =>  2600,
                'count' => 8,
                'mode'      => 'test',
                'created_at'    =>  strtotime('-14 ' . $type, time()),
                'updated_at'    =>  strtotime('-14 ' . $type, time())
            );

            foreach($data as $arr)
            {
                switch($type)
                {
                    case 'day':
                        $arr['created_at'] = strtotime(date('j F Y', $arr['updated_at']));
                        break;
                    case 'week':
                        $arr['created_at'] = strtotime(date('o-\\WW', $arr['updated_at']));
                        break;
                    case 'month':
                        $arr['created_at'] = strtotime(date('M Y', $arr['updated_at']));
                        break;
                    case 'year':
                        $arr['created_at'] = strtotime('1 Jan ' . date('Y', $arr['updated_at']));
                        break;
                }

                DB::table('transactions')->insert($arr);

            }
        }
    }

    private function seed()
    {
        $this->setupMerchant('10000000000000', 'Test Merchant Account', 'test@razorpay.com');
        $this->setupMerchant('100DemoAccount', 'Demo Merchant Account', 'demo@razorpay.com');
        $this->setupMerchant('100DeadAccount', 'Dead Merchant Account', 'dead@razorpay.com', [
            'archived_at' => time()
        ]);
    }
}
