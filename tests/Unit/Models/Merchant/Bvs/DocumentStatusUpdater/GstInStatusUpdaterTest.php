<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;

use Config;
use RZP\Models\Feature\Core;
use RZP\Models\Feature\Entity;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class GstInStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createFixtures(){
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $featureParams = [
            Entity::ENTITY_ID   => $mid,
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => Constants::NO_DOC_ONBOARDING,
        ];

        (new Core())->create($featureParams,true);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        return $mid;
    }
    private function processKafkaEvent($validationStatus, $expectedVerificationStatus)
    {
        $mid=$this->createFixtures();
        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'        => $mid,
                'artefact_type'   => Constant::GSTIN,
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

        $verificationDetail = $this->getDbLastEntity('merchant_detail', 'live');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());

        $this->assertEquals($expectedVerificationStatus, $verificationDetail->getGstinVerificationStatus());
    }

    public function testGSTINStatusForSuccessEventForNoDocFeature()
    {
        $this->processKafkaEvent('success', 'verified');
    }

    public function testGSTINStatusForFailureEventForNoDocFeature()
    {
        $this->processKafkaEvent('failed', 'failed');
    }
}
