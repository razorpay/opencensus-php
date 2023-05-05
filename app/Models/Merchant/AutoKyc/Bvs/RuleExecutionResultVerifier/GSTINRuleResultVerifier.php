<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;

use App;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants;

/**
 * used for verifying rules and rule_execution_result for LLPIN
 * Any Future verification of rule results can be done in here.
 *
 * Class GSTINRuleResultVerifier
 * @package RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier
 */
class GSTINRuleResultVerifier extends BaseRuleResultVerifier
{
    public function verifyAndReturnRuleResult($merchant, $validation): array
    {
        $this->app = App::getFacadeRoot();

        $isArtefactsSignatoryVerificationEnabled = $this->isArtefactsSignatoryVerificationExperimentEnabled($merchant->getId());

        if ($isArtefactsSignatoryVerificationEnabled === false)
        {
            $data[Constants::IS_ARTEFACT_VALIDATED] = $validation->getValidationStatus() === DetailConstants::SUCCESS;

            return $data;
        }

        $ruleExecutionList = $validation->getRuleExecutionList();

        $ruleExecutionList = $ruleExecutionList['details'] ?? $ruleExecutionList;

        if (isset($ruleExecutionList[0]['rule_execution_result']['result']) === false or
            isset($ruleExecutionList[1]['rule_execution_result']['result']) === false)
        {
            $data[Constants::IS_ARTEFACT_VALIDATED] = $validation->getValidationStatus() === DetailConstants::SUCCESS;

            return $data;
        }

        $data[Constants::IS_ARTEFACT_VALIDATED]       = $ruleExecutionList[0]['rule_execution_result']['result'];

        $data[Constants::IS_SIGNATORY_VALIDATED]      = $ruleExecutionList[1]['rule_execution_result']['result'];

        return $data;
    }
}
