<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

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

        $merchantId = $validation->getOwnerId();

        $validation->edit($validationObj);

        $this->mutex->acquireAndRelease(
            $merchantId,
            function() use ($validation, $merchantId) {

                $this->repo->transactionOnLiveAndTest(
                    function() use ($validation, $merchantId) {

                        $this->repo->bvs_validation->saveOrFail($validation);

                        $this->updateValidationStatusForMerchant(
                            $merchantId,
                            $validation->getArtefactType(),
                            $validation->getValidationId());
                    });
            });
    }

    /**
     * @param array $input
     *
     * @return Entity
     * @throws \RZP\Exception\LogicException
     */
    public function create(array $input): Entity
    {
        $this->trace->info(TraceCode::BVS_CREATE_VALIDATION_PAYLOAD, $input);

        $validation = new Entity();

        $validation->build($input);

        $this->repo->bvs_validation->saveOrFail($validation);

        return $validation;
    }

    /**
     * convert payload consumed form kafka queue to bvs_validation object
     *
     * @param array $payload
     *
     * @return array
     */
    public function getValidationObject(array $payload): array
    {
        return [
            Entity::VALIDATION_ID     => $payload[Constants::VALIDATION_ID],
            Entity::VALIDATION_STATUS => $payload[Constants::STATUS],
            Entity::ERROR_CODE        => $payload[Constants::ERROR_CODE] ?? null,
            Entity::ERROR_DESCRIPTION => $payload[Constants::ERROR_DESCRIPTION] ?? null,
        ];
    }

    /**
     * Updates Document validation status for merchant
     * (multiple artefact can belongs to same document type for example for poa document type
     * artefact can be aadhaar, passport, voterId)
     *
     * @param string $merchantId
     * @param string $artefactType
     * @param string $validationId
     *
     * @throws \RZP\Exception\LogicException
     */
    protected function updateValidationStatusForMerchant(string $merchantId, string $artefactType, string $validationId): void
    {
        [$merchant, $merchantDetails] = (New Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        $statusUpdateFactory = new DocumentStatusUpdater\Factory();

        $statusUpdater = $statusUpdateFactory->getInstance($merchant, $artefactType, $validationId);

        $statusUpdater->updateValidationStatus();

        if ($merchantDetails->isSubmitted() === true)
        {
            $statusUpdater->updateMerchantContext();
        }

        $this->repo->saveOrFail($merchantDetails);
    }
}
