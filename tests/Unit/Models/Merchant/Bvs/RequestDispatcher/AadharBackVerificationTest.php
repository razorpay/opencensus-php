<?php


namespace Unit\Models\Merchant\Bvs\RequestDispatcher;

use RZP\Tests\Functional\Helpers;
use Illuminate\Http\UploadedFile;

use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\AadharBackOcr;
use RZP\Tests\Functional\TestCase;
use RZP\Services\RazorXClient;
class AadharBackVerificationTest extends TestCase
{
    protected $dispatcherClass = AadharBackOcr::class;

    protected function getMerchantDetailFixture($businessType, $extraAttributes = [])
    {
        $merchantAttributes = [
            'business_type'     => $businessType,
        ];

        $merchantAttributes = array_merge($merchantAttributes, $extraAttributes);

        return $this->fixtures->create('merchant_detail:valid_fields', $merchantAttributes);
    }
    public function testCanTriggerValidation()
    {
        $merchantDetail = $this->getMerchantDetailFixture(1);

        $merchant_document = $this->fixtures->create('merchant_document', [
            'document_type' => Document\Type::AADHAR_BACK,
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
            'document_type' => Document\Type::AADHAR_BACK,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail->getMerchantId(),
        ]);

        $dispatcher = new $this->dispatcherClass($merchantDetail->merchant, $merchantDetail,$merchant_document);

        $requestPayload = $dispatcher->getRequestPayload();

        $this->assertTrue(isset($requestPayload['details']));
        $this->assertTrue(isset($requestPayload['details'][Constant::NAME]));

        $this->assertTrue(isset($requestPayload[Constant::ARTEFACT_TYPE]));
        $this->assertEquals(Constant::AADHAAR, $requestPayload[Constant::ARTEFACT_TYPE]);
        $this->assertEquals(Constant::AADHAR_BACK, $requestPayload[Constant::CONFIG_NAME]);
        $this->assertEquals('proof', $requestPayload[Constant::VALIDATION_UNIT]);
    }
}
