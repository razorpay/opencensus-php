<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Session as SessionTable;
use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DeleteUserSessions extends Command
{
    protected $signature = 'user_session:delete {user_ids*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all the user sessions given in the list';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $userIds = $this->argument('user_ids');

        foreach ($userIds as $userId)
        {
            (new SessionTable\Entity)->deleteSessionsForUser($userId);

            $this->comment($userId . PHP_EOL);
        }

        $this->comment(Inspiring::quote().PHP_EOL);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $array = parent::getOptions();

        array_push($array, ['user_ids', null, InputOption::VALUE_REQUIRED, 'UserIds List']);

        return $array;
    }
}

