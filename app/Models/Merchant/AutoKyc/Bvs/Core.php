<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Models\Base;
use RZP\Models\Merchant\BvsValidation;
use RZP\Models\Merchant\AutoKyc\Response;
use RZP\Models\Merchant\BvsValidation\Entity;

class Core extends Base\Core
{
    /**
     * all BVS Artefact verification should be triggered from this function.
     * this function triggers request to bvs and creates new entry in bvs_validation table if no error
     *
     * @param string $merchantId
     * @param string $artefactType
     * @param array  $input
     */
    public function Verify(string $merchantId, string $artefactType, array $input)
    {
        $input[Constant::OWNER_ID] = $merchantId;

        try
        {
            $processor = (new Factory())->getProcessor($artefactType, $input);

            $response = $processor->Process();

            $validationObject = $this->getValidationObject($merchantId, $artefactType, $response);

            (new BvsValidation\Core())->create($validationObject);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }
    }

    /**
     * this function return payload for creation of Bvs_Validation entity
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
            Entity::OWNER_ID      => $merchantID,
            Entity::OWNER_TYPE    => Constant::MERCHANT,
            Entity::PLATFORM      => Constant::PG,
            Entity::ARTEFACT_TYPE => $artefactType,
        ];
        $validationObject = array_merge($validationObject, $response->getResponseData());

        return $validationObject;
    }
}
