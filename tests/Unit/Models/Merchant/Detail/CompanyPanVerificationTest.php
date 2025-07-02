<?php


namespace Unit\Models\Merchant\Detail;

use Config;
use Mail;
use App;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use Mockery;
use RZP\Models\Merchant\Detail\DeDupe\Core as DedupeCore;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\BvsValidation\Repository;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\AutoKyc\Bvs;
use RZP\Exception\BadRequestValidationFailureException;

class CompanyPanVerificationTest extends TestCase
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
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app['razorx']->method('getTreatment')
            ->willReturn($razorXEnabled ? 'on' : 'off');

        $detailCore = $this->getMockBuilder(Detail\Core::class)
            ->onlyMethods(["canSubmit"])
            ->getMock();
        $detailCore->expects($this->exactly(2))->method('canSubmit')->willReturn(true);

        return [$detailCore];
    }

    private function createAndFetchFixtures()
    {
        // create merchant data where only company pan verification not done
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            Detail\Entity::BUSINESS_NAME    => "kitty su",
            Detail\Entity::COMPANY_PAN      => "AAAPA1234J",
            Detail\Entity::BUSINESS_TYPE    => BusinessType::getIndexFromKey(BusinessType::PRIVATE_LIMITED),
            Detail\Entity::BANK_DETAILS_VERIFICATION_STATUS => 'verified',
            Detail\Entity::POI_VERIFICATION_STATUS          => 'verified',
            Detail\Entity::CIN_VERIFICATION_STATUS          => 'verified'
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

    public function testCompanyPanVerificationViaBvsIfExpIsEnabled()
    {
        [$detailCore] = $this->createAndFetchMocks(true);
        [$merchantDetail] = $this->createAndFetchFixtures();

        $this->app->instance("rzp.mode", Mode::LIVE);
        $detailCore->saveMerchantDetails(["company_pan"=>"ABCCD1234A"], $merchantDetail->merchant);

        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($merchantDetail->getMerchantId(),'merchant',Bvs\Constant::BUSINESS_PAN);
        $this->assertNotEmpty($bvsValidation);

        $this->assertEquals(Bvs\Constant::BUSINESS_PAN, $bvsValidation->getArtefactType());
        $this->assertEquals("success", $bvsValidation->getValidationStatus());
    }

    public function createAndFetchMocksNew($isDedupeRequired = true, array $mockDedupeMethods = [])
    {
        $defaultMockDedupeMethods = ['isDedupeRequired'];

        $mockDedupeMethods = array_merge($defaultMockDedupeMethods, $mockDedupeMethods);
        $mockMC = $this->getMockBuilder(DedupeCore::class)
            ->onlyMethods($mockDedupeMethods)
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isDedupeRequired')
            ->willReturn($isDedupeRequired);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->onlyMethods(['canSubmitActivationForm', 'triggerWorkflowFlowForImpersonatedMerchant'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('canSubmitActivationForm')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('triggerWorkflowFlowForImpersonatedMerchant')
            ->willReturn(null);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $diagMock = Mockery::mock('RZP\Services\DiagClient');
        $diagMock->shouldReceive([
            'trackOnboardingEvent'  => [],
            'buildRequestAndSend'   => [],
            'trackEmailEvent'       => null
        ]);

        $this->app->instance('diag', $diagMock);

        return [
            "dedupeCoreMock"    => $mockMC,
            "detailCoreMock"    => $detailCoreMock
        ];
    }

    public function testCompanyPanValidationByBusinessTypeWithBankingProduct()
    {
        $mocks = $this->createAndFetchMocksNew(true, ['match','isDedupeBlocked', 'isMerchantImpersonated']);

        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock = $mocks['dedupeCoreMock'];

        $dedupeCoreMock->expects($this->any())
            ->method('match')
            ->willReturn([false, null]);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        [$merchantDetail] = $this->createAndFetchFixtures();

        $this->ba = App::getFacadeRoot()->make('basicauth');
        $this->ba->setMerchant($merchantDetail->merchant);
        App::instance('basicauth', $this->ba);

        $merchantDetail->setBusinessTypeValue('4');
        $merchantDetail->save();

        $this->app->instance("rzp.mode", Mode::LIVE);

        // Test valid PAN for private limited
        $merchantData = $detailCoreMock->saveMerchantDetails([
            Detail\Entity::COMPANY_PAN      => "AAACA1234J",
            Detail\Entity::COMPANY_PAN_NAME => "TEST PRIVATE LIMITED"
        ], $merchantDetail->merchant);

        $this->assertEquals("AAACA1234J",  $merchantData[Detail\Entity::COMPANY_PAN]);
    }

    public function testCompanyPanValidationByBusinessTypeWithBankingProductInvalidPan()
    {
        $mocks = self::createAndFetchMocksNew(true, ['match','isDedupeBlocked', 'isMerchantImpersonated']);

        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock = $mocks['dedupeCoreMock'];

        $dedupeCoreMock->expects($this->any())
            ->method('match')
            ->willReturn([false, null]);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        [$merchantDetail] = $this->createAndFetchFixtures();

        $this->ba = App::getFacadeRoot()->make('basicauth');
        $this->ba->setMerchant($merchantDetail->merchant);
        App::instance('basicauth', $this->ba);

        $merchantDetail->setBusinessTypeValue('4');
        $merchantDetail->save();

        $this->app->instance("rzp.mode", Mode::LIVE);

        // Test invalid PAN for private limited
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('The company pan field is invalid for business type: private_limited');

        $detailCoreMock->saveMerchantDetails([
            Detail\Entity::COMPANY_PAN      => "AAAPA1234J",
            Detail\Entity::COMPANY_PAN_NAME => "TEST PRIVATE LIMITED"
        ], $merchantDetail->merchant);
    }

}

