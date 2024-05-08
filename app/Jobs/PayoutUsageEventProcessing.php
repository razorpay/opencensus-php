<?php

namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Payout\Core;
use RZP\Services\RazorXClient;

/*
 * We just push to this queue in api. Messages will be consumed at PS end.
 */

class PayoutUsageEventProcessing extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'payout_usage_event_processing';

    /**
     * @var array
     */
    protected $params;

    /**
     * @var string
     */
    protected $entityID;

    /**
     * @var string
     */
    protected $entityType;

    public function __construct(string $entityID, string $entityType, array $params)
    {
        parent::__construct(MODE::LIVE);

        $this->params = $params;

        $this->entityID = $entityID;

        $this->entityType = $entityType;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::PAYOUT_USAGE_EVENT_PROCESSING_INIT,
            [
                "params" => $this->params,
                "entity_id" => $this->entityID,
                "entity_type" => $this->entityID
            ]
        );
    }

    public function getEntityID()
    {
        return $this->entityID;
    }

    public function getEntityType()
    {
        return $this->entityType;
    }

    public function getParams()
    {
        return $this->params;
    }
}
