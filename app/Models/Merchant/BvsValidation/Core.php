<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc\Bvs;

class Core extends Base\Core
{
    /**
     * this function is called to process the response pushed to kafka queue by BVS,
     * here we update the bvs_validation entity status based on the response
     *
     * @param array $validationObject
     *
     * @throws \Throwable
     */
    public function Process(array $validationObject)
    {
        (new Validator())->validateInput('process_kafka_message', $validationObject);

        $validation = $this->repo->bvs_validation->findByPublicId($validationObject[Entity::VALIDATION_ID]);

        $validation->edit($validationObject);

        $merchantId = $validation->getOwnerId();

        [$merchant, $merchantDetails] = (New Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $this->UpdateDocumentVerificationStatus($validation, $merchantDetails);

        $this->repo->bvs_validation->saveOrFail($validation);
    }

    /**
     * @param Entity        $validation
     * @param Detail\Entity $merchantDetails
     */
    private function UpdateDocumentVerificationStatus(Entity $validation, Detail\Entity $merchantDetails)
    {
        switch ($validation->getArtefactType())
        {
            case Bvs\Constant::PERSONAL_PAN :
                $this->PersonalPanVerify($validation, $merchantDetails);
        }
    }

    /**
     * @param Entity        $validation
     * @param Detail\Entity $merchantDetails
     *
     * this function is monitor how bvs is verifying the documents
     * the verification metrics will be published.
     */
    private function PersonalPanVerify(Entity $validation, Detail\Entity $merchantDetails)
    {
        $panBvsToKycVerificationResult = Constants::MATCH;

        if (($validation->getStatus() === Constants::SUCCESS) and
            ($merchantDetails->getPoiVerificationStatus() != Detail\POIStatus::VERIFIED))
        {
            $panBvsToKycVerificationResult = Constants::MISMATCH;
        }

        if (($merchantDetails->getPoiVerificationStatus() === Detail\POIStatus::NOT_MATCHED) and
            ($validation->getErrorCode() != Constants::BVS_RULE_EXECUTION_ERROR))
        {
            $panBvsToKycVerificationResult = Constants::MISMATCH;
        }

        if ((($merchantDetails->getPoiVerificationStatus() === Detail\POIStatus::FAILED) or
             ($merchantDetails->getPoiVerificationStatus() === Detail\POIStatus::INCORRECT_DETAILS)) and
            ($validation->getStatus() != Constants::FAILED))
        {
            $panBvsToKycVerificationResult = Constants::MISMATCH;
        }

        $bvsPoiVerificationMetrics = [
            Constants::BVS_KYC_VERIFICATION_RESULT      => $panBvsToKycVerificationResult,
            Detail\Constants::POI_STATUS                => $merchantDetails->getPoiVerificationStatus(),
            Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $validation->getStatus()
        ];
        $this->trace->count(Detail\Metric::BVS_VALIDATION_STATUS_TOTAL, $bvsPoiVerificationMetrics);
    }

    /**
     * @param array $input
     *
     * @throws \RZP\Exception\LogicException
     */
    public function create(array $input)
    {
        $this->trace->info(TraceCode::BVS_REQUEST_CREATE_VALIDATION, $input);

        $validation = new Entity();

        $validation->build($input);

        $this->repo->bvs_validation->saveOrFail($validation);
    }
}
