<?php
namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\OneClickCheckout\RtoRecommendation;


class OneCCReviewCODOrder extends Job
{
    const BASE_RETRY_INTERVAL_SEC = 60;
    const BACKOFF_FACTOR = 2;
    const MAX_RETRY_ATTEMPTS = 4;

    /**
     * @var string
     */
    protected $queueConfigKey = 'one_cc_review_cod_order';

    /**
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
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

        $this->trace->info(
            TraceCode::ONE_CC_REVIEW_COD_ORDER_JOB,
            array_merge($this->data, ['attempts' => $this->attempts()]));
        try
        {
            (new RtoRecommendation\Service())->handleAction($this->data);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ONE_CC_REVIEW_COD_ORDER_EXCEPTION,
                []);
            $this->checkRetry();
        }

    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPTS)
        {
            $this->trace->error(
                TraceCode::ONE_CC_REVIEW_COD_ORDER_JOB_FAILED,
                [
                    'attempts' => $this->attempts(),
                    'message'  => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]);

            $this->delete();
        }
        else
        {
            $delay = self::BASE_RETRY_INTERVAL_SEC + pow($this->attempts() + 1, self::BACKOFF_FACTOR);

            $this->trace->info(
                TraceCode::ONE_CC_REVIEW_COD_ORDER_EXCEPTION,
                [
                    'attempts' => $this->attempts(),
                    'delay'    => $delay,
                ]);
            $this->release($delay);
        }
    }
}
