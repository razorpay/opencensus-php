<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Models\Merchant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Stakeholder;
use RZP\Trace\TraceCode;

class AadhaarFrontAndBackValidationOcr extends Base
{
    protected $aadhaarFrontDocument;
    protected $aadhaarBackDocument;

    /**
     * @return bool
     */
    public function canTriggerValidation(): bool
    {
        // if both documents do not exist do not send the request to bvs
        if (empty($this->aadhaarFrontDocument) or empty($this->aadhaarBackDocument))
        {
            return false;
        }
        return true;
    }

    public function __construct(Merchant\Entity $merchant, DetailEntity $merchantDetails, DocumentEntity $document)
    {
        parent::__construct($merchant, $merchantDetails);

        $relatedDocumentType = Type::getRelatedDocumentType($document->getDocumentType());
        $merchantId = $merchant->getMerchantId();
        $relatedDocument =  $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentType($merchantId, $relatedDocumentType);
        if ($document->getDocumentType() == Type::AADHAR_FRONT)
        {
            $this->aadhaarFrontDocument = $document;
            $this->aadhaarBackDocument = $relatedDocument;
        }
        else
        {
            $this->aadhaarFrontDocument = $relatedDocument;
            $this->aadhaarBackDocument = $document;
        }
        $this->app['trace']->debug(TraceCode::BVS_JOINT_VALIDATION_REQUEST, [
            'aadhaarFrontDocument' => $this->aadhaarFrontDocument,
            'aadhaarBackDocument' => $this->aadhaarBackDocument
        ]);
    }

    /**
     * @return array
     */
    public function getRequestPayload(): array
    {
        $aadhaarFrontArtefactDetails = Constant::FIELD_ARTEFACT_DETAILS_MAP[Type::AADHAR_FRONT];
        $aadhaarBackArtefactDetails = Constant::FIELD_ARTEFACT_DETAILS_MAP[Type::AADHAR_BACK];
        $artefactFrontProofIndex = $aadhaarFrontArtefactDetails[Constant::PROOF_INDEX] ?? '1';
        $artefactBackProofIndex = $aadhaarBackArtefactDetails[Constant::PROOF_INDEX] ?? '1';

        $this->app['trace']->debug(TraceCode::BVS_JOINT_VALIDATION_REQUEST, [
            '$aadhaarFrontArtefactDetails' => $aadhaarFrontArtefactDetails,
            '$aadhaarBackArtefactDetails' => $aadhaarBackArtefactDetails
        ]);

        return  [
            Constant::ARTEFACT_TYPE   => Constant::AADHAAR,
            Constant::CONFIG_NAME     => $this->getConfigName(),
            Constant::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            Constant::DETAILS         => [
                Constant::NAME => $this->merchantDetails->getPromoterPanName(),
            ],
            Constant::PROOFS          => [
                $artefactFrontProofIndex => [Constant::UFH_FILE_ID => $this->aadhaarFrontDocument->getPublicFileStoreId()],
                $artefactBackProofIndex =>  [Constant::UFH_FILE_ID => $this->aadhaarBackDocument->getPublicFileStoreId()]
            ],
        ];
    }

    public function performPostProcessOperation(BvsValidation\Entity $bvsValidation): void
    {

        $this->aadhaarFrontDocument->setValidationId($bvsValidation->getValidationId());
        $this->aadhaarBackDocument->setValidationId($bvsValidation->getValidationId());

        $this->merchantDetails->setPoaVerificationStatus(null); // set POA as null

        $exists = (new Stakeholder\Core)->checkIfStakeholderExists($this->merchantDetails); // stakeholder changes as well
        if ($exists === true)
        {
            $this->merchantDetails->stakeholder->setPoaStatus(null);
        }

        $this->repo->merchant_detail->saveOrFail($this->merchantDetails);
        $this->repo->merchant_document->saveOrFail($this->aadhaarFrontDocument);
        $this->repo->merchant_document->saveOrFail($this->aadhaarBackDocument);

        $this->app['trace']->debug(TraceCode::BVS_JOINT_VALIDATION_REQUEST, [
            'performPostProcessOperation' => 'records saved successfully.',
            'validation_id' => $bvsValidation->getValidationId()
        ]);
    }

    protected function getConfigName(): string
    {
        return Constant::AADHAAR_FRONT_AND_BACK_OCR;
    }

    public function getVerificationResponseKey($validation)
    {
        $errorDescription       = $validation->getErrorDescription();

        $verificationResponseKey =   BVSConstants::AADHAAR . BvsValidationConstants::PROOF . $errorDescription;

        return $verificationResponseKey;
    }
}
