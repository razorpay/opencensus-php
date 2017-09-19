<?php

namespace RZP\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Inspire::class,
        Commands\AddDbData::class,
        Commands\GenerateEmailTemplates::class,
        Commands\RzpDbRefresh::class,
        Commands\IinImport::class,
        Commands\UidCheckDigitVerify::class,
        Commands\Index::class,
        Commands\IndexCreate::class,
        Commands\VerifyUpiProviders::class,
        Commands\VerifyTopLevelDomain::class,
        \Laravel\Tinker\Console\TinkerCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }
}
