<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;


use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;

class BusinessPanStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $bvsValidation = $this->fixtures->create('bvs_validation',
                                                 [
                                                     'owner_id'      => $mid,
                                                     'artefact_type' => Constant::BUSINESS_PAN,
                                                 ]);

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => $validationStatus,
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());
        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
        $this->assertEquals($expectedVerificationStatus, $merchantDetail->getCompanyPanVerificationStatus());
    }

    public function testSuccessEvent()
    {
        $this->processKafkaEvent('success', 'verified');
    }

    public function testFailureEvent()
    {
        $this->processKafkaEvent('failed', 'failed');
    }
}
