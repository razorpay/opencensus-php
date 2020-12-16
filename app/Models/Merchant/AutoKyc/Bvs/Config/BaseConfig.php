<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\RazorxTreatment;

class BaseConfig implements BvsConfig
{

    protected $enrichment = [];

    protected $rule_v2 = [];

    /**
     *
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getRule()
    {
        assertTrue(empty($this->rule_v2) === false);

        return $this->rule_v2;
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
