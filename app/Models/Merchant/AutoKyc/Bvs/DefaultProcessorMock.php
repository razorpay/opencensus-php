<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use Rzp\Bvs\Validation\V1\Error;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use Rzp\Bvs\Validation\V1\ValidationResponse;

class DefaultProcessorMock extends DefaultProcessor
{
    private $mockStatus;

    public function Process(): Response
    {
        $validationResponse = new ValidationResponse();

        switch ($this->mockStatus)
        {
            case Constant::SUCCESS:

                $validationResponse->setValidationId('ValidationId12');

                $validationResponse->setStatus('captured');

                break;

            case Constant::FAILURE:

                $error = new Error();

                $error->setCode("BAD_REQUEST_VALIDATION_ERROR");

                $error->setDescription("merchant type is not supported");

                $validationResponse->setError($error);

                break;

            default:

                throw new IntegrationException("integration error: failed to make request");
        }

        return new ValidationBaseResponse($validationResponse);
    }

    public function setMockStatus(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }
}
