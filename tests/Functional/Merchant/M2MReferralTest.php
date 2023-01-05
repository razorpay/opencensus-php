<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Hash;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use RZP\Services\HubspotClient;
use RZP\Models\Coupon\Constants;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Mock\ApachePinotClient;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Promotion\Repository;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use Illuminate\Validation\ValidationException;
use RZP\Http\Requests\RewardValidationRequest;
use RZP\Models\Coupon\Constants as CouponConstants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\M2MReferral\Core;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use Illuminate\Auth\Access\AuthorizationException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
use RZP\Models\Merchant\M2MReferral\FriendBuy\FriendBuyClient;
use RZP\Models\Merchant\M2MReferral\FriendBuy\Constants as FBConstants;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Merchant\M2MReferral\Status as M2MEntityStatus;
use RZP\Models\Merchant\M2MReferral\Entity as M2MReferralEntity;
use RZP\Models\Merchant\Cron\Core as CronJobHandler;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use function GuzzleHttp\json_decode;

class M2MReferralTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/M2MReferralTestData.php';

        parent::setUp();
        $this->enableRazorXTreatmentForRazorX();
        $this->markTestSkipped('M2M referral program is terminated');

    }


    private function createMerchant($merchantId,$atributes=[])
    {

        $atributes['id'] = $merchantId;

        $merchant = $this->fixtures->on(Mode::LIVE)->create('merchant', $atributes);

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'            => $merchant->getId(),
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId()
        ]);
        return $merchant;
    }

    private function createPayment(string $merchantId, int $amount, int $createdAt = null)
    {
        if ($createdAt === null)
        {
            $createdAt = Carbon::now()->getTimestamp();
        }

        $transaction = $this->fixtures->on(Mode::LIVE)->create('payment', [
            'amount'      => $amount * 100,   // in paisa
            'merchant_id' => $merchantId,
            'created_at'  => $createdAt
        ]);

        $transaction = $this->fixtures->create('transaction', [
            'type'        => 'payment',
            'amount'      => $amount * 100,   // in paisa
            'merchant_id' => $merchantId,
            'created_at'  => $createdAt
        ]);
    }

    public function mockDruid($merchantId, $amount)
    {

        config(['services.druid.mock' => true]);

        $druidService = $this->getMockBuilder(MockDruidService::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods(['getDataFromDruid'])
                             ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = [
            'merchant_lifetime_gmv'        => $amount,
            'merchant_details_merchant_id' => $merchantId
        ];

        $druidService->method('getDataFromDruid')
                     ->willReturn([null, [$dataFromDruid]]);
    }

    protected function mockHubSpotClient($methodName, $times = 1)
    {
        $hubSpotMock = $this->getMockBuilder(HubspotClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods([$methodName])
                            ->getMock();

        $this->app->instance('hubspot', $hubSpotMock);

        $hubSpotMock->expects($this->exactly($times))
                    ->method($methodName);
    }

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::SHOW_FRIENDBUY_WIDGET)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
                       ->setMethods(['isRazorxExperimentEnable'])
                       ->getMock();

        $mockMC->expects($this->any())
               ->method('isRazorxExperimentEnable')
               ->willReturn(true);

        return [
            "merchantCoreMock" => $mockMC
        ];
    }

    public function mockAdvocateCoupon()
    {
        $m2mAdvocate1 = $this->fixtures->on('live')->create('promotion:onetime', ['credit_amount' => 20000000]);

        $couponAttributes = [
            'entity_id'   => $m2mAdvocate1->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::M2M_ADVOCATE1
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $m2mAdvocate2 = $this->fixtures->on('live')->create('promotion:onetime', ['credit_amount' => 20000000]);

        $couponAttributes = [
            'entity_id'   => $m2mAdvocate1->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::M2M_ADVOCATE2
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $m2mAdvocate3 = $this->fixtures->on('live')->create('promotion:onetime', ['credit_amount' => 20000000]);

        $couponAttributes = [
            'entity_id'   => $m2mAdvocate3->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::M2M_ADVOCATE3
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $m2mAdvocate4 = $this->fixtures->on('live')->create('promotion:onetime', ['credit_amount' => 20000000]);

        $couponAttributes = [
            'entity_id'   => $m2mAdvocate4->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::M2M_ADVOCATE4
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $m2mAdvocate5 = $this->fixtures->on('live')->create('promotion:onetime', ['credit_amount' => 20000000]);

        $couponAttributes = [
            'entity_id'   => $m2mAdvocate5->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::M2M_ADVOCATE5
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        return [$m2mAdvocate1, $m2mAdvocate2, $m2mAdvocate3, $m2mAdvocate4, $m2mAdvocate5];
    }

    public function mockFriendCoupon()
    {
        $m2mFriend = $this->fixtures->on('live')->create('promotion:onetime', ['credit_amount' => 20000000]);

        $couponAttributes = [
            'entity_id'   => $m2mFriend->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::M2M_FRIEND
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        return [$m2mFriend];
    }

    public function mockCoupon()
    {
        return array_merge($this->mockFriendCoupon(), $this->mockAdvocateCoupon());
    }

    public function testOauthSignup()
    {
        $adminId   = Org::MAKER_ADMIN;
        $formData  = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname"
            }',
            true
        );
        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);
        $this->app['config']->set('oauth.merchant_oauth_mock', true);
        $testData                                              = $this->testData[__FUNCTION__];
        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');

        $this->assertNotNull($m2mReferral);
        $this->assertEquals(M2MEntityStatus::SIGNUP_EVENT_SENT, $m2mReferral->getAttribute(M2MReferralEntity::STATUS));
        $this->assertArraySubset(
            [
                'utmSource'    => 'friendbuy',
                'utmMedium'    => 'referral',
                "utmCampaign"  => "Referral Test",
                "referralCode" => "mg5xnqzn",
            ], $m2mReferral->getAttribute(M2MReferralEntity::METADATA));

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('merchant_id'), $data, StoreConstants::INTERNAL);

        $this->assertNotNull($data);
        $this->assertEquals(true, $data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);

    }

    public function testUserRegisterVerifySignupOtpSms()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');

        $this->assertNotNull($m2mReferral);
        $this->assertEquals(M2MEntityStatus::SIGN_UP, $m2mReferral->getAttribute(M2MReferralEntity::STATUS));

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('merchant_id'), $data, StoreConstants::INTERNAL);

        $this->assertNotNull($data);
        $this->assertEquals(true, $data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);

    }
    private function mockApachePinot(string $merchantId, int $amount)
    {
        $pinotService = $this->getMockBuilder(ApachePinotClient::class)
                             ->setConstructorArgs([$this->app])
                             ->onlyMethods(['getDataFromPinot'])
                             ->getMock();

        $this->app->instance('apache.pinot', $pinotService);

        $dataFromPinot = ['merchant_id' => $merchantId, "amount" => $amount * 100];

        $pinotService->method('getDataFromPinot')
                     ->willReturn([$dataFromPinot]);
    }

    public function testSignupFromFriendBuy()
    {
        $this->mockHubSpotClient('trackSignupEvent');

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');

        $this->assertNotNull($m2mReferral);
        $this->assertEquals(M2MEntityStatus::SIGNUP_EVENT_SENT, $m2mReferral->getAttribute(M2MReferralEntity::STATUS));
        $this->assertArraySubset(
            [
                'utmSource'    => 'friendbuy',
                'utmMedium'    => 'referral',
                "utmCampaign"  => "Referral Production",
                "referralCode" => "mg5xnqzn",
                'email'        => 'test2@c.com',
            ], $m2mReferral->getAttribute(M2MReferralEntity::METADATA));

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('merchant_id'), $data, StoreConstants::INTERNAL);

        $this->assertNotNull($data);
        $this->assertEquals(true, $data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);

        $transaction = $this->createPayment($m2mReferral->getAttribute('merchant_id'), 1000);

        (new CronJobHandler())->handleCron(CronConstants::FRIEND_BUY_SEND_PURCHASE_EVENTS_CRON_JOB_NAME, []);
        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertEquals(M2MEntityStatus::SIGNUP_EVENT_SENT, $m2mReferral->getAttribute(M2MReferralEntity::STATUS));
        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('merchant_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals(true, $data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);

        $transaction = $this->createPayment($m2mReferral->getAttribute('merchant_id'), 1000);
        $this->mockApachePinot($m2mReferral->getAttribute('merchant_id'), 1000);

        (new CronJobHandler())->handleCron(CronConstants::FRIEND_BUY_SEND_PURCHASE_EVENTS_CRON_JOB_NAME, []);

        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertEquals(M2MEntityStatus::MTU_EVENT_SENT, $m2mReferral->getAttribute(M2MReferralEntity::STATUS));
        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('merchant_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals(false, $data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
    }

    public function testRewardValidation()
    {
        $this->app['rzp.mode'] = 'live';
        $refereeMerchant       = $this->createMerchant('I0qYGdG9IGaVxz',['activated'=>1]);
        $referrerMerchant      = $this->createMerchant('Hm9Bv6kFufFS36',['activated'=>1]);

        $input = [
            M2MReferralEntity::MERCHANT_ID => $refereeMerchant->getId(),
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];

        $m2m = (new Core())->createM2MReferral($refereeMerchant, $input);

        $promotions = $this->mockCoupon();

        $this->ba->noAuth();

        $this->startTest();

        //referrer
        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertEquals(M2MEntityStatus::REWARDED, $m2mReferral->getAttribute('status'));
        $this->assertNotNull($m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals($referrerMerchant->getMerchantId(), $m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals(M2MEntityStatus::REWARDED, $m2mReferral->getAttribute('referrer_status'));

        $repo              = new Repository();
        $merchantPromotion = $repo->findByMerchantAndPromotionId(
            $referrerMerchant->getId(),
            $promotions[1]->getId()
        );

        $this->assertNotNull($merchantPromotion);

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('referrer_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals($promotions[1]->getCreditAmount(), $data[StoreConfigKey::REFERRAL_AMOUNT]);
        $this->assertEquals(1, $data[StoreConfigKey::REFERRED_COUNT]);
        $this->assertEquals(1, $data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);
        $this->assertEquals([$refereeMerchant->getName()], $data[StoreConfigKey::REFEREE_NAME]);
        $this->assertEquals([$refereeMerchant->getId()], $data[StoreConfigKey::REFEREE_ID]);

        //referee
        $repo              = new Repository();
        $merchantPromotion = $repo->findByMerchantAndPromotionId(
            $refereeMerchant->getId(),
            $promotions[0]->getId()
        );

        $this->assertNotNull($merchantPromotion);

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('merchant_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals(false, $data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertEquals(1, $data[StoreConfigKey::REFEREE_SUCCESS_POPUP_COUNT]);

    }

    public function testRewardValidationMaxReached()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');
        $randomMerchant1  = $this->createMerchant('HucXhpLFHt8tQp');
        $randomMerchant2  = $this->createMerchant('2atNkeOLamMmgV');
        $randomMerchant3  = $this->createMerchant('2atM2thQ5S83wd');
        $randomMerchant4  = $this->createMerchant('2aTQDoOlSXTft9');
        $randomMerchant5  = $this->createMerchant('2aTP2v1Kef4dv5');

        $input = [
            M2MReferralEntity::STATUS          => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_STATUS => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_ID     => $referrerMerchant->getMerchantId(),
        ];
        (new Core())->createM2MReferral($randomMerchant1, $input);
        $input = [
            M2MReferralEntity::STATUS          => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_STATUS => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_ID     => $referrerMerchant->getMerchantId(),
        ];
        (new Core())->createM2MReferral($randomMerchant2, $input);
        $input = [
            M2MReferralEntity::STATUS          => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_STATUS => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_ID     => $referrerMerchant->getMerchantId(),
        ];
        (new Core())->createM2MReferral($randomMerchant3, $input);
        $input = [
            M2MReferralEntity::STATUS          => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_STATUS => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_ID     => $referrerMerchant->getMerchantId(),
        ];
        (new Core())->createM2MReferral($randomMerchant4, $input);
        $input = [
            M2MReferralEntity::STATUS          => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_STATUS => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_ID     => $referrerMerchant->getMerchantId(),
        ];
        (new Core())->createM2MReferral($randomMerchant5, $input);

        $input = [
            M2MReferralEntity::MERCHANT_ID => $refereeMerchant->getId(),
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];

        $m2m  = (new Core())->createM2MReferral($refereeMerchant, $input);
        $data = [
            StoreConstants::NAMESPACE      => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::REFERRED_COUNT => 5
        ];

        (new StoreCore())->updateMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);

        $promotions = $this->mockCoupon();

        $this->ba->noAuth();

        $this->startTest();

        //referrer
        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertEquals(M2MEntityStatus::REWARDED, $m2mReferral->getAttribute('status'));
        $this->assertNotNull($m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals($referrerMerchant->getMerchantId(), $m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals(null, $m2mReferral->getAttribute('referrer_status'));

        $repo              = new Repository();
        $merchantPromotion = $repo->getByMerchantId(
            $referrerMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());
        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('referrer_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals(5, $data[StoreConfigKey::REFERRED_COUNT]);

        //referee
        $repo              = new Repository();
        $merchantPromotion = $repo->findByMerchantAndPromotionId(
            $refereeMerchant->getId(),
            $promotions[0]->getId()
        );

        $this->assertNotNull($merchantPromotion);

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('merchant_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals(false, $data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertEquals(1, $data[StoreConfigKey::REFEREE_SUCCESS_POPUP_COUNT]);

    }

    public function testAlreadyRewardedReferrer()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');
        $randomMerchant   = $this->createMerchant('HucXhpLFHt8tQp');

        $input = [
            M2MReferralEntity::STATUS          => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_STATUS => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_ID     => $referrerMerchant->getMerchantId(),
        ];
        (new Core())->createM2MReferral($randomMerchant, $input);

        $input = [
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT,
            M2MReferralEntity::REFERRER_ID => $referrerMerchant->getMerchantId(),
            M2MReferralEntity::METADATA    => [
                M2MConstants::REFERRAL_CODE => 'zawdfd8x',
                FBConstants::EMAIL          => $refereeMerchant->getEmail()
            ]
        ];
        (new Core())->createM2MReferral($refereeMerchant, $input);

        $promotions = $this->mockCoupon();

        $data = [
            StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::REFERRED_COUNT               => 1,
            StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => 1,
            StoreConfigKey::REFEREE_ID                   => [$randomMerchant->getId()],
            StoreConfigKey::REFEREE_NAME                 => [$randomMerchant->getName()],
            StoreConfigKey::REFERRAL_AMOUNT              => 100
        ];

        (new StoreCore())->updateMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);

        $this->ba->noAuth();

        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertEquals(M2MEntityStatus::REWARDED, $m2mReferral->getAttribute('referrer_status'));

        $repo = new Repository();

        //referrer validation
        $merchantPromotion = $repo->getByMerchantId(
            $refereeMerchant->getId()
        );

        $this->assertEquals(1, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('referrer_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals($promotions[2]->getCreditAmount() + 100, $data[StoreConfigKey::REFERRAL_AMOUNT]);
        $this->assertEquals(2, $data[StoreConfigKey::REFERRED_COUNT]);
        $this->assertEquals(1, $data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);
        $this->assertEquals([$randomMerchant->getName(), $refereeMerchant->getName()], $data[StoreConfigKey::REFEREE_NAME]);
        $this->assertEquals([$randomMerchant->getId(), $refereeMerchant->getId()], $data[StoreConfigKey::REFEREE_ID]);

    }

    public function testAlreadyRewardedAndSeenReferrer()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');
        $randomMerchant   = $this->createMerchant('HucXhpLFHt8tQp');

        $input = [
            M2MReferralEntity::STATUS          => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_STATUS => M2MEntityStatus::REWARDED,
            M2MReferralEntity::REFERRER_ID     => $referrerMerchant->getMerchantId(),
        ];
        (new Core())->createM2MReferral($randomMerchant, $input);

        $input = [
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT,
            M2MReferralEntity::REFERRER_ID => $referrerMerchant->getMerchantId(),
            M2MReferralEntity::METADATA    => [
                M2MConstants::REFERRAL_CODE => 'zawdfd8x',
                FBConstants::EMAIL          => $refereeMerchant->getEmail()
            ]
        ];
        (new Core())->createM2MReferral($refereeMerchant, $input);

        $promotions = $this->mockCoupon();

        $data = [
            StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::REFERRED_COUNT               => 1,
            StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => 0,
            StoreConfigKey::REFEREE_NAME                 => [$randomMerchant->getName()],
            StoreConfigKey::REFEREE_ID                   => [$randomMerchant->getId()],
            StoreConfigKey::REFERRAL_AMOUNT              => 100
        ];

        (new StoreCore())->updateMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);

        $this->ba->noAuth();

        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertEquals(M2MEntityStatus::REWARDED, $m2mReferral->getAttribute('referrer_status'));

        $repo = new Repository();

        //referrer validation
        $merchantPromotion = $repo->getByMerchantId(
            $refereeMerchant->getId()
        );

        $this->assertEquals(1, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($m2mReferral->getAttribute('referrer_id'), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertEquals($promotions[2]->getCreditAmount(), $data[StoreConfigKey::REFERRAL_AMOUNT]);
        $this->assertEquals(2, $data[StoreConfigKey::REFERRED_COUNT]);
        $this->assertEquals(1, $data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);
        $this->assertEquals([$refereeMerchant->getName()], $data[StoreConfigKey::REFEREE_NAME]);
        $this->assertEquals([$refereeMerchant->getId()], $data[StoreConfigKey::REFEREE_ID]);

    }

    public function testRewardValidationInvalidPayload()
    {
        $this->ba->noAuth();

        $this->expectException(ValidationException::class);
        $this->startTest();
    }

    public function testRewardValidationInvalidSignature()
    {
        $this->ba->noAuth();

        $this->expectException(AuthorizationException::class);
        $this->startTest();
    }

//    public function testRewardValidationInvalidAuth()
//    {
//        $this->ba->appAuth('rzp_' . 'test', 'invalid');
//
//        $this->startTest();
//    }

    public function testRewardValidationInvalidReferrer()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');
        $this->ba->noAuth();
        $this->expectException(BadRequestException::class);
        $this->startTest();

    }

    public function testRewardValidationInvalidReferee()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');
        $this->ba->noAuth();

        $this->expectException(BadRequestException::class);
        $this->startTest();

    }

    public function testRewardValidationNonExistentReferral()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');

        $this->ba->noAuth();
        $promotions = $this->mockCoupon();

        $this->startTest();

        $repo = new Repository();

        //referee
        $merchantPromotion = $repo->findByMerchantAndPromotionId(
            $refereeMerchant->getId(),
            $promotions[0]->getId()
        );

        $this->assertNull($merchantPromotion);

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($refereeMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

        //referrer
        $merchantPromotion = $repo->findByMerchantAndPromotionId(
            $referrerMerchant->getId(),
            $promotions[1]->getId()
        );

        $this->assertNull($merchantPromotion);

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

    }

    public function testRewardValidationNotMTU()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');

        $promotions = $this->mockCoupon();

        $input = [
            M2MReferralEntity::STATUS      => M2MEntityStatus::SIGNUP_EVENT_SENT,
            M2MReferralEntity::REFERRER_ID => $referrerMerchant->getMerchantId(),
            M2MReferralEntity::METADATA    => [
                M2MConstants::REFERRAL_CODE => 'zawdfd8x',
                FBConstants::EMAIL          => $refereeMerchant->getEmail()
            ]
        ];
        (new Core())->createM2MReferral($refereeMerchant, $input);

        $this->ba->noAuth();

        $this->startTest();
        $repo = new Repository();

        //referee
        $merchantPromotion = $repo->findByMerchantAndPromotionId(
            $refereeMerchant->getId(),
            $promotions[0]->getId()
        );

        $this->assertNull($merchantPromotion);

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($refereeMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

        //referrer
        $merchantPromotion = $repo->findByMerchantAndPromotionId(
            $referrerMerchant->getId(),
            $promotions[1]->getId()
        );

        $this->assertNull($merchantPromotion);

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

    }

    public function testRewardValidationInvalidCoupon()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');

        $input = [
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT,
            M2MReferralEntity::REFERRER_ID => $referrerMerchant->getMerchantId(),
            M2MReferralEntity::METADATA    => [
                M2MConstants::REFERRAL_CODE => 'zawdfd8x',
                FBConstants::EMAIL          => $refereeMerchant->getEmail()
            ]
        ];
        (new Core())->createM2MReferral($refereeMerchant, $input);

        $this->ba->noAuth();

        $this->expectException(BadRequestException::class);

        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertNull($m2mReferral->getAttribute('referrer_status'));
        $this->assertEquals(M2MEntityStatus::MTU_EVENT_SENT, $m2mReferral->getAttribute('status'));
        $this->assertNotNull($m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals($referrerMerchant->getMerchantId(), $m2mReferral->getAttribute('referrer_id'));

        $repo = new Repository();

        $merchantPromotion = $repo->getByMerchantId(
            $refereeMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($refereeMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

        $merchantPromotion = $repo->getByMerchantId(
            $referrerMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);
    }

    public function testRewardValidationFriendCouponApplyFailedAdvocateAlreadyReferred()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');
        $randomMerchant   = $this->createMerchant('HucXhpLFHt8tQp');

        $input      = [
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT,
            M2MReferralEntity::REFERRER_ID => $referrerMerchant->getMerchantId(),
            M2MReferralEntity::METADATA    => [
                M2MConstants::REFERRAL_CODE => 'zawdfd8x',
                FBConstants::EMAIL          => $refereeMerchant->getEmail()
            ]
        ];
        $promotions = $this->mockAdvocateCoupon();

        (new Core())->createM2MReferral($refereeMerchant, $input);

        $originalData = [
            StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::REFERRED_COUNT               => 1,
            StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => 1,
            StoreConfigKey::REFEREE_ID                   => [$randomMerchant->getId()],
            StoreConfigKey::REFEREE_NAME                 => [$randomMerchant->getName()],
            StoreConfigKey::REFERRAL_AMOUNT              => 100
        ];

        (new StoreCore())->updateMerchantStore($referrerMerchant->getId(), $originalData, StoreConstants::INTERNAL);

        $this->ba->noAuth();

        $this->expectException(BadRequestException::class);
        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertNull($m2mReferral->getAttribute('referrer_status'));
        $this->assertEquals(M2MEntityStatus::MTU_EVENT_SENT, $m2mReferral->getAttribute('status'));
        $this->assertNotNull($m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals($referrerMerchant->getMerchantId(), $m2mReferral->getAttribute('referrer_id'));

        $repo = new Repository();

        $merchantPromotion = $repo->getByMerchantId(
            $refereeMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($refereeMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

        $merchantPromotion = $repo->getByMerchantId(
            $referrerMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertArraySubset($data, $originalData);

    }
    public function testRewardValidationFriendCouponApplyFailed()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');
        $randomMerchant   = $this->createMerchant('HucXhpLFHt8tQp');

        $input      = [
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT,
            M2MReferralEntity::REFERRER_ID => $referrerMerchant->getMerchantId(),
            M2MReferralEntity::METADATA    => [
                M2MConstants::REFERRAL_CODE => 'zawdfd8x',
                FBConstants::EMAIL          => $refereeMerchant->getEmail()
            ]
        ];
        $promotions = $this->mockAdvocateCoupon();

        (new Core())->createM2MReferral($refereeMerchant, $input);

        $this->ba->noAuth();

        $this->expectException(BadRequestException::class);
        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertNull($m2mReferral->getAttribute('referrer_status'));
        $this->assertEquals(M2MEntityStatus::MTU_EVENT_SENT, $m2mReferral->getAttribute('status'));
        $this->assertNotNull($m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals($referrerMerchant->getMerchantId(), $m2mReferral->getAttribute('referrer_id'));

        $repo = new Repository();

        $merchantPromotion = $repo->getByMerchantId(
            $refereeMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($refereeMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

        $merchantPromotion = $repo->getByMerchantId(
            $referrerMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $expectedData = [
            StoreConfigKey::REFERRED_COUNT               => 0,
            StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => 0,
            StoreConfigKey::REFEREE_NAME                 => [],
            StoreConfigKey::REFEREE_ID                   => [],
            StoreConfigKey::REFERRAL_AMOUNT              => 0,
            StoreConfigKey::REFERRAL_AMOUNT_CURRENCY     => 'INR'
        ];
        $data         = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data         = (new StoreCore())->fetchMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertArraySubset($data, $expectedData);

    }

    public function testRewardValidationAdvocateCouponApplyFailed()
    {
        $refereeMerchant  = $this->createMerchant('I0qYGdG9IGaVxz');
        $referrerMerchant = $this->createMerchant('Hm9Bv6kFufFS36');

        $input      = [
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT,
            M2MReferralEntity::REFERRER_ID => $referrerMerchant->getMerchantId(),
            M2MReferralEntity::METADATA    => [
                M2MConstants::REFERRAL_CODE => 'zawdfd8x',
                FBConstants::EMAIL          => $refereeMerchant->getEmail()
            ]
        ];
        $promotions = $this->mockFriendCoupon();

        (new Core())->createM2MReferral($refereeMerchant, $input);

        $this->ba->noAuth();

        $this->expectException(BadRequestException::class);
        $this->startTest();

        $m2mReferral = $this->getDbLastEntity('m2m_referral');
        $this->assertNull($m2mReferral->getAttribute('referrer_status'));
        $this->assertEquals(M2MEntityStatus::MTU_EVENT_SENT, $m2mReferral->getAttribute('status'));
        $this->assertNotNull($m2mReferral->getAttribute('referrer_id'));
        $this->assertEquals($referrerMerchant->getMerchantId(), $m2mReferral->getAttribute('referrer_id'));

        $repo = new Repository();

        $merchantPromotion = $repo->getByMerchantId(
            $refereeMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($refereeMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::IS_SIGNED_UP_REFEREE]);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);

        $merchantPromotion = $repo->getByMerchantId(
            $referrerMerchant->getId()
        );

        $this->assertEquals(0, $merchantPromotion->count());

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];
        $data = (new StoreCore())->fetchMerchantStore($referrerMerchant->getId(), $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNull($data[StoreConfigKey::REFERRAL_SUCCESS_POPUP_COUNT]);
    }

    public function testFetchReferralLinkWithDisabledFeature()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $promotions = $this->mockCoupon();

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testFetchReferralLinkWithEnabledFeature()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('feature', [
            'name'        => 'm2m_referral',
            'entity_id'   => $merchantDetail['merchant_id'],
            'entity_type' => 'merchant'
        ]);

        $promotions = $this->mockCoupon();

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->startTest();

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];

        $data = (new StoreCore())->fetchMerchantStore($merchantDetail['merchant_id'], $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNotNull($data[ConfigKey::REFERRAL_CODE]);
        $this->assertNotNull($data[ConfigKey::REFERRAL_LINK]);
    }

    public function testFetchReferralLinkFromStore()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('feature', [
            'name'        => 'm2m_referral',
            'entity_id'   => $merchantDetail['merchant_id'],
            'entity_type' => 'merchant'
        ]);
        $data = [
            StoreConstants::NAMESPACE     => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::REFERRAL_LINK => "https://fbuy.io/05c3a537-257c-48ae-a0f6-319bff3ac55f/x4skwckp?share=c4ea645a-b50d-44c4-8dea-a6ce8305b824",
            StoreConfigKey::REFERRAL_CODE => 'mg5xnqzn'
        ];

        (new StoreCore())->updateMerchantStore($merchantDetail['merchant_id'], $data, StoreConstants::INTERNAL);

        $promotions = $this->mockCoupon();

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->startTest();

        $data = [
            StoreConstants::NAMESPACE => StoreConfigKey::ONBOARDING_NAMESPACE
        ];

        $data = (new StoreCore())->fetchMerchantStore($merchantDetail['merchant_id'], $data, StoreConstants::INTERNAL);
        $this->assertNotNull($data);
        $this->assertNotNull($data[ConfigKey::REFERRAL_CODE]);
        $this->assertNotNull($data[ConfigKey::REFERRAL_LINK]);
    }

    public function testFetchReferralDetailsFromSignup()
    {
        $promotions = $this->mockCoupon();
        $this->ba->dashboardGuestAppAuth();
        $this->startTest();
    }
}
