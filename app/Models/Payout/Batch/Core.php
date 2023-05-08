<?php

namespace RZP\Models\Payout\Batch;

use App;
use Ramsey\Uuid\Uuid;

use RZP\Error\Error;
use RZP\Models\Batch;
use RZP\Models\Payout\Metric;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Product;
use Razorpay\Trace\Logger;
use RZP\Models\Payout\Bulk;
use RZP\Constants\Entity as E;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\BatchMicroService;
use RZP\Models\Base\Core as BaseCore;
use RZP\Exception\BadRequestException;
use RZP\Models\Event\Entity as EventEntity;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\FundAccount\Entity as FaEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Webhook\Event as WebhookEvent;
use RZP\Models\Payout\BatchHelper as PayoutBatchHelper;
use RZP\Models\FundAccount\BatchHelper as FaBatchHelper;

class Core extends BaseCore
{
    const WEBHOOK_PAYOUT_FAILED_REQUEST_TIMEOUT_MS = 350;

    public function create(array $input, MerchantEntity $merchant)
    {
        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_ENTITY_CREATE_REQUEST,
            [
                'input'       => $input,
                'merchant_id' => $merchant->getId(),
            ]
        );

        if ($this->merchant->isFeatureEnabled(Features::PAYOUTS_BATCH) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUTS_BATCH_NOT_ALLOWED,
                null,
                $input
            );
        }

        // Save the entity
        $payoutsBatchEntity = new Entity();

        // Building the entity before calling batch service to run validations in advance
        $payoutsBatchEntity->build($input);

        $fileDetailsArray
            = $this->createFileForBatchService($input[Constants::REFERENCE_ID], $input[Constants::PAYOUTS], $merchant);

        $generatedFileName
            = substr($fileDetailsArray[FileStore\Entity::NAME], 0, -1 * (strlen('.' . Constants::EXTENSION_CSV)));

        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_ENTITY_FILE_GENERATED,
            [
                'file_details'        => $fileDetailsArray,
                'generated_file_name' => $generatedFileName,
                'merchant_id'         => $merchant->getId(),
            ]
        );

        $batchServiceResponse = (new Batch\Core())->create(
            [
                Batch\Entity::TYPE    => Batch\Type::PAYOUT,
                Batch\Entity::NAME    => $generatedFileName,
                Batch\Entity::FILE_ID => $fileDetailsArray[Bulk\Base::FILE_ID],
                Batch\Entity::CONFIG  => [Constants::BATCH_REFERENCE_ID => $input[Entity::REFERENCE_ID]],
            ],
            $merchant
        )->toArrayPublic();

        $status = Status::$statusMapBetweenPayoutsBatchAndBatchService[$batchServiceResponse[Batch\Entity::STATUS]];

        $payoutsBatchEntity->setId($batchServiceResponse['id']);

        $payoutsBatchEntity->setStatus($status);

        $payoutsBatchEntity->merchant()->associate($merchant);

        $this->repo->saveOrFail($payoutsBatchEntity);

        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_ENTITY_CREATE_RESPONSE,
            [
                'payouts_batch_entity' => $payoutsBatchEntity->toArrayPublic(),
            ]
        );

        return $payoutsBatchEntity;
    }

    public function createFileForBatchService($refId, $payouts, MerchantEntity $merchant)
    {
        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_FILE_CREATION_BEGINS,
            [
                'reference_id' => $refId,
                'merchant_id'  => $merchant->getId(),
            ]
        );

        return (new Bulk\BatchPayoutsApiFile($payouts, $refId))
                   ->createAndSaveSampleFile(Constants::EXTENSION_CSV, $merchant);
    }

    public function pushWebhookForPayoutCreationFailure(array $exceptionData, array $item, MerchantEntity $merchant)
    {
        try
        {
            $this->trace->info(
                TraceCode::PAYOUTS_BATCH_PAYOUT_ENTITY_CREATION_FAILED_WEBHOOK,
                [
                    'exception_data' => $exceptionData,
                    'merchant_id'    => $merchant->getId(),
                ]);

            $webhookPayload = [
                EventEntity::ENTITY   => EventEntity::EVENT,
                EventEntity::EVENT    => WebhookEvent::PAYOUT_CREATION_FAILED,
                EventEntity::CONTAINS => [
                    0 => PayoutEntity::PAYOUT,
                ],
                EventEntity::PAYLOAD  => [
                    PayoutEntity::PAYOUT => [
                        PayoutEntity::ENTITY => [
                            PayoutEntity::ID              => '',
                            PayoutEntity::ENTITY          => PayoutEntity::PAYOUT,
                            PayoutEntity::FUND_ACCOUNT_ID => '',
                            PayoutEntity::AMOUNT          => $item[PayoutEntity::PAYOUT][PayoutEntity::AMOUNT],
                            PayoutEntity::CURRENCY        => $item[PayoutEntity::PAYOUT][PayoutEntity::CURRENCY],
                            PayoutEntity::NOTES           => $item[PayoutEntity::NOTES] ?? '',
                            PayoutEntity::STATUS          => PayoutStatus::FAILED,
                            PayoutEntity::PURPOSE         => $item[PayoutEntity::PAYOUT][PayoutEntity::PURPOSE],
                            PayoutEntity::MODE            => $item[PayoutEntity::PAYOUT][PayoutEntity::MODE],
                            PayoutEntity::REFERENCE_ID    => $item[PayoutEntity::PAYOUT][PayoutEntity::REFERENCE_ID] ??
                                                             '',
                            PayoutEntity::NARRATION       => $item[PayoutEntity::PAYOUT][PayoutEntity::NARRATION] ?? '',
                            Entity::BATCH_ID              => 'batch_' . $exceptionData[Entity::BATCH_ID],
                            PayoutEntity::FAILURE_REASON  => $exceptionData['error']['description'],
                            'error'                       => [
                                'description' => $exceptionData['error'][Error::DESCRIPTION],
                                'source'      => 'business',
                                'reason'      => $exceptionData['error'][Error::PUBLIC_ERROR_CODE],
                            ],
                        ],
                    ],
                ],
            ];

            if($merchant->isFeatureEnabled(Features::MFN))
            {
                $this->fillPayoutCreationFailedPayloadWithDataRequiredForMfn(
                    $webhookPayload, $item, $exceptionData[Entity::BATCH_ID]);
            }

            $service = $this->app['stork_service'];

            $service->init($this->app['rzp.mode'], Product::BANKING);

            $processEventReq = [
                'event' => [
                    'id'         => UniqueIdEntity::generateUniqueId(),
                    'service'    => $service->service,
                    'owner_id'   => $merchant->getId(),
                    'owner_type' => E::MERCHANT,
                    'name'       => $webhookPayload[EventEntity::EVENT],
                    'payload'    => json_encode($webhookPayload),
                ],
            ];

            $response = $service->request(
                '/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent',
                $processEventReq,
                self::WEBHOOK_PAYOUT_FAILED_REQUEST_TIMEOUT_MS
            );

            $this->trace->info(
                TraceCode::PAYOUTS_BATCH_PAYOUT_ENTITY_CREATION_FAILED_WEBHOOK_COMPLETE,
                [
                    'request'  => $webhookPayload,
                    'response' => $response
                ]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::CRITICAL,
                TraceCode::PAYOUTS_BATCH_PAYOUT_ENTITY_CREATION_FAILED_WEBHOOK_FAILED,
                [
                    'batch_id' => $exceptionData[Entity::BATCH_ID],
                    'message'  => $e->getMessage(),
                ]);

            $this->trace->count(Metric::PAYOUTS_BATCH_PAYOUT_ENTITY_CREATION_FAILED_WEBHOOK_FAILED);

        }
    }

    public function updateEntityFromBatchService($batchId)
    {
        // Re-adding prefix here to avoid rejection at batch service
        if (str_starts_with($batchId, 'batch_') === false)
        {
            $batchId = 'batch_' . $batchId;
        }

        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_STATUS_UPDATE_CALL,
            [
                'batch_id' => $batchId,
            ]);

        $batchResponse = (new BatchMicroService())->fetch(BatchMicroService::BATCH_SERVICE, $batchId, []);

        if (is_null($batchResponse))
        {
            $this->trace->info(
                TraceCode::PAYOUTS_BATCH_BATCH_STATUS_UPDATE_NULL_RECEIVED,
                [
                    'batch_id' => $batchId,
                    'message'  => 'There was an exception while calling the Batch service. Please check logs using ' .
                                  'tracecodes BATCH_SERVICE_BAD_REQUEST or SERVER_ERROR_BATCH_SERVICE_NOT_FOUND',
                ]);
        }
        else
        {
            $this->trace->info(
                TraceCode::PAYOUTS_BATCH_STATUS_UPDATE_RESPONSE,
                [
                    'batch_id'     => $batchId,
                    'batch_entity' => $batchResponse,
                ]);
        }

        return $batchResponse;
    }

    public function fillPayoutCreationFailedPayloadWithDataRequiredForMfn(
        array &$webhook, array $item, $batchId)
    {
        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_PAYOUT_CREATION_FAILED_WEBHOOK_BATCH_STATUS_CHECK,
            [
                'batch_id'  => $batchId,
                'webhook'   => $webhook,
            ]);

        // Check batch status
        try
        {
            $status = $this->checkPayoutsBatchStatus($batchId);
        }
        catch(BadRequestException $e)
        {
            // If the batch entity was not found in the table Payouts_batch
            // it means the batch request didn't come as a private auth based API request.
            if ($e->getCode() === ErrorCode::BAD_REQUEST_INVALID_ID)
            {
                return $webhook;
            }
        }

        $webhook[EventEntity::PAYLOAD][PayoutEntity::PAYOUT][PayoutEntity::ENTITY][Constants::BATCH_STATUS] = $status;

        // set razorpay X acc number in payload
        $webhook[EventEntity::PAYLOAD][PayoutEntity::PAYOUT][PayoutEntity::ENTITY][PayoutEntity::ACCOUNT_NUMBER]
            = $item[PayoutBatchHelper::RAZORPAYX_ACCOUNT_NUMBER];

        // We need to check if fund account stuff is available, as it is possible that only FA ID is provided.
        if ((empty($item[FaBatchHelper::FUND_ACCOUNT][FaBatchHelper::NAME]) === false) and
            (empty($item[FaBatchHelper::FUND_ACCOUNT][FaBatchHelper::NUMBER]) === false) and
            (empty($item[FaBatchHelper::FUND_ACCOUNT][FaBatchHelper::ID]) === true))
        {
            $webhook[EventEntity::PAYLOAD][PayoutEntity::PAYOUT][PayoutEntity::ENTITY]
            [PayoutEntity::FUND_ACCOUNT][FaEntity::BANK_ACCOUNT][FaEntity::NAME]
                = $item[FaBatchHelper::FUND_ACCOUNT][FaBatchHelper::NAME];

            $webhook[EventEntity::PAYLOAD][PayoutEntity::PAYOUT][PayoutEntity::ENTITY]
            [PayoutEntity::FUND_ACCOUNT][FaEntity::BANK_ACCOUNT][FaEntity::ACCOUNT_NUMBER]
                = $item[FaBatchHelper::FUND_ACCOUNT][FaBatchHelper::NUMBER];
        }

        $webhook[EventEntity::PAYLOAD][PayoutEntity::PAYOUT][PayoutEntity::ENTITY]
        [PayoutEntity::NOTES][Constants::CORRELATION_ID]
            = $this->generateCorrelationId();

        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_PAYOUT_CREATION_FAILED_NEW_WEBHOOK,
            [
                'batch_id'  => $batchId,
                'webhook'   => $webhook,
            ]);
    }

    public function fillOtherPayoutWebhooksWithDataRequiredForMfn(array $webhookPayload, PayoutEntity $payoutEntity)
    {
        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_PAYOUT_WEBHOOK_BATCH_STATUS_CHECK,
            [
                'batch_id'          => $payoutEntity->getBatchId(),
                'webhook_payload'   => $webhookPayload,
            ]);

        // Check batch status
        try
        {
            $status = $this->checkPayoutsBatchStatus($payoutEntity->getBatchId());
        }
        catch(BadRequestException $e)
        {
            // If the batch entity was not found in the table Payouts_batch
            // it means the batch request didn't come as a private auth based API request.
            if ($e->getCode() === ErrorCode::BAD_REQUEST_INVALID_ID)
            {
                return $webhookPayload;
            }
        }

        $webhookPayload[Constants::BATCH_STATUS] = $status;

        // get debit account number
        $accNumber = $payoutEntity->getAccountNumberAttribute();

        $webhookPayload[PayoutEntity::ACCOUNT_NUMBER] = $accNumber;

        $fundAcc = $payoutEntity->fundAccount;

        $fundAccType = $fundAcc->getAccountType();

        // Fill fund account details in the webhook
        $webhookPayload[PayoutEntity::FUND_ACCOUNT] = [$fundAccType => $fundAcc->getAccountDetails($fundAccType)];

        $webhookPayload[PayoutEntity::NOTES][Constants::CORRELATION_ID] = $this->generateCorrelationId();

        $this->trace->info(
            TraceCode::PAYOUTS_BATCH_PAYOUT_WEBHOOK_NEW_MFN_PAYLOAD,
            [
                'batch_id'          => $payoutEntity->getBatchId(),
                'webhook_payload'   => $webhookPayload,
            ]);

        return $webhookPayload;
    }

    public function checkPayoutsBatchStatus($batchId)
    {
        // Removing prefix here to avoid rejection when fetching payouts_batch entity
        if (str_starts_with($batchId, 'batch_') === true)
        {
            $batchId = substr($batchId, strlen('batch_'));
        }

        /**
         * @var Entity
         */
        $payoutsBatchEntity = (new Repository())->findOrFailPublic($batchId);

        return $payoutsBatchEntity->updateStatusFromBatchService();
    }

    /**
     * Used in MFN to generate the correlation ID for MFN callback
     * MFN will use this for request idempotency when we call the MFN Callback API
     *
     * @return string
     * @throws \Exception
     */
    private function generateCorrelationId() : string
    {
        $app = App::getFacadeRoot();

        if ($app['env'] === 'testing')
        {
            return '67d30314-f9b7-11eb-ab60-acde48001122';
        }

        return Uuid::uuid1();
    }
}
