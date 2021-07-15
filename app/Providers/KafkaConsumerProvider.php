<?php

namespace RZP\Providers;

use Illuminate\Support\ServiceProvider;

use RZP\Console\Commands\KafkaConsumerCommand;

class KafkaConsumerProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
                            KafkaConsumerCommand::class
                        ]);
    }
}
