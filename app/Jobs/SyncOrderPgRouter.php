<?php


namespace RZP\Jobs;


use App;
use Throwable;
use RZP\Trace\TraceCode;
use RZP\Models\Order\Metric;
use Razorpay\Trace\Logger as Trace;

class SyncOrderPgRouter extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 10;
    const RELEASE_WAIT_SECS = 120;

    const JOB_DELETED = 'job_deleted';
    const JOB_RELEASED = 'job_released';

    /**
     * @var string
     */
    protected $queueConfigKey = 'sync_order_pg_router';

    /**
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->count(Metric::PG_ROUTER_ORDER_SYNC_QUEUE_PUSH_COUNT);

            App::getFacadeRoot()['pg_router']->syncOrderToPgRouter($this->data, true);

//            $this->trace->info(TraceCode::ORDER_DATA_SYNC_TO_PG_ROUTER_SUCCESS,
//                [
//                    'id' => $this->data['id']
//                ]
//            );

            $this->trace->count(Metric::PG_ROUTER_ORDER_SYNC_QUEUE_CONSUME_COUNT);

            $this->delete();
        }
        catch(\Exception $e)
        {
            $this->handleException($e);
        }
    }

    /**
     * When an exception occurs, the job gets deleted if it has
     * exceeded the maximum attempts. Otherwise it is released back
     * into the queue after the set release wait time
     *
     * @param Throwable $e
     */
    protected function handleException(\Throwable $e)
    {
        $jobAction = self::JOB_DELETED;

        if($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $this->trace->traceException(
            $e,
            Trace::ERROR,
            TraceCode::ORDER_DATA_SYNC_TO_PG_ROUTER_FAILURE,
            ['job_action' => $jobAction,
                'order_id'=> $this->data['id']]);
    }
}
