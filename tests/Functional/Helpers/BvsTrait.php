<?php


namespace Functional\Helpers;


use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\BvsValidation\Entity;

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
}
