<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleExecutionResultVerifier;


interface RuleExecutionResultVerifier
{
    public function verifyAndReturnRuleResult($merchant, $validation) : array ;
}
