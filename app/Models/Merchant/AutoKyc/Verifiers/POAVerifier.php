<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\lib\FuzzyMatcher;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Document\OcrVerificationStatus;

class POAVerifier implements Verifier
{
    /**
     * @var string
     */
    protected $panOwnerName;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $ocrName;

    public function __construct(string $panOwnerName, array $data)
    {
        $this->panOwnerName = $panOwnerName;
        $this->data         = $data;
        $this->ocrName      = $data[Constants::NAME] ?? "";
    }

    public function verify()
    {
        $isOcrMatch = false;

        $matchPercent = null;

        $poaFuzzyMatcher = new FuzzyMatcher(OcrVerificationStatus::OCR_VERIFICATION_THRESHOLD, FuzzyMatcher::JUMBLED_MATCH);

        if ((empty($this->ocrName) === false) and
            empty($this->panOwnerName) === false)
        {
            $isOcrMatch = $poaFuzzyMatcher->isMatch($this->panOwnerName, $this->ocrName, $matchPercent);
        }

        return [
            Constants::OCR_MATCHING_PERCENTAGE_WITH_PAN_NAME => $matchPercent,
            Constants::DOCUMENT_VERIFICATION_STATUS          => $this->getOcrVerificationStatus($isOcrMatch),
            Constants::POA_FUZZY_MATCH_TYPE                  => $poaFuzzyMatcher->getMatchType(),
        ];
    }

    protected function getOcrVerificationStatus(bool $isOcrMatch): string
    {
        $ocrVerifiedStatus = $isOcrMatch ? OcrVerificationStatus::VERIFIED : OcrVerificationStatus::FAILED;

        return $ocrVerifiedStatus;
    }
}
