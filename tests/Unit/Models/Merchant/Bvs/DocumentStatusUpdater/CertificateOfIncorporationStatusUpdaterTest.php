<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;


use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class CertificateOfIncorporationStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createFixtures()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $verificationDetail = $this->fixtures->create('merchant_verification_detail', [
            'merchant_id'          => $mid,
            'artefact_type'        => Constant::CERTIFICATE_OF_INCORPORATION,
            'artefact_identifier'  => 'doc',
        ]);

        $this->fixtures->connection('live')->create('merchant_verification_detail', $verificationDetail->toArray());

        return $mid;
    }

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus)
    {
        $mid=$this->createFixtures();

        $bvsValidation = $this->fixtures->create('bvs_validation',[
                'owner_id'        => $mid,
                'artefact_type'   => Constant::CERTIFICATE_OF_INCORPORATION,
                'validation_unit' => 'proof',
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

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail', 'live');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());

        $this->assertEquals($expectedVerificationStatus, $verificationDetail->getStatus());
    }

    public function testCOIDocVerificationStatusForSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified');
    }

    public function testCOIDocVerificationStatusForFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed');
    }
}
