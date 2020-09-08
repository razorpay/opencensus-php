<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;

use App;
use Cache;
use Config;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Models\Admin\ConfigKey;
use Illuminate\Support\Facades\Redis;
use RZP\Exception\ServerErrorException;

class TidGenerator extends core
{
    // TID
    const RANGE_START_INDEX                    = 0;
    const RANGE_END_INDEX                      = 1;
    const WORLDLINE_TID_RANGE_LIST             = 'atos_tid_range_list';
    const WORLDLINE_TID_EXHAUSTION_ALERT_THRESHOLD  = 20000;

    public function __construct()
    {
        parent::__construct();
            
        $this->redis = Redis::Connection('mutex_redis');

        $this->redisTidKey = $this->mode . '_' . self::WORLDLINE_TID_RANGE_LIST;
    }

    protected function insertTidRangesIntoRedisIfEmpty()
    {   
        if (empty($this->redis->lrange($this->redisTidKey, 0, -1)))
        {
            $staticTidRanges = Cache::get(ConfigKey::WORLDLINE_TID_RANGE_LIST, false);

            foreach ($staticTidRanges as $tidRange)
            {
                $this->redis->rpush($this->redisTidKey, json_encode($tidRange));
            }
        }
    }

      /**
     * @return int returns the next free TID
     * throws exception if run out of TID
     * @throws \Exception
     */
    public function generateTid(): string
    {
        $mutex = App::getFacadeRoot()['api.mutex'];

        $tid = $mutex->acquireAndRelease($this->redisTidKey, function ()
        {
            $this->insertTidRangesIntoRedisIfEmpty();

            $currentTidRange = $this->leftPopFromTidRangeList();

            // This will also update the passed currentTidRange
            $nextTid = $this->allocateTidFrom($currentTidRange);

            $tidRangeList = $this->getTidRangeList();

            $tidAvailableCount = $this->getTidAvailableCount($tidRangeList);

            if ($tidAvailableCount < self::WORLDLINE_TID_EXHAUSTION_ALERT_THRESHOLD)
            {
                $this->sendApproachingTidExhaustionAlert($tidAvailableCount, $tidRangeList);
            }

            if ($this->isTidRangeExhausted($currentTidRange) === false)
            {
                $this->leftInsertIntoTidRangeList($currentTidRange);
            }

            return $nextTid;
        },
        60,
        ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
        20);

        return strval($tid);
    }

    protected function leftPopFromTidRangeList() : array
    {
        $tidRangeList = json_decode($this->redis->lpop($this->redisTidKey));

        if ($tidRangeList === null)
        {
            throw new ServerErrorException(null, ErrorCode::SERVER_ERROR_TID_EXHAUSTED, null, null);
        }

        return $tidRangeList;
    }

    protected function allocateTidFrom(array & $tidRange): int
    {
        $tid = $tidRange[self::RANGE_START_INDEX];

        $tidRange[self::RANGE_START_INDEX] = $tidRange[self::RANGE_START_INDEX] + 1;

        return $tid;
    }

    protected function isTidRangeExhausted($tidRange) : bool
    {
        return $tidRange[self::RANGE_START_INDEX] > $tidRange[self::RANGE_END_INDEX];
    }

    protected function leftInsertIntoTidRangeList($tidRange)
    {
        $this->redis->lpush($this->redisTidKey, json_encode($tidRange));
    }

    protected function getTidRangeList() : array
    {
        //0 means start of list in redis
        //-1 means end of list in redis
        $tidRangeRedisList = $this->redis->lrange($this->redisTidKey, 0, -1);

        $tidRangeList = [];

        foreach ($tidRangeRedisList as $range)
        {
            array_push($tidRangeList, json_decode($range));
        }

        return $tidRangeList;
    }

    protected function getTidAvailableCount(array $tidRangeList)
    {
        $freeTidCount = 0;

        foreach ($tidRangeList as $range)
        {
            $freeTidCount = $freeTidCount + ($range[self::RANGE_END_INDEX] - $range[self::RANGE_START_INDEX] + 1);
        }

        return $freeTidCount;
    }

    protected function sendApproachingTidExhaustionAlert(int $tidAvailableCount, array $tidRangeList)
    {
        $data = [
          'tidAvailable' => $tidAvailableCount,
          'tidRangeList' => $tidRangeList,
        ];

        $this->trace->critical(TraceCode::TERMINAL_ONBOARDING_TID_NEARING_EXHAUSTION, $data);

        $this->app['slack']->queue(
            TraceCode::TERMINAL_ONBOARDING_TID_NEARING_EXHAUSTION, $data,
            [
                'channel'               => Config::get('slack.channels.tech_alerts'),
                'username'              => 'alerts',
                'icon'                  => ':x:'
            ]
        );
    }

}