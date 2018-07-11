<?php

namespace RZP\Jobs;

use App;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Job implements ShouldQueue
{
    use Extended\Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * If specified, it's value would be used from config/queue.php to choose proper queue connection and name.
     * By default the same would be looked up by snake cased class name, finally fall backs to default connection.
     * @var string|null
     */
    protected $queueConfigKey;

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

    /**
     * Default timeout value for a job is 60s.
     * @var integer
     */
    public $timeout = 60;

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
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return string|null
     */
    public function getPreviousMode()
    {
        return $this->previousMode;
    }

    public function getQueueConfigKey(): string
    {
        return $this->queueConfigKey ?: snake_case(class_basename($this));
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

        $this->repoManager->resetConnectionAttributes();
    }
}
