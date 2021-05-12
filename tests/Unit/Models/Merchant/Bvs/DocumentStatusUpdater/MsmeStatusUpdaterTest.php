<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;


use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class MsmeStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'      => $mid,
                'artefact_type' => 'msme',
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
        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
        $this->assertEquals($expectedVerificationStatus, $merchantDetail->getMsmeDocVerificationStatus());
    }

    public function testMsmeDocVerificationStatusForSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified');
    }

    public function testMsmeDocVerificationStatusForFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed');
    }
}
