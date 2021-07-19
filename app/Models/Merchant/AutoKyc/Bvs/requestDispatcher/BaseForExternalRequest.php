<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;

use RZP\Exception;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

abstract class BaseForExternalRequest
{

    /**
     * @return array
     */
    public abstract function getRequestPayload(): array;

    /**
     * Used this function for doing post process action
     */
    public function performPostProcessOperation(): array
    {
        // send response back to external service
        return [
            Constant::OWNER_TYPE        => Constant::BAS_DOCUMENT,
            Constant::OWNER_ID          => $this->ownerId,
            Entity::VALIDATION_STATUS   => BvsValidationConstants::INITIATED,
        ];
    }

    /**
     * Triggers bvs validation request
     *
     * @throws Exception\ServerErrorException
     */
    public function triggerBVSRequest(): array
    {
        $payload = $this->getRequestPayload();

        $bvsValidation = (new AutoKyc\Bvs\Core())->verify($this->ownerId, $payload);

        if ($bvsValidation != null)
        {
            return $this->performPostProcessOperation();
        }

        throw new Exception\ServerErrorException(
            'BVS Request Validation Request failed',null, null, null);
    }
}
