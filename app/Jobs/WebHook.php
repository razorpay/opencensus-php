<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Bus\SelfHandling;

class WebHook extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $app = \App::getFacadeRoot();

        $data = json_decode($this->data, true);

        $app['webhook.inferno']->fire($this, $data);
    }
}
