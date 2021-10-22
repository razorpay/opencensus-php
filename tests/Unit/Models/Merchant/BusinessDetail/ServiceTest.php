<?php

namespace Unit\Models\Merchant\BusinessDetail;


use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\BusinessDetail\Service;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
class ServiceTest extends TestCase
{
    public function testSaveWebsiteDetailsForMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $saveInput = [
            BusinessDetailEntity::WEBSITE_DETAILS => [
                BusinessDetailConstants::ABOUT => "https://hello.com/en/index.html"
            ]
        ];
        $businessDetailService = new Service();
        $businessDetails       = $businessDetailService->saveBusinessDetailsForMerchant($merchantDetail->getMerchantId(), $saveInput);
        $this->assertNotNull($businessDetails);
        $this->assertEquals($saveInput[BusinessDetailEntity::WEBSITE_DETAILS][BusinessDetailConstants::ABOUT], $businessDetails[BusinessDetailEntity::WEBSITE_DETAILS][BusinessDetailConstants::ABOUT]);
        $businessDetails = $businessDetailService->fetchBusinessDetailsForMerchant($merchantDetail->getMerchantId());
        $this->assertNotNull($businessDetails);
        $this->assertEquals($saveInput[BusinessDetailEntity::WEBSITE_DETAILS][BusinessDetailConstants::ABOUT], $businessDetails[BusinessDetailEntity::WEBSITE_DETAILS][BusinessDetailConstants::ABOUT]);

    }

    public function testInvalidWebsiteDetailsForMerchant()
    {
        $merchantDetail        = $this->fixtures->create('merchant_detail:valid_fields');
        $businessDetailService = new Service();
        $errorInput            = [
            BusinessDetailEntity::WEBSITE_DETAILS => [
                BusinessDetailConstants::ABOUT => "hello"
            ]
        ];
        $this->expectException(BadRequestValidationFailureException::class);
        $businessDetails = $businessDetailService->saveBusinessDetailsForMerchant($merchantDetail->getMerchantId(), $errorInput);

    }

    public function testFetchWebsiteDetailsForMerchant()
    {
        $fetchInput            = [
            BusinessDetailEntity::WEBSITE_DETAILS => [
                BusinessDetailConstants::ABOUT => "https://hello.com/en/index.html"
            ]
        ];
        $merchantBusinessDetail = $this->fixtures->create('merchant_business_detail', $fetchInput);
        $businessDetailService = new Service();
        $response = $businessDetailService->fetchBusinessDetailsForMerchant($merchantBusinessDetail->getMerchantId());
        $this->assertNotNull($response);
        $this->assertEquals($fetchInput[BusinessDetailEntity::WEBSITE_DETAILS][BusinessDetailConstants::ABOUT], $response[BusinessDetailEntity::WEBSITE_DETAILS][BusinessDetailConstants::ABOUT]);


    }
}
