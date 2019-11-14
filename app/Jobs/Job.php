<?php

namespace RZP\Jobs;

use App;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;

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
     * @var bool|null Whether application's http auth type was app when job was pushed
     */
    protected $appAuth;

    /**
     * This is a name of the current job which is being executed.
     *
     * Can be set in the child classes.
     * If not explicitly set, this is snake case name of the job class
     *
     * @var string|null
     */
    protected $jobName = null;

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
     */
    protected $trace;

    protected $taskId;

    protected $cache;

    /**
     * Default timeout value for a job is 60s.
     * @var integer
     */
    public $timeout = 60;

    public function __construct(string $mode = null)
    {
        $this->mode = $mode;

        $app = App::getFacadeRoot();

        //
        // While queueing a job, we need current mode so the
        // job worker can set it while being invoked from console.
        //
        // App rzp mode should actually be the go-to source for current mode,
        // since basic auth sets it while setting its own mode variable, and
        // other flows (ones that don't use basic auth) also set it explicitly.
        // Eg. GatewayDowntime/Service:setMode.
        //
        // However, we're still picking up basic auth mode first on the off
        // chance that there's some flow where it is set but rzp.mode is not.
        //
        $previousMode = $app['basicauth']->getMode();

        if (isset($app['rzp.mode']) === true)
        {
            $previousMode = $app['rzp.mode'];
        }

        $this->previousMode = $previousMode;

        $this->taskId       = $app['request']->getTaskId();
        $this->jobName      = $this->jobName ?? snake_case(class_basename($this));
        $this->appAuth      = $app['basicauth']->isAppAuth();
    }

    public function handle()
    {
        $this->init();
    }

    /**
     * @return string|null
     */
    public function getJobName()
    {
        return $this->jobName;
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

        // For current job running set the context data, which can then be used application wide
        $app['worker.ctx']->init($this);

        $this->trace = $app['trace'];

        $this->cache = $app['cache'];

        // Task Id needs to be set in trace
        $this->trace->processor('web')->setTaskId($this->taskId);

        // Sets application and db mode if $mode is set
        if ($this->mode !== null)
        {
            $app['basicauth']->setModeAndDbConnection($this->mode);
        }

        $app['basicauth']->init();

        //
        // We need to set the appAuth, if it was set when this job was pushed to the queue.
        // This is needed when we want to create child recon batches while processing a
        // recon batch (inside queue, e.g. VAS Hdfc Reconciliation). If this appAuth is not
        // set here, Auth validation will fail and child recon batch can not be created.
        // (Refer API PR : 11827)
        //
        $app['basicauth']->setBasicAppAuth($this->appAuth ?: false);

        $this->repoManager->resetConnectionAttributes();

        ConfigKey::resetFetchedKeys();

        //
        // reset the job timeout
        //
        $this->registerJobTimeoutSignal($app);
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals()
    {
        return (version_compare(PHP_VERSION, '7.1.0') >= 0) and
            (extension_loaded('pcntl') === true);
    }

    /**
     * Register the worker timeout handler (PHP 7.1+).
     * this will override the default SIGALRM signal handler
     * this will add a trace before terminating the job
     * which will provide the details of termination if its caused job by timeout
     *
     * @param $app
     * @return void
     */
    protected function registerJobTimeoutSignal($app)
    {
        if ($this->supportsAsyncSignals() === true)
        {
            //
            // this will override the default laravel handler where it just terminated the job.
            //
            // We will register a signal handler for the alarm signal so that we can kill this
            // process if it is running too long because it has frozen. This uses the async
            // signals supported in recent versions of PHP to accomplish it conveniently.
            //
            pcntl_signal(SIGALRM, function () use ($app){
                $this->trace->error(
                    TraceCode::QUEUE_JOB_TIMEOUT,
                    [
                        'job'     => $this->getJobName(),
                        'timeout' => $this->timeout,
                    ]);

                $app['queue.worker']->kill(1);
            });
        }
    }
}
