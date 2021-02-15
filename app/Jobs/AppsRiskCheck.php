<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Services\MerchantRiskClient;

class AppsRiskCheck extends Job
{
    protected $queueConfigKey = "apps_risk_check";

    protected $params;

    public function __construct(string $mode, array $params)
    {
        parent::__construct($mode);

        $this->params = $params;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::APPS_RISK_CHECK_QUEUE_INITIATED,
            [
                'params' => $this->params,
            ]
        );

        $response = (new MerchantRiskClient())->getMerchantRiskScores($this->params['client_type'],
                                                                      $this->params['entity_id'],
                                                                      $this->params['fields']);
        $this->validateRiskFactorResponse($response);

        $this->trace->info(
            TraceCode::APPS_RISK_CHECK_QUEUE_COMPLETED,
            [
                'Response' => $response,
            ]
        );
    }

    protected function validateRiskFactorResponse(array $response)
    {
        $riskFactorFields = (array_key_exists('fields', $response) === true) ? $response['fields'] : [];

        foreach ($riskFactorFields as $riskFactorField)
        {
            if ($riskFactorField['score'] > 60)
            {
                /**
                 * @todo : Add alert API call
                 */
            }
        }
    }
}
