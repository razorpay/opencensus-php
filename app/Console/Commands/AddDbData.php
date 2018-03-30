<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

use RZP\Constants\Table;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Processor\Netbanking;

class AddDbData extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:addDbData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds data to database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Database\DefaultConnection::set('test');

            DB::table(Table::TERMINAL)->where('id', '1n25f6uN5S1Z5a')->update(
                array(
                    'id'                    => '1n25f6uN5S1Z5a',
                    'merchant_id'           => Account::TEST_ACCOUNT,
                    'gateway'               => 'hdfc',
                    'gateway_acquirer'      => 'hdfc',
                    'gateway_merchant_id'   => 'test_merchant_hdfc',
                    'gateway_terminal_id'   => 'test_terminal_hdfc',
                    'gateway_terminal_password' => Crypt::encrypt('test_account_hdfc_terminal_pass'),
                    'recurring'             => 1,
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    'type'                  => 1,
                    )
                );

            DB::table(Table::TERMINAL)->where('id', '1BjhC5CJAqNF7R')->update(
                array(
                    'id'                    => '1BjhC5CJAqNF7R',
                    'merchant_id'           => Account::TEST_ACCOUNT,
                    'gateway'               => 'atom',
                    'gateway_merchant_id'   => 'test_merchant_atom',
                    'gateway_terminal_id'   => 'test_terminal_atom',
                    'gateway_terminal_password' => Crypt::encrypt('test_account_atom_terminal_pass'),
                    'recurring'             => 1,
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    )
                );

            DB::table(Table::TERMINAL)->insert(
                array(
                    'id'                    => '1VwJebUIU7hIhU',
                    'merchant_id'           => Account::DEMO_ACCOUNT,
                    'gateway'               => 'hdfc',
                    'gateway_acquirer'      => 'hdfc',
                    'gateway_merchant_id'   => 'demo_merchant_hdfc',
                    'gateway_terminal_id'   => 'demo_terminal_hdfc',
                    'gateway_terminal_password' => Crypt::encrypt('demo_account_hdfc_terminal_pass'),
                    'recurring'             => 1,
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    'type'                  => 1,
                    )
                );

            DB::table(Table::TERMINAL)->insert(
                array(
                    'id'                    => '1XwJrbxrfB0i8G',
                    'merchant_id'           => Account::DEMO_ACCOUNT,
                    'gateway'               => 'atom',
                    'gateway_merchant_id'   => 'demo_merchant_atom',
                    'gateway_terminal_id'   => 'demo_terminal_atom',
                    'gateway_terminal_password' => Crypt::encrypt('demo_account_atom_terminal_pass'),
                    'recurring'             => 1,
                    'created_at'            =>  time(),
                    'updated_at'            =>  time(),
                    )
                );


            DB::table(Table::METHODS)->insert(
                array(
                    'merchant_id'   => Account::DEMO_ACCOUNT,
                    'banks'         => json_encode(Netbanking::getAllBanks()),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                )
            );


            DB::table(Table::METHODS)->insert(
                array(
                    'merchant_id'   => Account::TEST_ACCOUNT,
                    'banks'         => json_encode(Netbanking::getAllBanks()),
                    'created_at'    =>  time(),
                    'updated_at'    =>  time()
                )
            );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $array = parent::getOptions();

        array_push($array, ['timestamp', null, InputOption::VALUE_OPTIONAL, 'Convert timestamp to Uid']);

        array_push($array, ['uid', null, InputOption::VALUE_OPTIONAL, 'Convert Uid to timestamp']);

        return $array;
    }
}
