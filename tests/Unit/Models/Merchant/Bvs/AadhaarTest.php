<?php


namespace Unit\Models\Merchant\Bvs;


use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class AadhaarTest extends TestCase
{
    use DbEntityFetchTrait;

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id' => $mid
        ]);

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'      => $mid,
                'artefact_type' => 'aadhaar',
            ]);

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => $validationStatus,
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());
        $stakeholder = $this->getDbLastEntity('stakeholder');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
        $this->assertEquals($expectedVerificationStatus, $stakeholder->getAadhaarVerificationWithPanStatus());
    }

    public function testAadhaarWithPanStatusUpdaterForSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified');
    }

    public function testAadhaarWithPanStatusUpdaterForFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed');
    }
}
