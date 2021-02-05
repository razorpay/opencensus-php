<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Merchant;
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

        $response = (new MerchantRiskClient())->getMerchantRiskScores($this->params['client_type'],
                                                                      $this->params['entity_id'],
                                                                      $this->params['fields']);
        $this->validateRiskFactorResponse($response);
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
