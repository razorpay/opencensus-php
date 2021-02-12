<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier;

use RZP\Models\Merchant\Detail\NeedsClarification\Constants;

/**
 * used for verifying rules and rule_execution_result for GSTIN
 * Any Future verification of rule results can be done in here.
 *
 * Class GstinRuleResultVerifier
 * @package RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier
 */
class GstinRuleResultVerifier extends BaseRuleResultVerifier
{
    public function verifyAndReturnRuleResultForMatched(): array
    {
        $ruleExecutionList = $this->validation->getRuleExecutionList();

        $data[Constants::IS_SIGNATORY_NAME_MATCHED] = true;

        if (isset($ruleExecutionList[1]['rule_execution_result']['remarks']['match_percentage']) === false)
        {
            if (isset($ruleExecutionList[1]['rule_execution_result']) and
                empty($ruleExecutionList[1]['rule_execution_result']['remarks']) === true)
            {
                $data[Constants::IS_SIGNATORY_NAME_MATCHED] = false;
            }

            return $data;
        }

        $matchPercentageForSignatoryRule = $ruleExecutionList[1]['rule_execution_result']['remarks']['match_percentage'];

        if ($matchPercentageForSignatoryRule < self::SIGNATORY_NAME_MATCH_THRESHOLD)
        {
            $data[Constants::IS_SIGNATORY_NAME_MATCHED] = false;
        }

        return $data;
    }
}
