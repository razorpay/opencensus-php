<?php

namespace RZP\Jobs;

use App;
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
        $className  = $this->data['es_repo_path'];
	
        $entity = new $className();

        $entity->fireStoreEntity($this, $this->data);
    }
}