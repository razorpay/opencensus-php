<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;


use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class DocumentStatusUpdaterForBASTest extends TestCase
{
    use DbEntityFetchTrait;

    private function processKafkaEvent($validationStatus)
    {
        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'      => '35dfe4t5wdsf4',
                'artefact_type' => Constant::PERSONAL_PAN,
                'owner_type'    => Constant::BAS_DOCUMENT
            ]);

        $kafkaEventPayload = [
            'data' => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => $validationStatus,
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        $cacheKey = 'bvs_validation_custom_process_validation_' . $bvsValidation->getValidationId();

        $this->app['cache']->put($cacheKey, 'UpdateValidationStatusForBAS');

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
    }

    public function testSuccessEvent()
    {
        $this->processKafkaEvent('success');
    }

    public function testFailureEvent()
    {
        $this->processKafkaEvent('failed');
    }
}
