<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Detail\GSTINVerificationStatus;

class GSTINVerifier implements Verifier
{
    use DefaultVerifier;

    use DefaultMatcher;

    protected $dataToVerify;

    public function __construct(array $dataToVerify, array $data)
    {
        $this->dataToVerify = $dataToVerify;

        $this->initData($data);
    }

    protected function isCorrectDetails(): bool
    {
        return (empty($this->data[Constants::LEGAL_NAME]) === false);
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
        return (($this->isTradeNameMatch() === true) or
                ($this->isLegalNameMatch() === true));
    }

    private function isLegalNameMatch(): bool
    {
        $legalName = $this->data[Constants::LEGAL_NAME] ?? '';

        $promoterPanName = $this->dataToVerify[Constants::PROMOTER_PAN_NAME] ?? '';

        return $this->isMatch($legalName,
                              $promoterPanName,
                              Constants::PROMOTER_PAN_NAME,
                              GSTINVerificationStatus::GSTIN_VERIFICATION_BUSINESS_NAME_THRESHOLD,
                              FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH);
    }


    private function isTradeNameMatch(): bool
    {
        $tradeName = $this->data[Constants::TRADE_NAME] ?? '';

        $companyName = $this->dataToVerify[Constants::COMPANY_NAME] ?? '';

        return $this->isMatch($tradeName,
                              $companyName,
                              Constants::COMPANY_NAME,
                              GSTINVerificationStatus::GSTIN_VERIFICATION_BUSINESS_NAME_THRESHOLD,
                              FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH);
    }

    private function isPromoterPanNameMatch(): bool
    {
        $members = $this->data[Constants::MEMBERS] ?? [];

        $promoterPanName = $this->dataToVerify[Constants::PROMOTER_PAN_NAME] ?? '';

        foreach ($members as $member)
        {
            $isMatch = $this->isMatch($member,
                                      $promoterPanName,
                                      Constants::PROMOTER_PAN_NAME,
                                      GSTINVerificationStatus::GSTIN_VERIFICATION_BUSINESS_NAME_THRESHOLD,
                                      FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH);
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
