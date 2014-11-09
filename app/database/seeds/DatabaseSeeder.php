<?php

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
    }

    private function seed()
    {
        DB::table('merchants')->insert(array(
            'id'            =>  '363e4efa820b0c06208ccd99',
            'name'          =>  'Harshil',
            'email'         =>  'das@razorpay.com',
            'password'      =>  Hash::make('123456'),
            'created_at'    =>  time(),
            'updated_at'    =>  time()
            )
        );

        DB::table('merchant_details')->insert(array(
            'merchant_id'   =>  '363e4efa820b0c06208ccd99',
            )
        );

        $modes = array('live', 'test');

        foreach($modes as $mode)
        {
    		DB::table('aggregations')->insert(array(
    			'merchant_id'	=>	'363e4efa820b0c06208ccd99',
    			'total_amount'	=>  12345,
    			'txn_count'		=>	20,
    			'successful_txn_count' => 12,
    			'resource'		=> 'payment',
    			'created_at'	=>	time(),
    			'updated_at'	=>	time(),
    			'mode'			=> 	$mode
    			)
    		);


            $types = array('day', 'week', 'month', 'year');

            foreach($types as $type)
            {   
                $data = array();

                $data[] = array(
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'type'  =>  $type,
                    'amount'     =>  5000,
                    'count' => 15,
                    'mode'      => $mode,
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                );

                $data[] = array(
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'type'  =>  $type,
                    'amount'     =>  4500,
                    'count' => 10,
                    'mode'      => $mode,
                    'created_at'    =>  strtotime('-1 ' . $type, time()),
                    'updated_at'    =>  strtotime('-1 ' . $type, time())
                );

                $data[] = array(
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'type'  =>  $type,
                    'amount'     =>  4200,
                    'count' => 8,
                    'mode'      => $mode,
                    'created_at'    =>  strtotime('-2 ' . $type, time()),
                    'updated_at'    =>  strtotime('-2 ' . $type, time())
                );

                $data[] = array(
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'type'  =>  $type,
                    'amount'     =>  3600,
                    'count' => 3,
                    'mode'      => $mode,
                    'created_at'    =>  strtotime('-3 ' . $type, time()),
                    'updated_at'    =>  strtotime('-3 ' . $type, time())
                );

                $data[] = array(
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'type'  =>  $type,
                    'amount'     =>  4500,
                    'count' => 12,
                    'mode'      => $mode,
                    'created_at'    =>  strtotime('-4 ' . $type, time()),
                    'updated_at'    =>  strtotime('-4 ' . $type, time())
                );

                $data[] = array(
                    'merchant_id'   =>  '363e4efa820b0c06208ccd99',
                    'type'  =>  $type,
                    'amount'     =>  5500,
                    'count' => 18,
                    'mode'      => $mode,
                    'created_at'    =>  strtotime('-5 ' . $type, time()),
                    'updated_at'    =>  strtotime('-5 ' . $type, time())
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
