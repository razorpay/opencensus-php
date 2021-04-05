<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Services\MerchantRiskAlertClient;

class NotifyRas extends Job
{
    protected $queueConfigKey = "notify_ras";

    protected $params;

    public function __construct(string $mode, array $params)
    {
        parent::__construct($mode);

        $this->params = $params;
    }

    public function handle()
    {
        parent::handle();

        $params = $this->params;

        $data = $params['data'];

        $params['data'] = [];

        $this->trace->info(
            TraceCode::MERCHANT_RISK_ALERT_QUEUED_CREATE_OPERATION_INITIATED,
            [
                'params' => $params,
            ]
        );

        $params['data'] = $data;

        $response = (new MerchantRiskAlertClient())->createMerchantAlert(
            $params['merchant_id'],
            $params['entity_type'],
            $params['entity_id'],
            $params['category'],
            $params['source'],
            $params['event_type'],
            $params['event_timestamp'],
            $params['data']);

        $this->trace->info(
            TraceCode::MERCHANT_RISK_ALERT_QUEUED_CREATE_OPERATION_PROCESSED,
            [
                'response' => $response,
            ]
        );
    }
}
