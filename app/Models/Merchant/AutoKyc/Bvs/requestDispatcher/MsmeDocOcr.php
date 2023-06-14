<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Document\Type;

class MsmeDocOcr extends Base
{
    protected const MSME_DOC_INDEX = '1';
    protected $document;
    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails, DocumentEntity $document)
    {
        $this->document = $document;
        parent::__construct($merchant, $merchantDetails);
    }
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
                Constant::SIGNATORY_NAME => $this->merchantDetails->getPromoterPanName() ?? '',
                Constant::TRADE_NAME     => $this->merchantDetails->getBusinessName() ?? ''
            ],
            Constant::PROOFS          => [
                self::MSME_DOC_INDEX => [
                    Constant::UFH_FILE_ID => $this->document->getPublicFileStoreId(),
                ],
            ],
        ];

        return $payload;
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $this->merchantDetails->setMsmeDocVerificationStatus(BvsValidationConstants::INITIATED);

        $input = [
            MVD\Entity::MERCHANT_ID         => $this->merchant->getId(),
            MVD\Entity::ARTEFACT_TYPE       => Constant::MSME,
            MVD\Entity::ARTEFACT_IDENTIFIER => MVD\Constants::DOC,
            MVD\Entity::STATUS              => BvsValidationConstants::INITIATED,
            MVD\Entity::METADATA            => [
                'signatory_validation_status' => BvsValidationConstants::INITIATED,
                'bvs_validation_id'           => $entity->getValidationId()]
        ];

        (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);
    }
}
