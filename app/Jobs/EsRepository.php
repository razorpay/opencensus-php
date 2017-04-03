<?php

namespace RZP\Jobs;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class EsRepository extends Job implements ShouldQueue
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

        if (isset($this->data['es_repo_path']) === false)
        {
            $trace->error(TraceCode::ES_SAVE_FAILED, ['data' => $this->data]);

            throw new Exception\IntegrationException(
                'EsRepository job does not have a es_repo_path key',
                ['data' => $this->data]);
        }

        $className  = $this->data['es_repo_path'];

        $entity = new $className();

        $entity->fireStoreEntity($this, $this->data);
    }
}
