<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class CertificateOfIncorporationOcr extends Base
{
    protected $document;

    protected $type;

    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails, DocumentEntity $document)
    {
        $this->document = $document;

        $this->type = Constant::CERTIFICATE_OF_INCORPORATION;

        parent::__construct($merchant, $merchantDetails);
    }

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        return (($this->document->getPublicFileStoreId() !== null) and
            (in_array($this->merchantDetails->getBusinessType(), BusinessType::getCOIApplicableBusinessTypes(), true) === true));
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        $artefactDetails = Constant::FIELD_ARTEFACT_DETAILS_MAP[$this->type] ?? [];

        $artefactType       = $artefactDetails[Constant::ARTEFACT_TYPE] ?? '';
        $artefactProofIndex = $artefactDetails[Constant::PROOF_INDEX] ?? '1';
        $config             = $artefactDetails[Constant::CONFIG_NAME] ?? '';

        return [
            Constant::ARTEFACT_TYPE   => $artefactType,
            Constant::CONFIG_NAME     => $config,
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::TRADE_NAME   => $this->merchantDetails->getBusinessName()
            ],
            Constant::PROOFS          => [
                $artefactProofIndex => [
                    Constant::UFH_FILE_ID => $this->document->getPublicFileStoreId(),
                ],
            ],
        ];
    }

    public function performPostProcessOperation(BvsValidation\Entity $entity): void
    {
        $input = [
            MVD\Entity::MERCHANT_ID         => $this->merchant->getId(),
            MVD\Entity::ARTEFACT_TYPE       => Constant::CERTIFICATE_OF_INCORPORATION,
            MVD\Entity::ARTEFACT_IDENTIFIER => MVD\Constants::DOC,
            MVD\Entity::STATUS              => BvsValidationConstants::INITIATED
        ];

        (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);

        $this->document->setValidationId($entity->getValidationId());
    }
}
