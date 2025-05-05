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
     * Creates a mapping between a source request ID and a payout ID
     * 
     * @param string $payoutId The payout ID to associate with the request ID
     * @return void
     */
    public function createSourceRequestIdMapping(string $payoutId): void
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
            Entity::SOURCE_TYPE => 'aws_trace_id',
            Entity::SOURCE_ID   => $requestId,
            Entity::CREATED_AT  => Carbon::now(Timezone::IST)->getTimestamp(),
            Entity::UPDATED_AT  => Carbon::now(Timezone::IST)->getTimestamp(),
        ];

        $this->trace->info(
            'payout.source_request_id.mapping_create',
            ['data' => $data, 'payout_id' => $payoutId]
        );

        (new SourceRequestIDMappingRepository)->insertSourceRequestIdMapping($data);
    }

    /**
     * Gets the current source request ID (AWS trace ID)
     * 
     * @return string The request ID
     */
    public function getCurrentRequestId(): string
    {
        try {
            return $this->awsTraceIdExtractor->getAwsTraceId();
        } catch (\Exception $e) {
            $this->trace->error(
                'payout.source_request_id.get_current_error',
                ['error' => $e->getMessage()]
            );
            return '';
        }
    }

    /**
     * Find a payout ID associated with a source request ID
     * 
     * @param string $requestId The request ID
     * @return string|null The payout ID or null if not found
     */
    public function getPayoutIdFromSourceRequestId(string $requestId): ?string
    {
        $mapping = $this->getPayoutBySourceRequestId($requestId);
        
        if ($mapping === null)
        {
            return null;
        }
        
        return $mapping['source_id'];
    }
} 