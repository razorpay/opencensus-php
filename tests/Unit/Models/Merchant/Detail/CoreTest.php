<?php


namespace Unit\Models\Merchant\Detail;


use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use \RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields;
use RZP\Services\MerchantRiskClient;
use \RZP\Tests\Functional\TestCase;

class CoreTest extends TestCase
{
    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->exactly(2))
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    public function testValidationFieldsIfAadhaarEsignVerificationIsDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
            'aadhaar_esign_status'                 => 'verified'
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertFalse(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarEsignVerificationIsNotDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarIsNotLinked()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'         => $mid,
            'aadhaar_linked'      => 0
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));

    }
}
