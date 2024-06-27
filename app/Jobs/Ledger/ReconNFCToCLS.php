<?php

namespace RZP\Jobs\Ledger;

use App;
use Exception;
use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Services\KafkaProducer;
use RZP\Models\Ledger\Constants as LedgerConstants;


class ReconNFCToCLS extends Job
{
    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 3;
    const TOPIC = LedgerConstants::LIVE_ART_EVENTS;

    protected $transactionMessage;

    protected $paymentId;

    public function __construct(string $mode, array $transactionMessage, $paymentId)
    {
        parent::__construct($mode);

        $this->transactionMessage = $transactionMessage;

        $this->paymentId = $paymentId;
    }

    public function handle()
    {
        parent::handle();

       if(($this->mode === Mode::TEST))
       {
           return;
       }

        $producerKey =  $this->paymentId;

        $topic = env('LIVE_ART_EVENTS', LedgerConstants::LIVE_ART_EVENTS);

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($this->transactionMessage), $producerKey));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::NFC_RECON_DATA_PUSH_SUCCESS, [
                LedgerConstants::PRODUCER_KEY => $producerKey,
                LedgerConstants::TOPIC        => $topic,
                LedgerConstants::MESSAGE      => $this->transactionMessage,
            ]);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::NFC_RECON_DATA_PUSH_FAILURE,
                [
                    LedgerConstants::PRODUCER_KEY => $producerKey,
                    LedgerConstants::TOPIC        => $topic,
                    LedgerConstants::MESSAGE      => $this->transactionMessage,
                ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::NFC_RECON_DATA_PUSH_FAILURE, [
                'transaction_message' => $this->transactionMessage,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
