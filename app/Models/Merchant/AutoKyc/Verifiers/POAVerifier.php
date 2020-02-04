<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

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
        $ocrMatchingPercentage = 0;

        if ((empty($this->ocrName) === false) and
            empty($this->panOwnerName) === false)
        {
            $ocrMatchingPercentage = get_similar_text_percent($this->panOwnerName, $this->ocrName);
        }

        return [
            Constants::OCR_MATCHING_PERCENTAGE_WITH_PAN_NAME => $ocrMatchingPercentage,
            Constants::DOCUMENT_VERIFICATION_STATUS          => $this->getOcrVerificationStatus($ocrMatchingPercentage),
        ];
    }

    protected function getOcrVerificationStatus($percent)
    {
        $ocrVerifiedStatus = OcrVerificationStatus::FAILED;

        if ($percent >= OcrVerificationStatus::OCR_VERIFICATION_THRESHOLD)
        {
            $ocrVerifiedStatus = OcrVerificationStatus::VERIFIED;
        }

        return $ocrVerifiedStatus;
    }
}
