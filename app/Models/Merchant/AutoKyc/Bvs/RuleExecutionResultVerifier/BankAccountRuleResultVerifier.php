<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;

use RZP\Models\Merchant\Detail\NeedsClarification\Constants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

/**
 * used for verifying rules and rule_execution_result for Bank Account
 * Any Future verification of rule results can be done in here.
 *
 * Class BankAccountRuleResultVerifier
 * @package RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier
 */

class BankAccountRuleResultVerifier extends BaseRuleResultVerifier
{
    public function verifyAndReturnRuleResult($merchant, $validation): array
    {
        $isArtefactsSignatoryVerificationEnabled = $this->isArtefactsSignatoryVerificationExperimentEnabled($merchant->getId());

        $data[Constants::IS_ARTEFACT_VALIDATED] = $validation->getValidationStatus() === DetailConstants::SUCCESS;

        if ($isArtefactsSignatoryVerificationEnabled === false)
        {
            return $data;
        }

        $data[Constants::IS_SIGNATORY_VALIDATED] = $validation->getValidationStatus() === DetailConstants::SUCCESS;

        return $data;
    }
}
