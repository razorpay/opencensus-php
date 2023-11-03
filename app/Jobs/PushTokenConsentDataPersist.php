<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class PushTokenConsentDataPersist extends Job
{
    /**
     * @var array
     */
    protected $data;


    public function __construct(array $data, string $mode = null)
    {
        parent::__construct($mode);

        $this->data = $data;
    }

    /**
     * Process queue request
     */
    public function handle()
    {

        parent::handle();
        try
        {
            $topic = 'events.push-token-consent.v1.' .$this->mode;

            $this->trace->info(
                TraceCode::PUSH_TOKEN_CONSENT_PERSIST_EVENT,
                ['mode' => $this->mode,
                    'topic' => $topic,
                    'token_id' => $this->data['token_id']]);


            $event = [
                "event_name"         => "push-token-consent-given",
                "event_type"         => "push-token-consent",
                "version"            => "v1",
                "event_timestamp"    => Carbon::now()->getTimestamp(),
                "producer_timestamp" => Carbon::now()->getTimestamp(),
                "source"             => "push-token-create",
                "mode"               => $this->mode,
                "context"            => ['token_id'  => $this->data['token_id']],
                "properties"         => $this->data['consent'],
            ];

            (new KafkaProducer($topic, stringify($event)))->Produce();

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::PUSH_TOKEN_CONSENT_PERSIST_FAILURE,
                $this->data);
        }
    }
}
