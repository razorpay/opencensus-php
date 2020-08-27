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
     * @param array $payload
     *
     * @throws \Throwable
     */
    public function Process(array $payload)
    {
        (new Validator())->validateInput('process_kafka_message', $payload);

        $validationObj = $this->getvalidationObject($payload);

        $validation = $this->repo->bvs_validation->findOrFail($validationObj[Entity::VALIDATION_ID]);

        $validation->edit($validationObj);

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

        if (($validation->getValidationStatus() === Constants::SUCCESS) and
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
            ($validation->getValidationStatus() != Constants::FAILED))
        {
            $panBvsToKycVerificationResult = Constants::MISMATCH;
        }

        $bvsPoiVerificationMetrics = [
            Constants::BVS_KYC_VERIFICATION_RESULT      => $panBvsToKycVerificationResult,
            Detail\Constants::POI_STATUS                => $merchantDetails->getPoiVerificationStatus(),
            Constants::BVS_DOCUMENT_VERIFICATION_STATUS => $validation->getValidationStatus()
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

    /**
     * convert payload consumed form kafka queue to bvs_validation object
     *
     * @param array $payload
     *
     * @return array
     */
    public function getValidationObject(array $payload)
    {
        return [
            Entity::VALIDATION_ID     => $payload[Constants::VALIDATION_ID],
            Entity::VALIDATION_STATUS => $payload[Constants::STATUS],
            Entity::ERROR_CODE        => $payload[Constants::ERROR_CODE] ?? null,
            Entity::ERROR_DESCRIPTION => $payload[Constants::ERROR_DESCRIPTION] ?? null,
        ];
    }
}
