<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;

use App;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Trace\TraceCode;

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
        $data[Constants::IS_ARTEFACT_VALIDATED] = $validation->getValidationStatus() === DetailConstants::SUCCESS;

        return $data;
    }
}
