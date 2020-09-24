<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Response;

class Core extends Base\Core
{
    /**
     * All BVS Artefact verification should be triggered from this function.
     * This function triggers request to bvs and creates new entry in bvs_validation table if no error.
     * Return null if verification failed because of any reason.
     *
     * @param string $merchantId
     * @param array $input
     * @return BvsValidation\Entity|null
     */
    public function verify(string $merchantId, array $input): ?BvsValidation\Entity
    {
        $this->trace->info(TraceCode::BVS_VERIFICATION_REQUEST, ['input' => $input]);

        $input[Constant::OWNER_ID] = $merchantId;

        $artefactType = $input[Constant::ARTEFACT_TYPE];

        try
        {
            $processor = (new Factory())->getProcessor($artefactType, $input);

            $response = $processor->Process();

            $validationObject = $this->getValidationObject($merchantId, $artefactType, $response);

            return (new BvsValidation\Core())->create($validationObject);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }

        return null;
    }

    /**
     * This function return payload for creation of Bvs_Validation entity
     *
     * @param string   $merchantID
     * @param string   $artefactType
     * @param Response $response
     *
     * @return array
     */
    private function getValidationObject(string $merchantID, string $artefactType, Response $response): array
    {
        $validationObject = [
            BvsValidation\Entity::OWNER_ID      => $merchantID,
            BvsValidation\Entity::OWNER_TYPE    => Constant::MERCHANT,
            BvsValidation\Entity::PLATFORM      => Constant::PG,
            BvsValidation\Entity::ARTEFACT_TYPE => $artefactType,
        ];

        $validationObject = array_merge($validationObject, $response->getResponseData());

        return $validationObject;
    }
}
