<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Detail\GSTINVerificationStatus;

class GSTINVerifier implements Verifier
{
    protected $data;

    protected $dataToVerify;

    protected $matchedMember;

    protected $internalErrorCode;

    protected $isSuccessResponse;

    public function __construct(array $dataToVerify, array $data)
    {
        $this->data               = $data;
        $this->dataToVerify       = $dataToVerify;

        $this->internalErrorCode  = $this->data[Constants::INTERNAL_ERROR_CODE] ?? '';
        $this->isSuccessResponse  = $this->data[Constants::SUCCESS] ?? false;
    }

    public function verify()
    {
        if ($this->isSuccessResponse === true)
        {
            return $this->getStatusForSuccess();
        }

        return $this->getStatusForFailure();
    }

    private function getStatusForFailure()
    {
        switch ($this->internalErrorCode)
        {
            case  Constants::VALIDATION_ERROR :
            case  Constants::NO_DATA_FOUND:
            case  Constants::BAD_REQUEST:
                return GSTINVerificationStatus::INCORRECT_DETAILS;

                break;

            case  Constants::UNAUTHORIZED:

                return GSTINVerificationStatus::FAILED;
                break;

            default :
                return GSTINVerificationStatus::FAILED;
        }
    }

    private function getStatusForSuccess()
    {
        if ($this->isCorrectDetails() === false)
        {
            return GSTINVerificationStatus::INCORRECT_DETAILS;
        }

        if ($this->isDetailsMatch() === false)
        {
            return GSTINVerificationStatus::NOT_MATCHED;
        }

        return GSTINVerificationStatus::VERIFIED;
    }

    private function isCorrectDetails(): bool
    {
        return (empty($this->data[Constants::LEGAL_NAME] ?? '') === false);
    }

    private function isDetailsMatch(): bool
    {
        $promoterPanNameMatch = $this->isPromoterPanNameMatch();

        $businessNameMatch = $this->isBusinessNameMatch();

        return ($businessNameMatch === true) and
               ($promoterPanNameMatch === true);
    }

    protected function isBusinessNameMatch()
    {
        $gstinFuzzyMatcher = new FuzzyMatcher(GSTINVerificationStatus::GSTIN_VERIFICATION_BUSINESS_NAME_THRESHOLD, FuzzyMatcher::SIMPLE_MATCH);

        return $gstinFuzzyMatcher->isMatch($this->dataToVerify[Constants::COMPANY_NAME], $this->data[Constants::LEGAL_NAME] ?? '') === true;
    }

    protected function isPromoterPanNameMatch()
    {
        $gstinFuzzyMatcher = new FuzzyMatcher(GSTINVerificationStatus::GSTIN_VERIFICATION_PROMOTER_PAN_NAME_THRESHOLD, FuzzyMatcher::SIMPLE_MATCH);

        $members = $this->data[Constants::MEMBERS] ?? '';

        foreach ($members as $member)
        {
            if ($gstinFuzzyMatcher->isMatch($this->dataToVerify[Constants::PROMOTER_PAN_NAME], $member) === true)
            {
                $this->matchedMember = $member;

                return true;
            }
        }
        return false;
    }
}
