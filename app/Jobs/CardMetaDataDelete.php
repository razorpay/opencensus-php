<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Services\KafkaProducer;

class CardMetaDataDelete extends Job
{
    const MAX_JOB_ATTEMPTS = 2;

    public $delay = 10;

    protected $trace;

    protected $data;

    protected $traceData;

    protected $queueConfigKey = 'card_metadata_deletion';

    private $app;

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;

        $this->traceData = [];
    }

    public function handle()
    {
        parent::handle();

        $this->app = App::getFacadeRoot();

        $this->trace->info(
            TraceCode::DELETING_METADATA_AFTER_RECONCILIATION,
            $this->data
        );

        try {

            $this->app['card.cardVault']->deleteToken($this->data['vault_token']);

            $this->traceData['state'] = 'success';

            $this->traceData();

            $this->trace->info(
                TraceCode::CARD_METADATA_DELETE_SUCCESSFUL,
                $this->data
            );

            $this->pushCardMetaDataDeleteEvent($this->data['payment_id']);

            $this->delete();

        }
        catch (\Exception $ex)
        {

            $traceCode = TraceCode::CARD_METADATA_DELETE_EXCEPTION;

            $this->handleCardMetaDataDeleteException($traceCode, $ex);
        }
    }

    protected function traceData()
    {
        $this->trace->info(
            TraceCode::DELETING_METADATA_AFTER_RECONCILIATION,
            $this->traceData
        );
    }

    protected function pushCardMetaDataDeleteEvent($payment_id)
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        $topic = 'events.payments-card-meta.v1.' .$mode;

        $event = [
            'event_name'         => 'PAYMENT.CARD.DELETE_METADATA',
            'event_type'         => 'payment-events',
            'event_group'        => 'deletion',
            'version'            => 'v1',
            'event_timestamp'    => (int)(microtime(true)),
            'producer_timestamp' => (int)(microtime(true)),
            'source'             => 'api',
            'mode'               => $mode,
            'payment_id'         => $payment_id
        ];

        $this->trace->info(
            TraceCode::CARD_METADATA_DELETE_EVENT,
            $event
        );

        (new KafkaProducer($topic, stringify($event)))->Produce();
    }

    protected function handleCardMetaDataDeleteException($traceCode, $ex)
    {
        $this->data['job_attempts'] = $this->attempts();

        $this->trace->error(
            $traceCode,
            $this->data
        );

        $this->handleCardMetaDeleteJobRelease();
    }

    protected function handleCardMetaDeleteJobRelease()
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
