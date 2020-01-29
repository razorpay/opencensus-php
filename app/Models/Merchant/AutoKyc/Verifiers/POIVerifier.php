<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\Models\Merchant\Detail\Constants;
use RZP\Models\Merchant\Detail\POIStatus;

class POIVerifier implements Verifier
{
    /**
     * @var string
     */
    protected $panOwnerName;

    protected $data;

    protected $nameFromNSDL;

    protected $internalErrorCode;

    protected $isSuccessResponse;

    public function __construct(string $panOwnerName, array $data)
    {
        $this->panOwnerName      = $panOwnerName;
        $this->data              = $data;
        $this->nameFromNSDL      = $this->data[Constants::PAN_NAME_FROM_NSDL] ?? null;
        $this->internalErrorCode = $this->data[Constants::INTERNAL_ERROR_CODE] ?? '';
        $this->isSuccessResponse = $this->data[Constants::SUCCESS] ?? false;
    }

    public function verify()
    {
        if($this->isSuccessResponse === true)
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

                return POIStatus::INCORRECT_DETAILS;

                break;

            case  Constants::UNAUTHORIZED:

                return POIStatus::FAILED;
                break;

            default :
                return POIStatus::FAILED;
        }
    }

    private function getStatusForSuccess()
    {
        if ($this->isCorrectDetails() === false)
        {
            return POIStatus::INCORRECT_DETAILS;
        }

        if ($this->isNameMatch() === false)
        {
            return POIStatus::NOT_MATCHED;
        }

        return POIStatus::VERIFIED;
    }

    private function isCorrectDetails(): bool
    {
        return empty($this->nameFromNSDL) === false;
    }

    private function isNameMatch(): bool
    {
        if ($this->isCorrectDetails() === false)
        {
            return false;
        }

        $nameMathPercent = get_similar_text_percent($this->panOwnerName, $this->nameFromNSDL);

        return ($nameMathPercent >= POIStatus::POI_VERIFICATION_THRESHOLD);
    }
}
