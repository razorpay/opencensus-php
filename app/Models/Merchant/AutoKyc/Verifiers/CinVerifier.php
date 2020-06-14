<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Detail\CinVerificationStatus;

class CinVerifier implements Verifier
{
    use DefaultVerifier;

    protected $dataToVerify;

    public function __construct(array $dataToVerify, array $data)
    {
        $this->dataToVerify = $dataToVerify;

        $this->initData($data);
    }

    function isCorrectDetails(): bool
    {
        return (empty($this->data[Constants::COMPANY_NAME] ?? '') === false);
    }

    function isDetailsMatch(): bool
    {
        $isBusinessNameMatched  = $this->isCompanyNameMatches();
        $isDirectoryNameMatched = $this->isSignatoryDetailMatches();

        //
        // Just calculate fuzzy matching logic for address , this does not control cin verification
        //
        $isAddressMatched = $this->isAddressMatch();

        return (($isBusinessNameMatched === true) and ($isDirectoryNameMatched === true));

    }

    private function isCompanyNameMatches(): bool
    {
        $matchType = FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH;

        $companyName = $this->data[Constants::COMPANY_NAME] ?? '';

        $businessNameFuzzyMatcher = new FuzzyMatcher(CinVerificationStatus::CIN_VERIFICATION_THRESHOLD, $matchType);

        $isMatch = $businessNameFuzzyMatcher->isMatch($this->dataToVerify[Constants::COMPANY_NAME], $companyName, $matchPercentage);

        $this->updateVerificationComparisionResult(
            [
                Constants::DOCUMENT_TYPE             => Constants::COMPANY_NAME,
                Constants::DETAILS_FROM_API_RESPONSE => $companyName,
                Constants::DETAILS_FROM_USER         => $this->dataToVerify[Constants::COMPANY_NAME],
                Constants::MATCH_THRESHOLD           => CinVerificationStatus::CIN_VERIFICATION_THRESHOLD,
                Constants::MATCH_PERCENTAGE          => $matchPercentage,
                Constants::SUCCESS                   => ($isMatch === true),
                Constants::MATCH_TYPE                => $matchType,
            ]);

        return $isMatch === true;
    }

    private function isSignatoryDetailMatches(): bool
    {
        $matchType = FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH;

        $fuzzyMatcher = new FuzzyMatcher(CinVerificationStatus::CIN_VERIFICATION_THRESHOLD, $matchType);

        $signatoryDetails = $this->data[Constants::SIGNATORY_DETAILS] ?? [];

        foreach ($signatoryDetails as $signatoryDetail)
        {
            $signatoryDetailName = $signatoryDetail['full_name'] ?? '';

            $isMatch = $fuzzyMatcher->isMatch($this->dataToVerify[Constants::PROMOTER_PAN_NAME], $signatoryDetailName, $matchPercentage);

            $this->updateVerificationComparisionResult(
                [
                    Constants::DOCUMENT_TYPE             => Constants::PROMOTER_PAN_NAME,
                    Constants::DETAILS_FROM_API_RESPONSE => $signatoryDetailName,
                    Constants::DETAILS_FROM_USER         => $this->dataToVerify[Constants::PROMOTER_PAN_NAME],
                    Constants::MATCH_THRESHOLD           => CinVerificationStatus::CIN_VERIFICATION_THRESHOLD,
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

    private function isAddressMatch(): bool
    {
        $matchType = FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH;

        $fuzzyMatcher = new FuzzyMatcher(CinVerificationStatus::CIN_VERIFICATION_THRESHOLD, $matchType);

        $address = $this->data[Constants::ADDRESS] ?? [];

        $isMatch = $fuzzyMatcher->isMatch($this->dataToVerify[Constants::REGISTERED_ADDRESS], $address, $matchPercentage);

        $this->updateVerificationComparisionResult(
            [
                Constants::DOCUMENT_TYPE             => Constants::REGISTERED_ADDRESS,
                Constants::DETAILS_FROM_API_RESPONSE => $address,
                Constants::DETAILS_FROM_USER         => $this->dataToVerify[Constants::REGISTERED_ADDRESS],
                Constants::MATCH_THRESHOLD           => CinVerificationStatus::CIN_VERIFICATION_THRESHOLD,
                Constants::MATCH_PERCENTAGE          => $matchPercentage,
                Constants::SUCCESS                   => ($isMatch === true),
                Constants::MATCH_TYPE                => $matchType,
            ]);

        return $isMatch;
    }


    function getIncorrectDetailsStatus()
    {
        return CinVerificationStatus::INCORRECT_DETAILS;
    }

    function getFailedStatus()
    {
        return CinVerificationStatus::FAILED;
    }

    function getNotMatchedStatus()
    {
        return CinVerificationStatus::NOT_MATCHED;
    }

    function getVerifiedStatus()
    {
        return CinVerificationStatus::VERIFIED;
    }
}
