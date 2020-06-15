<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;

trait DefaultMatcher
{

    /**
     * @param        $verifiedField
     * @param        $fieldToVerify
     * @param string $fieldName
     * @param float  $matchThreshold
     * @param string $matchType
     *
     * @return bool
     * @throws \RZP\Exception\BadRequestException
     */
    public function isMatch($verifiedField, $fieldToVerify, string $fieldName, float $matchThreshold, string $matchType)
    {
        $fuzzyMatcher = new FuzzyMatcher($matchThreshold, $matchType);

        $isMatch = $fuzzyMatcher->isMatch($fieldToVerify, $verifiedField, $matchPercentage);

        $this->updateVerificationComparisionResult(
            [
                Constants::DOCUMENT_TYPE             => $fieldName,
                Constants::DETAILS_FROM_API_RESPONSE => $verifiedField,
                Constants::DETAILS_FROM_USER         => $fieldToVerify,
                Constants::MATCH_THRESHOLD           => $matchThreshold,
                Constants::MATCH_PERCENTAGE          => $matchPercentage,
                Constants::SUCCESS                   => ($isMatch === true),
                Constants::MATCH_TYPE                => $matchType
            ]);

        return $isMatch === true;
    }

}
