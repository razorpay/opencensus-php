<?php

namespace RZP\Jobs;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Dashboard extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
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
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        if (isset($this->data['type']) === false)
        {
            $trace->error(TraceCode::DASHBOARD_INTEGRATION_ERROR, ['data' => $this->data]);

            throw new Exception\IntegrationException(
                'Dashboard job does not have a type key',
                ['data' => $this->data]);
        }

        $className  = '\RZP\Dashboard\\' . ucfirst($this->data['type']);

        //will be payment or refund
        $entity = new $className();

        $entity->postRequest($this, $this->data);
    }
}
