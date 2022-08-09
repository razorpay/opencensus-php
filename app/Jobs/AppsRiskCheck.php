<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\PaymentLink;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use RZP\Models\VirtualAccount;
use RZP\Services\MerchantRiskClient;
use Illuminate\Support\Facades\Config;

class AppsRiskCheck extends Job
{
    protected $queueConfigKey = "apps_risk_check";

    const MRS_DEPTH             = 1;
    const MRS_CALLER            = "payment_pages";
    const MRS_ENTITY_TYPE       = "payment_pages";
    const MRS_MODERATION_TYPE   = "site";

    /**
     * @var array Along with other necessary key value pairs based on the usecase, pass "checks" as additional
     * parameter as list. values for "checks" could be following.
     * - risk_factor
     * - profanity_check
     */
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

        $response = $this->performNecessaryChecks();

        $this->trace->info(
            TraceCode::APPS_RISK_CHECK_QUEUE_COMPLETED,
            [
                'Response' => $response,
            ]
        );
    }

    protected function performNecessaryChecks(): array
    {
        $responses = [];
        foreach (Arr::get($this->params, 'checks', []) as $check)
        {
            $handler = "handle" . Str::studly($check);
            if (method_exists($this, $handler))
            {
                $responses[$check] = $this->$handler();
            }
        }

        return $responses;
    }

    protected function handleRiskFactor(): array
    {
        $response = (new MerchantRiskClient())->validateRiskFactorForMerchantRequest($this->params);

        $this->validateRiskFactorResponse($response);

        return $response;
    }

    protected function handleProfanityCheck(): array
    {
        $url = $this->getPaymentPageUrl();

        $response = (new MerchantRiskClient())->enqueueProfanityCheckerRequest(
            $this->params['merchant_id'],
            self::MRS_MODERATION_TYPE,
            self::MRS_ENTITY_TYPE,
            $this->params['entity_id'],
            $url,
            self::MRS_DEPTH,
            self::MRS_CALLER
        );

        return $response;
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

    /**
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    private function getPaymentPageUrl(): string
    {
        $pageId     = $this->params['entity_id'];
        $merchantId = $this->params['merchant_id'];

        $pageCore   = new PaymentLink\Core;

        return $pageCore->getRiskCheckUrl($pageId, $merchantId);
    }
}
