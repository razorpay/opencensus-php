<?php

namespace RZP\Models\Payout\SourceRequestIDMapping;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use RZP\lib\AwsTraceIdExtractor;
use RZP\Models\Payout\SourceRequestIDMapping\Repository as SourceRequestIDMappingRepository;

class Core extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get payout information by request source ID
     *
     * @param string $requestId The request ID to lookup (e.g. AWS trace ID)
     * @return array|null The payout info or null if not found
     */
    public function getPayoutBySourceRequestId(string $requestId)
    {
        $this->trace->info(
            'payout.source_request_id.lookup',
            ['request_id' => $requestId]
        );

        $payoutRequestMapping = (new SourceRequestIDMappingRepository)->getPayoutBySourceRequestId($requestId);

        if (count($payoutRequestMapping) === 0)
        {
            return null;
        }

        $mapping = $payoutRequestMapping[0];

        // Converts the stdClass object into associative array
        return get_object_vars($mapping);
    }

    /**
     * Creates a mapping between a source request ID and an entity
     *
     * @param string $sourceId The entity ID to associate with the request ID
     * @param string $sourceType The entity type (e.g. 'payout', 'contact')
     * @return void
     */
    public function createSourceRequestIdMapping(string $sourceId, string $sourceType): void
    {
        $requestId = $this->extractAWSTraceIDFromHeaders($this->app);
        if (empty($requestId)) {
            $this->trace->info(
                'aws_trace_id.empty_error',
                [
                    Entity::SOURCE_ID => $sourceId,
                    Entity::SOURCE_TYPE => $sourceType
                ]
            );

            return;
        }

        $data = [
            Entity::ID          => PublicEntity::generateUniqueId(),
            Entity::SOURCE_TYPE => $sourceType,
            Entity::SOURCE_ID   => $sourceId,
            Entity::REQUEST_ID  => $requestId,
            Entity::CREATED_AT  => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::UPDATED_AT  => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $this->trace->info(
            'source_request_id.mapping_create',
            ['data' => $data, 'source_id' => $sourceId, 'source_type' => $sourceType]
        );

        (new SourceRequestIDMappingRepository)->insertSourceRequestIdMapping($data);
    }

    public function extractAWSTraceIDFromHeaders($app) {
        try {
            $awsTraceID = $app['request']->headers?->get(RequestHeader::X_AMAZON_TRACE_ID);
            if (empty($awsTraceID) === false)
            {
                // add the requestID instead
                $awsTraceID = $app['request']->getTaskId();
            }

            return $awsTraceID;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                'aws_trace_id.extraction_error',
                ['error' => $e->getMessage()]
            );

            return null;
        }
    }
}
