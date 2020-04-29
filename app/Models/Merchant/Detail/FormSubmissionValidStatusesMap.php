<?php

namespace RZP\Models\Merchant\Detail;

class FormSubmissionValidStatusesMap
{
    /**
     * list of documents for which we need to verify status before submission
     */
    const DOCUMENT_LIST_L2 = [
        Constants::POI,
        Constants::COMPANY_PAN,
        Constants::GSTIN,
    ];
    /**
     * list of documents for which we need to verify status before submission
     */
    const DOCUMENT_LIST_FOR_L1 = [
        Constants::POI,
        Constants::COMPANY_PAN,
    ];

    const DOCUMENT_VERIFICATION_STATUS_MAP = [
        Constants::POI         => Entity::POI_VERIFICATION_STATUS,
        Constants::POA         => Entity::POA_VERIFICATION_STATUS,
        Constants::COMPANY_PAN => Entity::COMPANY_PAN_VERIFICATION_STATUS,
        Constants::GSTIN       => Entity::GSTIN_VERIFICATION_STATUS,
    ];

    const ALLOWED_VERIFICATION_STATUS_MAP = [
        Constants::POI         => [POIStatus::FAILED, POIStatus::VERIFIED, POIStatus::NOT_MATCHED],
        Constants::COMPANY_PAN => [CompanyPanStatus::FAILED, CompanyPanStatus::VERIFIED, CompanyPanStatus::NOT_MATCHED],
        Constants::POA         => [PoaVerificationStatus::VERIFIED, PoaVerificationStatus::FAILED],
        Constants::GSTIN       => [GSTINVerificationStatus::FAILED, GSTINVerificationStatus::VERIFIED, GSTINVerificationStatus::NOT_MATCHED],
    ];

    /**
     * checks all documents specified in DOCUMENT_LIST
     *
     * @param Entity $merchantDetail
     *
     * @param array  $documentList
     *
     * @return bool
     */
    public function isDocumentsStatusValidForFormSubmission(Entity $merchantDetail, array $documentList)
    {
        $isSubmissionAllowed = true;

        foreach ($documentList as $document)
        {
            $documentVerificationStatusKey = self::DOCUMENT_VERIFICATION_STATUS_MAP[$document];

            $documentVerificationStatus = $merchantDetail->getAttribute($documentVerificationStatusKey);

            if (self::isFormSubmissionAllowed($document, $documentVerificationStatus) === false)
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
     * @param string $verificationDocument
     * @param string $verificationStatus
     *
     * @return bool
     */
    public function isFormSubmissionAllowed(string $verificationDocument, string $verificationStatus = null)
    {
        if (empty($verificationStatus) === true)
        {
            return true;
        }

        $allowedStatuses = self::ALLOWED_VERIFICATION_STATUS_MAP[$verificationDocument] ?? [];

        return in_array($verificationStatus, $allowedStatuses) === true;
    }
}
