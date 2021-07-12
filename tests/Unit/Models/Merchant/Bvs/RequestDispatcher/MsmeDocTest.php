<?php


namespace Unit\Models\Merchant\Bvs\RequestDispatcher;


use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\MsmeDocOcr;
use RZP\Tests\Functional\TestCase;

class MsmeDocTest extends TestCase
{
    protected $dispatcherClass = MsmeDocOcr::class;

    const PERSONAL_PAN_NAME = "kitty su personal pan";
    const BUSINESS_PAN_NAME = "kitty su business pan";

    protected function getMerchantDetailFixture($businessType, $extraAttributes = [])
    {
        $merchantAttributes = [
            'business_type'     => $businessType,
            'promoter_pan_name' => self::PERSONAL_PAN_NAME,
            'business_name'     => self::BUSINESS_PAN_NAME,
        ];

        $merchantAttributes = array_merge($merchantAttributes, $extraAttributes);

        return $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);
    }

    public function testCanTriggerValidationForUnregistered()
    {
        $merchantDetail = $this->getMerchantDetailFixture(11);
        $merchant_document=$this->fixtures->create('merchant_document', [
            'document_type' => 'msme_certificate',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail,$merchant_document);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertFalse($canTriggerValidation);
    }

    public function testCanTriggerValidationForProprietorship()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document=$this->fixtures->create('merchant_document', [
            'document_type' => 'msme_certificate',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail,$merchant_document);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertTrue($canTriggerValidation);
    }

    public function testRequestPayload()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document=$this->fixtures->create('merchant_document', [
            'document_type' => 'msme_certificate',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail,$merchant_document);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->assertTrue(isset($requestPayload['details']));
        $this->assertTrue(isset($requestPayload['details'][Constant::SIGNATORY_NAME]));
        $this->assertTrue(isset($requestPayload['details'][Constant::TRADE_NAME]));

        $this->assertTrue(isset($requestPayload[Constant::ARTEFACT_TYPE]));
        $this->assertEquals(Constant::MSME, $requestPayload[Constant::ARTEFACT_TYPE]);
        $this->assertEquals(Constant::MSME_OCR, $requestPayload[Constant::CONFIG_NAME]);
        $this->assertEquals('proof', $requestPayload[Constant::VALIDATION_UNIT]);
    }
}
