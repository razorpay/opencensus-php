<?php


namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier;


interface RuleResultVerifier
{
    public function verifyAndReturnRuleResultForMatched() : array ;
}
