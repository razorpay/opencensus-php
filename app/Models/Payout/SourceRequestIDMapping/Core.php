<?php

namespace RZP\Models\Payout\SourceRequestIDMapping;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use RZP\lib\AwsTraceIdExtractor;
use RZP\Models\Payout\SourceRequestIDMapping\Repository as SourceRequestIDMappingRepository;

class Core extends Base
{
    protected $awsTraceIdExtractor;

    public function __construct()
    {
        parent::__construct();
        $this->awsTraceIdExtractor = new AwsTraceIdExtractor();
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
        $requestId = "";
        
        try {
            $requestId = $this->awsTraceIdExtractor->getAwsTraceId();
        } catch (\Exception $e) {
            $this->trace->error(
                'payout.source_request_id.extraction_error',
                ['error' => $e->getMessage()]
            );
            return;
        }
        
        if (empty($requestId)) {
            return;
        }

        $data = [
            Entity::ID          => PublicEntity::generateUniqueId(),
            Entity::SOURCE_TYPE => $sourceType,
            Entity::SOURCE_ID   => $sourceId,
            Entity::CREATED_AT  => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::UPDATED_AT  => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $this->trace->info(
            'source_request_id.mapping_create',
            ['data' => $data, 'source_id' => $sourceId, 'source_type' => $sourceType]
        );

        (new SourceRequestIDMappingRepository)->insertSourceRequestIdMapping($data);
    }
}
