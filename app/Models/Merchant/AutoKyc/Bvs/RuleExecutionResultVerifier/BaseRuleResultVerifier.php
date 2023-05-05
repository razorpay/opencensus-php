<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\BvsValidation\Entity;

abstract class BaseRuleResultVerifier implements RuleExecutionResultVerifier
{
    const SIGNATORY_NAME_MATCH_THRESHOLD = 51;

    const COMPANY_NAME_MATCH_THRESHOLD   = 51;

    protected $validation;

    public function __construct(Entity $validation)
    {
        $this->validation = $validation;
    }


    public function isArtefactsSignatoryVerificationExperimentEnabled($merchantId): bool
    {
        $this->app = App::getFacadeRoot();

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.artefacts_signatory_validations_experiment_id'),
        ];

        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'properties' => $properties
            ]);

        }

        $variant = $response['response']['variant']['name'] ?? '';

        $this->app['trace']->info(TraceCode::ARTEFACTS_SIGNATORY_EXPERIMENT_REQUEST, [
            'id' => $merchantId,
            'experiment_id' => $this->app['config']->get('app.artefacts_signatory_validations_experiment_id'),
            'result' => $variant
        ]);

        $isArtefactsSignatoryEnabled = ($variant === 'true');

        return $isArtefactsSignatoryEnabled;
    }
}
