<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use Rzp\Bvs\Validation\V1\Error;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use Rzp\Bvs\Validation\V1\ValidationResponse;

class DefaultProcessorMock extends DefaultProcessor
{
    private $mockStatus;

    const UNITTEST_VALIDATION_ARRAY_CACHE_KEY = 'unittest_bvs_validation_array';

    const UNITTEST_VALIDATION_ARRAY_CACHE_TTL = 15 * 60; // 15 minutes

    public function Process(): Response
    {
        $validationResponse = new ValidationResponse();

        $app = \App::getFacadeRoot();

        if ($app->runningUnitTests() === true)
        {
            // setting it to redis here so that we can assert in tests that the correct values were sent to BvsService
            $app['cache']->put(self::UNITTEST_VALIDATION_ARRAY_CACHE_KEY,
                $this->getCreateValidationArray(),
            self::UNITTEST_VALIDATION_ARRAY_CACHE_TTL);
        }

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

        return new BaseResponse\ValidationBaseResponse($validationResponse);
    }

    public function setMockStatus(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }
}
