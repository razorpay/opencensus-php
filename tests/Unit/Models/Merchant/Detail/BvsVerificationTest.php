<?php


namespace Unit\Models\Merchant\Detail;


use Config;
use Mail;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\BvsValidation\Repository;
use RZP\Tests\Functional\Fixtures\Entity\Merchant;
use RZP\Models\Merchant\MerchantActionNotification;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class BvsVerificationTest extends TestCase
{
    use DbEntityFetchTrait;

    private function createAndFetchMocks($razorXEnabled)
    {
        Mail::fake();
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        if ($razorXEnabled)
        {
            $this->app['razorx']->method('getTreatment')
                                ->will($this->returnCallback(
                                    function($mid, $feature, $mode) {
                                        if ($feature === RazorxTreatment::BVS_PENNY_TESTING)
                                        {
                                            return 'on';
                                        }

                                        return 'on';
                                    }));
        }
        else
        {
            $this->app['razorx']->method('getTreatment')
                                ->will($this->returnCallback(
                                    function($mid, $feature, $mode) {
                                        if ($feature === RazorxTreatment::BVS_PENNY_TESTING)
                                        {
                                            return 'on';
                                        }

                                        return 'off';
                                    }));
        }

        $detailCore = $this->getMockBuilder(Detail\Core::class)
                           ->onlyMethods(["canSubmit"])
                           ->getMock();
        $detailCore->expects($this->exactly(2))->method('canSubmit')->willReturn(true);

        return [$detailCore];
    }

    private function createAndFetchFixtures($businessType)
    {

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [

            Detail\Entity::BUSINESS_TYPE => BusinessType::getIndexFromKey($businessType),
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => $merchantId
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        return [$merchantDetail];
    }

    private function createAndFetchFixturesL2($businessType)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [

            Detail\Entity::GSTIN                           => "03AADCB1234M1ZX",
            Detail\Entity::GSTIN_VERIFICATION_STATUS       => "pending",
            Detail\Entity::COMPANY_CIN                     => "U67190TN2014PTC096971",
            Detail\Entity::CIN_VERIFICATION_STATUS         => "pending",
            Detail\Entity::POI_VERIFICATION_STATUS         => "pending",
            Detail\Entity::PROMOTER_PAN                    => "BRRPK8070K",
            Detail\Entity::PROMOTER_PAN_NAME               => "vasanthi kakarla",
            Detail\Entity::BUSINESS_NAME                   => "vasanthi kakarla",
            Detail\Entity::COMPANY_PAN_NAME                => "vasanthi kakarla",
            Detail\Entity::COMPANY_PAN                     => "BRRPK8070K",
            Detail\Entity::COMPANY_PAN_VERIFICATION_STATUS => "pending",
            Detail\Entity::BUSINESS_TYPE                   => BusinessType::getIndexFromKey($businessType),
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        return [$merchantDetail];
    }

    private function createAndFetchFixturesNC($businessType)
    {

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [

            Detail\Entity::BUSINESS_TYPE             => BusinessType::getIndexFromKey($businessType),
            Detail\Entity::ACTIVATION_FORM_MILESTONE => DetailConstants::L2_SUBMISSION,
            Detail\Entity::GSTIN                     => "03AADCB1234M1ZX",
            Detail\Entity::GSTIN_VERIFICATION_STATUS => 'not_matched',
            Detail\Entity::COMPANY_CIN               => "U67190TN2014PTC096972",
            Detail\Entity::CIN_VERIFICATION_STATUS   => 'not_matched',
            'activation_status'                      => 'needs_clarification',
            'submitted'                              => 1,
            'locked'                                 => 0,
            'bank_branch_ifsc'                 => 'HDFC0000930',
            'bank_account_number'              => '1234567890', ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => $merchantId
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        return [$merchantDetail];
    }

    private function createAndFetchFixturesRetry($businessType)
    {

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [

            Detail\Entity::BUSINESS_TYPE             => BusinessType::getIndexFromKey($businessType),
            Detail\Entity::ACTIVATION_FORM_MILESTONE => DetailConstants::L1_SUBMISSION,
            Detail\Entity::GSTIN                     => "03AADCB1234M1ZX",
            Detail\Entity::GSTIN_VERIFICATION_STATUS => 'not_matched',
            Detail\Entity::COMPANY_CIN               => "U67190TN2014PTC096972",
            Detail\Entity::CIN_VERIFICATION_STATUS   => 'not_matched',
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        return [$merchantDetail];
    }

    //gst in
    public function testGstInVerificationViaBvsIfExpIsEnabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getGstinVerificationStatus());

    }

    public function testPOIVerificationViaBvsIfExpIsEnabledOnL2HUF()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::HUF);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals(Bvs\Constant::PERSONAL_PAN, $bvsValidation->getArtefactType());
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getPoiVerificationStatus());

    }
    public function testCompanyPanVerificationViaBvsIfExpIsEnabledOnL2HUF()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::HUF);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals(Bvs\Constant::BUSINESS_PAN, $bvsValidation->getArtefactType());
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCompanyPanVerificationStatus());

    }

    public function testGstInVerificationViaBvsIfExpIsEnabledOnL2HUF()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::HUF);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals(Bvs\Constant::GSTIN, $bvsValidation->getArtefactType());
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getGstinVerificationStatus());

    }

    public function testGstInVerificationViaBvsIfExpIsDisabledOnL2HUF()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::HUF);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals($bvsValidation->getArtefactType(), Bvs\Constant::GSTIN);
        $this->assertEquals($bvsValidation->getValidationStatus(), "captured");

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getGstinVerificationStatus());
    }

    public function testGstInVerificationViaBvsIfExpIsDisabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals($bvsValidation->getValidationStatus(), "captured");

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getGstinVerificationStatus());
    }

    public function testGstInVerificationViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::GSTIN => "01AADCB1234M1ZX"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getGstinVerificationStatus());

    }

    public function testGstInVerificationViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::GSTIN => "01AADCB1234M1ZX"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getGstinVerificationStatus());
    }

    public function testGstInVerificationRetryViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PROPRIETORSHIP);



        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::GSTIN => "01AADCB1234M1ZX"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getGstinVerificationStatus());

    }

    public function testGstInVerificationRetryViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::GSTIN => "01AADCB1234M1ZX"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getGstinVerificationStatus());
    }

    public function testGstInVerificationViaBvsIfExpIsEnabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1", Detail\Entity::GSTIN => "01AADCB1234M1ZX",
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getGstinVerificationStatus());

    }

    public function testGstInVerificationViaBvsIfExpIsDisabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1", Detail\Entity::GSTIN => "01AADCB1234M1ZX",
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::GSTIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getGstinVerificationStatus());

    }

    //cin
    public function testCInVerificationViaBvsIfExpIsEnabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testCInVerificationViaBvsIfExpIsDisabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());


        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());
    }

    public function testCInVerificationViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testCInVerificationViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());
    }

    public function testCInVerificationRetryViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testCInVerificationRetryViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());
    }

    public function testCInVerificationViaBvsIfExpIsEnabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1", Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971",
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testCInVerificationViaBvsIfExpIsDisabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1", Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971",
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::CIN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());

    }

    //llp
    public function testLLPInVerificationViaBvsIfExpIsEnabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::LLP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testLLPInVerificationViaBvsIfExpIsDisabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::LLP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());
    }

    public function testLLPInVerificationViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::LLP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testLLPInVerificationViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::LLP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());
    }

    public function testLLPInVerificationRetryViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::LLP);
        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testLLPInVerificationRetryViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::LLP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());
    }

    public function testLLPInVerificationViaBvsIfExpIsEnabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::LLP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1", Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971",
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCinVerificationStatus());

    }

    public function testLLPInVerificationViaBvsIfExpIsDisabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::LLP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1", Detail\Entity::COMPANY_CIN => "U67190TN2014PTC096971",
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(), 'merchant', Bvs\Constant::LLP_DEED);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCinVerificationStatus());

    }


    //personal pan
    public function testPersonalPanVerificationViaBvsIfExpIsEnabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getPoiVerificationStatus());

    }

    public function testPersonalPanVerificationViaBvsIfExpIsDisabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getPoiVerificationStatus());
    }

    public function testPersonalPanVerificationViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::PROMOTER_PAN      => "BRRPK8070K",
                                          Detail\Entity::PROMOTER_PAN_NAME => "vasanthi kakarla"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getPoiVerificationStatus());

    }

    public function testPersonalPanVerificationViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::PROMOTER_PAN      => "BRRPK8070K",
                                          Detail\Entity::PROMOTER_PAN_NAME => "vasanthi kakarla"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getPoiVerificationStatus());
    }

    public function testPersonalPanVerificationRetryViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::PROMOTER_PAN      => "BRRPK8070K",
                                          Detail\Entity::PROMOTER_PAN_NAME => "vasanthi kakarla"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getPoiVerificationStatus());

    }

    public function testPersonalPanVerificationRetryViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([
                                             Detail\Entity::PROMOTER_PAN      => "BRRPK8070K",
                                             Detail\Entity::PROMOTER_PAN_NAME => "vasanthi kakarla"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getPoiVerificationStatus());
    }

    public function testPersonalPanVerificationViaBvsIfExpIsEnabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit"                         => "1",
                                          Detail\Entity::PROMOTER_PAN      => "BRRPK8070K",
                                          Detail\Entity::PROMOTER_PAN_NAME => "vasanthi kakarla"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getPoiVerificationStatus());

    }

    public function testPersonalPanVerificationViaBvsIfExpIsDisabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PROPRIETORSHIP);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit"                         => "1",
                                          Detail\Entity::PROMOTER_PAN      => "BRRPK8070K",
                                          Detail\Entity::PROMOTER_PAN_NAME => "vasanthi kakarla"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::PERSONAL_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getPoiVerificationStatus());

    }

    //company pan
    public function testCompanyPanVerificationViaBvsIfExpIsEnabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCompanyPanVerificationStatus());

    }

    public function testCompanyPanVerificationViaBvsIfExpIsDisabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCompanyPanVerificationStatus());
    }

    public function testCompanyPanVerificationViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_PAN      => "ABCCD1234A",
                                          Detail\Entity::COMPANY_PAN_NAME => "CHIZRINZ INFOWAY PRIVATE LIMITED"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCompanyPanVerificationStatus());

    }

    public function testCompanyPanVerificationViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_PAN      => "ABCCD1234A",
                                          Detail\Entity::COMPANY_PAN_NAME => "CHIZRINZ INFOWAY PRIVATE LIMITED"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCompanyPanVerificationStatus());
    }

    public function testCompanyPanVerificationRetryViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([Detail\Entity::COMPANY_PAN      => "ABCCD1234A",
                                          Detail\Entity::COMPANY_PAN_NAME => "CHIZRINZ INFOWAY PRIVATE LIMITED"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCompanyPanVerificationStatus());

    }

    public function testCompanyPanVerificationRetryViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([
                                             Detail\Entity::COMPANY_PAN      => "ABCCD1234A",
                                             Detail\Entity::COMPANY_PAN_NAME => "CHIZRINZ INFOWAY PRIVATE LIMITED"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getCompanyPanVerificationStatus());
    }

    public function testCompanyPanVerificationViaBvsIfExpIsEnabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit"                        => "1",
                                          Detail\Entity::COMPANY_PAN      => "ABCCD1234A",
                                          Detail\Entity::COMPANY_PAN_NAME => "CHIZRINZ INFOWAY PRIVATE LIMITED"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getCompanyPanVerificationStatus());
    }
    public function testCompanyPanVerificationViaBvsIfExpIsDisabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit"                        => "1",
                                          Detail\Entity::COMPANY_PAN      => "ABCCD1234A",
                                          Detail\Entity::COMPANY_PAN_NAME => "CHIZRINZ INFOWAY PRIVATE LIMITED"
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BUSINESS_PAN);

        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());

        $this->assertEquals('initiated', $merchant_details->getCompanyPanVerificationStatus());

    }

    //Bank account
    public function testBankAccountVerificationViaBvsIfExpIsEnabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getBankDetailsVerificationStatus());

    }

    public function testBankAccountVerificationViaBvsIfExpIsDisabledOnL2()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesL2(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit" => "1"], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getBankDetailsVerificationStatus());
    }

    public function testBankAccountVerificationViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(['bank_branch_ifsc'                 => 'CBIN0281697',
                                          'bank_account_number'              => '0002020000304030434',
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getBankDetailsVerificationStatus());

    }

    public function testBankAccountVerificationViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixtures(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(['bank_branch_ifsc'                 => 'CBIN0281697',
                                          'bank_account_number'              => '0002020000304030434',
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getBankDetailsVerificationStatus());
    }

    public function testBankAccountVerificationRetryViaBvsIfExpIsEnabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(['bank_branch_ifsc'                 => 'CBIN0281697',
                                          'bank_account_number'              => '0002020000304030434',
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getBankDetailsVerificationStatus());

    }

    public function testBankAccountVerificationRetryViaBvsIfExpIsDisabled()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesRetry(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails([
                                             'bank_branch_ifsc'                 => 'CBIN0281697',
                                             'bank_account_number'              => '0002020000304030434',
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getBankDetailsVerificationStatus());
    }

    public function testBankAccountVerificationViaBvsIfExpIsEnabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit"                        => "1",
                                          'bank_branch_ifsc'                 => 'CBIN0281697',
                                          'bank_account_number'              => '0002020000304030434',
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('verified', $merchant_details->getBankDetailsVerificationStatus());

    }

    public function testBankAccountVerificationViaBvsIfExpIsDisabledOnNcSubmission()
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        [$detailCore] = $this->createAndFetchMocks(false);
        [$merchantDetail] = $this->createAndFetchFixturesNC(BusinessType::PRIVATE_LIMITED);

        $this->app->instance("rzp.mode", Mode::TEST);
        // Submit L2 form
        $detailCore->saveMerchantDetails(["submit"                        => "1",
                                          'bank_branch_ifsc'                 => 'CBIN0281697',
                                          'bank_account_number'              => '0002020000304030434',
                                         ], $merchantDetail->merchant);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),
                                                                                             'merchant',
                                                                                             Bvs\Constant::BANK_ACCOUNT);
        $this->assertNotEmpty($bvsValidation);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());
        $merchant_details = (new Detail\Repository())->findOrFailPublic($merchantDetail->getMerchantId());
        $this->assertEquals('initiated', $merchant_details->getBankDetailsVerificationStatus());


    }
}
