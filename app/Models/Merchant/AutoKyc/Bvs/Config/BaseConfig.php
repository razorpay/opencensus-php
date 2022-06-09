<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\RazorxTreatment;

class BaseConfig implements BvsConfig
{

    protected $enrichmentDetails = [];

    protected $enrichment = [];

    protected $rule_v2 = [];

    protected $fetchDetailsRule = [];

    protected $input;

    public function __construct(array $input = [])
    {
        $this->input = $input;
    }

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

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getEnrichmentV2()
    {
        assertTrue(empty($this->enrichment_v2) === false);

        return $this->enrichment_v2;
    }

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getEnrichmentDetails()
    {
        assertTrue(empty($this->enrichmentDetails) === false);

        return $this->enrichmentDetails;
    }

    /**
     * @throws \RZP\Exception\AssertionException
     */
    public function getFetchDetailsRule(): array
    {
        assertTrue(empty($this->fetchDetailsRule) === false);

        return $this->fetchDetailsRule;
    }
}
