<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Config;

interface BvsConfig
{
    /**
     * @param string $ownerId
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getRule(string $ownerId);

    /**
     * @return array
     * @throws \RZP\Exception\AssertionException
     */
    public function getEnrichment();
}
