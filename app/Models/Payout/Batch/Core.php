<?php

namespace RZP\Models\Payout\Batch;

use RZP\Error\Error;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Product;
use Razorpay\Trace\Logger;
use RZP\Models\Payout\Bulk;
use RZP\Constants\Entity as E;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\Core as BaseCore;
use RZP\Exception\BadRequestException;
use RZP\Models\Event\Entity as EventEntity;
use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Webhook\Event as WebhookEvent;

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

        if ($this->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::PAYOUTS_BATCH) === false)
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
                            Entity::BATCH_ID              => $exceptionData[Entity::BATCH_ID],
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
        }
    }
}
