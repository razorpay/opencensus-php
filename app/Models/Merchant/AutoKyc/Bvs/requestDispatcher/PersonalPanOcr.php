<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Stakeholder;

class PersonalPanOcr extends Base
{
    protected const PERSONAL_PAN_DOC_INDEX = '1';

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        return (($this->merchantDetails->getBusinessType() === Merchant\Detail\BusinessType::PROPRIETORSHIP) and
                ($this->merchantDetails->getPersonalPanDocVerificationStatus() === BvsValidationConstants::PENDING) and
                ($this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::PERSONAL_PAN) !== null));
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::PERSONAL_PAN,
            Constant::CONFIG_NAME     => Constant::PERSONAL_PAN_OCR,
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::PAN_NUMBER => $this->merchantDetails->getPromoterPan(),
                Constant::NAME       => $this->merchantDetails->getPromoterPanName(),
            ],
            Constant::PROOFS          => [
                self::PERSONAL_PAN_DOC_INDEX => [
                    Constant::UFH_FILE_ID => $this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::PERSONAL_PAN),
                ],
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $this->merchantDetails->setPersonalPanDocVerificationStatus(BvsValidationConstants::INITIATED);

        (new Stakeholder\Core)->createOrFetchStakeholder($this->merchantDetails);
        $this->merchantDetails->stakeholder->setPanDocStatus(BvsValidationConstants::INITIATED);
    }
}
