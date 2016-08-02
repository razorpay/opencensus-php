<?php
namespace App\Console\Commands;

use App\Merchant\Entity as Merchant;
use Models\User\Entity as UserEntity;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TransferExistingMerchantsDataToUsers extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'transfer:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfers existing data from merchants table to users table.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        //Data will be copied in batches - Will not eat all of the RAM
        $eachBatch = 300;
        $created_at = $this->argument('timestamp');

        $this->info("Data transfer in staring at record with created_at as $created_at in batches of $eachBatch.");
        DB::table('merchants')
            ->select('id', 'name', 'email', 'password', 'remember_token',
                    'confirm_token', 'created_at', 'updated_at', 'archived_at')
            ->where('created_at', '<', $created_at)
            ->chunk($eachBatch, function ($tuples)
            {
                foreach ($tuples as $tuple)
                {
                    $user = new UserEntity;

                    $user->timestamps = false;
                    $user->id = Uuid::generate();
                    $user->name = $tuple->name;
                    $user->email = $tuple->email;
                    $user->password = $tuple->password;
                    $user->remember_token = $tuple->remember_token;
                    $user->confirm_token = $tuple->confirm_token;

                    $user->created_at = $tuple->created_at;
                    $user->updated_at = $tuple->updated_at;
                    $user->save();

                    $merchant = Merchant::where('id',$tuple->id)->first();
                    $merchant->timestamps = false;

                    $merchant->save();
                    $merchant = $user->merchants()->attach($merchant, ['role' => 'owner']);
                }
            }
        );
        $this->info("Hurray ! Data transfer was successful.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('timestamp',
                InputArgument::REQUIRED,
                'Timestamp value for the 1st record in users table.'
            ),
        );
    }
}
