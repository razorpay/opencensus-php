<?php


namespace Unit\Models\Merchant\Bvs\RequestDispatcher;


use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\VerificationDetail\Entity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\GSTCertificateOcr;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\PartnershipDeedOcr;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\ShopEstablishmentDocOcr;

class PartnershipDeedDocTest extends TestCase
{
    use DbEntityFetchTrait;

    protected $dispatcherClass = PartnershipDeedOcr::class;

    const PERSONAL_PAN_NAME = 'personal pan';
    const BUSINESS_PAN_NAME = 'business pan';

    const MVD_ENTITY            = 'merchant_verification_detail';

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

    public function testCanTriggerValidationForPartnershipExperimentOn()
    {
        $merchantDetail = $this->getMerchantDetailFixture(BusinessType::getIndexFromKey(BusinessType::PARTNERSHIP));
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(function($mid, $feature, $mode) {
                return 'on';
            }));

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'business_proof_url',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

        $canTriggerValidation = $dispatcher->canTriggerValidation();

        $this->assertTrue($canTriggerValidation);
    }

    public function testRequestPayload()
    {
        $merchantDetail = $this->getMerchantDetailFixture(BusinessType::getIndexFromKey(BusinessType::PARTNERSHIP));

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'business_proof_url',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->assertTrue(isset($requestPayload['details']));
        $this->assertTrue(isset($requestPayload['details'][Constant::BUSINESS_NAME]));
        $this->assertTrue(isset($requestPayload['details'][Constant::NAME_OF_PARTNERS]));
        $this->assertEquals( [self::PERSONAL_PAN_NAME],$requestPayload['details'][Constant::NAME_OF_PARTNERS]);
        $this->assertEquals( self::BUSINESS_PAN_NAME,$requestPayload['details'][Constant::BUSINESS_NAME]);

        $this->assertTrue(isset($requestPayload[Constant::ARTEFACT_TYPE]));
        $this->assertEquals(Constant::PARTNERSHIP_DEED, $requestPayload[Constant::ARTEFACT_TYPE]);
        $this->assertEquals(Constant::PARTNERSHIP_DEED_OCR, $requestPayload[Constant::CONFIG_NAME]);
        $this->assertEquals('proof', $requestPayload[Constant::VALIDATION_UNIT]);
    }

    public function testPostProcessOperation()
    {
        $merchantDetail = $this->getMerchantDetailFixture(BusinessType::getIndexFromKey(BusinessType::PARTNERSHIP));

        $mid = $merchantDetail->getId();

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'business_proof_url',
            'file_store_id' => '123123',
            'merchant_id'   => $mid,
        ]);

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'        => $mid,
                'artefact_type'   => Constant::PARTNERSHIP_DEED,
                'validation_unit' => 'proof'
            ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

        $dispatcher->performPostProcessOperation($bvsValidation);

        $verificationDetail = $this->getDbLastEntity(self::MVD_ENTITY);

        $this->assertEquals(Constant::PARTNERSHIP_DEED, $verificationDetail->getAttribute(Entity::ARTEFACT_TYPE));

        $this->assertEquals('doc', $verificationDetail->getAttribute(Entity::ARTEFACT_IDENTIFIER));

        $this->assertEquals($mid, $verificationDetail->getAttribute(Entity::MERCHANT_ID));
    }

    public function testMultipleUploads()
    {
        $merchantDetail = $this->getMerchantDetailFixture(BusinessType::getIndexFromKey(BusinessType::PARTNERSHIP));

        $mid = $merchantDetail->getId();

        $document = $this->fixtures->create('merchant_document', [
            'document_type' => 'business_proof_url',
            'file_store_id' => '123123',
            'merchant_id'   => $mid,
        ]);

        for ($i = 0; $i < 4; $i++)
        {
            $bvsValidation = $this->fixtures->create('bvs_validation',
                [
                    'owner_id' => $mid,
                    'artefact_type' => Constant::PARTNERSHIP_DEED,
                    'validation_unit' => 'proof'
                ]);

            $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail, $document);

            $dispatcher->performPostProcessOperation($bvsValidation);
        }

        $numOfEntries = sizeof($this->getDbEntities(self::MVD_ENTITY, [ 'merchant_id' => $mid ])->toArray());

        $this->assertEquals(1, $numOfEntries);
    }
}
