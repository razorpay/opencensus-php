<?php


namespace Unit\Models\Merchant\Bvs\RequestDispatcher;


use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\VerificationDetail\Entity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\ShopEstablishmentDocOcr;

class ShopEstbDocTest extends TestCase
{
    use DbEntityFetchTrait;

    protected $dispatcherClass = ShopEstablishmentDocOcr::class;

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

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'shop_establishment_certificate',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertFalse($canTriggerValidation);
    }

    public function testCanTriggerValidationForPropertiership()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'shop_establishment_certificate',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertTrue($canTriggerValidation);
    }

    public function testRequestPayload()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'shop_establishment_certificate',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->assertTrue(isset($requestPayload['details']));
        $this->assertTrue(isset($requestPayload['details'][Constant::SHOP_ENTITY_NAME]));
        $this->assertTrue(isset($requestPayload['details'][Constant::SHOP_OWNER_NAME]));

        $this->assertTrue(isset($requestPayload[Constant::ARTEFACT_TYPE]));
        $this->assertEquals(Constant::SHOP_ESTABLISHMENT, $requestPayload[Constant::ARTEFACT_TYPE]);
        $this->assertEquals(Constant::SHOP_ESTABLISHMENT_OCR, $requestPayload[Constant::CONFIG_NAME]);
        $this->assertEquals('proof', $requestPayload[Constant::VALIDATION_UNIT]);
    }

    public function testPostProcessOperation()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $mid = $merchantDetail->getId();

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'shop_establishment_certificate',
            'file_store_id' => '123123',
            'merchant_id'   => $mid,
        ]);

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'        => $mid,
                'artefact_type'   => Constant::SHOP_ESTABLISHMENT,
                'validation_unit' => 'proof'
            ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

        $dispatcher->performPostProcessOperation($bvsValidation);

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail');

        $this->assertEquals(Constant::SHOP_ESTABLISHMENT, $verificationDetail->getAttribute(Entity::ARTEFACT_TYPE));

        $this->assertEquals('doc', $verificationDetail->getAttribute(Entity::ARTEFACT_IDENTIFIER));

        $this->assertEquals($mid, $verificationDetail->getAttribute(Entity::MERCHANT_ID));
    }
}
