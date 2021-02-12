<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\RuleResultVerifier;

use RZP\Models\Merchant\BvsValidation\Entity;

abstract class BaseRuleResultVerifier implements RuleResultVerifier
{
    const SIGNATORY_NAME_MATCH_THRESHOLD = 51;

    const COMPANY_NAME_MATCH_THRESHOLD   = 51;

    protected $validation;

    public function __construct(Entity $validation)
    {
        $this->validation = $validation;
    }

}
