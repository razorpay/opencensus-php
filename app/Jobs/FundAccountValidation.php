<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Services\KafkaProducer;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\PennyTesting;
use RZP\Models\FundAccount\Validation\Entity as ValidationEntity;

class FundAccountValidation extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    protected $queueConfigKey = 'fund_account_validation';

    protected $fundAccountValidationId;

    const MERCHANT_ID                     = 'merchant_id';
    const KAFKA_MESSAGE_TASK_NAME         = 'task_name';
    const KAFKA_MESSAGE_DATA              = 'data';
    const BANK_ACCOUNT_VALIDATION_RESULTS = 'bank_account_validation_results';


    public function __construct(string $mode, string $fundAccountValidationId)
    {
        parent::__construct($mode);

        $this->fundAccountValidationId = $fundAccountValidationId;
    }

    public function handle()
    {
        parent::handle();

        // Trace payload should include all necessary info for debugging.

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'id'           => $this->fundAccountValidationId,
        ];

        try
        {
            $this->trace->debug(TraceCode::FUND_ACCOUNT_VALIDATION_JOB_REQUEST, $tracePayload);

            ValidationEntity::verifyIdAndSilentlyStripSign($this->fundAccountValidationId);

            $validationEntity = $this->repoManager->fund_account_validation->findOrFail($this->fundAccountValidationId);

            $notes = $validationEntity->getNotes();

            //
            // Here decision making to route results to BVS or API is done based on Notes.
            // because While triggering FAV create call, API passes notes with merchant_id but BVS does not.
            //
            if (isset($notes[self::MERCHANT_ID]) === false)
            {
                $this->PushFundAccountValidationEventToKafka($validationEntity);
            }
            else
            {
                (new PennyTesting())->handlePennyTestingEvent($validationEntity);
            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FUND_ACCOUNT_VALIDATION_JOB_ERROR,
                [
                    'mode' => $this->mode,
                    'id'   => $this->fundAccountValidationId,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_JOB_DELETE, [
                'id'           => $this->fundAccountValidationId,
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

    public function PushFundAccountValidationEventToKafka(ValidationEntity $validationEntity)
    {
        $topic = env('BVS_BANK_ACCOUNT_RESPONSE_TOPIC', 'fav-bvs-result-events');

        $data = [
            ValidationEntity::ID      => $validationEntity->getPublicId(),
            ValidationEntity::STATUS  => $validationEntity->getStatus(),
            ValidationEntity::RESULTS => [
                ValidationEntity::ACCOUNT_STATUS  => $validationEntity->getAccountStatus(),
                ValidationEntity::REGISTERED_NAME => [
                    $validationEntity->getRegisteredName(),
                ],
            ],
        ];

        $message = [
            self::KAFKA_MESSAGE_TASK_NAME => self::BANK_ACCOUNT_VALIDATION_RESULTS,
            self::KAFKA_MESSAGE_DATA      => $data,
        ];

        (new KafkaProducer($topic, stringify($message)))->Produce();
    }
}
