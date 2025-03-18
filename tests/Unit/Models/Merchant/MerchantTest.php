<?php

namespace Tests\Unit\Models\Merchant;


use Mockery;
use RZP\Constants\Mode;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Service;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Fixtures\Entity\MerchantDetail;
use Tests\Unit\TestCase;
use Accounts\Account\V1\SaveRequest;
use Accounts\Account\V1\SaveResponse;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Account as AsvSdkAccount;

class UserTest extends TestCase
{
    protected $merchantService;

    protected $userEntityMock;

    protected $merchantEntityMock;

    protected $merchantRepoMock;

    protected $merchantCore;

    protected $partnerSubMerchantConfigCoreMock;

    protected $partnerConfigEntityMock;


    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestDependencyMocks();

        $this->merchantService = new Service();
    }

    public function testEnableBusinessBankingIfApplicable()
    {
        $r = new \ReflectionMethod('RZP\Models\Merchant\Service', 'enableBusinessBankingIfApplicable');

        $r->setAccessible(true);

        $this->basicAuthMock->shouldReceive('isProductBanking')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('isBusinessBankingEnabled')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('setBusinessBanking')->andReturn();

        $response = $r->invoke($this->merchantService, $this->merchantEntityMock);

        $this->assertEquals(true, $response);
    }

    public function testSetSubMerchantMaxPaymentCalled()
    {
        $submerchant = $this->merchantEntityMock;

        $this->partnerSubMerchantConfigCoreMock->shouldReceive('fetchPartnerSubMerchantConfig')
                                    ->andReturn([["value"=>"20000000","business_type"=>"individual"]]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $submerchant->shouldReceive('setMaxPaymentAmount')->andReturn();

        $submerchant->shouldReceive('isPartner')->andReturn(true);

        $result = $this->merchantCore->setSubMerchantMaxPaymentAmount($this->merchantEntityMock,$submerchant,BusinessType::INDIVIDUAL);

        $submerchant->shouldHaveReceived('setMaxPaymentAmount')->once();

        $this->assertNull($result);
    }

    public function testSetSubMerchantMaxPaymentNotCalled()
    {
        $submerchant = $this->merchantEntityMock;

        $this->partnerSubMerchantConfigCoreMock->shouldReceive('fetchPartnerSubMerchantConfig')
                                    ->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000001');

        $submerchant->shouldReceive('setMaxPaymentAmount')->andReturn();

        $submerchant->shouldReceive('isPartner')->andReturn(true);

        $result = $this->merchantCore->setSubMerchantMaxPaymentAmount($this->merchantEntityMock,$submerchant,BusinessType::INDIVIDUAL);

        $submerchant->shouldNotHaveReceived('setMaxPaymentAmount');

        $this->assertNull($result);
    }

    public function testAddPartnerAddedFeaturesToSubmerchantWhenPartnerIsNull()
    {
        $coreMock = $this->merchantCore->makePartial();
        $submerchant = $this->merchantEntityMock;

        $result = $coreMock->addPartnerAddedFeaturesToSubmerchant($submerchant, null);

        $coreMock->shouldNotHaveReceived("isRazorxExperimentEnable");
        $coreMock->shouldNotHaveReceived("addPartnerAddedFeaturesToSubmerchantOnMode");
        $this->assertNull($result);
    }

    public function testAddPartnerAddedFeaturesToSubmerchantWhenPartnerIsNotNull()
    {
        $coreMock = $this->merchantCore->makePartial();
        $submerchant = $this->merchantEntityMock;
        $partner = $this->merchantEntityMock;
        $partner->shouldReceive('getId')->andReturn('10000000000001');

        $coreMock->shouldReceive('addPartnerAddedFeaturesToSubmerchantOnMode')
                 ->with($submerchant, $partner, Mode::TEST)->andReturn();
        $coreMock->shouldReceive('addPartnerAddedFeaturesToSubmerchantOnMode')
                 ->with($submerchant, $partner, Mode::LIVE)->andReturn();

        $result = $coreMock->addPartnerAddedFeaturesToSubmerchant($submerchant, $this->merchantEntityMock);

        $coreMock->shouldHaveReceived("addPartnerAddedFeaturesToSubmerchantOnMode")->twice();
        $this->assertNull($result);
    }

    public function testValidationInvalidCharacterInDisplayName()
    {
        $entity = new \RZP\Models\Merchant\Entity();

        $this->expectException(BadRequestValidationFailureException::class);

        $entity->edit([\RZP\Models\Merchant\Entity::DISPLAY_NAME => "Sample name 𤨒"], "editConfig");
    }

    public function createTestDependencyMocks()
    {
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $this->merchantCore = Mockery::mock('RZP\Models\Merchant\Core')->makePartial();

        $this->userEntityMock = Mockery::mock('RZP\Models\User\Entity');

        $this->partnerSubMerchantConfigCoreMock = Mockery::mock('overload:\RZP\Models\Partner\Config\SubMerchantConfig\Core');

        $this->partnerConfigEntityMock = Mockery::mock('\RZP\Models\Partner\Config\Entity');

        $this->basicAuthMock->shouldReceive('getUser')->andReturn($this->userEntityMock);

        $this->basicAuthMock->shouldReceive('getMerchant')->andReturn($this->merchantEntityMock);
    }

    public function checkIfLatestStatusIsNC(SaveRequest $request ){
        $account = $request->getAccount();
        $additionalDetail = $account->getAdditionalDetail();
        $details = $additionalDetail->getDetails();
        $manualRekycStruct = $details->getFields()["manual_rekyc"]->getStructValue();
        $statuses = $manualRekycStruct->getFields()["status"]->getListValue();
        $entries = $statuses->getValues();
        $latestEntry = $statuses->getValues()[$entries->count()-1];
        $latestStatus = $latestEntry->getStructValue()->getFields()["rekyc_status"]->getStringValue();
        return $latestStatus=== "needs_clarification";
    }

    public function testTransitionToNextRekycStatusFromURtoNC()
    {
        $merchantId = "10000000000000";
        $asvSdkAccount = new AsvSdkAccount();

        $mockAccountClient = $this->getMockAsvClient();
        $asvSdkAccount->getAsvSdkClient()->setAccount($mockAccountClient);

        $response = new SaveResponse();
        $response->setAccountId("10000000000000");

        $mockAccountClient->expects($this->once())->method("Save")->with($this->callback(function($request) {
           return $this->checkIfLatestStatusIsNC($request);
        }))->willReturn([$response, null]);

        $details = [
            'manual_rekyc' => [
                'status' => [
                    [
                        'rekyc_status' => 'under_review',
                        'created_at' => 1622547800
                    ]
                ]
            ]
        ];

        $nextStatus = 'needs_clarification';

        $result = $this->merchantService->transitionToNextRekycStatus($merchantId, $details, $nextStatus);
        $this->assertEquals($result, $merchantId);
    }

    public function testTransitionToNextRekycStatusFromEmptytoNC()
    {
        $merchantId = "10000000000000";
        $asvSdkAccount = new AsvSdkAccount();

        $mockAccountClient = $this->getMockAsvClient();
        $asvSdkAccount->getAsvSdkClient()->setAccount($mockAccountClient);

        $response = new SaveResponse();
        $response->setAccountId("10000000000000");

        $mockAccountClient->expects($this->once())->method("Save")->with($this->callback(function($request) {
            return $this->checkIfLatestStatusIsNC($request);
        }))->willReturn([$response, null]);

        $details = [];

        $nextStatus = 'needs_clarification';

        $result = $this->merchantService->transitionToNextRekycStatus($merchantId, $details, $nextStatus);
        $this->assertEquals($result, $merchantId);
    }

    public function checkIfLatestStatusIsURWithVerifications(SaveRequest $request) {
        $account = $request->getAccount();
        $additionalDetail = $account->getAdditionalDetail();
        $details = $additionalDetail->getDetails();
        $manualRekycStruct = $details->getFields()["manual_rekyc"]->getStructValue();
        $statuses = $manualRekycStruct->getFields()["status"]->getListValue();
        $verifications = $manualRekycStruct->getFields()["verifications"]->getListValue();
        $entries = $statuses->getValues();
        $latestEntry = $statuses->getValues()[$entries->count()-1];
        $latestStatus = $latestEntry->getStructValue()->getFields()["rekyc_status"]->getStringValue();
        return $latestStatus=== "under_review" && $verifications!=null;
    }
    public function testTransitionToNextRekycStatusFromNCtoURWithVerifications()
    {
        $merchantId = "10000000000000";
        $asvSdkAccount = new AsvSdkAccount();

        $mockAccountClient = $this->getMockAsvClient();
        $asvSdkAccount->getAsvSdkClient()->setAccount($mockAccountClient);

        $response = new SaveResponse();
        $response->setAccountId("10000000000000");

        $mockAccountClient->expects($this->once())->method("Save")->with($this->callback(function($request) {
            return $this->checkIfLatestStatusIsURWithVerifications($request);
        }))->willReturn([$response, null]);

        $details = [
            'manual_rekyc' => [
                'status' => [
                    [
                        'rekyc_status' => 'under_review',
                        'created_at' => 1622547800
                    ],
                    [
                        'rekyc_status' => 'needs_clarification',
                        'created_at' => 1622547800
                    ]
                ],
                'verifications' => [
                    [
                        'id' => 1,
                    ]
                ]
            ]
        ];

        $nextStatus = 'under_review';

        $result = $this->merchantService->transitionToNextRekycStatus($merchantId, $details, $nextStatus);
        $this->assertEquals($result, $merchantId);
    }

    protected function getMockAsvClient()
    {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\AccountInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }
}
