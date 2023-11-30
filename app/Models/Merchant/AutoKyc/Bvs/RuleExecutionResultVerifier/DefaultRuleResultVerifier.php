<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

/**
 * used for verifying rules and rule_execution_result for default
 * Any Future verification of rule results can be done in here.
 *
 * Class DefaultRuleResultVerifier
 * @package RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier
 */
class DefaultRuleResultVerifier extends BaseRuleResultVerifier
{
    public function verifyAndReturnRuleResult($merchant, $validation): array
    {
        $this->app = App::getFacadeRoot();

        $this->app['trace']->info(TraceCode::BASE_STATUS_UPDATER_DOCUMENT_VALIDATION_STATUS, [
            'verifier'          => "DefaultRuleResultVerifier",
            'validationStatus'  => $validation->getValidationStatus()
        ]);

        $data[Constants::IS_ARTEFACT_VALIDATED] = $validation->getValidationStatus() === DetailConstants::SUCCESS;

        return $data;
    }
}
