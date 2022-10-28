<?php


namespace Unit\Models\Merchant\Detail;

use DB;
use Mockery;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use RZP\Services\MerchantRiskClient;
use RZP\Models\MerchantRiskAlert;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Models\Merchant\Detail\DeDupe\Constants;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail\DeDupe\Core as DedupeCore;

class DedupeTest extends OAuthTestCase
{

    protected function mockMerchantRiskClient(string $merchantId, array $fields = [])
    {

        $mockMR = $this->getMockBuilder(MerchantRiskClient::class)
            ->setMethods(['getMerchantRiskScores'])
            ->getMock();


        $mockMR->expects($this->any())
            ->method('getMerchantRiskScores')
            ->willReturn([
                "client_type" => "onboarding",
                "entity_id" => $merchantId,
                "fields" => $fields
            ]);


        return $mockMR;
    }


    protected function createAndFetchMocks($isDedupeRequired = true, array $mockDedupeMethods = [])
    {
        $defaultMockDedupeMethods = ['isDedupeRequired'];

        $mockDedupeMethods = array_merge($defaultMockDedupeMethods, $mockDedupeMethods);
        $mockMC = $this->getMockBuilder(DedupeCore::class)
            ->setMethods($mockDedupeMethods)
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isDedupeRequired')
            ->willReturn($isDedupeRequired);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['canSubmitActivationForm', 'triggerWorkflowFlowForImpersonatedMerchant'])
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
//        $diagMock->shouldReceive('trackOnboardingEvent')->andReturn([]);
//        $diagMock->shouldReceive('buildRequestAndSend')->andReturn([]);

        $this->app->instance('diag', $diagMock);

        return [
            "dedupeCoreMock"    => $mockMC,
            "detailCoreMock"    => $detailCoreMock
        ];
    }

    public function testDedupeBeingSkippedForLinkedAccount()
    {
        $core = new DedupeCore();

        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $this->assertEquals(false, $core->isDedupeRequired($linkedAccount));
    }

    public function testDedupeBeingSkippedForNonRazorpayOrg()
    {
        $core = new DedupeCore();

        $dummyOrg = $this->fixtures->create('org', ['custom_code' => 'dummy']);

        $merchant = $this->fixtures->create('merchant', ['org_id' => $dummyOrg['id']]);

        $this->assertEquals(false, $core->isDedupeRequired($merchant));
    }

    public function testDedupeBeingNotSkippedForNonRazorpayOrgIfOrgFeatureEnabled()
    {
        $core = new DedupeCore();

        $dedupe = true;

        $dummyOrg = $this->fixtures->create('org', ['custom_code' => 'dummy']);

        $this->fixtures->create('feature', [
            'name'          => Feature::ORG_SUB_MERCHANT_MCC_PENDING,
            'entity_id'     => $dummyOrg['id'],
            'entity_type'   => 'org',
        ]);

        $merchant = $this->fixtures->create('merchant', ['org_id' => $dummyOrg['id']]);

        $this->assertEquals(true, $core->isDedupeRequired($merchant,$dedupe));
    }

    public function testDedupeBeingSkippedForFullyManagedSubmerchant()
    {
        $subMerchant = $this->createSubmerchantAndRelatedEntities('fully_managed');

        $this->mockRazorx();

        $core = new DedupeCore();

        $this->assertFalse($core->isDedupeRequired($subMerchant));
    }

    public function testDedupeBeingNotSkippedForNonFullyManagedSubmerchant()
    {
        $subMerchant = $this->createSubmerchantAndRelatedEntities('aggregator');

        $this->mockRazorx();

        $core = new DedupeCore();

        $this->assertTrue($core->isDedupeRequired($subMerchant));
    }

    public function testDedupeBeingNotSkippedForNonSubmerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['email' => 'submerchant@gmail.com']);

        $this->mockRazorx();

        $core = new DedupeCore();

        $this->assertTrue($core->isDedupeRequired($merchant));
    }

    public function testDedupeTrueAndActionOnFields()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');
        $merchant = $merchantDetail->merchant;

        $mocks = $this->createAndFetchMocks(true);
        $dedupeCore = $mocks['dedupeCoreMock'];

        foreach (Constants::MERCHANT_RISK_ACTIONS as $action)
        {
            $mockedResponse = [];

            foreach ($action['keysToCheck'] as $fieldName => $data)
            {
                $mockedResponse[] = [
                    'field'     => $fieldName,
                    'list'      => $data['list'],
                    'score'     => 900  // some random score
                ];
            }
            $merchantRiskClientMock = $this->mockMerchantRiskClient($merchant->getId(), $mockedResponse);

            $dedupeCore->setMerchantRiskClient($merchantRiskClientMock);

            [$isImpersonated, $actionToExecute] = $dedupeCore->match($merchant);

            $this->assertTrue($isImpersonated);
            $this->assertEquals($action[Constants::ACTION] ?? null, $actionToExecute);
        }
    }

    public function testDedupeSkipForSubMerchantOnBusinessWebsite()
    {
        $subMerchant = $this->createSubmerchantAndRelatedEntities('aggregator');
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',['merchant_id'=>$subMerchant->getId()]);

        $mocks = $this->createAndFetchMocks(true);
        $dedupeCore = $mocks['dedupeCoreMock'];

        foreach (Constants::MERCHANT_RISK_ACTIONS as $action)
        {
            $mockedResponse = [];

            foreach ($action['keysToCheck'] as $fieldName => $data)
            {
                if($fieldName == 'business_website')
                    continue;

                $mockedResponse[] = [
                    'field'     => $fieldName,
                    'list'      => $data['list'],
                    'score'     => 900  // some random score
                ];
            }

            $merchantRiskClientMock = $this->mockMerchantRiskClient($subMerchant->getId(), $mockedResponse);

            $dedupeCore->setMerchantRiskClient($merchantRiskClientMock);

            [$isImpersonated, $actionToExecute] = $dedupeCore->match($subMerchant);

            if( $fieldName == 'business_website')
            {
                $this->assertFalse($isImpersonated);
            }
            else
            {
                $this->assertTrue($isImpersonated);
            }

        }
    }

    public function testGetFieldValueForIpAddress()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');
        $merchant = $merchantDetail->merchant;

        $this->mockRazorx();

        $mocks = $this->createAndFetchMocks(true);
        $dedupeCore = $mocks['dedupeCoreMock'];

        $request = Mockery::mock('Illuminate\Http\Request')->makePartial();

        $request->shouldReceive('getClientIp')->withAnyArgs()->andReturn("127.0.0.0");
        $request->shouldReceive('getId')->withAnyArgs()->andReturn("8bde38291fc32cf7af535d062c18f0f8");
        $request->shouldReceive('getTaskId')->withAnyArgs()->andReturn("8bde38291fc32cf7af535d062c18f0f8");

        $this->app->instance('request', $request);

        $value = $dedupeCore->getFieldValue("client_ip",$merchant);

        $this->assertEquals("127.0.0.0", $value);

    }

    public function testGetFieldValueForClientId()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');
        $merchant = $merchantDetail->merchant;

        $this->mockRazorx();

        $mocks = $this->createAndFetchMocks(true);
        $dedupeCore = $mocks['dedupeCoreMock'];

        $request = Mockery::mock('Illuminate\Http\Request')->makePartial();

        $request->shouldReceive('cookie')->withAnyArgs()->andReturn("iamClient_id");
        $request->shouldReceive('getId')->withAnyArgs()->andReturn("8bde38291fc32cf7af535d062c18f0f8");
        $request->shouldReceive('getTaskId')->withAnyArgs()->andReturn("8bde38291fc32cf7af535d062c18f0f8");

        $this->app->instance('request', $request);

        $value = $dedupeCore->getFieldValue("clientId",$merchant);

        $this->assertEquals("iamClient_id", $value);

    }

    public function l2FormSubmit($merchant, $dedupeFlag = true)
    {
        $mocks = $this->createAndFetchMocks(true, ['match','isDedupeBlocked']);

        $detailCoreMock = $mocks['detailCoreMock'];

        $this->app['basicauth']->setMerchant($merchant);

        if ($dedupeFlag === false)
        {
            $dedupeCoreMock = $mocks['dedupeCoreMock'];

            $dedupeCoreMock->expects($this->any())
                           ->method('match')
                           ->willReturn([false, null]);

            $detailCoreMock->setDedupeCore($dedupeCoreMock);
        }

        $input = ['submit' => '1'];

        return $detailCoreMock->saveMerchantDetails($input, $merchant);
    }

    public function testL2FormSubmitWithDedupeFalse()
    {
        $merchantDetail = $this->createFixtures();

        $merchant = $merchantDetail->merchant;

        $response = $this->l2FormSubmit($merchant, false);

        $this->assertNotNull($response['activation_status']);
    }

    public function testL2FormSubmitWithDedupeFalseForRASSignupFraud()
    {
        $this->mockRazorx('ok');

        $merchantDetail = $this->createFixtures();

        $merchant = $merchantDetail->merchant;

        $this->app['cache']->connection()->hset(
            MerchantRiskAlert\Constants::REDIS_DEDUPE_SIGNUP_CHECKER_MAP,
            $merchant->getId(),
            now()->timestamp
        );

        $response= $this->l2FormSubmit($merchant);

        $this->assertEquals('risk_review_suspend_tag', $merchant->merchantDetail->getFraudType());

        $this->assertNotNull($this->app['cache']->connection()->hget(MerchantRiskAlert\Constants::REDIS_DEDUPE_SIGNUP_CHECKER_MAP, $merchant->getId()));

        $this->assertTrue($response['locked']);
    }

    public function testL2FormSubmitWithDedupeTrueAndDeactivateAction()
    {
        $merchantDetail = $this->createFixtures();
        $merchant = $merchantDetail->merchant;

        $mocks = $this->createAndFetchMocks(true, ['match','isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];
        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock->expects($this->any())
            ->method('match')
            ->willReturn([true, Constants::DEACTIVATE]);
        $dedupeCoreMock->expects($this->any())->method('isDedupeBlocked')
            ->willReturn(true);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        $input = ['submit' => '1'];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->assertNotNull($response['activation_status']);
        $this->verifyLockAndDeactivate($response);
    }

    public function testL2FormSubmitWithDedupeTrueAndUnRegDeactivateAction()
    {
        $this->fixtures->create('merchant', [
            'id' => 'KFMWFIqabujap8'
        ]);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => 'KFMWFIqabujap8'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type' => BusinessType::getIndexFromKey(BusinessType::NOT_YET_REGISTERED),
            'merchant_id' => 'KFMWFIqabujap8'
        ]);

        $merchant = $merchantDetail->merchant;

        $mocks = $this->createAndFetchMocks(true, ['match', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];
        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock->expects($this->any())
            ->method('match')
            ->willReturn([true, Constants::UNREG_DEACTIVATE]);
        $dedupeCoreMock->expects($this->any())->method('isDedupeBlocked')
            ->willReturn(true);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        $input = ['submit' => '1'];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->assertNotNull($response['activation_status']);
        $this->verifyLockAndDeactivate($response);
    }

    public function testL2FormSubmitWithDedupeTrueAndNoAction()
    {
        $this->fixtures->create('merchant', [
            'id' => 'KFMWFIqabujap8'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',
            [
                'merchant_id' => 'KFMWFIqabujap8'
            ]);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => 'KFMWFIqabujap8'
        ]);

        $merchant = $merchantDetail->merchant;

        $mocks = $this->createAndFetchMocks(true, ['match', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];
        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock->expects($this->any())->method('match')
            ->willReturn([true, null]);
        $dedupeCoreMock->expects($this->any())->method('isDedupeBlocked')
            ->willReturn(false);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        $input = ['submit' => '1'];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->assertNotNull($response['activation_status']);
    }

    public function testDedupeTagOnDeactivateAction()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mocks = $this->createAndFetchMocks(true, ['match', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];

        $dedupeTag = $dedupeCoreMock->getDedupeTagForAction($merchantDetail, Constants::DEACTIVATE);

        self::assertEquals(Constants::DEDUPE_BLOCKED_TAG, $dedupeTag);
    }

    public function testDedupeTagOnAndUnRegDeactivateAction()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type' => BusinessType::getIndexFromKey(BusinessType::NOT_YET_REGISTERED)
        ]);

        $mocks = $this->createAndFetchMocks(true, ['match', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];

        $dedupeTag = $dedupeCoreMock->getDedupeTagForAction($merchantDetail, Constants::UNREG_DEACTIVATE);

        self::assertEquals(Constants::DEDUPE_BLOCKED_TAG, $dedupeTag);
    }

    public function testDedupeTagOnNoAction()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mocks = $this->createAndFetchMocks(true, ['match', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];

        $dedupeTag = $dedupeCoreMock->getDedupeTagForAction($merchantDetail, null);

        self::assertEquals(Constants::DEDUPE_TAG, $dedupeTag);
    }

    public function testL2FormSubmitWithDedupeTrueAndUnderReviewPaymentBlocked()
    {
        $this->fixtures->create('merchant', [
            'id' => 'KFMWFIqabujap8'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type' => 4,
            'merchant_id' => 'KFMWFIqabujap8'
        ]);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => 'KFMWFIqabujap8'
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetail->getId(), [
            'activated'     => 1,
            'live'          => 1
        ]);

        $mocks = $this->createAndFetchMocks(true, ['match', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];
        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock->expects($this->any())
            ->method('match')
            ->willReturn([true, Constants::UNREG_DEACTIVATE]);
        $dedupeCoreMock->expects($this->any())->method('isDedupeBlocked')
            ->willReturn(false);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->assertFalse($response['merchant']['live']);
        $this->assertFalse($response['merchant']['activated']);
    }

    public function testIsDedupeBlockAfterL1Submission()
    {
        $this->fixtures->create('merchant', [
            'id' => 'KFMWFIqabujap8'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'activation_form_milestone' => 'L1',
            'merchant_id' => 'KFMWFIqabujap8'
        ]);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => 'KFMWFIqabujap8'
        ]);

        $mocks = $this->createAndFetchMocks(true, ['isMerchantImpersonated', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];
        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock->expects($this->any())->method('isMerchantImpersonated')
            ->willReturn(true);
        $dedupeCoreMock->expects($this->any())->method('isDedupeBlocked')
            ->willReturn(true);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        $merchantDetailResponse = $detailCoreMock->createResponse($merchantDetail);

        $dedupeResponseObj = $merchantDetailResponse['dedupe'];

        $this->assertTrue($dedupeResponseObj['isMatch']);
        $this->assertTrue($dedupeResponseObj['isUnderReview']);
    }

    public function testIsDedupeBlockAfterL2Submission()
    {
        $this->fixtures->create('merchant', [
            'id' => 'KFMWFIqabujap8'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'activation_form_milestone' => 'L2',
            'merchant_id' => 'KFMWFIqabujap8'
        ]);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => 'KFMWFIqabujap8'
        ]);

        $mocks = $this->createAndFetchMocks(true, ['isMerchantImpersonated', 'isDedupeBlocked']);

        $dedupeCoreMock = $mocks['dedupeCoreMock'];
        $detailCoreMock = $mocks['detailCoreMock'];

        $dedupeCoreMock->expects($this->any())->method('isMerchantImpersonated')
            ->willReturn(true);
        $dedupeCoreMock->expects($this->any())->method('isDedupeBlocked')
            ->willReturn(true);

        $detailCoreMock->setDedupeCore($dedupeCoreMock);

        $merchantDetailResponse = $detailCoreMock->createResponse($merchantDetail);

        $dedupeResponseObj = $merchantDetailResponse['dedupe'];

        $this->assertTrue($dedupeResponseObj['isMatch']);
        $this->assertFalse($dedupeResponseObj['isUnderReview']);
    }

    private function verifyLockAndDeactivate(array $response)
    {
        $this->assertTrue($response['locked']);
        $this->assertTrue($response['merchant']['hold_funds']);
        $this->assertFalse($response['merchant']['live']);
        $this->assertFalse($response['merchant']['activated']);
    }

    private function createSubmerchantAndRelatedEntities(string $partnerType)
    {
        $partner = $this->fixtures->create('merchant', ['partner_type' => $partnerType]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => $partnerType]);

        $subMerchant = $this->fixtures->create('merchant', ['email' => 'submerchant@gmail.com']);

        $accessMap = [
            'id'              => 'CMe2wjY0hiWBrL',
            'entity_type'     => 'application',
            'entity_id'       => $app->getId(),
            'merchant_id'     => $subMerchant->getId(),
            'entity_owner_id' => $partner->getId(),
        ];

        $this->fixtures->create('merchant_access_map', $accessMap);

        return $subMerchant;
    }

    private function mockRazorx($variant = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(function($mid, $feature, $mode) use($variant) {
                return $variant;
            }));
    }

    private function createFixtures()
    {
        $this->fixtures->create('merchant', [
            'id' => 'KFMWFIqabujap8'
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id' => 'KFMWFIqabujap8'
        ]);

        $this->fixtures->on('live')->create('file_store', [
            'id'            => 'abcdef12345678',
            'merchant_id'   => 'KFMWFIqabujap8'
        ]);

        return $merchantDetail;
    }
}
