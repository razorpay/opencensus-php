<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Services\KafkaProducer;

class TokenHqPricingEvents extends Job
{
    const MAX_JOB_ATTEMPTS = 3;

    public $delay = 10;

    protected $trace;

    protected $data;

    protected $traceData;

    protected $queueConfigKey = 'token_hq_pricing_events';

    private $app;

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;

        $this->traceData = $data;
    }

    public function handle()
    {
        parent::handle();

        $this->app = App::getFacadeRoot();

        $this->trace->info(
            TraceCode::TOKEN_HQ_PRICING_EVENT_DATA,
            $this->data
        );

        try {

            $this->pushTokenHqPricingEvent($this->data);

            $this->traceData['state'] = 'success';

            $this->traceData();

            $this->delete();

        }
        catch (\Exception $ex)
        {

            $traceCode = TraceCode::TOKEN_HQ_PRICING_PUSH_EVENT_EXCEPTION;

            $this->handleEventPushFailureException($traceCode, $ex);
        }
    }

    protected function traceData()
    {
        $this->trace->info(
            TraceCode::TOKEN_HQ_PRICING_PUSH_EVENT_STATE,
            $this->traceData
        );
    }

    protected function pushTokenHqPricingEvent($data)
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        $topic = 'events.token-hq-charge-events.v1.' .$mode;

        $context = [
            'task_id' => $this->app['request']->getTaskId(),
            'request_id' => $this->app['request']->getId(),
        ];

        $properties = $data;

        $event = [
            'event_name'         => 'TOKENHQ.CHARGE.EVENT',
            'event_type'         => 'token-hq-events',
            'version'            => 'v1',
            'event_timestamp'    => Carbon::now(Timezone::IST)->getTimestamp(),
            'producer_timestamp' => Carbon::now(Timezone::IST)->getTimestamp(),
            'source'             => 'api',
            'mode'               => $mode,
            'context'            => $context,
            'properties'         => $properties
        ];

        $this->trace->info(
            TraceCode::TOKEN_HQ_PRICING_EVENT,
            $event
        );

        (new KafkaProducer($topic, stringify($event)))->Produce();
    }

    protected function handleEventPushFailureException($traceCode, $ex)
    {
        $this->data['job_attempts'] = $this->attempts();

        $this->trace->error(
            $traceCode,
            $this->data
        );

        $this->handleDeleteJobRelease();
    }

    protected function handleDeleteJobRelease()
    {
        if ($this->attempts() > self::MAX_JOB_ATTEMPTS)
        {
            $this->delete();

            $this->traceData['state'] = 'deleted';
        }
        else
        {
            // retry interval is 10 sec
            $this->release(10);

            $this->traceData['state'] = 'reattempt';
        }

        $this->traceData();
    }
}
