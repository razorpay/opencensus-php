<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use Rzp\Bvs\Validation\V1\Error;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use Rzp\Bvs\Validation\V1\ValidationResponse;
use RZP\Models\Merchant\BvsValidation\Entity as BvsValidationEntity;
class DefaultProcessorMock extends DefaultProcessor
{
    private $mockStatus;

    private $mockValidationDetail;

    const UNITTEST_VALIDATION_ARRAY_CACHE_KEY = 'unittest_bvs_validation_array';

    const UNITTEST_VALIDATION_ARRAY_CACHE_TTL = 15 * 60; // 15 minutes

    public function __construct(array $input, string $configName=null,string $mockStatus=null)
    {
       parent::__construct($input,$configName);
        //
        // This config is not defined in application config , this is used in test case only
        //
        $mockStatus = $mockStatus?? ($this->app['config']['services.bvs.response'] ?? Constant::SUCCESS);

        $mockValidationDetail = $this->app['config']['services.bvs.validationDetail'] ?? [];

        $this->setMockStatus($mockStatus);

        $this->setMockValidationDetail($mockValidationDetail);

    }
    public function Process(): Response
    {
        $validationResponse = new ValidationResponse();

        if ($this->app->runningUnitTests() === true)
        {
            // setting it to redis here so that we can assert in tests that the correct values were sent to BvsService
            $this->app['cache']->put(self::UNITTEST_VALIDATION_ARRAY_CACHE_KEY,
                $this->getCreateValidationArray(),
            self::UNITTEST_VALIDATION_ARRAY_CACHE_TTL);
        }

        switch ($this->mockStatus)
        {
            case Constant::SUCCESS:

                $bvsValidation = new BvsValidationEntity();

                $bvsValidation->generateId();

                $validationResponse->setValidationId($bvsValidation->getId());

                $validationResponse->setStatus('captured');

                break;

            case Constant::FAILURE:

                $error = new Error();

                $validationResponse->setErrorCode("BAD_REQUEST_VALIDATION_ERROR");

                $validationResponse->setErrorDescription("merchant type is not supported");

                break;

            default:

                throw new IntegrationException("integration error: failed to make request");
        }

        return new BaseResponse\ValidationBaseResponse($validationResponse);
    }

    public function FetchDetails(string $validationId): Response
    {
        $status = $this->mockStatus ?? 'success';

        $data = [
            'validation_id' => $validationId,
            'status'        => $status,
            'enrichment_details' => get_Protobuf_Struct([
                'online_provider' => [
                    'details' => [
                        'account_holder_names' => [
                            [
                                'score'  => 0,
                                'value'  => 'name 1'
                            ],
                            [
                                'score'  => 0,
                                'value'  => 'name 2'
                            ]
                        ],
                        'account_status' => [
                            'value' => 'active',
                        ]
                    ]
                ]
            ])
        ];

        $data = array_merge($data, $this->mockValidationDetail);

        $validationResponse = new ValidationResponse($data);

        return new BaseResponse\ValidationDetailsResponse($validationResponse);
    }

    public function setMockStatus(string $mockStatus)
    {
        $this->mockStatus = $mockStatus;
    }

    public function setMockValidationDetail(array $validationDetail)
    {
        $this->mockValidationDetail = $validationDetail;
    }
}
