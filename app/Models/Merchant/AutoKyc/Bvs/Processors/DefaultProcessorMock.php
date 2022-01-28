<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Processors;

use Rzp\Bvs\Validation\V1\Error;
use RZP\Exception\LogicException;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\AutoKyc\Response;
use Rzp\Bvs\Validation\V2\ValidationResponse as ValidationResponseV2;
use Rzp\Bvs\Validation\V1\ValidationResponse as ValidationResponseV1;
use RZP\Models\Merchant\BvsValidation\Entity as BvsValidationEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationBaseResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationBaseResponseV2;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\ValidationDetailsResponse;

class DefaultProcessorMock extends DefaultProcessor
{
    private $mockStatus;

    private $mockValidationDetail;

    const UNITTEST_VALIDATION_ARRAY_CACHE_KEY = 'unittest_bvs_validation_array';

    const UNITTEST_VALIDATION_ARRAY_CACHE_TTL = 15 * 60; // 15 minutes

    /**
     * @param array  $input
     * @param        $configName
     *
     * @param        $merchant
     *
     * @throws LogicException
     */
    public function __construct(array $input, $configName, $merchant)
    {
        parent::__construct($input, $configName, $merchant);

        //
        // This config is not defined in application config , this is used in test case only
        //

        $this->setMockStatus($this->app['config']['services.bvs.response'] ?? Constant::SUCCESS);

        $this->setMockValidationDetail($this->app['config']['services.bvs.validationDetail'] ?? []);
    }

    public function Process(): Response
    {

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

                $bvsValidation = new BvsValidationEntity();

                $bvsValidation->generateId();

                if ($this->requestMode() == Constant::SYNC)
                {
                    $validationResponse = new ValidationResponseV2();

                    $validationResponse->setValidationId($bvsValidation->getId());

                    $validationResponse->setStatus('success');

                    return new ValidationBaseResponseV2($validationResponse);
                }
                else
                {
                    $validationResponse = new ValidationResponseV1();

                    $validationResponse->setValidationId($bvsValidation->getId());

                    $validationResponse->setStatus('captured');

                    return new ValidationBaseResponse($validationResponse);
                }
                break;

            case Constant::FAILURE:

                $error = new Error();

                if ($this->requestMode() == Constant::SYNC)
                {
                    $validationResponse = new ValidationResponseV2();

                    $validationResponse->setErrorCode("BAD_REQUEST_VALIDATION_ERROR");

                    $validationResponse->setErrorDescription("merchant type is not supported");

                    return new ValidationBaseResponseV2($validationResponse);
                }
                else
                {
                    $validationResponse = new ValidationResponseV1();

                    $validationResponse->setErrorCode("BAD_REQUEST_VALIDATION_ERROR");

                    $validationResponse->setErrorDescription("merchant type is not supported");

                    return new ValidationBaseResponse($validationResponse);
                }
                break;

            default:

                throw new IntegrationException("integration error: failed to make request");
        }

    }

    public function FetchDetails(string $validationId): Response
    {
        $status = $this->mockStatus ?? 'success';

        $data = [
            'validation_id'      => $validationId,
            'status'             => $status,
            'enrichment_details' => get_Protobuf_Struct([
                                                            'online_provider' => [
                                                                'details' => [
                                                                    'account_holder_names' => [
                                                                        [
                                                                            'score' => 0,
                                                                            'value' => 'name 1'
                                                                        ],
                                                                        [
                                                                            'score' => 0,
                                                                            'value' => 'name 2'
                                                                        ]
                                                                    ],
                                                                    'account_status'       => [
                                                                        'value' => 'active',
                                                                    ]
                                                                ]
                                                            ]
                                                        ])
        ];

        $data = array_merge($data, $this->mockValidationDetail);

        $validationResponse = new ValidationResponseV1($data);

        return new ValidationDetailsResponse($validationResponse);
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
