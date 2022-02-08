<?php
// TODO: Will be removed once FriendBuySendPurchaseEvent cron job is deployed to production.

namespace RZP\Models\Merchant\Onboarding\Cron;

use App;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Base\RepositoryManager;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\Escalations\Constants;
use RZP\Models\Merchant\Constants as MConstants;

abstract class BaseJobProcessor
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    protected $trace;

    protected $cache;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app[MConstants::REPO];

        $this->trace = $this->app[MConstants::TRACE];

        $this->cache = $this->app['cache'];
    }

    private function getLastCronTime(string $cacheKey,int $thresholdMins)
    {
        $lastCronTime = $this->cache->get($cacheKey);

        if (empty($lastCronTime))
        {
            return Carbon::now()->subMinutes($thresholdMins)->getTimestamp();
        }

        return $lastCronTime;
    }

    private function updateLastCronTime(string $cacheKey)
    {
        $this->cache->put($cacheKey, Carbon::now()->getTimestamp());
    }

    public function getTimeWindowForCron($input, string $cacheKey,int $thresholdMins)
    {
        if (empty($input)===true or empty($input[Constants::START_TIME]) === true)
        {
            $lastCronTime = Carbon::createFromTimestamp(
                $this->getLastCronTime($cacheKey,$thresholdMins), Timezone::IST);

            $this->updateLastCronTime($cacheKey);

            $to = Carbon::now()->getTimestamp();

            $from = $lastCronTime->getTimestamp();
        }
        else
        {
            $to = $input[Constants::END_TIME];

            $from = $input[Constants::START_TIME];
        }

        return [$from, $to];
    }

    public abstract function execute($input);
}
