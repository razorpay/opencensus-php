<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier;

use RZP\Models\Merchant\Detail\NeedsClarification\Constants;

/**
 * used for verifying rules and rule_execution_result for LLPIN
 * Any Future verification of rule results can be done in here.
 *
 * Class LlpinRuleResultVerifier
 * @package RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier
 */
class LlpinRuleResultVerifier extends BaseRuleResultVerifier
{
    public function verifyAndReturnRuleResultForMatched(): array
    {
        $ruleExecutionList = $this->validation->getRuleExecutionList();

        $data[Constants::IS_SIGNATORY_NAME_MATCHED] = true;

        $data[Constants::IS_COMPANY_NAME_MATCHED] = true;

        if (isset($ruleExecutionList[0]['rule_execution_result']['remarks']['match_percentage']) === false
            || isset($ruleExecutionList[1]['rule_execution_result']['remarks']['match_percentage']) === false)
        {
            return $data;
        }

        $matchPercentageForRule0 = $ruleExecutionList[0]['rule_execution_result']['remarks']['match_percentage'];
        $matchPercentageForRule1 = $ruleExecutionList[1]['rule_execution_result']['remarks']['match_percentage'];

        if ($matchPercentageForRule0 < self::COMPANY_NAME_MATCH_THRESHOLD)
        {
            $data[Constants::IS_COMPANY_NAME_MATCHED] = false;
        }

        if ($matchPercentageForRule1 < self::SIGNATORY_NAME_MATCH_THRESHOLD)
        {
            $data[Constants::IS_SIGNATORY_NAME_MATCHED] = false;
        }

        return $data;
    }
}
