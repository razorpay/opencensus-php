<?php

namespace RZP\Models\Merchant\AutoKyc\Verifiers;

use RZP\Models\Merchant\Detail\Constants;

trait DefaultVerifier
{
    /**
     * @var array
     */
    protected $data;

    protected $internalErrorCode;

    protected $isSuccessResponse;

    protected $documentType;

    /**
     * @var array
     */
    protected $verificationComparisons = [];

    /**
     * @var array
     */
    protected $verificationData;

    abstract function getIncorrectDetailsStatus();

    abstract function getFailedStatus();

    abstract function getNotMatchedStatus();

    abstract function getVerifiedStatus();

    abstract function isCorrectDetails(): bool;

    abstract function isDetailsMatch(): bool;

    protected function initData(array $data)
    {
        $this->data              = $data;
        $this->documentType      = $this->data[Constants::DOCUMENT_TYPE] ?? '';
        $this->isSuccessResponse = $this->data[Constants::SUCCESS] ?? false;
        $this->internalErrorCode = $this->data[Constants::INTERNAL_ERROR_CODE] ?? '';
    }

    /**
     * Populates data related to verification , this data can be pushed to data lake
     *
     * @param string $verificationStatus
     */
    protected function populateVerificationData(string $verificationStatus)
    {
        $this->verificationData = [
            Constants::DOCUMENT_TYPE       => $this->documentType,
            Constants::API_STATUS_CODE     => $this->data[Constants::STATUS_CODE] ?? '',
            Constants::API_CALL_SUCCESSFUL => $this->isSuccessResponse,
            Constants::API_ERROR_CODE      => $this->internalErrorCode,
            Constants::VERIFIED            => ($verificationStatus === Constants::VERIFIED),
            Constants::VERIFICATION_STATUS => $verificationStatus,
            Constants::COMPARISION         => $this->verificationComparisons,
        ];
    }

    public function verify(): string
    {
        $verificationStatus = ($this->isSuccessResponse === true) ? $this->getStatusForSuccess() : $this->getStatusForFailure();

        $this->populateVerificationData($verificationStatus);

        return $verificationStatus;
    }

    /**
     * @return array
     */
    public function getVerificationData(): array
    {
        return $this->verificationData;
    }

    protected function updateVerificationComparisionResult(array $input)
    {
        $this->verificationComparisons[$input[Constants::DOCUMENT_TYPE]][] = [
            Constants::DETAILS_FROM_API_RESPONSE => $input[Constants::DETAILS_FROM_API_RESPONSE],
            Constants::DETAILS_FROM_USER         => $input[Constants::DETAILS_FROM_USER],
            Constants::MATCH_THRESHOLD           => $input[Constants::MATCH_THRESHOLD],
            Constants::MATCH_PERCENTAGE          => $input[Constants::MATCH_PERCENTAGE],
            Constants::SUCCESS                   => ($input[Constants::SUCCESS] === true),
            Constants::MATCH_TYPE                => $input[Constants::MATCH_TYPE],
        ];
    }

    protected function getStatusForSuccess()
    {
        if ($this->isCorrectDetails() === false)
        {
            return $this->getIncorrectDetailsStatus();
        }

        if ($this->isDetailsMatch() === false)
        {
            return $this->getNotMatchedStatus();
        }

        return $this->getVerifiedStatus();
    }

    protected function getStatusForFailure()
    {
        switch ($this->internalErrorCode)
        {
            case  Constants::VALIDATION_ERROR :
            case  Constants::NO_DATA_FOUND:
            case  Constants::BAD_REQUEST:

                return $this->getIncorrectDetailsStatus();

                break;

            case  Constants::UNAUTHORIZED:

                return $this->getFailedStatus();
                break;

            default :
                return $this->getFailedStatus();
        }
    }
}
