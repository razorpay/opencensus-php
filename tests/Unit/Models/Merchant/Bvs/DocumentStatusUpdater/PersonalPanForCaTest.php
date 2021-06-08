<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;


use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PersonalPanForCaTest extends TestCase
{
    use DbEntityFetchTrait;

    private function processKafkaEvent($validationStatus, $expectedVerificationStatus)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $bankingAccount = $this->fixtures->create('banking_account', [
            'id'            => 'randomBaAccId1',
            'account_type'  => 'current',
            'merchant_id'   => $mid,
        ]);

        $bankingAccountId = $bankingAccount->getId();

        $bvsValidation = $this->fixtures->create('bvs_validation',
                                                 [
                                                     'owner_id'      => $bankingAccountId,
                                                     'artefact_type' => Constant::PERSONAL_PAN,
                                                     'owner_type'    => Constant::BANKING_ACCOUNT
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
        $this->assertEquals($expectedVerificationStatus, $merchantDetail->getPoiVerificationStatus());
    }

    //public function testSuccessEvent()
    //{
    //    $this->processKafkaEvent('success', 'verified');
    //}
    //
    //public function testFailureEvent()
    //{
    //    $this->processKafkaEvent('failed', 'failed');
    //}
}
