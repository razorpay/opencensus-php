<?php

namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Admin\ConfigKey;

class Job implements ShouldQueue
{
    use Extended\Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const MAX_RETRY_ATTEMPT = 1;
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
     * @var bool Whether to push metrics for this job, default is false
     */
    protected $metricsEnabled = false;

    /**
     * @var bool|null Whether application's http auth type was app when job was pushed
     */
    protected $appAuth;

    /**
     * @var string Origin product of the request, i.e.. primary or banking
     */
    protected $originProduct;

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
     * In case of sync queue implementation it's needed that we keep mode of
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
     * @var Logger
     */
    protected $trace;

    protected $taskId;

    protected $cache;

    /**
     * @var $mutex Mutex
     */
    protected $mutex;

    /**
     * Default timeout value for a job is 60s.
     * @var integer
     */
    public $timeout = 60;

    /**
     * Store and send passport in async requests
     */
    public $jobPassport;

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

        $this->originProduct = $app['basicauth']->getProduct();

        $this->taskId       = $app['request']->getTaskId();
        $this->jobName      = $this->jobName ?? snake_case(class_basename($this));
        $this->appAuth      = $app['basicauth']->isAppAuth();
        $this->jobPassport  = "";

        $this->setArrayOffsetErrorHandler();
    }

    public function handle()
    {
        $mode = $this->mode ?? MODE::LIVE;

        $attrs = ['jobName'         =>  $this->jobName,
                    'mode'          =>  $mode,
                    'originProduct' =>  $this->originProduct,
                    'taskId'        =>  $this->taskId
                ];

        Tracer::inSpan(['name' => 'SQS/init', 'attributes' => $attrs], function() {
           $this->init();
        });

        if ($this->isMetricsEnabled())
        {
            $this->trace->gauge(Metric::QUEUE_JOB_ATTEMPT_COUNT, $this->attempts(), [
                'job_name'         => $this->getJobName(),
                'queue_name'       => $this->getQueueConfigKey(),
                'origin_product'   => $this->getOriginProduct(),
            ]);
        }
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

    public function getOriginProduct()
    {
        return $this->originProduct;
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

        $this->mutex = $app['api.mutex'];

        // Task Id needs to be set in trace
        $this->trace->processor('web')->setTaskId($this->taskId);

        // Sets application and db mode if $mode is set
        if ($this->mode !== null)
        {
            $app['basicauth']->setModeAndDbConnection($this->mode);
        }

        //Overwrite to db connection via proxysql
        $app['proxysql.config']->setDatabaseHostsIfApplicable();

        // Set origin product, this is useful in tracking X logs and exceptions
        // Refer ApiTraceProcessor::addProduct()
        if ($this->originProduct !== null)
        {
            $app['basicauth']->setProduct($this->getOriginProduct());
        }

        //
        // We need to set the appAuth, if it was set when this job was pushed to the queue.
        // This is needed when we want to create child recon batches while processing a
        // recon batch (inside queue, e.g. VAS Hdfc Reconciliation). If this appAuth is not
        // set here, Auth validation will fail and child recon batch can not be created.
        // (Refer API PR : 11827)
        //
        $app['basicauth']->setBasicAppAuth($this->appAuth ?: false);

        // if any job sets passport token, assign it or clear it if prev set
        (empty($this->jobPassport) === false) ? $app['basicauth']->setPassportFromJob($this->jobPassport): $app['basicauth']->setPassportFromJob("");

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

                try
                {
                    $this->beforeJobKillCleanUp();
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e);
                }

                $this->trace->error(TraceCode::QUEUE_JOB_TIMEOUT, [
                    'job'     => $this->getJobName(),
                    'timeout' => $this->timeout,
                ]);

                if ($this->isMetricsEnabled())
                {
                    $this->trace->count(Metric::QUEUE_JOB_WORKER_TIMEOUT, [
                        'job_name'           => $this->getJobName(),
                        'queue_name'         => $this->getQueueConfigKey(),
                        'origin_product'     => $this->getOriginProduct(),
                        'attempts_exhausted' => $this->attempts() > static::MAX_RETRY_ATTEMPT,
                    ]);
                }

                $app['queue.worker']->kill(1);
            });
        }
    }

    protected function beforeJobKillCleanUp()
    {
        $this->mutex->releaseAllAcquired();
    }

    /**
     * This function is introduced to enable metrics for a job
     * By default, metrics are disabled for all jobs.
     * Override this function to enable metrics for a job
     */
    protected function isMetricsEnabled()
    {
        return $this->metricsEnabled;
    }

    protected function countJobException(\Throwable $e)
    {
        if ($this->isMetricsEnabled())
        {
            $this->trace->count(Metric::QUEUE_JOB_WORKER_EXCEPTION, [
                'job_name'           => $this->getJobName(),
                'error_code'         => $e->getCode(),
                'queue_name'         => $this->getQueueConfigKey(),
                'origin_product'     => $this->getOriginProduct(),
                'attempts_exhausted' => $this->attempts() > static::MAX_RETRY_ATTEMPT,
            ]);
        }
    }

    /**
     * Any job which needs to send passport token can call this func.
     * Make sure to call before dispatch, since request context is unavailable in workers.
     */
    protected function setPassportTokenForJobs(string $merchantId = "", int $tokenExpiryInSecs = 1200)
    {
        if ((new Payment\Service)->isRazorxTreatmentForRefundsV1_1($merchantId) === false)
        {
            return;
        }

        $app = App::getFacadeRoot();
        $this->jobPassport = $app['basicauth']->getPassportJwt(get_called_class(), $tokenExpiryInSecs);
    }

    /**
     * We are suppressing the "Trying to access array offset on value of type null" because of the changes in PHP 8.1
     * which is explicitly throwing error if array is null or when we are accessing fields without null check
     *
     * @return void
     */
    private function setArrayOffsetErrorHandler(): void
    {
        set_error_handler(function($errNo, $errStr)
        {
            // error was suppressed with the @-operator
            if (0 === error_reporting())
            {
                return false;
            }

            // $errStr may need to be escaped:
            $errStr = htmlspecialchars($errStr);

            if ($errStr === "Trying to access array offset on value of type null")
            {
                // log to sumo here, so we can fix over time.
                return true;
            }

            return false;
        }, E_WARNING);
    }
}
