<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Document\Type;

class CompanyPanOcr extends Base
{
    protected const BUSINESS_PAN_DOC_INDEX = '1';

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        $businessType = $this->merchantDetails->getBusinessType();

        return (($businessType !== Detail\BusinessType::PROPRIETORSHIP) and
                (Detail\BusinessType::isUnregisteredBusiness($businessType) === false) and
                ($this->merchantDetails->getCompanyPanDocVerificationStatus() === BvsValidationConstants::PENDING) and
                ($this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::BUSINESS_PAN_URL) !== null));
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::BUSINESS_PAN,
            Constant::CONFIG_NAME     => Constant::BUSINESS_PAN_OCR,
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::PAN_NUMBER => $this->merchantDetails->getPan(),
                Constant::NAME       => $this->merchantDetails->getBusinessName(),
            ],
            Constant::PROOFS          => [
                self::BUSINESS_PAN_DOC_INDEX => [
                    Constant::UFH_FILE_ID => $this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::BUSINESS_PAN_URL),
                ],
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $this->merchantDetails->setCompanyPanDocVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
