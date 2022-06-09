<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

interface BvsConfig
{
    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getRule();

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getEnrichment();

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */

    public function getEnrichmentV2();

    /**
     * @return array
     */
    public function getEnrichmentDetails();

    /**
     * @return array
     */

    public function getFetchDetailsRule(): array;
}
