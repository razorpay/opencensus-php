<?php

namespace RZP\Jobs;

use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Metric;
use Razorpay\Trace\Logger as Trace;

class PaymentReminder extends Job
{
    const MUTEX_LOCK_TIMEOUT = 3600; // sec

    const RELEASE_WAIT_SECS    = 300;

    /**
     * @var string
     */
    protected $queueConfigKey = 'payment_reminder';

    /**
     * @var array
     */
    protected $input;


    public function __construct(array $input, string $mode = null)
    {
        parent::__construct($mode);

        $this->input = $input;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        $startTime = $this->input['start_time'];

        try
        {
            parent::handle();

            $topic = $this->input['topic'];
            $msg = $this->input['message'];
            $producerKey = $this->input['producer_key'];

            (new KafkaProducer($topic, $msg, $producerKey))->Produce();

            $this->trace->info(
                TraceCode::REMINDER_KAFKA_PUSH_SUCCESSFUL,[
                'input'          => $this->input
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::REMINDER_KAFKA_PUSH_FAILURE,
                $this->input);

            (new Metric())->pushKafkaPushFailedForFailedPaymentMetrics(get_diff_in_millisecond($startTime));
        }
    }
}
