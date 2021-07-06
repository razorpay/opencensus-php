<?php


namespace Unit\Models\Merchant\Bvs\DocumentStatusUpdater;


use RZP\Constants\Entity;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\AutoKyc\Bvs\Config\Aadhaar;
use RZP\Models\Merchant\Document;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\BvsValidation;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\AutoKyc\Bvs;
class AadharBackStatusUpdaterTest extends TestCase
{
    use DbEntityFetchTrait;

    private function processKafkaEvent($validationStatus)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $bvsValidation     = $this->fixtures->create('bvs_validation',
                                                     [
                                                         'owner_id'      => $mid,
                                                         'artefact_type' => Bvs\Constant::AADHAAR,
                                                         \RZP\Models\Merchant\BvsValidation\Entity::VALIDATION_UNIT => 'proof'
                                                     ]);
        $merchant_document = $this->fixtures->create('merchant_document',
                                                     [
                                                         'merchant_id'   => $mid,
                                                         'validation_id' => $bvsValidation->getValidationId(),
                                                         'document_type' => Document\Type::AADHAR_BACK,
                                                         'file_store_id' => '123123',
                                                     ]);

        $kafkaEventPayload = [
            'data' => [
                'validation_id'     => $bvsValidation->getValidationId(),
                'status'            => $validationStatus,
                'error_description' => '',
                'error_code'        => ''
            ]
        ];

        $oldMerchantDetail = $this->getDbLastEntity('merchant_detail');
        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $bvsValidation     = $this->getDbEntityById('bvs_validation', $bvsValidation->getValidationId());
        $newMerchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($validationStatus, $bvsValidation->getValidationStatus());
        if(is_null($oldMerchantDetail->getPoaVerificationStatus())===true)
            $this->assertNull($newMerchantDetail->getPoaVerificationStatus());
        else $this->assertEquals($oldMerchantDetail->getPoaVerificationStatus(), $newMerchantDetail->getPoaVerificationStatus());
    }

    public function testAadharBackDocVerificationStatusForSuccessEvent()
    {
        $this->processKafkaEvent('success');
    }

    public function testAadharBackDocVerificationStatusForFailureEvent()
    {
        $this->processKafkaEvent('failed');
    }
}
