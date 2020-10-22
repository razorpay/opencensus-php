<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\RazorxTreatment;

class BaseConfig implements BvsConfig
{

    protected $enrichment = [];

    protected $rule_v1 = [];

    protected $rule_v2 = [];

    /**
     * @param string $ownerId
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getRule(string $ownerId)
    {
        $isNewVersionBvsRuleDefExperimentEnable = (new Core())->isRazorxExperimentEnable(
            $ownerId,
            RazorxTreatment::BVS_RULE_NEW_VERSION);

        if (($isNewVersionBvsRuleDefExperimentEnable === true) and (empty($this->rule_v2) === false))
        {
            return $this->rule_v2;
        }

        assertTrue(empty($this->rule_v1) === false);

        return $this->rule_v1;
    }

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getEnrichment()
    {
        assertTrue(empty($this->enrichment) === false);

        return $this->enrichment;
    }
}
