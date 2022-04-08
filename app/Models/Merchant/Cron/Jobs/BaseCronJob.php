<?php


namespace RZP\Models\Merchant\Cron\Jobs;

use App;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Exception\Cron\CronConfigIntegrityException;
use RZP\Models\Merchant\Cron\Collectors\Core\BaseCollector;
use RZP\Models\Merchant\Cron\Constants;
use RZP\Models\Merchant\Cron\Dto\ActionDto;
use RZP\Models\Merchant\Cron\Actions\BaseAction;
use RZP\Models\Merchant\Cron\Metrics;
use RZP\Trace\TraceCode;
use RZP\Notifications\Onboarding\Events as OnboardingEvents;

class BaseCronJob
{

    /**
     * @var array list of data processors from which data needs to be gathered before taking the actions
     */
    protected $dataCollectors = [];

    /**
     * @var array All the actions that should be taken while processing the cron
     * Actions are executed in order
     */
    protected $actions = [];

    /**
     * @var string If `cacheOnly` is enabled this should not be empty
     */
    protected $lastCronTimestampCacheKey = "";

    /**
     * @var bool Enable if last cron time is to be picked from cache itself
     */
    protected $cacheOnly = false;

    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    protected $args = [];

    protected $defaultArgs = [];

    protected $cronName;

    protected $lastCronTime;

    protected $data;

    protected $cronStartTime;

    protected $cronEndTime;

    protected $attempts = 0;

    protected function getStartInterval(): ?int
    {
        return null;
    }

    protected function getEndInterval(): ?int
    {
        return null;
    }

    protected function getDefaultLastCronValue(): ?int
    {
        return null;
    }

    public function __construct(array $args)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->args = $args;

        $this->cronName = $args[Constants::CRON_NAME] ?? "";

        $this->data = [];

        $this->lastCronTime = $this->getLastCronTime();
    }

    private function initCron()
    {
        $this->cronStartTime = Carbon::now()->getTimestamp();

        $this->attempts = $this->attempts + 1;

        $this->populateDefaultArgs();
    }

    private function populateDefaultArgs() {

        if($this->getStartInterval() != null)
        {
            $this->args["start_time"] = $this->getStartInterval();
        }

        if($this->getEndInterval() != null)
        {
            $this->args["end_time"] = $this->getEndInterval();
        }

        $this->args = array_merge($this->args, $this->defaultArgs);
    }

    protected function getAttempts(): int
    {
        return $this->attempts;
    }

    public function process() : bool
    {
        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_STARTED, [
            'args'              => $this->args,
            'last_cron_time'    => $this->lastCronTime,
            'attemptys'         => $this->attempts
        ]);

        $this->app['trace']->count(Metrics::CRON_STARTED_TOTAL, $this->getMetricDimensions());

        try
        {

            $this->initCron();

            $this->updateLastCronTimeIfApplicable($this->cronStartTime);

            $this->data = $this->fetchDataFromCollectors();

            $status = $this->executeActions();

            $this->handleCompletion($status);

            return true;
        }
        catch (\Throwable $ex)
        {
            $this->handleFailure($ex);

            return false;
        }
    }

    public function handleCompletion($status)
    {
        $this->cronEndTime = Carbon::now()->getTimestamp();

        $this->app['trace']->info(TraceCode::CRON_ATTEMPT_COMPLETE, [
            'cron_start_time'   => $this->cronStartTime,
            'cron_end_time'     => $this->cronEndTime,
            'args'              => $this->args,
            'attempts'          => $this->attempts,
            'status'            => $status
        ]);

        $duration = $this->cronEndTime - $this->cronStartTime;
        $dimensions = $this->getMetricDimensions();

        $this->app['trace']->count(Metrics::CRON_COMPLETE_TOTAL, $dimensions);
        $this->app['trace']->histogram(Metrics::CRON_DURATION_MILLISECONDS, $duration, $dimensions);
    }

    public function handleFailure(\Throwable $ex)
    {
        $this->cronEndTime = Carbon::now()->getTimestamp();

        $this->app['trace']->traceException($ex, Trace::ERROR, TraceCode::CRON_ATTEMPT_FAILURE, [
            'cron_start_time'   => $this->cronStartTime,
            'cron_end_time'     => $this->cronEndTime,
            'args'              => $this->args,
            'attempts'          => $this->attempts
        ]);

        $duration = $this->cronEndTime - $this->cronStartTime;
        $dimensions = $this->getMetricDimensions();

        $this->app['trace']->count(Metrics::CRON_FAILED_TOTAL, $dimensions);
        $this->app['trace']->histogram(Metrics::CRON_DURATION_MILLISECONDS, $duration, $dimensions);
    }

    public function fetchDataFromCollectors(): array
    {
        $data = [];

        foreach ($this->dataCollectors as $name => $collector)
        {
            if(is_subclass_of($collector, BaseCollector::class) === false)
            {
                throw new CronConfigIntegrityException("invalid data collector defined");
            }

            $collectorInstance = new $collector($this->lastCronTime, $this->cronStartTime, $this->args);

            $collectorData = $collectorInstance->collect();

            $data[$name] = $collectorData ?? null;
        }

        return $data;
    }

    /**
     * @return string status of actions
     * @throws CronConfigIntegrityException
     */
    protected function executeActions(): string
    {
        foreach ($this->actions as $action)
        {
            if(is_subclass_of($action, BaseAction::class) === false)
            {
                throw new CronConfigIntegrityException("invalid action defined");
            }

            $actionInstance = new $action($this->args);

            /** @var ActionDto $actionResponse */
            $actionResponse = $actionInstance->execute($this->data);

            if($actionResponse->getStatus() != Constants::SUCCESS)
            {
                return $actionResponse->getStatus();
            }
        }

        return Constants::SUCCESS;
    }

    public function updateLastCronTimeIfApplicable($timeStamp)
    {
        if(empty($this->lastCronTimestampCacheKey) === false)
        {
            $this->app['cache']->put($this->lastCronTimestampCacheKey, $timeStamp);
        }
    }

    /**
     * Fetches the timestamp of last time the cron ran.
     * If `cacheOnly` is enabled, we'll fetch last cron time from cache itself
     * Otherwise, we'll query DB to find the last cron entry and return created_at
     * @return int|null
     * @throws CronConfigIntegrityException
     */
    protected function getLastCronTime(): ?int
    {
        if (empty($this->lastCronTimestampCacheKey) === true)
        {
            return null;
        }

        $cacheValue = $this->app['cache']->get($this->lastCronTimestampCacheKey);

        // if value fetched from cache is null check a default value for cron time is provided.
        // if not provided set current time - 15 minutes as default value
        if($cacheValue === null)
        {
            $defaultLastCronValue = $this->getDefaultLastCronValue();
            return is_null($defaultLastCronValue) ? Carbon::now()->subMinutes(15)->getTimestamp() :
                $defaultLastCronValue;
        }

        return $cacheValue;
    }

    private function getMetricDimensions($status = null)
    {
        $dimensions = [];

        $dimensions[Metrics::LABEL_CRON_ATTEMPTS] = $this->attempts;

        if(empty($status) === false)
        {
            $dimensions[Metrics::LABEL_CRON_STATUS] = $status;
        }

        $dimensions[Metrics::LABEL_CRON_NAME] = $this->args[Constants::CRON_NAME];

        return $dimensions;
    }
}
