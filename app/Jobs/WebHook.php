<?php

namespace RZP\Jobs;

use App;

class WebHook extends Job
{
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        parent::__construct();

        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $app = App::getFacadeRoot();

        $app['webhook.inferno']->fire($this, $this->data);
    }
}
