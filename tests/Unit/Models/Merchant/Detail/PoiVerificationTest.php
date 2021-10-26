<?php


namespace Unit\Models\Merchant\Detail;


use Config;
use Mail;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\BvsValidation\Repository;
use RZP\Tests\Functional\Fixtures\Entity\Merchant;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs;

class PoiVerificationTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createAndFetchMocks($razorXEnabled)
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app['razorx']->method('getTreatment')
            ->willReturn($razorXEnabled ? 'on' : 'off');

        $detailCore = $this->getMockBuilder(Detail\Core::class)
            ->onlyMethods(["canSubmit"])
            ->getMock();
        $detailCore->expects($this->once())->method('canSubmit')->willReturn(true);

        return [$detailCore];
    }

    private function createAndFetchFixtures()
    {
        // create merchant data with only poi verification not done
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            Detail\Entity::PROMOTER_PAN_NAME    => "kitty su",
            Detail\Entity::PROMOTER_PAN         => "AAAPA1234J",
            Detail\Entity::BUSINESS_TYPE        => BusinessType::getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS => 'verified'
        ]);

        return [$merchantDetail];
    }

    public function testPoiVerificationViaBvsIfExpIsEnabled()
    {
        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures();

        $this->app->instance("rzp.mode", Mode::LIVE);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit"=>"1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),'merchant',Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals($bvsValidation->getArtefactType(), Bvs\Constant::PERSONAL_PAN);
        $this->assertEquals($bvsValidation->getValidationStatus(), "captured");
    }
}
