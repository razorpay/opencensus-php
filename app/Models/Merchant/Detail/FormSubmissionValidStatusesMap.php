<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Document\OcrVerificationStatus;

class FormSubmissionValidStatusesMap
{
    /**
     * list of documents for which we need to verify status before submission
     */
    const DOCUMENT_LIST_L2 = [
        Constants::POI,
    ];

    const DOCUMENT_VERIFICATION_STATUS_MAP = [
        Constants::POI         => Entity::POI_VERIFICATION_STATUS,
        Constants::POA         => Entity::POA_VERIFICATION_STATUS,
        Constants::GSTIN       => Entity::GSTIN_VERIFICATION_STATUS,
    ];

    /**
     * here registered refer to Registered Business types
     */
    const POI_UNREGISTERED = [POIStatus::VERIFIED];
    const POA              = [OcrVerificationStatus::VERIFIED, OcrVerificationStatus::FAILED, OcrVerificationStatus::NOT_MATCHED, OcrVerificationStatus::INCORRECT_DETAILS];
    const GSTIN            = [GSTINVerificationStatus::FAILED, GSTINVerificationStatus::VERIFIED, GSTINVerificationStatus::NOT_MATCHED, GSTINVerificationStatus::INCORRECT_DETAILS];

    const ALLOWED_VERIFICATION_STATUS_MAP_REGISTERED = [
        Constants::POA   => self::POA,
        Constants::GSTIN => self::GSTIN,
    ];

    const ALLOWED_VERIFICATION_STATUS_MAP_UNREGISTERED = [
        Constants::POI   => self::POI_UNREGISTERED,
        Constants::POA   => self::POA,
        Constants::GSTIN => self::GSTIN,
    ];

    /**
     * checks all documents specified in DOCUMENT_LIST
     *
     * @param Entity $merchantDetails
     *
     * @param array  $documentList
     *
     * @return bool
     */
    public function isDocumentsStatusValidForFormSubmission(Entity $merchantDetails, array $documentList)
    {
        $isSubmissionAllowed = true;

        foreach ($documentList as $document)
        {
            $documentVerificationStatusKey = self::DOCUMENT_VERIFICATION_STATUS_MAP[$document];

            $documentVerificationStatus = $merchantDetails->getAttribute($documentVerificationStatusKey);

            if (self::isFormSubmissionAllowed($merchantDetails, $document, $documentVerificationStatus) === false)
            {
                $isSubmissionAllowed = false;

                break;
            }
        }

        return $isSubmissionAllowed;
    }

    /**
     * idea behind this function: don't allow a merchant to submit form if provided detail is incorrect.
     *
     * if verification status is empty ie null or empty string etc,then allow merchant to submit form
     *
     * @param Entity $merchantDetails
     * @param string $verificationDocument
     * @param string $verificationStatus
     *
     * @return bool
     */
    public function isFormSubmissionAllowed(Entity $merchantDetails, string $verificationDocument, string $verificationStatus = null)
    {
        if (empty($verificationStatus) === true)
        {
            return true;
        }

        $allowedVerificationStatuses = self::ALLOWED_VERIFICATION_STATUS_MAP_REGISTERED;

        if ($merchantDetails->isUnregisteredBusiness())
        {
            $allowedVerificationStatuses = self::ALLOWED_VERIFICATION_STATUS_MAP_UNREGISTERED;
        }

        $allowedStatuses = $allowedVerificationStatuses[$verificationDocument] ?? [];

        if (empty($allowedStatuses) === true)
        {
            return true;
        }

        return in_array($verificationStatus, $allowedStatuses) === true;
    }
}
