<?php

namespace RZP\Jobs;

use App;

use Illuminate\Bus\Queueable;

class Job
{
    // General use constants
    const TIME_TAKEN = 'time_taken';

    /**
     * Mode as received from pushed job payload. We set the basic auth's mode
     * and db connection to this value for convenience.
     *
     * @var string|null
     */
    protected $mode;

    /**
     * In case of sync queue implementaiton it's needed that we keep mode of
     * current request context and once job is processed we reset back to that.
     *
     * Also ref EventServiceProvider::resetModePostSyncQueueProcessed()
     *
     * @var string|null
     */
    protected $previousMode;

    /**
     * Repository manager
     *
     * @var \RZP\Base\RepositoryManager
     */
    protected $repoManager;

    /**
     * Trace instance
     *
     * @var \RZP\Trace
     */
    protected $trace;

    protected $taskId;

    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "onQueue" and "delay" queue helper methods.
    |
    */

    use Queueable;

    public function __construct(string $mode = null)
    {
        $this->mode = $mode;

        $app = App::getFacadeRoot();

        $this->previousMode = $app['basicauth']->getMode();
        $this->taskId       = $app['request']->getTaskId();
    }

    public function handle()
    {
        $this->init();
    }

    /**
     * @return string|null
     */
    public function getPreviousMode()
    {
        return $this->previousMode;
    }

    /**
     * Initializes most needed services in queued jobs.
     *
     * Why we initializes services out of constructor?
     * - Because the constructed job instance gets serialized and sent over
     * queue. We don't want to initialize services and increase the size of
     * message. Additionally that throws error in most of the cases as not all
     * services are serialized expectedly.
     */
    protected function init()
    {
        $app = App::getFacadeRoot();

        $this->repoManager = $app['repo'];

        // For jobs, we set the task id to the task_id of the api request which queued the job
        $app['request']->setTaskId($this->taskId);

        $this->trace = $app['trace'];

        // Task Id needs to be set in trace
        $this->trace->processor('web')->setTaskId($this->taskId);

        // Sets application and db mode if $mode is set
        if ($this->mode !== null)
        {
            $app['basicauth']->setModeAndDbConnection($this->mode);
        }
    }
}
