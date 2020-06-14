<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Detail\GSTINVerificationStatus;

class GSTINVerifier implements Verifier
{
    use DefaultVerifier;

    protected $dataToVerify;

    public function __construct(array $dataToVerify, array $data)
    {
        $this->dataToVerify = $dataToVerify;

        $this->initData($data);
    }

    protected function isCorrectDetails(): bool
    {
        return (empty($this->data[Constants::LEGAL_NAME] ?? '') === false);
    }

    protected function isDetailsMatch(): bool
    {
        $promoterPanNameMatch = $this->isPromoterPanNameMatch();

        $businessNameMatch = $this->isBusinessNameMatch();

        return (($businessNameMatch === true) and
               ($promoterPanNameMatch === true));
    }

    private function isBusinessNameMatch(): bool
    {
        $matchType = FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH;

        $legalName = $this->data[Constants::LEGAL_NAME] ?? '';

        $gstinFuzzyMatcher = new FuzzyMatcher(GSTINVerificationStatus::GSTIN_VERIFICATION_BUSINESS_NAME_THRESHOLD, $matchType);

        $isMatch = $gstinFuzzyMatcher->isMatch($this->dataToVerify[Constants::COMPANY_NAME], $legalName, $matchPercentage);

        $this->updateVerificationComparisionResult(
            [
                Constants::DOCUMENT_TYPE             => Constants::COMPANY_NAME,
                Constants::DETAILS_FROM_API_RESPONSE => $legalName,
                Constants::DETAILS_FROM_USER         => $this->dataToVerify[Constants::COMPANY_NAME],
                Constants::MATCH_THRESHOLD           => GSTINVerificationStatus::GSTIN_VERIFICATION_BUSINESS_NAME_THRESHOLD,
                Constants::MATCH_PERCENTAGE          => $matchPercentage,
                Constants::SUCCESS                   => ($isMatch === true),
                Constants::MATCH_TYPE                => $matchType,
            ]);

        return $isMatch === true;
    }

    private function isPromoterPanNameMatch(): bool
    {
        $matchType = FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH;

        $gstinFuzzyMatcher = new FuzzyMatcher(GSTINVerificationStatus::GSTIN_VERIFICATION_PROMOTER_PAN_NAME_THRESHOLD, $matchType);

        $members = $this->data[Constants::MEMBERS] ?? [];

        foreach ($members as $member)
        {
            $isMatch = $gstinFuzzyMatcher->isMatch($this->dataToVerify[Constants::PROMOTER_PAN_NAME], $member, $matchPercentage);

            $this->updateVerificationComparisionResult(
                [
                    Constants::DOCUMENT_TYPE             => Constants::PROMOTER_PAN_NAME,
                    Constants::DETAILS_FROM_API_RESPONSE => $member,
                    Constants::DETAILS_FROM_USER         => $this->dataToVerify[Constants::PROMOTER_PAN_NAME],
                    Constants::MATCH_THRESHOLD           => GSTINVerificationStatus::GSTIN_VERIFICATION_PROMOTER_PAN_NAME_THRESHOLD,
                    Constants::MATCH_PERCENTAGE          => $matchPercentage,
                    Constants::SUCCESS                   => ($isMatch === true),
                    Constants::MATCH_TYPE                => $matchType,
                ]);

            if ($isMatch === true)
            {
                return true;
            }
        }

        return false;
    }

    function getIncorrectDetailsStatus()
    {
        return GSTINVerificationStatus::INCORRECT_DETAILS;
    }

    function getFailedStatus()
    {
        return GSTINVerificationStatus::FAILED;
    }

    function getNotMatchedStatus()
    {
        return GSTINVerificationStatus::NOT_MATCHED;
    }

    function getVerifiedStatus()
    {
        return GSTINVerificationStatus::VERIFIED;
    }
}
