<?php

namespace Unit\Models\Merchant\Bvs\RequestDispatcher;

use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\AadhaarFrontAndBackValidationOcr;
use RZP\Tests\Functional\TestCase;

class AadhaarFrontAndBackVerificationTest extends TestCase
{
    protected $dispatcherClass = AadhaarFrontAndBackValidationOcr::class;

    protected function getMerchantDetailFixture($businessType, $extraAttributes = [])
    {
        $merchantAttributes = [
            'business_type'     => $businessType,
        ];

        $merchantAttributes = array_merge($merchantAttributes, $extraAttributes);

        return $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);
    }

    public function testCanTriggerValidationInvalidOnlyFront()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_FRONT,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $merchant_document);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertFalse($canTriggerValidation);
    }

    public function testCanTriggerValidationInvalidOnlyBack()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_BACK,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail,$merchant_document);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertFalse($canTriggerValidation);
    }

    public function testCanTriggerValidationValid()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document_front = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_FRONT,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $merchant_document_back = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_BACK,
            'file_store_id' => '123124',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $merchant_document_front);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertTrue($canTriggerValidation);
    }

    public function testRequestPayloadWithFront()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document_front = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_FRONT,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $merchant_document_back = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_BACK,
            'file_store_id' => '123124',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $merchant_document_front);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->assertTrue(isset($requestPayload['details']));
        $this->assertTrue(isset($requestPayload['details'][Constant::NAME]));

        $this->assertTrue(isset($requestPayload[Constant::ARTEFACT_TYPE]));
        $this->assertEquals(Constant::AADHAAR, $requestPayload[Constant::ARTEFACT_TYPE]);
        $this->assertEquals(Constant::AADHAAR_FRONT_AND_BACK_OCR, $requestPayload[Constant::CONFIG_NAME]);
        $this->assertEquals('proof', $requestPayload[Constant::VALIDATION_UNIT]);
    }

    public function testRequestPayloadWithBack()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document_front = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_FRONT,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $merchant_document_back = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_BACK,
            'file_store_id' => '123124',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $merchant_document_front);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->assertTrue(isset($requestPayload['details']));
        $this->assertTrue(isset($requestPayload['details'][Constant::NAME]));

        $this->assertTrue(isset($requestPayload[Constant::ARTEFACT_TYPE]));
        $this->assertEquals(Constant::AADHAAR, $requestPayload[Constant::ARTEFACT_TYPE]);
        $this->assertEquals(Constant::AADHAAR_FRONT_AND_BACK_OCR, $requestPayload[Constant::CONFIG_NAME]);
        $this->assertEquals('proof', $requestPayload[Constant::VALIDATION_UNIT]);

    }
}
