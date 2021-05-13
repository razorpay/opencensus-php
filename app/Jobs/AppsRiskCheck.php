<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\VirtualAccount;
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

        $response = (new MerchantRiskClient())->validateRiskFactorForMerchantRequest($this->params);

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

        $entityType = $response['entity_type'];

        $alertRequest = [
            'entity_type' => $response['entity_type'],
            'entity_id'   => $response['entity_id'],
        ];

        $entity = null;
        switch ($entityType)
        {
            case Entity::VIRTUAL_ACCOUNT:
            {
                $entity = $this->repoManager->virtual_account->find($response['entity_id']);

                $alertRequest['merchant_id']     = $entity->getMerchantId();
                $alertRequest['event_timestamp'] = $entity->getCreatedAt();
                $alertRequest['event_type']      = 'create';
                $alertRequest['source']          = 'va_service';
                $alertRequest['category']        = 'high_risk_keywords';

                break;
            }
        }

        $dataFields = $this->getAlertServiceInput($riskFactorFields);

        if (empty($dataFields) === true)
        {
            return;
        }

        $alertRequest['data'] = $dataFields;

        (new MerchantRiskClient())->createAlertRequest($alertRequest);

    }

    private function getAlertServiceInput(array $riskFactorFields, $dataFields = [])
    {
        foreach ($riskFactorFields as $riskFactorField)
        {
            $score = $riskFactorField['score'];

            $matchedField = $riskFactorField['config_key'];

            switch ($matchedField)
            {
                case VirtualAccount\Entity::DESCRIPTOR:
                {
                    if ($score > 60)
                    {
                        $dataFields[VirtualAccount\Entity::DESCRIPTOR] = $this->params['fields'][0]['value'];
                    }

                    break;
                }
            }
        }
        return $dataFields;
    }
}
