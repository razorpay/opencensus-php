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

    /**
     * here registered refer to Registered Business types
     */
    const POI_REGISTERED   = [POIStatus::FAILED, POIStatus::VERIFIED, POIStatus::NOT_MATCHED];
    const POI_UNREGISTERED = [POIStatus::VERIFIED];
    const COMPANY_PAN      = [CompanyPanStatus::FAILED, CompanyPanStatus::VERIFIED, CompanyPanStatus::NOT_MATCHED];
    const POA              = [PoaVerificationStatus::VERIFIED, PoaVerificationStatus::FAILED];
    const GSTIN            = [GSTINVerificationStatus::FAILED, GSTINVerificationStatus::VERIFIED, GSTINVerificationStatus::NOT_MATCHED];

    const ALLOWED_VERIFICATION_STATUS_MAP_REGISTERED = [
        Constants::POI         => self::POI_REGISTERED,
        Constants::COMPANY_PAN => self::COMPANY_PAN,
        Constants::POA         => self::POA,
        Constants::GSTIN       => self::GSTIN,
    ];

    const ALLOWED_VERIFICATION_STATUS_MAP_UNREGISTERED = [
        Constants::POI         => self::POI_UNREGISTERED,
        Constants::COMPANY_PAN => self::COMPANY_PAN,
        Constants::POA         => self::POA,
        Constants::GSTIN       => self::GSTIN,
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

        return in_array($verificationStatus, $allowedStatuses) === true;
    }
}
