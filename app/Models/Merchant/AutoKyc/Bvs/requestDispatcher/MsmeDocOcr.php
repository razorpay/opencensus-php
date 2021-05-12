<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class MsmeDocOcr extends Base
{
    protected const MSME_DOC_INDEX = '1';

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        return (($this->merchantDetails->getBusinessType() === Merchant\Detail\BusinessType::PROPRIETORSHIP) and
                ($this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::MSME_CERTIFICATE) !== null));
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::MSME,
            Constant::CONFIG_NAME     => Constant::MSME_OCR,
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::SIGNATORY_NAME       => $this->merchantDetails->getPromoterPanName() ?? '',
                Constant::TRADE_NAME           => $this->merchantDetails->getBusinessName() ?? ''
            ],
            Constant::PROOFS          => [
                self::MSME_DOC_INDEX => [
                    Constant::UFH_FILE_ID => $this->documentCore->getPublicFileStoreIdForDocumentType($this->merchant, Type::MSME_CERTIFICATE),
                ],
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(): void
    {
        $this->merchantDetails->setMsmeDocVerificationStatus(BvsValidationConstants::INITIATED);
    }
}
