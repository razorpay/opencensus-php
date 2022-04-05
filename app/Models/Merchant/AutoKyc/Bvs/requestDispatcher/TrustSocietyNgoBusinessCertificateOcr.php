<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\VerificationDetail as MVD;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Trace\TraceCode;

class TrustSocietyNgoBusinessCertificateOcr extends Base
{
    protected $document;

    protected $type;

    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails, DocumentEntity $document)
    {
        $this->document = $document;

        $this->type = Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE;

        parent::__construct($merchant, $merchantDetails);
    }

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        $isExperimentEnabledForTrustSocietyNgo = (new Merchant\Core)->isRazorxExperimentEnable($this->merchantDetails->getMerchantId(),
            RazorxTreatment::TRUST_SOCIETY_NGO_BVS_VALIDATION);

        $this->app['trace']->info(TraceCode::TRUST_SOCIETY_NGO_EXPERIMENT,[
            "merchantIds"                 => $this->merchantDetails->getMerchantId(),
            "isExpEnable"                 => $isExperimentEnabledForTrustSocietyNgo,
            "businessType"                => $this->merchantDetails->getBusinessType()
        ]);

        return (($this->document->getPublicFileStoreId() !== null) and
            (in_array($this->merchantDetails->getBusinessType(), BusinessType::getTrustSocietyNgoBusinessCertificateApplicableBusinessTypes(), true) === true) and $isExperimentEnabledForTrustSocietyNgo === true);
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
                Constant::BUSINESS_NAME   => $this->merchantDetails->getBusinessName()
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
            MVD\Entity::ARTEFACT_TYPE       => Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE,
            MVD\Entity::ARTEFACT_IDENTIFIER => MVD\Constants::DOC,
            MVD\Entity::STATUS              => BvsValidationConstants::INITIATED
        ];

        (new MVD\Core)->createOrEditVerificationDetail($this->merchantDetails, $input);

        $this->document->setValidationId($entity->getValidationId());
    }
}
