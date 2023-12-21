<?php


namespace Functional\Helpers;

use Google\Protobuf\Struct;
use Platform\Bvs\Credencecheck\V1\CreateResponse;
use Platform\Bvs\Credencecheck\V1\GetDetailsByAccountIdResponse;
use Platform\Bvs\Credencecheck\V1\GetDetailsByIdResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsCredenceCheckClient;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\CredenceCheckBaseResponse;

trait BvsTrait
{
    private function processBvsResponseAndValidate(array $bvsResponse, string $validationId)
    {
        $this->processBvsResponse($bvsResponse);

        $this->validateBvsResponse($validationId, $bvsResponse['data']);
    }

    private function getBvsResponse(string $validationId,
                                    string $status = 'success',
                                    string $errorCode = '',
                                    string $errorDesc = '')
    {
        $bvsResponse['data'] = [
            'validation_id'     => $validationId,
            'status'            => $status,
            'error_code'        => $errorCode,
            'error_description' => $errorDesc,
        ];

        return $bvsResponse;
    }

    private function bvsValidation(Entity $bvsValidation,
                                   array $expectedValues = [])
    {
        //
        // resetting time based data
        //
        unset($expectedValues['created_at']);
        unset($expectedValues['updated_at']);

        foreach ($expectedValues as $key => $value)
        {
            $this->assertEquals($value, $bvsValidation->getAttribute($key));
        }
    }

    /**
     * @param string $validationId
     * @param $data
     */
    private function validateBvsResponse(string $validationId, $data): void
    {
        $bvsValidationPostResponse = $this->getDbEntity('bvs_validation',
            ['validation_id' => $validationId]);

        $expectedValues = $data;
        $expectedValues['validation_status'] = $expectedValues['status'];

        //
        // Unsetting status because field is different in bvs kafka response and bvs validation entity
        //
        unset($expectedValues['status']);

        $this->bvsValidation($bvsValidationPostResponse, $expectedValues);
    }

    /**
     * @param array $bvsResponse
     */
    private function processBvsResponse(array $bvsResponse): void
    {
        (new KafkaMessageProcessor())->process('api-bvs-validation-result-events',
            $bvsResponse, 'test');
    }

    public function mockCreateCredenceCheck($merchantId, $status, $type): \PHPUnit\Framework\MockObject\MockObject
    {
        $response = new CreateResponse();
        $getAccountDetailResponse = new GetDetailsByAccountIdResponse();

        $details = new Struct();

        if ($type === Constants::VKYC)
        {
            $details->weblink = "https://capture.kyc.idfy.com/captures?t=6QSH24fkYekx";
            $details->weblink_expiry = "1703358232";
            $details->created_by = "rzptest@razorpay.com";
        }

        $response->setId('100000Razorpay');
        $response->setStatus($status);
        $response->setDetails($details);

        $mock = $this->getMockBuilder(BvsCredenceCheckClient::class)
            ->onlyMethods(['getCredenceCheckDetailsByAccountID', 'createCredenceCheck'])
            ->getMock();

        $mock->method('getCredenceCheckDetailsByAccountID')
            ->willReturn(new CredenceCheckBaseResponse($getAccountDetailResponse));

        $mock->method('createCredenceCheck')
            ->willReturn($response);

        $this->app->instance('bvs_credence_check_manager', $mock);

        return $mock;

    }
}
