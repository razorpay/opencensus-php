<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

class BaseConfig implements BvsConfig
{

    protected $enrichment = [];

    protected $rule = [];

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getRule()
    {
        assertTrue(empty($this->rule) === false);

        return $this->rule;
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
