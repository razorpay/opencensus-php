<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant;
use RZP\Models\Merchant\BvsValidation;
class AadharBackOcr extends Base
{

    protected $document;

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        return true;
    }

    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails, DocumentEntity $document)
    {
        $this->document = $document;
        parent::__construct($merchant, $merchantDetails);
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        $artefactDetails = Constant::FIELD_ARTEFACT_DETAILS_MAP[Type::AADHAR_BACK] ?? [];

        $artefactType       = $artefactDetails[Constant::ARTEFACT_TYPE] ?? '';
        $artefactProofIndex = $artefactDetails[Constant::PROOF_INDEX] ?? '1';

        return [
            Constant::ARTEFACT_TYPE   => $artefactType,
            Constant::CONFIG_NAME     => $this->getConfigName(),
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::NAME => $this->merchantDetails->getPromoterPanName(),
            ],
            Constant::PROOFS          => [
                $artefactProofIndex => [Constant::UFH_FILE_ID => $this->document->getPublicFileStoreId()],
            ],
        ];
    }

    public function performPostProcessOperation(BvsValidation\Entity $bvsValidation): void
    {
        $this->document->setValidationId($bvsValidation->getValidationId());
    }
    protected function getConfigName()
    {
        return Constant::AADHAR_BACK;
    }

    public function getVerificationResponseKey($validation)
    {
        $errorDescription       = $validation->getErrorDescription();

        $verificationResponseKey =   BVSConstants::AADHAAR . BvsValidationConstants::PROOF . $errorDescription;

        return $verificationResponseKey;
    }
}
