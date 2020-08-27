<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Document\OcrVerificationStatus;

class POAVerifier implements Verifier
{
    use DefaultVerifier;

    /**
     * @var string
     */
    protected $panOwnerName;

    /**
     * @var string
     */
    protected $ocrName;

    public function __construct(string $panOwnerName, array $data)
    {
        $this->initData($data);

        $this->panOwnerName = $panOwnerName;
        $this->ocrName      = $data[Constants::NAME] ?? '';
    }

    protected function isDetailsMatch(): bool
    {
        if ($this->isCorrectDetails() === false)
        {
            return false;
        }

        $documentType = $this->data[Constants::DOCUMENT_TYPE] ?? '';

        $matchType = FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH;

        $poaFuzzyMatcher = new FuzzyMatcher(OcrVerificationStatus::OCR_VERIFICATION_THRESHOLD, FuzzyMatcher::TOKEN_OR_TOKEN_SET_MATCH);

        $isOcrMatch = $poaFuzzyMatcher->isMatch($this->panOwnerName, $this->ocrName, $matchPercent);

        $this->updateVerificationComparisionResult(
            [
                Constants::DOCUMENT_TYPE             => $documentType,
                Constants::DETAILS_FROM_API_RESPONSE => $this->ocrName,
                Constants::DETAILS_FROM_USER         => $this->panOwnerName,
                Constants::MATCH_THRESHOLD           => OcrVerificationStatus::OCR_VERIFICATION_THRESHOLD,
                Constants::MATCH_PERCENTAGE          => $matchPercent,
                Constants::SUCCESS                   => ($isOcrMatch === true),
                Constants::MATCH_TYPE                => $matchType,
            ]);

        return ($isOcrMatch === true);
    }

    protected function isCorrectDetails(): bool
    {
        return empty($this->ocrName) === false;
    }

    function getIncorrectDetailsStatus()
    {
        return OcrVerificationStatus::INCORRECT_DETAILS;
    }

    function getFailedStatus()
    {
        return OcrVerificationStatus::FAILED;
    }

    function getNotMatchedStatus()
    {
        return OcrVerificationStatus::NOT_MATCHED;
    }

    function getVerifiedStatus()
    {
        return OcrVerificationStatus::VERIFIED;
    }
}
