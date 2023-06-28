<?php

namespace RZP\Tests\Functional\User;

use DB;
use App;
use Illuminate\Support\Facades\Bus;
use Mail;
use Hash;
use Queue;
use Config;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\User\BankingRole;
use RZP\Http\UserRolePermissionsMap;
use RZP\Jobs\NotifyRas;
use RZP\Error\ErrorCode;
use RZP\Mail\User\Otp;
use RZP\Models\Feature;
use RZP\Mail\User\Login;
use RZP\Models\User\Role;
use Razorpay\OAuth\Client;
use RZP\Constants\Product;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Admin;
use RZP\Models\Merchant\PurposeCode\PurposeCodeList;
use RZP\Models\User\Constants;
use RZP\Models\User\Entity;
use RZP\Mail\User\OtpSignup;
use RZP\Services\Mock\Raven;
use RZP\Services\RazorXClient;
use RZP\Services\HubspotClient;
use RZP\Services\SplitzService;
use RZP\Models\Admin\ConfigKey;
use RZP\Mail\User\PasswordReset;
use RZP\Models\Admin\Permission;
use RZP\Services\Mock\AuthToken;
use RZP\Services\VendorPortal\Service as VendorPortalService;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Error\PublicErrorCode;
use RZP\Services\SalesForceClient;
use RZP\Jobs\SubMerchantTaggingJob;
use Illuminate\Support\Facades\Redis;
use RZP\Models\BankingAccount\Channel;
use RZP\Exception\BadRequestException;
use RZP\Mail\User\AccountVerification;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Services\Segment\XSegmentClient;
use RZP\Models\Merchant\Attribute\Type;
use Illuminate\Database\Eloquent\Factory;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\User\Repository as UserRepo;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Feature\Constants as Features;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\User\Constants as UserConstants;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\TestsStorkServiceRequests;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Services\Dcs\Features\Service as DCSService;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Merchant\Partner\PartnerTest;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Tests\Functional\Fixtures\Entity\User as UserFixture;
use RZP\Models\Merchant\M2MReferral\Status as M2MEntityStatus;
use RZP\Models\Merchant\M2MReferral\Entity as M2MReferralEntity;
use RZP\Models\Merchant\MerchantUser\Entity as MerchantUserEntity;
use function GuzzleHttp\json_decode;

class UserTest extends TestCase
{
    use MocksSplitz;
    use PartnerTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use TestsStorkServiceRequests;

    protected $coreMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/UserTestData.php';

        parent::setUp();



        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->createAndFetchMocks();
    }

    public function testCreate()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRegister()
    {
        Mail::fake();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $this->startTest();

        $merchant = $this->getLastEntity('merchant', true);

        $row = DB::table('merchant_map')
                    ->where('merchant_id', '=', $merchant['id'])
                    ->where('entity_id', '=', $adminId)
                    ->where('entity_type', '=', 'admin')
                    ->first();

        $this->assertNotNull($row);
    }

    public function testRegisterRegularTestPartner()
    {
        Mail::fake();

        $this->mockDCS();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname",
                "merchant_type":"Regular Test Partner"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $this->startTest();

        $merchant = $this->getLastEntity('merchant', true);

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id'   => $merchant['id'],
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::ADMIN_LEAD_PARTNER, $featuresArray);
    }

    public function testRegisterRegularTestMerchant()
    {
        Mail::fake();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname",
                "merchant_type":"Regular Test Merchant"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $this->startTest();

        $merchant = $this->getLastEntity('merchant', true);

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id'   => $merchant['id'],
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::REGULAR_TEST_MERCHANT, $featuresArray);
    }

    public function testRegisterOptimiserMerchant()
    {
        Mail::fake();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname",
                "merchant_type":"Optimizer Only Merchant"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $this->startTest();

        $merchant = $this->getLastEntity('merchant', true);

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id'   => $merchant['id'],
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::OPTIMIZER_ONLY_MERCHANT, $featuresArray);
    }

    public function testRegisterDsMerchant()
    {
        Mail::fake();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname",
                "merchant_type":"DS Only Merchant"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $this->startTest();

        $merchant = $this->getLastEntity('merchant', true);

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id'   => $merchant['id'],
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::ONLY_DS, $featuresArray);
    }

    public function testRegisterWithDuplicateEmail()
    {
        $user = $this->fixtures->create('user', ['email' => 'hello123@c.com', 'password' => 'hello123']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    /**
     * given: linked account exists for an email without dashboard access
     * when: user tries to register as normal merchant with the same email
     * then: user should be able to register
     */
    public function testRegisterWithDuplicateEmailWhenLinkedAccountExistsWithoutDashboardAccess()
    {
        $existingLAMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingLAMerchant['email'];
        $this->testData[__FUNCTION__]['response']['content']['email'] = $existingLAMerchant['email'];

        $this->ba->dashboardGuestAppAuth();

        $newMerchant = $this->startTest();

        // the newly created user should have access only to the new merchant
        $users = DB::table('merchant_users')->where('merchant_id', '=', $newMerchant['id'])->get();
        $this->assertEquals(1, $users->count());

        // check that there are two merchants with the same email
        $merchants = DB::table('merchants')->where('email', '=', $existingLAMerchant['email'])->get();
        $this->assertEquals(2, $merchants->count());
    }

    /**
     * given: linked account exists for an email with dashboard access
     * when: user tries to register as normal merchant with the same email
     * then: user should not be able to register
     */
    public function testRegisterWithDuplicateEmailFailsWhenLinkedAccountExistsWithDashboardAccess()
    {
        $user = $this->fixtures->create('user', ['email' => 'test2@razorpay.com']);

        $existingLAMerchant = $this->fixtures->create('merchant', [
            'id' => '10000000000002',
            'email' => 'test2@razorpay.com',
            'parent_id' => '10000000000000'
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $existingLAMerchant['id'],
            'role'        => Role::LINKED_ACCOUNT_OWNER,
        ]);

        $this->testData[__FUNCTION__]['request']['content']['email'] = $existingLAMerchant['email'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testRegisterWithOauthPayload()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->app['config']->set('oauth.merchant_oauth_mock', true);

        $this->startTest();
    }

    public function testRegisterWithoutPassword()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testSignupSourceShowingUpInMerchantAfterRegistration()
    {
        //Given
        $this->ba->dashboardGuestAppAuth();

        //When
        $this->withHeader(RequestHeader::X_REQUEST_ORIGIN, "https://x.razorpay.com");
        $this->startTest();

        //Then
        /** @var MerchantEntity $merchant */
        $merchant = $this->getLastEntity('merchant', true);

        $this->assertEquals($merchant['signup_source'], "banking");
    }

    public function testPreSignupSourceInfoStoredAfterRegistrationForBanking()
    {
        $testDataToReplace = [
            'request' => [
                'cookies' => [
                    'rzp_utm' => json_encode([
                        'final_page' => 'razorpay.com/x/current-accounts/'
                    ])
                ]
            ]
        ];

        $this->ba->dashboardGuestAppAuth();
        $this->startTest($testDataToReplace);

        $merchantAttribute = $this->getDbEntity('merchant_attribute', [
            'type' => 'ca_page_visited',
        ]);

        $this->assertArraySelectiveEquals(
            [
                'product' => 'banking',
                'group'   => 'x_signup',
                'type'    => 'ca_page_visited',
                'value'   => '1'
            ],
            $merchantAttribute->toArrayPublic()
        );

        $featuresArray = $this->getDbEntity('feature',
                                                [
                                                    'entity_id' => $merchantAttribute->getMerchantId(),
                                                    'entity_type' => 'merchant'
                                                ])->pluck('name')->toArray();

        $this->assertContains(Features::NEW_BANKING_ERROR, $featuresArray);
    }

    public function testPreSignupCampaignInfoStoredAfterRegistrationForBanking()
    {
        $testDataToReplace = [
            'request' => [
                'cookies' => [
                    'rzp_utm' => json_encode([
                                                 'attributions' => [
                                                     [
                                                         Constants::UTM_SOURCE   => 'Facebook',
                                                         Constants::UTM_MEDIUM   => 'CPC',
                                                         Constants::UTM_CAMPAIGN => 'Facebook_RZPx_CA_Conv_NewAcquisItion_India_Owners_2555_MF_All_24082021'
                                                     ]
                                                 ]
                                             ])
                ]
            ]
        ];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testDataToReplace);

        $merchantAttribute = $this->getDbEntity('merchant_attribute', [
            'type' => Type::CAMPAIGN_TYPE,
        ]);

        $this->assertArraySelectiveEquals(
            [
                'product' => 'banking',
                'group'   => 'x_signup',
                'type'    => Type::CAMPAIGN_TYPE,
                'value'   => 'ca_neostone'
            ],
            $merchantAttribute->toArrayPublic()
        );

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id' => $merchantAttribute->getMerchantId(),
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::NEW_BANKING_ERROR, $featuresArray);
    }

    public function createEntitiesForProductSwitch()
    {
        $this->fixtures->on('live')->edit('terminal', 'BANKACC3DSN3DT',
            ['gateway_merchant_id' => '456456']);
        $this->fixtures->on('live')->edit('terminal', 'BANKACC3DSN3DZ',
            ['gateway_merchant_id' => '232323']);
        $this->fixtures->on('test')->edit('terminal', 'BANKACC3DSN3DT',
            ['gateway_merchant_id' => '456456']);
        $this->fixtures->on('test')->edit('terminal', 'BANKACC3DSN3DZ',
            ['gateway_merchant_id' => '232323']);
    }

    public function mockLookerEvent()
    {
        $diagMock = Mockery::mock('RZP\Services\DiagClient');

        $diagMock->shouldReceive('trackOnboardingEvent')->andReturn([]);
    }

    public function testProductSwitchToXForUserWithoutEmail()
    {
        $this->createEntitiesForProductSwitch();

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
            'id' => '2abcd000000000',
            'email' => null,
        ]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'activated'
            ]);

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => true, 'business_banking' => false, 'email' => null]);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $this->mockSalesforceEventTracked(true, false, true);

        $this->mockHubSpotClient('trackHubspotEvent',0);

        $this->mockLookerEvent();

        $this->startTest();

        $testBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'test');

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNotNull($testBankingAccount);

        $this->assertNull($liveBankingAccount);

    }

    public function testVerifyOTPForAddEmailInX()
    {
        $this->createEntitiesForProductSwitch();

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
            'id' => '2abcd000000000',
            'email' => null,
        ]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'activated'
            ]);

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => true, 'business_banking' => false,'email' => null, 'signup_source' => 'primary']);

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNull($liveBankingAccount);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id'], 'owner');

        $this->mockSalesforceEventTracked(true,true, false);

        $this->mockHubSpotClient('trackHubspotEvent');

        $this->mockLookerEvent();

        $this->startTest();

        $liveBankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ],
            'live');

        $this->assertNotNull($liveBankingAccount);
    }


    public function mockSalesforceEventTracked($captureInterestOfPrimaryMerchantInBanking = false, $afterEmailVerified = false, $sendProductSwitchDetails = false)
    {
        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['captureInterestOfPrimaryMerchantInBanking', 'sendProductSwitchDetails'])
            ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        if ($afterEmailVerified)
        {
            if ($captureInterestOfPrimaryMerchantInBanking == true)
            {
                $salesforceClientMock->expects($this->exactly(2))
                    ->method('captureInterestOfPrimaryMerchantInBanking');
            }
        }
        else
        {
            if ($captureInterestOfPrimaryMerchantInBanking == true)
            {
                $salesforceClientMock->expects($this->exactly(1))
                    ->method('captureInterestOfPrimaryMerchantInBanking');
            }

            if ($sendProductSwitchDetails == true)
            {
                $salesforceClientMock->expects($this->exactly(1))
                    ->method('sendProductSwitchDetails');
            }
        }
    }

    public function testPreSignupCampaignInfoStoredAfterRegistrationInSmallCap()
    {
        $testDataToReplace = [
            'request' => [
                'cookies' => [
                    'rzp_utm' => json_encode([
                                                 'attributions' => [
                                                     [
                                                         Constants::UTM_SOURCE   => 'Facebook',
                                                         Constants::UTM_MEDIUM   => 'CPC',
                                                         Constants::UTM_CAMPAIGN => 'facebook_RZPx_CA_Conv_NewAcquisItion_India_Entrepreneurship_2555_M_All_07092021'
                                                     ]
                                                 ]
                                             ])
                ]
            ]
        ];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testDataToReplace);

        $merchantAttribute = $this->getDbEntity('merchant_attribute', [
            'type' => Type::CAMPAIGN_TYPE,
        ]);

        $this->assertArraySelectiveEquals(
            [
                'product' => 'banking',
                'group'   => 'x_signup',
                'type'    => Type::CAMPAIGN_TYPE,
                'value'   => 'ca_neostone'
            ],
            $merchantAttribute->toArrayPublic()
        );

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id' => $merchantAttribute->getMerchantId(),
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::NEW_BANKING_ERROR, $featuresArray);
    }

    public function testPreSignupSourceInfoStoredForWebsiteAfterRegistrationForBanking()
    {
        $testDataToReplace = [
            'request' => [
                'cookies' => [
                    'rzp_utm' => json_encode([
                                                 'website' => 'razorpay.com/x/current-accounts/'
                                             ])
                ]
            ]
        ];

        $this->ba->dashboardGuestAppAuth();
        $this->startTest($testDataToReplace);

        $merchantAttribute = $this->getDbEntity('merchant_attribute', [
            'type' => 'ca_page_visited',
        ]);

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id' => $merchantAttribute->getMerchantId(),
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::NEW_BANKING_ERROR, $featuresArray);

        $this->assertArraySelectiveEquals(
            [
                'product' => 'banking',
                'group'   => 'x_signup',
                'type'    => 'ca_page_visited',
                'value'   => '1'
            ],
            $merchantAttribute->toArrayPublic()
        );
    }

    public function testPreSignupSourceInfoStoredAfterRegistrationForBankingWithExtraQuotesInCookie()
    {
        $testDataToReplace = [
            'request' => [
                'cookies' => [
                    'rzp_utm' => '"' . json_encode([
                        'final_page' => 'razorpay.com/x/current-accounts/'
                    ]) . '"'
                ]
            ]
        ];

        $this->ba->dashboardGuestAppAuth();
        $this->startTest($testDataToReplace);

        $merchantAttribute = $this->getDbEntity('merchant_attribute', [
            'type' => 'ca_page_visited',
        ]);

        $this->assertArraySelectiveEquals(
            [
                'product' => 'banking',
                'group'   => 'x_signup',
                'type'    => 'ca_page_visited',
                'value'   => '1'
            ],
            $merchantAttribute->toArrayPublic()
        );
    }

    public function testRegisterWithOtp()
    {
        Mail::fake();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $response = $this->startTest();

        $merchant = $this->getLastEntity('merchant', true);

        $row = DB::table('merchant_map')
                 ->where('merchant_id', '=', $merchant['id'])
                 ->where('entity_id', '=', $adminId)
                 ->where('entity_type', '=', 'admin')
                 ->first();

        $this->assertNotNull($row);

        $this->assertArrayHasKey('token', $response);
    }

    public function testRegisterForSignUpFlowInX()
    {
        Mail::fake();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $response = $this->startTest();

        $merchant = $this->getLastEntity('merchant', true);

        $featuresArray = $this->getDbEntity('feature',
                                            [
                                                'entity_id' => $merchant['id'],
                                                'entity_type' => 'merchant'
                                            ])->pluck('name')->toArray();

        $this->assertContains(Features::NEW_BANKING_ERROR, $featuresArray);

        $row = DB::table('merchant_map')
            ->where('merchant_id', '=', $merchant['id'])
            ->where('entity_id', '=', $adminId)
            ->where('entity_type', '=', 'admin')
            ->first();

        $this->assertNotNull($row);

        $this->assertArrayHasKey('token', $response);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('x_verify_email', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.razorpayx.otp_email_verify', $mail->view);

            $mailSubject = "Verify your Email for RazorpayX";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('x.support@razorpay.com', $mail->from[0]['address']);

            return true;
        });
    }

    protected function mockDCS()
    {
        $dcsMock = $this->getMockBuilder(DCSService::class)
                        ->setConstructorArgs([$this->app])
                        ->onlyMethods(['editFeature'])
                        ->getMock();

        $this->app->instance('dcs', $dcsMock);

        $dcsMock->expects($this->any())->method('editFeature')->willReturn(null);

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

    public function testGet()
    {
        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetInXWhenUserOnPg()
    {
        $user = $this->fixtures->create('user');

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $user->merchants()->get()[0]->getId(),
        ]);

        $this->fixtures->edit('merchant', $user->merchants()->get()[0]->getId(), [
            'activated' => true,
        ]);

        $this->disableRazorXTreatmentCAC();

        $this->fixtures->terminal->createRXTerminal();

        $this->testData[__FUNCTION__] = $this->testData['testGet'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $testData['request']['content']['product_switch'] = 'true';

        $testData['response']['content']['merchants'][0]['activated'] = true;

        $bankingMerchant = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user->getId())
            ->where('merchant_id', '=', $user->merchants()->get()[0]->getId())
            ->where('product', '=', 'banking')
            ->pluck('user_id','merchant_id', 'product');

        $this->assertEquals(count($bankingMerchant), 0);

        $this->assertBankingEntitiesNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNullInLiveMode($merchantDetail);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $bankingMerchant = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user->getId())
            ->where('merchant_id', '=', $user->merchants()->get()[0]->getId())
            ->where('product', '=', 'banking')
            ->pluck('user_id','merchant_id', 'product');

        $this->assertEquals(count($bankingMerchant), 1);

        $this->assertBankingEntitiesNotNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNotNullInLiveMode($merchantDetail);
    }

    public function testGetInXWhenUserOnPgAndAdminInX()
    {
        $user = $this->fixtures->create('user');

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $user->merchants()->get()[0]->getId(),
        ]);

        $this->fixtures->edit('merchant', $user->merchants()->get()[0]->getId(), [
            'activated' => true,
        ]);

        $this->disableRazorXTreatmentCAC();

        $this->fixtures->terminal->createRXTerminal();

        $this->testData[__FUNCTION__] = $this->testData['testGet'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $testData['request']['content']['product_switch'] = 'true';

        $testData['response']['content']['merchants'][0]['activated'] = true;

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $user->merchants()->get()[0]->getId(),
            'role'        => 'admin',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $bankingMerchant = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user->getId())
            ->where('merchant_id', '=', $user->merchants()->get()[0]->getId())
            ->where('product', '=', 'banking')
            ->pluck('user_id','merchant_id', 'product');

        $this->assertEquals(count($bankingMerchant), 1);

        $this->assertBankingEntitiesNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNullInLiveMode($merchantDetail);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $bankingMerchant = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user->getId())
            ->where('merchant_id', '=', $user->merchants()->get()[0]->getId())
            ->where('product', '=', 'banking')
            ->pluck('user_id','merchant_id', 'product');

        $this->assertEquals(count($bankingMerchant), 1);

        $this->assertBankingEntitiesNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNullInLiveMode($merchantDetail);
    }

    public function testGetInPgWhenUserOnX()
    {
        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner', 'live');

        $mappingData = [
            'merchant_id' => '10000000000000',
            'user_id'     => $user['id'],
            'role'        => 'owner',
            'product'     => 'banking'
        ];

        $this->fixtures->user->createUserMerchantMapping($mappingData);

        $this->disableRazorXTreatmentCAC();

        $this->testData[__FUNCTION__] = $this->testData['testGet'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['content']['product_switch'] = 'true';

        $bankingMerchant = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user->getId())
            ->where('merchant_id', '=', $user->merchants()->get()[0]->getId())
            ->where('product', '=', 'primary')
            ->pluck('user_id','merchant_id', 'product');

        $this->assertEquals(count($bankingMerchant), 0);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $bankingMerchant = DB::connection('test')->table('merchant_users')
            ->where('user_id', '=', $user->getId())
            ->where('merchant_id', '=', $user->merchants()->get()[0]->getId())
            ->where('product', '=', 'primary')
            ->pluck('user_id','merchant_id', 'product');

        $this->assertEquals(count($bankingMerchant), 1);
    }

    public function assertBankingEntitiesNotNullInTestMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'test');


        $this->assertNotNull($bankAccount);

        $entityId = $bankAccount['entity_id'];

        $bankAccountId = $bankAccount['id'];

        $virtualAccount = $this->getDbEntity('virtual_account',
            [
                'merchant_id'     => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'id'              => $entityId,
                'bank_account_id' => $bankAccountId
            ], 'test');

        $this->assertNotNull($virtualAccount);

        $balanceId = $virtualAccount['balance_id'];

        $balance = $this->getDbEntity('balance',
            [
                'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'         => 'banking',
                'account_type' => 'shared',
                'id'           => $balanceId
            ], 'test');

        $this->assertNotNull($balance);

        $accountNumber = $balance['account_number'];

        $bankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id'    => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'account_type'   => 'nodal',
                'account_number' => $accountNumber,
                'balance_id'     => $balanceId
            ], 'test');

        $this->assertNotNull($bankingAccount);
    }

    public function assertBankingEntitiesNotNullInLiveMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'live');


        $this->assertNotNull($bankAccount);

        $entityId = $bankAccount['entity_id'];

        $bankAccountId = $bankAccount['id'];

        $virtualAccount = $this->getDbEntity('virtual_account',
            [
                'merchant_id'     => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'id'              => $entityId,
                'bank_account_id' => $bankAccountId
            ], 'live');

        $this->assertNotNull($virtualAccount);

        $balanceId = $virtualAccount['balance_id'];

        $balance = $this->getDbEntity('balance',
            [
                'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'         => 'banking',
                'account_type' => 'shared',
                'id'           => $balanceId
            ], 'live');

        $this->assertNotNull($balance);

        $accountNumber = $balance['account_number'];

        $bankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id'    => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'account_type'   => 'nodal',
                'account_number' => $accountNumber,
                'balance_id'     => $balanceId
            ], 'live');

        $this->assertNotNull($bankingAccount);
    }

    public function assertBankingEntitiesNullInTestMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'test');


        $this->assertNull($bankAccount);
    }

    public function assertBankingEntitiesNullInLiveMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'live');


        $this->assertNull($bankAccount);
    }

    private function mockRazorxWith(string $featureUnderTest, string $value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')->will(
            $this->returnCallback(
                function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                {
                    return $feature === $featureUnderTest ? $value : 'control';
                }
            ));
    }

    /*
    * when experiment in enabled
    * */
    /*public function testFetchUserPermissions_experimentEnable()
    {
        $this->mockRazorxWith(RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_ENABLED, 'on');

        $this->fixtures->create('role_access_policy_map',
            [
                'role_id' => 'admin',
                'authz_roles'   => ['admin'],
                'access_policy_ids' => ['accessPolicy10', 'accessPolicy11', 'accessPolicy13'],
            ]);

        $merchant = [
            'id'                    => '1cXSLlUU8V9sXl',
            'banking_role'          =>  'admin',
        ];
        $r = new \ReflectionMethod('RZP\Models\User\Core', 'fetchUserPermissions');

        $r->setAccessible(true);

        $response = $r->invoke($this->coreMock, $merchant);

        $this->assertEquals(["payout_create", "view_payout"], $response );
    }*/

    public function testGetActorInfo()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/actor_info_internal/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['headers']['X-Razorpay-Account'] = $merchant->getId();

        $this->ba->appAuthTest($this->config['applications.payouts_service.secret']);

        $this->startTest();
    }

    public function testProductEnabledPresenceInResponseForTrueCase()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->disableRazorXTreatmentCAC();

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => $merchant->getId(),
                'product'     => 'banking',
                'group'       => 'products_enabled',
                'type'        => 'X',
                'value'       => 'true'
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testProductEnabledPresenceInResponseForFalseCase()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->disableRazorXTreatmentCAC();

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => $merchant->getId(),
                'product'     => 'banking',
                'group'       => 'products_enabled',
                'type'        => 'X',
                'value'       => 'false'
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetAfterStoringPreSignUpSourceInfo()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->disableRazorXTreatmentCAC();

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => $merchant->getId(),
                'product'     => 'banking',
                'group'       => 'x_signup',
                'type'        => 'ca_page_visited',
                'value'       => 'true'
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetForPartnerHavingConfigs()
    {
        $user = $this->fixtures->create('user');

        $merchantId = $user->merchants->first()->getId();

        $this->fixtures->merchant->edit($merchantId, ['partner_type' => 'pure_platform']);

        $application = $this->createOAuthApplication(
            [
                'id'          => 'CSupbmEglqZkL9',
                'merchant_id' => $merchantId,
            ]
        );

        $this->createConfigForPartnerApp($application->getId());

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testLogin()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'browser_details'       => ['device' => 'Web', 'browser' => 'Chrome', 'os' => 'Windows 7']
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        Mail::assertQueued(Login::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('orgHostname', $viewData);
            $this->assertArrayHasKey('browserDetails', $viewData);
            $this->assertArrayHasKey('loginAt', $viewData);
            $this->assertEquals('emails.user.login', $mail->view);

            return true;
        });

        $this->assertFalse(isset($response['invitations']));
        $this->assertFalse(isset($response['settings']));
        $this->assertFalse(isset($response['merchants'][0]['methods']));
    }

    public function testLoginWithCrossOrgLoginDisable()
    {
        Mail::fake();


        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $this->fixtures->create('org', [
            OrgEntity::ID                          => '100000tazorpay',
        ]);

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000tazorpay',
        ]);

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'browser_details'       => ['device' => 'Web', 'browser' => 'Chrome', 'os' => 'Windows 7']
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();
    }

    public function testLoginWithCrossOrgLoginEnable()
    {
        Mail::fake();


        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $this->fixtures->create('org', [
            OrgEntity::ID                          => '100000tazorpay',
        ]);

        $merchant = $this->fixtures->create('merchant', [
            MerchantEntity::ORG_ID => '100000tazorpay',
        ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::CROSS_ORG_LOGIN], $merchant->getId());

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'browser_details'       => ['device' => 'Web', 'browser' => 'Chrome', 'os' => 'Windows 7']
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        // Core Mocking Partial
        $this->coreMock = Mockery::mock('RZP\Models\User\Core', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    public function setAuthServiceMockForGetApplicationForMobileApp($merchantId)
    {
        $this->authServiceMock
            ->expects($this->at(0))
            ->method('sendRequest')
            ->with('applications', 'GET',[
                'type'        => 'mobile_app',
                'merchant_id' => $merchantId
            ])
            ->willReturn([
                'items' => [
                ]
            ]);
    }

    public function setAuthServiceMockForGetApplicationForMobileAppForSwitchMerchant()
    {
        $this->authServiceMock
            ->expects($this->at(0))
            ->method('sendRequest')
            ->with('applications', 'GET',[
                'type'        => 'mobile_app',
                'merchant_id' => '10000000000000'
            ])
            ->willReturn([
                'items' => [
                    [
                        'id'                => 'appidfigorithi',
                        'type'              => 'mobile_app',
                        'client_details'    =>  [
                            'prod'   =>  [
                                'id'     =>  'client_id',
                                'secret' =>  'client_secret',
                            ]
                        ]
                    ]
                ]
            ]);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('applications', 'GET',[
                'type'        => 'mobile_app',
                'merchant_id' => '20000000000000'
            ])
            ->willReturn([
                'items' => [
                    [
                        'id'                => 'appidfigorithi',
                        'type'              => 'mobile_app',
                        'client_details'    =>  [
                            'prod'   =>  [
                                'id'     =>  'client_id',
                                'secret' =>  'client_secret',
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function setAuthServiceMockForPostApplicationForMobileApp($merchantId)
    {
        $this->authServiceMock
            ->expects($this->at(1))
            ->method('sendRequest')
            ->with('applications', 'POST',[
                'name'        => 'RX Mobile',
                'website'     => 'https://www.razorpay.com/x',
                'type'        => 'mobile_app',
                'merchant_id' => $merchantId
            ])
            ->willReturn([
                'id'                => 'appidfigorithi',
                'type'              => 'mobile_app',
                'client_details'    =>  [
                    'prod'   =>  [
                        'id'     =>  'client_id',
                        'secret' =>  'client_secret',
                    ]
                ]
            ]);
    }

    public function setAuthServiceMockForPostTokenForMobileApp($userId, $index = 2)
    {
        $this->authServiceMock
            ->expects($this->at($index))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'mobile_app_client_credentials',
                'scope'                 => 'x_mobile_app',
                'mode'                  => 'live',
                'user_id'               => $userId
            ])
            ->willReturn([
                'public_token'       => 'rzp_test_oauth_10000000000000',
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'access_token',
                'refresh_token'      => 'refresh_token',
                'client_id'          => 'client_id',
            ]);
    }

    public function setAuthServiceMockForRevokeToken($index = 1)
    {
        $this->authServiceMock
            ->expects($this->at($index))
            ->method('sendRequest')
            ->with('revoke','POST',[
                'client_id'          => 'client_id',
                'client_secret'      => 'client_secret',
                'token_type_hint'    => 'access_token',
                'token'              => 'token',
            ])
            ->willReturn([
                'message' => 'Token Revoked'
            ]);
    }

    public function testLoginForMobileOauth()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $merchantId = $merchant->getId();

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostTokenForMobileApp($user->getId());

        $this->startTest();
    }

    public function testLoginForMobileOauthWithError()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $merchantId = $merchant->getId();

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->startTest();
    }

    public function testOauthLogout()
    {
        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp('10000000000000');

        $this->setAuthServiceMockForPostApplicationForMobileApp('10000000000000');

        $this->setAuthServiceMockForRevokeToken(2);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOauthSwitchMerchant()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '20000000000000']);

        $mappingData = [
            'user_id'     => User::MERCHANT_USER_ID,
            'merchant_id' => $merchant->getId(),
            'role'        => 'admin',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileAppForSwitchMerchant();

        $this->setAuthServiceMockForRevokeToken();

        $this->setAuthServiceMockForPostTokenForMobileApp(User::MERCHANT_USER_ID, 3);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testRefreshTokenForMobileOAuth()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $merchantId = $merchant->getId();

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'refresh_token'        => 'test_refresh_token',
            'merchant_id'          => $merchant->getId(),
            'client_id'            => 'test_client_id',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->authServiceMock
            ->expects($this->at(0))
            ->method('sendRequest')
            ->with('applications', 'GET',[
                'type'        => 'mobile_app',
                'merchant_id' => $merchantId
            ])
            ->willReturn([
                'items' => [
                    [
                        'type'              => 'mobile_app',
                        'client_details'    =>  [
                            'prod'   =>  [
                                'id'     =>  'test_client_id',
                                'secret' =>  'test_client_secret',
                            ]
                        ]
                    ]
                ]
            ]);

        $this->authServiceMock
            ->expects($this->at(1))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'test_client_id',
                'client_secret'         => 'test_client_secret',
                'grant_type'            => 'mobile_app_refresh_token',
                'refresh_token'         => 'test_refresh_token',
            ])
            ->willReturn([
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'new_access_token',
                'refresh_token'      => 'updated_refresh_token',
            ]);

        $this->startTest();
    }

    public function testRefreshTokenForMobileOAuthWithInvalidClientId()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $merchantId = $merchant->getId();

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'refresh_token'        => 'test_refresh_token',
            'merchant_id'          => $merchant->getId(),
            'client_id'            => 'test_client_id',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->authServiceMock
            ->expects($this->once())
            ->method('sendRequest')
            ->with('applications', 'GET',[
                'type'        => 'mobile_app',
                'merchant_id' => $merchantId
            ])
            ->willReturn([
                'items' => [
                    [
                        'type'              => 'mobile_app',
                        'client_details'    =>  [
                            'prod'   =>  [
                                'id'     =>  'wrong_client_id',
                                'secret' =>  'test_client_secret',
                            ]
                        ]
                    ]
                ]
            ]);

        $this->startTest();
    }

    protected function testRefreshTokenForMobileOAuthWithEmptyParams(string $emptyParamKey)
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $merchantId = $merchant->getId();

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'refresh_token'        => 'refresh_token',
            'merchant_id'          => $merchantId,
            'client_id'            => 'test_client_id',
        ];

        $content[$emptyParamKey] = "";

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testRefreshTokenForMobileOAuthWithEmptyRefreshToken()
    {
        $this->testRefreshTokenForMobileOAuthWithEmptyParams('refresh_token');
    }

    public function testRefreshTokenForMobileOAuthWithEmptyMerchantId()
    {
        $this->testRefreshTokenForMobileOAuthWithEmptyParams('merchant_id');
    }

    public function testRefreshTokenForMobileOAuthWithEmptyClientId()
    {
        $this->testRefreshTokenForMobileOAuthWithEmptyParams('client_id');
    }

    public function testSegmentEventLogin(){

        $xsegmentMock = $this->getMockBuilder(XSegmentClient::class)
            ->setMethods(['pushIdentifyandTrackEvent'])
            ->getMock();

        $dcsMock = $this->getMockBuilder(DCSService::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['fetchEntityIdsByFeatureName'])
            ->getMock();

        $this->app->instance('x-segment', $xsegmentMock);
        $this->app->instance('dcs', $dcsMock);

        $dcsMock->expects($this->exactly(2))
            ->method('fetchEntityIdsByFeatureName')
            ->willReturn([]);

        $xsegmentMock->expects($this->exactly(1))
            ->method('pushIdentifyandTrackEvent')
            ->willReturn(true);

        $this->testLogin();
    }

    public function testMobileLoginWithPassword()
    {
        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        return $this->startTest();
    }

    public function testMobileLoginWithPasswordForXReturnsOtpAuthToken()
    {
        $testData = & $this->testData['testMobileLoginWithPassword'];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $response = $this->testMobileLoginWithPassword();
        $this->assertNotNull($response['otp_auth_token']);

    }

    public function testFailedLoginForMobileOAuth()
    {
        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello1234',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin()
    {
        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello1234',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testSendUserDetailsToSalesforce()
    {
        $methodName = 'sendUserDetailsToSalesforce';

        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        $salesforceClientMock->expects($this->exactly(1))->method($methodName);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }


    public function testMobileFailedLoginWrongPassword()
    {
        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello1234',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileFailedLogin()
    {
        $user = $this->fixtures->create('user', ['contact_mobile' => '0123457689', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileFailedLoginWithPasswordMultipleAccounts()
    {
        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);
        $user2 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello1234',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }


    public function testCaptchaBypassForDemoUserInX()
    {
        $user = $this->fixtures->create('user', ['email' => UserConstants::BANKING_DEMO_USER_EMAILS[0], 'password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha'               => 'foo',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['headers']['X-Request-Origin'] = config('applications.banking_service_url');

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileOtpLogin()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testMobileOtpLoginSkipVerificationLimitOnStage()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
                          ->setConstructorArgs([$this->app])
                          ->setMethods(['generateOtp'])
                          ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
                           ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'   => '9012345678',
            'skip_sms_request' => true
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testMobileOtpLoginStorkFailed()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $storkMock->shouldReceive('sendSms')->andThrow(new BadRequestException(
            ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED
        ));

        $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

    }

    public function testMobileOtpLoginForX()
    {
        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->enableRazorXTreatmentForRazorX();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testMobileOtpLoginWithPasswordMultipleAccounts()
    {
        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);
        $user2 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileOtpLoginWithPasswordWithNoAccounts()
    {
        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '1234567890',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileOtpLoginMultipleAccountsAssociatedWithCountryCode()
    {
        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);
        $this->fixtures->create('user', ['contact_mobile' => '+91'. $user1['contact_mobile'], 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData['testMobileOtpLoginWithPasswordMultipleAccounts'];

        $content = [
            'contact_mobile'        => $user1['contact_mobile'],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);
    }

    public function testMobileOtpLoginUserUnverified()
    {
        Mail::fake();

        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'contact_mobile_verified' => false]);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'                 => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response=$this->startTest();
    }

    public function testMobileLoginVerifyOtp()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'contact_mobile_verified' => true]);

        $this->ba->dashboardGuestAppAuth();

        return $this->startTest();
    }

    public function testMobileLoginVerifyOtpForX()
    {
        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->enableRazorXTreatmentForRazorX();
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'contact_mobile_verified' => true]);

        $this->ba->dashboardGuestAppAuth();

        return $this->startTest();
    }

    public function testVerifyCapitalReferralDuringMobileLogin()
    {
        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->enableRazorXTreatmentForRazorX();
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'id'    => "FL0nl7kME8j3Dd",
            'contact_mobile' => '9012345678',
            'contact_mobile_verified'  => true
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller'], true);

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => '10000000000000',
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => '10000000000000',
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $this->mockCapitalPartnershipSplitzExperiment();

        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockCreateApplicationRequestOnLOSService($losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        $this->mockGetNoApplicationRequestOnLOSService($losServiceMock);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test')
            ->toArray();

        $this->assertSame($merchant->getId(), $merchantAccessMap['merchant_id']);

        $this->assertSame('10000000000000', $merchantAccessMap['entity_owner_id']);

        $this->assertContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testNonCapitalReferralDuringMobileLogin()
    {
        $testData = & $this->testData['testVerifyCapitalReferralDuringMobileLogin'];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->enableRazorXTreatmentForRazorX();
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'contact_mobile_verified' => true]);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'primary']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());

        $losServiceMock->shouldNotReceive('sendRequest')
            ->with(
                MerchantConstants::CREATE_CAPITAL_APPLICATION_LOS_URL,
                Mockery::type('array'),
                Mockery::type('array')
            );
    }

    public function testMobileLoginVerifyOtpNoUserMobile()
    {
        $this->ba->dashboardGuestAppAuth();

        return $this->startTest();
    }

    public function testMobileLoginVerifyOtpNoUserEmail()
    {
        $this->ba->dashboardGuestAppAuth();

        return $this->startTest();
    }

    public function testMobileLoginVerifyOtpAccountLocked()
    {
        $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'contact_mobile_verified' => true, 'account_locked' => true]);

        $this->ba->dashboardGuestAppAuth();

        return $this->startTest();
    }

    public function testMobileLoginVerifyOtpForMobileOAuth()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->disableRazorXTreatmentCAC();

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create('user',['contact_mobile' => '9012345678', 'contact_mobile_verified' => true]);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $merchantId = $merchant->getId();

        $this->ba->dashboardGuestAppAuth();

        $testData = &$this->testData[__FUNCTION__];

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostTokenForMobileApp($user->getId());

        return $this->startTest();
    }

    public function testMobileVerifyOtpForXWithNewSmsTemplate()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('user', [
            'id'                      => '10000000000000',
            'password'                => 'hello123',
            'contact_mobile'          => '+919999999999',
            'contact_mobile_verified' => true,
        ]);

        $this->ba->appAuth();

        $smsPayload = [
            'success'    => true,
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => 'user_id:x_login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp')->with([
            'receiver'  => '+919999999999',
            'context'   => '10000000000000:x_login_otp:10000000000000',
            'source'    => 'api.user.x_login_otp',
            'otp'       => '0007'
        ])->willReturn($smsPayload);

        $this->startTest();
    }

    public function testMobileLoginVerifyOtpForXReturnsOtpAuthToken()
    {
        $testData = & $this->testData['testMobileLoginVerifyOtp'];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $response = $this->testMobileLoginVerifyOtp();

        $this->assertNotEmpty($response['otp_auth_token']);
    }

    public function testMailOtpLogin()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user');

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response=$this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('login_otp', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.otp_login', $mail->view);

            return true;
        });
    }

    public function testEmailLoginOtpSendThresholdExceeded()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user');

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
        ];

        $testData['request']['content'] = $content;

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['email'].'_login_otp_send_count', Constants::EMAIL_LOGIN_OTP_SEND_THRESHOLD);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $redis->del($user['email'].'_login_otp_send_count');

    }

    public function testEmailVerificationOtpSendThresholdExceeded()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:verify_user:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['email' => 'hello123@gmail.com', 'password' => 'hello123', 'confirm_token' => '0123456789']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['email'].'_verification_otp_send_count', Constants::EMAIL_VERIFICATION_OTP_SEND_THRESHOLD);

        $this->startTest();

        $redis->del($user['email'].'_verification_otp_send_count');
    }

    public function testLoginOtpVerificationThresholdCounter()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user');

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'token'                 => 'Gvt61zZ3Iwzcqy',
            'otp'                   => '0008',
            'captcha'               => 'faked',
        ];

        $testData['request']['content'] = $content;

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['email'].'_login_otp_verification_count', 1);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $count = $redis->get($user['email'].'_login_otp_verification_count');
        self::assertEquals(2, $count);
        $redis->del($user['email'].'_login_otp_verification_count');


    }

    public function testLoginOtpVerificationThresholdExceeded()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user');

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'token'                 => 'Gvt61zZ3Iwzcqy',
            'otp'                   => '0008',
            'captcha'               => 'faked',
        ];

        $testData['request']['content'] = $content;

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['email'].'_login_otp_verification_count', Constants::LOGIN_OTP_VERIFICATION_THRESHOLD);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $redis->del($user['email'].'_login_otp_verification_count');

    }

    public function testVerificationOtpVerificationThresholdCounter()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['confirm_token'=>'non-null-value']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'token'                 => 'Gvt61zZ3Iwzcqy',
            'otp'                   => '0008',
            'captcha'               => 'faked',
        ];

        $testData['request']['content'] = $content;

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['email'].'_verification_otp_verification_count', 1);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $count = $redis->get($user['email'].'_verification_otp_verification_count');
        self::assertEquals(2, $count);
        $redis->del($user['email'].'_verification_otp_verification_count');


    }

    public function testVerificationOtpVerificationThresholdExceeded()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['confirm_token'=>'non-null-value']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'token'                 => 'Gvt61zZ3Iwzcqy',
            'otp'                   => '0008',
            'captcha'               => 'faked',
        ];

        $testData['request']['content'] = $content;

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['email'].'_verification_otp_verification_count', Constants::VERIFICATION_OTP_VERIFICATION_THRESHOLD);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $redis->del($user['email'].'_verification_otp_verification_count');
    }

    public function testMailVerifyOtp()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create('user', ['email' => 'a@gmail.com']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMailSignupVerifyOtp()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMailExistsSignupVerifyOtp()
    {
        $this->fixtures->create('user', ['email' => 'abracadabra@gmail.com']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileExistsSignupVerifyOtp()
    {
        $this->fixtures->create('user', ['contact_mobile' => '+919866077649']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMailOtpLoginUserUnverified()
    {
        Mail::fake();

        $user = $this->fixtures->create('user', ['confirm_token' => '0123456789']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response=$this->startTest();
    }

    public function testMailOtpLoginNoAccount()
    {
        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => 'abracadabra@gmail.com',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMailSendVerificationOtp()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:verify_user:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['email' => 'hello123@gmail.com', 'password' => 'hello123', 'confirm_token' => '0123456789']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response=$this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('verify_user', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.verify_user', $mail->view);

            return true;
        });
    }

    public function testMailSendVerificationOtpVerifiedUser()
    {
        Mail::fake();

        $user = $this->fixtures->create('user', ['email' => 'hello123@gmail.com', 'password' => 'hello123']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response=$this->startTest();
    }

    public function testMailSendVerificationOtpWrongPassword()
    {
        $user = $this->fixtures->create('user', ['password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello1234',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileSendVerificationOtp()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:verify_user:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['contact_mobile' => '1234567890', 'password' => 'hello123', 'contact_mobile_verified' => false]);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '1234567890',
            'password'              => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testMobileResendVerificationOtp()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:verify_user:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['contact_mobile' => '1234567890', 'password' => 'hello123', 'contact_mobile_verified' => false]);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '1234567890',
            'password'              => 'hello123',
            'token'                 => 'BUIj3m2Nx2VvVj'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testMailResendVerificationOtp()
    {
        Mail::fake();

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:verify_user:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['email' => 'hello123@gmail.com', 'password' => 'hello123', 'confirm_token' => '0123456789']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'token'                 => 'BUIj3m2Nx2VvVj'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response=$this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('verify_user', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.verify_user', $mail->view);

            return true;
        });
    }

    public function testMobileSendVerificationOtpVerifiedUser()
    {
        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true, 'confirm_token' => null]);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response=$this->startTest();
    }

    public function testMobileSendVerificationOtpWrongPassword()
    {
        $user = $this->fixtures->create('user', ['contact_mobile'        => '9012345678', 'password' => 'hello123']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello1234',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileSendVerificationOtpForXWithNewSmsTemplateAndSendViaStork()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('user', [
            'email'                   => 'user@domain.com',
            'password'                => 'hello123',
            'contact_mobile'          => '+919999999999',
        ]);

        $this->ba->dashboardGuestAppAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.x_verify_user', '+919999999999', []);

        $this->startTest();
    }

    public function testMobileSendVerificationOtpMultipleAccounts()
    {
        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);
        $user2 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileSendVerificationOtpMultipleAccountsAssociatedWithCountryCodes()
    {
        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);
        $this->fixtures->create('user', ['contact_mobile' => '+91'. $user1['contact_mobile'], 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData['testMobileSendVerificationOtpMultipleAccounts'];

        $content = [
            'contact_mobile'        => $user1['contact_mobile'],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);
    }

    public function testVerificationMailVerifyOtp()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create('user', ['email' => 'a@gmail.com', 'password' => 'hello123', 'confirm_token' => '0123456789']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user',  $user['id']);

        $this->assertTrue($user->getConfirmedAttribute());
    }

    public function testVerificationMobileVerifyOtpForXWithNewSmsTemplate()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('user', [
            'id'                      => '10000000000000',
            'password'                => 'hello123',
            'contact_mobile'          => '+919999999999',
        ]);

        $this->ba->appAuth();

        $smsPayload = [
            'success'    => true,
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => 'user_id:x_verify_user:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp')->with([
            'receiver'  => '+919999999999',
            'context'   => '10000000000000:x_verify_user:10000000000000',
            'source'    => 'api.user.x_verify_user',
            'otp'       => '0007'
        ])->willReturn($smsPayload);

        $this->startTest();
    }

    public function testVerificationMobileVerifyOtp()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'contact_mobile_verified' => false]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', $user['id']);

        $this->assertTrue($user->isContactMobileVerified()===true);
    }

    public function testMobileResendOtpLogin()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'token'                 => 'BUIj3m2Nx2VvVj'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testMailResendOtpLogin()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:login_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $user = $this->fixtures->create('user', ['email' => 'a@gmail.com']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => 'a@gmail.com',
            'token'                 => 'BUIj3m2Nx2VvVj'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testOauthCreateWithUserRegisterPayload()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLoginWithOauthPayload()
    {
        $user = $this->fixtures->create('user', ['email' => 'hello123@gmail.com', 'password' => 'hello123']);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testOauthCreate()
    {
        Mail::fake();

        $adminId = Org::MAKER_ADMIN;

        $formData = json_decode(
            '{
                "merchant_name":"name",
                "contact_name":"contact",
                "contact_email":"leademail@razorpay.com",
                "dba_name":"dbaname"
            }',
            true
        );

        $adminLead = $this->fixtures->create('admin_lead', ['admin_id' => $adminId, 'form_data' => $formData]);


        $this->mockHubSpotClient('trackSignupEvent', 3);

        $this->ba->dashboardGuestAppAuth();

        $testData = $this->testData['testOauthCreateWithMissingIdToken'];
        $this->runRequestResponseFlow($testData);

        $this->app['config']->set('oauth.merchant_oauth_mock', false);
        $testData = $this->testData['testOauthCreateWithInvalidIdToken'];
        $this->runRequestResponseFlow($testData);

        $this->app['config']->set('oauth.merchant_oauth_mock', true);
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content']['merchant_invitation'] = $adminLead['token'];
        $this->runRequestResponseFlow($testData);

        $merchant = $this->getLastEntity('merchant', true);

        $row = DB::table('merchant_map')
                 ->where('merchant_id', '=', $merchant['id'])
                 ->where('entity_id', '=', $adminId)
                 ->where('entity_type', '=', 'admin')
                 ->first();

        $this->assertNotNull($row);
    }

    public function testOauthLogin()
    {
        // user already exists with confirmed password
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->dashboardGuestAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testOauthLoginWithMissingIdToken'];

        $this->runRequestResponseFlow($testData);

        $this->app['config']->set('oauth.merchant_oauth_mock', false);
        $testData = $this->testData['testOauthLoginWithInvalidIdToken'];
        $this->runRequestResponseFlow($testData);
    }

    public function testOauthLoginMobileSignupEmailNotConfirmed()
    {
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'contact_mobile' => '9876543210',
            'confirm_token'  => '0123456789',
            'signup_via_email' => 0,
            'password' => 'hello123',
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $user = $this->getDbEntity('user', ['id' => 'FL0nl7kME8j3Dd']);

        $this->assertNotNull($user->getContactMobile());
    }

    public function testOauthLoginEmailSignupEmailNotConfirmed()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'contact_mobile' => '9876543210',
            'confirm_token'  => '0123456789',
            'signup_via_email' => 1,
            'password' => 'hello123'
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'        => $merchant->getId(),
            'business_name'      => $merchant['name'],
            'contact_name'       => $merchant['name'],
            'business_type'      => '1',
            'transaction_volume' => '1',
            'contact_mobile'     => '9999999999'
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $user = $this->getDbLastEntity('user');

        $this->assertNull($user->getContactMobile());
    }

    public function testNonCapitalReferralWithOauthLogin()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'contact_mobile' => '9876543210',
            'confirm_token'  => '0123456789',
            'signup_via_email' => 1,
            'password' => 'hello123'
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'        => $merchant->getId(),
            'business_name'      => $merchant['name'],
            'contact_name'       => $merchant['name'],
            'business_type'      => '1',
            'transaction_volume' => '1',
            'contact_mobile'     => '9999999999'
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'primary']);

        $testData =   &$this->testData['testCapitalReferralWithOauthLogin'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testCapitalReferralWithOauthLogin()
    {
        $this->app['basicauth']->setRequestOriginProduct(Product::BANKING);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'contact_mobile' => '9876543210',
            'confirm_token'  => null,
            'signup_via_email' => 1,
            'password' => 'hello123'
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'        => $merchant->getId(),
            'business_name'      => $merchant['name'],
            'contact_name'       => $merchant['name'],
            'business_type'      => '1',
            'transaction_volume' => '1',
            'contact_mobile'     => '9999999999'
        ]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller'], true);

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => '10000000000000',
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => '10000000000000',
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->mockCapitalPartnershipSplitzExperiment();

        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockCreateApplicationRequestOnLOSService($losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        $this->mockGetNoApplicationRequestOnLOSService($losServiceMock);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $this->assertContains('Ref-' . '10000000000000', $merchant->tagNames());

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test')
            ->toArray();

        $this->assertSame($merchant->getId(), $merchantAccessMap['merchant_id']);

        $this->assertSame('10000000000000', $merchantAccessMap['entity_owner_id']);
    }

    public function testOauthLoginForDifferentSource()
    {
        // user already exists with confirmed password
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }


    public function testOauthLoginForMobileOAuth()
    {
        // user already exists with confirmed password
        $user = $this->fixtures->create('user',[
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $merchantId = $merchant->getId();

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => $user['email'],
            'oauth_provider'        => "[\"google\"]",
            'id_token'              => 'valid id token'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostTokenForMobileApp($user->getId());

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testOauthLoginForSourceAsXAndroid()
    {
        // user already exists with confirmed password
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testOauthLoginForSourceAsXIos()
    {
        // user already exists with confirmed password
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    /**
     * As oauth valid it will get precedence and give the user details
     * as oauth payload is coming from dashboard backend
     * we already have an extra layer of security
     * So it should prevent malicious attack
     */
    public function testOauthLoginSuccessPasswordAndOauthBothPresent()
    {
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->dashboardGuestAppAuth();


        $this->startTest();
    }

    public function testOauthLoginInvalidatePassword()
    {
        $this->fixtures->create('user', ['id' => 'FL0nl7kME8j3Dd', 'email' => 'hello123@gmail.com', 'password' => 'hello123', 'confirm_token' => 'confirm_token']);

        $testData =   &$this->testData[__FUNCTION__];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        // check if password is set as null

        $user = $this->getDbEntityById('user', 'FL0nl7kME8j3Dd');

        $this->assertEmpty($user['password']);
    }

    public function testOauthLoginSessionInvalidate()
    {
        $this->fixtures->create('user', ['id' => 'FL0nl7kME8j3Dd', 'email' => 'hello123@gmail.com', 'password' => 'hello123', 'confirm_token' => 'confirm_token']);

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertEquals(true, $response['invalidate_sessions']);
    }

    public function testOauthLoginSessionNotInvalidate()
    {
        $this->fixtures->create('user', ['id' => 'FL0nl7kME8j3Dd', 'email' => 'hello123@gmail.com', 'password' => 'hello123']);

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        // for confirmed user invalidate_sessions will be set to false;
        $this->assertEquals(false, $response['invalidate_sessions']);
    }

    public function testOauthLoginInvalidateContactDetails()
    {
        $user = $this->fixtures->create('user', ['id'             => 'FL0nl7kME8j3Dd',
                                                 'email'          => 'hello123@gmail.com',
                                                 'password'       => 'hello123',
                                                 'contact_mobile' => '9999999999',
                                                 'confirm_token'  => 'confirm_token']);

        $merchant = $user->getMerchantEntity();

        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   ['merchant_id'        => $merchant->getId(),
                                                    'business_name'      => $merchant['name'],
                                                    'contact_name'       => $merchant['name'],
                                                    'business_type'      => '1',
                                                    'transaction_volume' => '1',
                                                    'contact_mobile'     => '9999999999']);

        $testData =   &$this->testData['testOauthLoginInvalidatePassword'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        // check if password is set as null

        $user = $this->getDbEntityById('user', 'FL0nl7kME8j3Dd');

        $this->assertEmpty($user['password']);

        $merchant = $this->getDbEntityById('merchant', $merchant->getId());

        // check if name is set as empty
        $this->assertEquals('', $merchant['name']);
        $this->assertEquals('', $user['name']);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        // check if contact details are set as null.
        $this->assertEmpty($merchantDetails['business_name']);
        $this->assertEmpty($merchantDetails['contact_name']);
        $this->assertEmpty($merchantDetails['contact_mobile']);
        $this->assertEmpty($merchantDetails['business_type']);
        $this->assertEmpty($merchantDetails['transaction_volume']);
    }

    public function testOauthLoginInvalidateContactDetailsL2Submitted()
    {
        $user = $this->fixtures->create('user', ['id'             => 'FL0nl7kME8j3Dd',
                                                 'email'          => 'hello123@gmail.com',
                                                 'password'       => 'hello123',
                                                 'contact_mobile' => '9999999999',
                                                 'confirm_token'  => 'confirm_token']);

        $merchant = $user->getMerchantEntity();

        $this->fixtures->edit('merchant',$merchant->getId(),['name'=>'hello world']);

        $merchantDetails = $this->fixtures->create('merchant_detail',
                                                   ['merchant_id'        => $merchant->getId(),
                                                    'business_name'      => 'hello world',
                                                    'contact_name'       => 'hello',
                                                    'business_type'      => '1',
                                                    'transaction_volume' => '1',
                                                    'contact_mobile'     => '9999999999',
                                                    'activation_form_milestone'=>'L2']);

        $testData =   &$this->testData['testOauthLoginInvalidatePassword'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        // check if password is set as null

        $user = $this->getDbEntityById('user', 'FL0nl7kME8j3Dd');

        $this->assertEmpty($user['password']);

        $merchant = $this->getDbEntityById('merchant', $merchant->getId());

        // check if name is set as empty
        $this->assertNotNull($merchant['name']);
        $this->assertEquals('', $user['name']);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        // check if contact details are set as null.
        $this->assertNotNull($merchantDetails['business_name']);
        $this->assertNotNull($merchantDetails['contact_name']);
        $this->assertNotNull($merchantDetails['contact_mobile']);
        $this->assertNotNull($merchantDetails['business_type']);
        $this->assertNotNull($merchantDetails['transaction_volume']);
    }

    public function testOauthLoginFailInvalidProvider()
    {
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testOauthLoginFailPasswordOauthNotPresent()
    {
        $this->fixtures->create('user', [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'password' => 'hello123']);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMultipleOauthProviderLogin()
    {
        $this->fixtures->create('user', [
            'id'             => "FL0nl7kME8j3Dd",
            'email'          => 'hello123@gmail.com',
            'password'       => 'hello123',
            'oauth_provider' => "[\"google\"]"]);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', "FL0nl7kME8j3Dd");

        $this->assertEquals($user['oauth_provider'], "[\"google\"]");
    }

    public function testUserAccessWithProductPrimary()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'content'   => [
                'merchant_id'   => $merchant->getId(),
            ],
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testUserAccessWithProductBanking()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'content'   => [
                'merchant_id'   => $merchant->getId(),
            ],
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testUserAccessWithMappingForMultipleProducts()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingDataForPrimaryProduct = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $mappingDataForBankingProduct = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'admin',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingDataForPrimaryProduct);

        $this->fixtures->create('user:user_merchant_mapping', $mappingDataForBankingProduct);

        $testData = & $this->testData[__FUNCTION__];

        $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'content'   => [
                'merchant_id'   => $merchant->getId(),
            ],
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedUserAccessAccrossProducts()
    {
        // this should fail
        // since mapping is for one product
        // and request is coming for different product
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'content'   => [
                'merchant_id'   => $merchant->getId(),
            ],
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedUserAccess()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $testData = & $this->testData[__FUNCTION__];

        $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'content'   => [
                'merchant_id'   => $merchant->getId(),
            ],
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testUserAccessWithoutMerchantIdInRequest()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testUserDisable2fa()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 1,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $this->fixtures->edit('merchant', '10000000000000', [MerchantEntity::SECOND_FACTOR_AUTH => 0]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => 0,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isSecondFactorAuth());
    }

    public function testUserEnable2fa()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $this->fixtures->edit('merchant', '10000000000000', [MerchantEntity::SECOND_FACTOR_AUTH => 0]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($user->isSecondFactorAuth());
    }

    public function testUserEnable2FaAsCriticalAction()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
        [
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => '9999999999',
            UserEntity::SECOND_FACTOR_AUTH      => 0,
        ]);

        $this->fixtures->edit('merchant', '10000000000000',
        [
            MerchantEntity::SECOND_FACTOR_AUTH => 0
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setCOnstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content'] = [
            UserEntity::SECOND_FACTOR_AUTH  => true,
        ];

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($user->isSecondFactorAuth());

    }

    public function testUserDisable2FaAsCriticalAction()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
        [
            UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            UserEntity::CONTACT_MOBILE          => '9999999999',
            UserEntity::SECOND_FACTOR_AUTH      => 1,
        ]);

        $this->fixtures->edit('merchant', '10000000000000',
        [
            MerchantEntity::SECOND_FACTOR_AUTH => 0
        ]);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setCOnstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isSecondFactorAuth());
    }

    public function testFailedUserEnable2faMerchant2faEnforced()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $this->fixtures->edit('merchant', '10000000000000', [MerchantEntity::SECOND_FACTOR_AUTH => 1]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isSecondFactorAuth());
    }

    public function testFailedUserEnable2faOrg2faEnforced()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $this->fixtures->edit('org', '100000razorpay', [OrgEntity::MERCHANT_SECOND_FACTOR_AUTH => 1]);

        $this->fixtures->edit('merchant', '10000000000000', [
            MerchantEntity::ORG_ID => '100000razorpay',
            MerchantEntity::SECOND_FACTOR_AUTH => 0
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isSecondFactorAuth());
    }

    public function testFailedUserEnable2faOrg2faNotEnforced()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $this->fixtures->edit('org', '100000razorpay', [OrgEntity::MERCHANT_SECOND_FACTOR_AUTH => 0]);

        $this->fixtures->edit('merchant', '10000000000000', [
            MerchantEntity::ORG_ID => '100000razorpay',
            MerchantEntity::SECOND_FACTOR_AUTH => 0
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFailedUserEnable2faOneOfMultipleOrgs2faEnforced()
    {
        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $merchant2 = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user['id'],
            'merchant_id' => $merchant2['id'],
            'role'        => 'owner',
        ]);

        $this->fixtures->create('org', [
            OrgEntity::ID                          => '100000tazorpay',
            OrgEntity::MERCHANT_SECOND_FACTOR_AUTH => 1
        ]);

        $this->fixtures->edit('merchant', $merchant2['id'], [
            MerchantEntity::ORG_ID => '100000tazorpay',
            MerchantEntity::SECOND_FACTOR_AUTH => 0
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFailedUserEnable2faMobNotVerified()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 0,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $this->fixtures->edit('merchant', '10000000000000', [MerchantEntity::SECOND_FACTOR_AUTH => 0]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isSecondFactorAuth());
    }

    public function testFailedUserEnable2faMobNotPresent()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
        [
            UserEntity::CONTACT_MOBILE_VERIFIED => 0,
            UserEntity::CONTACT_MOBILE          => null,
            UserEntity::SECOND_FACTOR_AUTH      => 0,
            UserEntity::PASSWORD                => 'hello123',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', [MerchantEntity::SECOND_FACTOR_AUTH => 0]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isSecondFactorAuth());
    }

    public function testFailedUserChange2faBankingDemoAcc()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 0,
                UserEntity::CONTACT_MOBILE          => null,
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
                UserEntity::EMAIL                   => Constants::BANKING_DEMO_USER_EMAILS[1]
            ]);

        $this->fixtures->edit('merchant', '10000000000000', [MerchantEntity::SECOND_FACTOR_AUTH => 0]);

        $testData = & $this->testData[__FUNCTION__];

        $content =  [
            UserEntity::SECOND_FACTOR_AUTH => true,
            UserEntity::PASSWORD           => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isSecondFactorAuth());
    }

    /**
     * Otp is not sent while logging in for a user
     * who has 2fa enabled.
     */
    public function testFailedLogin2faNoOtp()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
                    'password'                => 'hello123',
                    'second_factor_auth'      => true,
                    'contact_mobile'          => '9999999999',
                    'contact_mobile_verified' => true,
                ]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    /**
     * Otp is not sent with the login credentials for whom 2fa
     * is enforced by one of the merchants.
     */
    public function testFailedLogin2faEnforcedNoOtp()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
                    'password'                => 'hello123',
                    'second_factor_auth'      => false,
                    'contact_mobile'          => '9999999999',
                    'contact_mobile_verified' => true,
                ]);

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin2faEnforcedNoOtpForMobileOAuth()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => false,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $merchantId = $merchant->getId();

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'mobile_app_client_credentials',
                'scope'                 => 'x_mobile_app_2fa_token',
                'mode'                  => 'live',
                'user_id'               => $user->getId()
            ])
            ->willReturn([
                'public_token'       => 'rzp_test_oauth_10000000000000',
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'access_token',
                'refresh_token'      => 'refresh_token',
                'client_id'          => 'client_id',
            ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin2faEnforcedNoOtpForMobileOAuthWithCreateWorkflowAction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => false,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $merchantId = $merchant->getId();

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'mobile_app_client_credentials',
                'scope'                 => 'x_mobile_app_2fa_token',
                'mode'                  => 'live',
                'user_id'               => $user->getId()
            ])
            ->willReturn([
                'public_token'       => 'rzp_test_oauth_10000000000000',
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'access_token',
                'refresh_token'      => 'refresh_token',
                'client_id'          => 'client_id',
            ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin2faEnforcedNoOtpForMobileOAuthWithUpdateWorkflowAction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => false,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $merchantId = $merchant->getId();

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'mobile_app_client_credentials',
                'scope'                 => 'x_mobile_app_2fa_token',
                'mode'                  => 'live',
                'user_id'               => $user->getId()
            ])
            ->willReturn([
                'public_token'       => 'rzp_test_oauth_10000000000000',
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'access_token',
                'refresh_token'      => 'refresh_token',
                'client_id'          => 'client_id',
            ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin2faEnforcedNoOtpForMobileOAuthWithDeleteWorkflowAction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => false,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $merchantId = $merchant->getId();

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'mobile_app_client_credentials',
                'scope'                 => 'x_mobile_app_2fa_token',
                'mode'                  => 'live',
                'user_id'               => $user->getId()
            ])
            ->willReturn([
                'public_token'       => 'rzp_test_oauth_10000000000000',
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'access_token',
                'refresh_token'      => 'refresh_token',
                'client_id'          => 'client_id',
            ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin2faEnforcedNoOtpForMobileOAuthWithBulkApproveAction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => false,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $merchantId = $merchant->getId();

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'mobile_app_client_credentials',
                'scope'                 => 'x_mobile_app_2fa_token',
                'mode'                  => 'live',
                'user_id'               => $user->getId()
            ])
            ->willReturn([
                'public_token'       => 'rzp_test_oauth_10000000000000',
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'access_token',
                'refresh_token'      => 'refresh_token',
                'client_id'          => 'client_id',
            ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin2faOtpLimitExceeds()
    {
        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => false,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['sendSms', 'generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('sendSms')->willThrowException(
            new BadRequestException(ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED)
        );

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testLoginWithAccountLockedAndWith2Fa()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'              => 'hello123',
            'account_locked'        => true,
            'second_factor_auth'    => true,
        ]);

        $content = [
            'email'             => $user['email'],
            'password'          => 'hello123',
            'captcha_disable'   => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileLoginWithIncorrectPasswordCountCaptchaDisabled()
    {
        $user = $this->fixtures->create('user', [
            'contact_mobile' => '9012345678',
            'password'         => 'P@ssw0rd',
            'contact_mobile_verified' => true
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();
        $redis = Redis::connection('mutex_redis')->client();

        $counter = $redis->set($user['contact_mobile'], Constants::INCORRECT_LOGIN_THRESHOLD_COUNT);

        $this->startTest();
    }

    public function testLoginWithIncorrectPasswordCount()
    {
        $user = $this->fixtures->create('user', [
            'password'         => 'hello123',
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();
        $redis = Redis::connection('mutex_redis')->client();

        $counter = $redis->set($user['email'], Constants::INCORRECT_LOGIN_THRESHOLD_COUNT);

        $this->startTest();
    }

    public function testLoginWithAccountLockedAndWithout2Fa()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'         => 'hello123',
            'account_locked'   => true,
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'browser_details'       => []
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFailedLogin2faNotSetup()
    {
        $this->enableRazorXTreatmentForRazorX();

        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => true,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => false,
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testSSWFSmsOtpViaStork()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->edit('merchant', '10000000000000', ['second_factor_auth' => 1]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000'], 'admin');

        $this->ba->proxyAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.otp_workflow_config_v2', '9999999999', []);

        $this->startTest();
    }

    public function testSetMobileNumberForMerchantEnabled2FAForXWithNewSmsTemplateAndSendsViaStork()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->edit('merchant', '10000000000000', ['second_factor_auth' => 1]);

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '20000000000000'], 'admin');

        $this->ba->dashboardGuestAppAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.x_second_factor_auth', '9999999999', []);

        $this->startTest();
    }

    public function testFailedLogin2faForXWithNewSmsTemplateAndSendsViaStork()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('user', [
            'email'                   => 'user@domain.com',
            'password'                => 'hello123',
            'second_factor_auth'      => true,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $this->ba->dashboardGuestAppAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.x_second_factor_auth', '9999999999', []);

        $this->startTest();
    }

    public function testVerify2faForXWithNewSmsTemplate()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('user', [
            'id'                      => '10000000000000',
            'email'                   => 'user@domain.com',
            'password'                => 'hello123',
            'second_factor_auth'      => true,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $this->ba->appAuth();

        $smsPayload = [
            'success'    => true,
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => 'user_id:x_second_factor_auth:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp')->with([
            'receiver'  => '9999999999',
            'context'   => '10000000000000:x_second_factor_auth:10000000000000',
            'source'    => 'api',
            'otp'       => '0007'
        ],false)->willReturn($smsPayload);

        $this->startTest();
    }

    public function testFailed2faSetupVerifyMobileWrongOtp()
    {
        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => true,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => false,
            'account_locked'          => false,
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'otp'      => 'X' . \RZP\Services\Raven::MOCK_VALID_OTPS[0],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function test2faSetupVerifyMobile()
    {
        $user = $this->fixtures->create('user', [
            'password'                => 'hello123',
            'second_factor_auth'      => true,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => false,
            'account_locked'          => false,
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'    => $user['email'],
            'password' => 'hello123',
            'otp'      => \RZP\Services\Raven::MOCK_VALID_OTPS[0],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', $user['id']);

        $this->assertTrue($user->isContactMobileVerified());
    }

    public function testUserRestrictedFeature()
    {
        $user = $this->fixtures->create('user');

        $merchantIds = $user->merchants()->distinct()->get()->pluck('id')->toArray();
        $merchant    = $this->getDbEntityById('merchant', $merchantIds[0]);

        $merchant->setRestricted(true);
        $merchant->saveOrFail();

        $this->assertTrue($user->getRestricted() === true);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'admin',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->assertTrue($user->getRestricted() === true);
    }

    public function testTriggerTwoFaOtpWithTwoFaSetup()
    {
        $user = $this->fixtures->create('user',
            [
                'contact_mobile'            => '9123456788',
                'contact_mobile_verified'   => true,
            ]);

        $merchantId = $user
                        ->merchants()
                        ->get()
                        ->pluck('id')
                        ->toArray()[0];

        $apiKey = 'rzp_live_'.$merchantId;

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => $merchantId,
            'user_id'     => $user->getId(),
            'role'        => 'owner',
        ], 'live');

        $this->ba->proxyAuth($apiKey, $user->getId());

        $this->startTest();
    }

    public function testTriggerTwoFaOtpWithoutContactMobile()
    {
        $user = $this->fixtures->create('user',
            [
                'contact_mobile'    => null,
            ]);

        $merchantId = $user
                        ->merchants()
                        ->get()
                        ->pluck('id')
                        ->toArray()[0];

        $apiKey = 'rzp_live_'.$merchantId;

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => $merchantId,
            'user_id'     => $user->getId(),
            'role'        => 'owner',
        ], 'live');

        $this->ba->proxyAuth($apiKey, $user->getId());

        $this->startTest();
    }

    public function testTriggerTwoFaOtpWithTwoFaSetupForMobileUsers()
    {
        $user = $this->fixtures->create('user',
                                        [
                                            'contact_mobile'            => '9123456788',
                                            'contact_mobile_verified'   => true,
                                            'signup_via_email'          => 0,
                                            'email'                     => null,
                                        ]);

        $this->fixtures->edit('org', '100000razorpay', [
            'second_factor_auth_mode'    => 'email',
        ]);

        $merchantId = $user
                        ->merchants()
                        ->get()
                        ->pluck('id')
                        ->toArray()[0];

        $apiKey = 'rzp_live_' . $merchantId;

        $this->fixtures->create('merchant_detail',[
            'merchant_id'   => $merchantId,
            'contact_name'  => 'Aditya',
            'business_type' => 2,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => $merchantId,
                                                             'user_id'     => $user->getId(),
                                                             'role'        => 'owner',
                                                         ], 'live');

        $this->ba->proxyAuth($apiKey, $user->getId());

        $this->startTest();
    }

    public function testTriggerTwoFaOtpVerificationForMobileUsers()
    {
        $user = $this->fixtures->create('user',
                                        [
                                            'contact_mobile'            => '9123456788',
                                            'contact_mobile_verified'   => true,
                                            'signup_via_email'          => 0,
                                            'email'                     => null,
                                        ]);

        $this->fixtures->edit('org', '100000razorpay', [
            'second_factor_auth_mode'    => 'email',
        ]);

        $merchantId = $user
                        ->merchants()
                        ->get()
                        ->pluck('id')
                        ->toArray()[0];

        $this->fixtures->create('merchant_detail',[
            'merchant_id'   => $merchantId,
            'contact_name'  => 'Aditya',
            'business_type' => 2,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => $merchantId,
                                                             'user_id'     => $user->getId(),
                                                             'role'        => 'owner',
                                                         ], 'live');

        $this->ba->dashboardGuestAppAuth();

        $this->ba->setAppAuthHeaders(['X-Dashboard-User-Id' => $user['id']]);

        $this->startTest();
    }

    public function testTriggerTwoFaOtpWithoutContactMobileVerified()
    {
        $user = $this->fixtures->create('user',[
            'contact_mobile'            => '9123456789',
            'contact_mobile_verified'   => false,
        ]);

        $merchantId = $user
                        ->merchants()
                        ->get()
                        ->pluck('id')
                        ->toArray()[0];

        $apiKey = 'rzp_live_'.$merchantId;

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => $merchantId,
            'user_id'     => $user->getId(),
            'role'        => 'owner',
        ], 'live');

        $this->ba->proxyAuth($apiKey, $user->getId());

        $this->startTest();
    }

    public function testConfirmByToken()
    {
        $user = $this->fixtures->create('user', ['confirm_token' => 'confirm_token']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'confirm_token' => 'confirm_token'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testConfirmByInvalidToken()
    {
        $user = $this->fixtures->create('user', ['confirm_token' => 'confirm_token']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'confirm_token' => ''
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testConfirmByEmail()
    {
        $this->ba->adminAuth();

        $user = $this->fixtures->create('user', ['confirm_token' => 'confirm_token']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email' => $user['email']
        ];

        $testData['request']['content'] = $content;

        $this->startTest();
    }

    /**
     * Asserts usual edit operation. Additionally asserts that editing mobile causes verified flag to be marked false.
     */
    public function testEdit()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE_VERIFIED => 1]);

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertFalse($user->isContactMobileVerified());
    }

    public function testChangePassword()
    {
        $user = $this->fixtures->create('user', ['password' => '12345']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello123',
            'password_confirmation' => 'hello123',
            'old_password'          => '12345',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/password';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testChangePasswordRateLimit()
    {
        $user = $this->fixtures->create('user', ['password' => '12345']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['id'].Constants::CHANGE_PASSWORD_RATE_LIMIT_SUFFIX, Constants::CHANGE_PASSWORD_RATE_LIMIT_THRESHOLD);

        $this->startTest();

        $redis->del($user['id'].Constants::CHANGE_PASSWORD_RATE_LIMIT_SUFFIX);
    }

    public function testChangePasswordMatchesLastNPasswords()
    {
        $user = $this->fixtures->create('user', ['password' => 'P@ssw0rd']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);
    }

    public function testChangePasswordAfterNChanges()
    {
        $origPassword   = 'P@ssw0rd';
        $user           = $this->fixtures->create('user', ['password' => $origPassword]);

        $testData = & $this->testData['testChangePassword'];
        $testData['request']['url'] = '/users/password';
        $testData['request']['server']['HTTP_X-Dashboard-User-Id']  = $user['id'];

        $oldPassword = $origPassword;

        for ($i = 1; $i <= Constants::MAX_PASSWORD_TO_RETAIN; $i++) {
            $newPassword                                            = $oldPassword.$i;
            $testData['request']['content']['password']             = $newPassword;
            $testData['request']['content']['password_confirmation']= $newPassword;
            $testData['request']['content']['old_password']         = $oldPassword;
            $oldPassword                                            = $newPassword;

            $this->ba->dashboardGuestAppAuth();

            $this->startTest($testData);
        }

        $redis = Redis::connection('mutex_redis')->client();
        $redis->del($user['id'].Constants::CHANGE_PASSWORD_RATE_LIMIT_SUFFIX);

        // After N attempts, the original password should again be reusable
        $testData['request']['content']['password']             = $origPassword;
        $testData['request']['content']['password_confirmation']= $origPassword;
        $testData['request']['content']['old_password']         = $oldPassword;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);


    }

    /*checks for user who signs up via google auth*/
    public function testcheckUserHasSetPassword()
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'],
                                                             [
                                                                 'signup_via_email'        => 1,
                                                                 'contact_mobile'          => '9012345678',
                                                                 'contact_mobile_verified' => true,
                                                             ]);

        $user->setPasswordNull();

        $user->save();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();
    }

    public function testCheckUserHasSetPasswordInX()
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'],
            [
                'signup_via_email'        => 1,
                'contact_mobile'          => '9012345678',
                'contact_mobile_verified' => true,
            ]);

        DB::table('merchant_users')
            ->insert([
                'merchant_id' => $merchant['id'],
                'user_id'     => $user->getId(),
                'product'     => 'banking',
                'role'        => 'owner',
                'created_at'  => time(),
                'updated_at'  => time(),
            ]);

        $user->setPasswordNull();

        $user->save();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();
    }

    /*checks for user has only set password before*/
    public function testcheckUserHasSetPasswordAlready()
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'],
                                                             [
                                                                 'signup_via_email'        => 0,
                                                                 'contact_mobile'          => '9012345678',
                                                                 'contact_mobile_verified' => true,
                                                             ]);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();
    }

    public function testSetUserPassword()
    {

        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'],
            [
                'signup_via_email'        => 0,
                'contact_mobile'          => '9012345678',
                'contact_mobile_verified' => true,
            ]);

        $user->setPasswordNull();

        (new UserRepo())->saveOrFail($user);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello123',
            'password_confirmation' => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->startTest();

        $user = $this->getDbEntityById('user', $user->getId());

        $this->assertNotNull($user->getPassword());
    }

    public function testPatchUserPassword()
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'],
            [
                'signup_via_email'        => 0,
                'contact_mobile'          => '9012345678',
                'contact_mobile_verified' => true,
            ]);

        $user->setPasswordNull();

        (new UserRepo())->saveOrFail($user);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello123',
            'password_confirmation' => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', $user->getId());

        $this->assertNotNull($user->getPassword());
    }

    public function testChangeInvalidPassword()
    {
        $user = $this->fixtures->create('user', ['password' => '12345']);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'password'              => 'hello1234',
            'password_confirmation' => 'hello123',
            'old_password'          => '12345',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/password';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testBulkUpdateUserRoleMapping()
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'], [], 'owner');

        $user1 = $this->fixtures->user->createUserForMerchant($merchant['id'], [], 'manager');

        $user2 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->adminAuth();

        $data = [
            [
                'user_id'     => $user['id'],
                'merchant_id' => $merchant['id'],
                'product'     => 'primary',
                'role'        => 'owner',
                'action'      => 'detach',
            ],
            [
                'user_id'     => $user1['id'],
                'merchant_id' => $merchant['id'],
                'product'     => 'primary',
                'role'        => 'owner',
                'action'      => 'update',
            ],
            [
                'user_id'     => $user2['id'],
                'merchant_id' => $merchant['id'],
                'product'     => 'primary',
                'role'        => 'finance',
                'action'      => 'attach',
            ],
        ];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $data;

        $this->runRequestResponseFlow($testData);

        $mapping = $this->fixtures->user->getMerchantUserMapping($merchant['id'], $user['id']);

        $this->assertEquals(0, count($mapping));

        $mapping = $this->fixtures->user->getMerchantUserMapping($merchant['id'], $user1['id']);

        $this->assertEquals('owner', $mapping->first()->role);

        $mapping = $this->fixtures->user->getMerchantUserMapping($merchant['id'], $user2['id']);

        $this->assertEquals('finance', $mapping->first()->role);
    }

    public function testAttachMerchant()
    {
        $user = $this->fixtures->create('user');

        $ownerUser = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $mappingData['user_id'] = $user['id'];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'manager',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $ownerUser['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $this->startTest();

        $merchants = DB::table('merchant_users')
                       ->where('user_id', '=', $user['id'])
                       ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 2);

        $this->assertEquals($merchants['manager'], $merchant['id']);
    }

    public function testDetachMerchant()
    {
        $user = $this->fixtures->create('user');

        $ownerUser = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'owner',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/detach';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $ownerUser['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $this->startTest();

        $merchants = DB::table('merchant_users')
                        ->where('user_id', '=', $user['id'])
                        ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 1);
    }

    public function testUpdateMerchant()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $ownerUser = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'        => 'manager',
            'merchant_id' => $merchant['id']
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $ownerUser['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $this->startTest();

        $merchants = DB::table('merchant_users')
                        ->where('user_id', '=', $user['id'])
                        ->pluck('merchant_id', 'role');

        $this->assertEquals(count($merchants), 2);

        $this->assertEquals($merchants['manager'], $merchant['id']);
    }

    public function testUpdateUserRoleByOwner()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $ownerUser = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
            'product'     => 'banking'
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'finance_l1', 'banking');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'=> 'admin',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $testData['request']['server']['HTTP_X-Request-Origin'] = 'https://x.razorpay.com';

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();

        $merchantUsers = DB::table('merchant_users')
            ->where('merchant_id', '=', $merchant['id'])
            ->pluck('user_id', 'role');

        $this->assertEquals(count($merchantUsers), 2);

        $this->assertEquals($merchantUsers['admin'], $user['id']);
    }

    public function testUpdateUserRoleByNonOwner()
    {
        $this->enableRazorXTreatmentForRazorX();
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $ownerUser = $this->fixtures->create('user');

        $dummyUser = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
            'product'     => 'banking'
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'finance_l1', 'banking');

        $this->createUserMerchantMapping($dummyUser['id'], $merchant['id'], 'view_only', 'banking');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'=> 'admin',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $testData['request']['server']['HTTP_X-Request-Origin'] = 'https://x.razorpay.com';

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $dummyUser['id']);

        $this->startTest();
    }

    public function testUpdateToOwnerRole()
    {
        $this->disableRazorXTreatmentCAC();

        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $ownerUser = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
            'product'     => 'banking'
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'finance_l1', 'banking');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'=> 'owner',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $testData['request']['server']['HTTP_X-Request-Origin'] = 'https://x.razorpay.com';

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    public function testUpdateOwnerRole()
    {
        $this->disableRazorXTreatmentCAC();

        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $ownerUser = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
            'product'     => 'banking'
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->createUserMerchantMapping($user['id'], $merchant['id'], 'owner', 'banking');

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'role'=> 'finance_l1',
        ];

        $testData['request']['content'] = $content;

        $testData['request']['url'] = '/users/' . $user['id'] . '/update';

        $testData['request']['server']['HTTP_X-Request-Origin'] = 'https://x.razorpay.com';

        $this->ba->proxyAuth('rzp_test_' . $merchant['id']);

        $this->startTest();
    }

    protected function createUserMerchantMapping(string $userId, string $merchantId, string $role, string $product='primary')
    {
        DB::table('merchant_users')
            ->insert(
                [
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'product'     => $product,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
                ]
            );
    }

    public function testResendVerificationMail()
    {
        Mail::fake();

        $user = $this->fixtures->create('user', [
            'id' => '12398102831231',
            'confirm_token' => 'testingtestingtesting',
        ]);

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertQueued(AccountVerification::class,function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);

            $this->assertArrayHasKey('token', $viewData);

            $this->assertEquals('emails.mjml.merchant.user.email_confirmation_via_link', $mail->view);

            return true;
        });
    }

    public function testResendOtpVerificationMail()
    {
        Mail::fake();

        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
                                      [UserEntity::CONFIRM_TOKEN => 'testing123456789',
                                       UserEntity::EMAIL => 'abc@rzp.com']);

        $merchant = $this->fixtures->create('merchant',
                                                   ['id'    => '10000000000002',
                                                    'email' => 'abc@rzp.com']);

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $response=$this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('verify_email', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.otp_email_verify', $mail->view);

            return true;
        });
    }

    public function testResendEmailOtpVerificationMailThresholdExhausted()
    {
        $user = $this->fixtures->edit(
            'user',
            UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONFIRM_TOKEN   => 'testing123456789',
                UserEntity::EMAIL           => 'abc@rzp.com'
            ]
        );

        $merchant = $this->fixtures->create(
            'merchant',
            [
                MerchantEntity::ID    => '10000000000002',
                MerchantEntity::EMAIL => 'abc@rzp.com'
            ]
        );

        $mappingData = [
            MerchantUserEntity::USER_ID     => $user->getId(),
            MerchantUserEntity::MERCHANT_ID => $merchant->getId(),
            MerchantUserEntity::ROLE        => Role::OWNER,
            MerchantUserEntity::PRODUCT     => Product::PRIMARY,
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user->getId().Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX, Constants::EMAIL_VERIFICATION_OTP_SEND_THRESHOLD);

        $this->ba->proxyAuth();

        $this->startTest();

        $redis->del($user->getId().Constants::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX);

    }

    public function testResendOtpVerificationMailForSignupFlowInX()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
                        [UserEntity::CONFIRM_TOKEN => 'testing123456789',UserEntity::EMAIL => 'abc@rzp.com']);

        $this->fixtures->edit('merchant','10000000000000', ['email' => 'abc@rzp.com']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('x_verify_email', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.razorpayx.otp_email_verify', $mail->view);

            $mailSubject = "Verify your Email for RazorpayX";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('x.support@razorpay.com', $mail->from[0]['address']);

            return true;
        });
    }

    public function testPasswordResetMail()
    {
        Mail::fake();

        $this->fixtures->create('user', ['email' => 'resetpass@razorpay.com']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertSent(PasswordReset::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('email', $viewData);
            $this->assertArrayHasKey('showAxisSupportUrl', $viewData['org']);

            $this->assertEquals('emails.user.password_reset', $mail->view);

            return true;
        });
    }

    public function testPasswordResetMailCaseInsensitive()
    {
        $this->enableRazorXTreatmentForRazorX();

        Mail::fake();

        $this->fixtures->create('user', ['email' => 'RESETPASS@RAZORPAY.COM']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Mail::assertSent(PasswordReset::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('org', $viewData);
            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('email', $viewData);
            $this->assertArrayHasKey('showAxisSupportUrl', $viewData['org']);

            $this->assertEquals('emails.user.password_reset', $mail->view);

            return true;
        });
    }

    public function testPasswordResetMailForBadEmail()
    {
        Mail::fake();

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

    }

    private function enableRazorXTreatmentForResetPasswordForSMS()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === 'reset_password_using_sms')
                    {
                        return 'on';
                    }

                    return 'off';
                }));
    }

    public function testPasswordResetSMS()
    {
        $this->enableRazorXTreatmentForResetPasswordForSMS();

        $this->fixtures->create('user', [
            'contact_mobile' => '7349196832',
            'contact_mobile_verified' => true,
        ]);

        $this->ba->dashboardGuestAppAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.reset_password', '7349196832', []);

        $this->startTest();
    }

    protected function expectStorkSendSmsRequest($storkMock, $templateName, $destination, $expectedParms = [])
    {
        $storkMock->shouldReceive('sendSms')
            ->with(
                Mockery::on(function ($mockInMode)
                {
                    return true;
                }),
                Mockery::on(function ($actualPayload) use ($templateName, $destination, $expectedParms)
                {

                    if(isset($actualPayload['contentParams']) === true)
                    {
                        $this->assertArraySelectiveEquals($expectedParms, $actualPayload['contentParams']);
                    }

                    if (($templateName !== $actualPayload['templateName']) or
                        ($destination !== $actualPayload['destination']))
                    {
                        return false;
                    }

                    return true;
                }))
            ->andReturnUsing(function ()
            {
                return ['success' => true];
            });
    }

    public function testPasswordResetUnverifiedMobileNumber()
    {
        $this->enableRazorXTreatmentForResetPasswordForSMS();

        $this->fixtures->create('user', ['contact_mobile' => '7349196832']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testPasswordResetMobileNumberMultipleUsersAssociated()
    {
        $this->fixtures->create('user', ['contact_mobile' => '7349196832']);

        $this->fixtures->create('user', ['contact_mobile' => '7349196832']);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

    }

    public function testPasswordResetByToken()
    {
        $resetAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
        ];

        // create user with password reset token; reset password
        // and check if old_passwords are getting updated
        $user = $this->fixtures->create('user', $resetAttributes);
        $oldPassword2 = $user->getPassword();
        $this->doTestPasswordResetByToken($user);
        $user = $this->getDbEntityById('user', $user->getId());
        $this->assertNotNull($user->getAttribute(Entity::OLD_PASSWORDS));
        $this->assertEquals(
            [$oldPassword2, $user->getPassword()],
            $user->getAttribute(Entity::OLD_PASSWORDS)
        );

        // Repeats same request against to assert attribute OLD_PASSWORDS is captured.
        $this->fixtures->edit('user', $user->getId(), $resetAttributes);
        $user = $this->getDbEntityById('user', $user->getId());
        $oldPassword1 = $user->getPassword();
        $this->doTestPasswordResetByToken($user);
        $user = $this->getDbEntityById('user', $user->getId());
        $this->assertEquals(
            [$oldPassword2, $oldPassword1, $user->getPassword()],
            $user->getAttribute(Entity::OLD_PASSWORDS)
        );
    }

    public function testPasswordResetByTokenCaseInsensitive()
    {
        $this->enableRazorXTreatmentForRazorX();

        $resetAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
        ];

        // create user with password reset token; reset password
        // and check if old_passwords are getting updated
        $user = $this->fixtures->create('user', $resetAttributes);
        $oldPassword2 = $user->getPassword();
        $this->doTestPasswordResetByToken($user);
        $user = $this->getDbEntityById('user', $user->getId());
        $this->assertNotNull($user->getAttribute(Entity::OLD_PASSWORDS));
        $this->assertEquals(
            [$oldPassword2, $user->getPassword()],
            $user->getAttribute(Entity::OLD_PASSWORDS)
        );

        // Repeats same request against to assert attribute OLD_PASSWORDS is captured.
        $this->fixtures->edit('user', $user->getId(), $resetAttributes);
        $user = $this->getDbEntityById('user', $user->getId());
        $oldPassword1 = $user->getPassword();
        $this->doTestPasswordResetByToken($user);
        $user = $this->getDbEntityById('user', $user->getId());
        $this->assertEquals(
            [$oldPassword2, $oldPassword1, $user->getPassword()],
            $user->getAttribute(Entity::OLD_PASSWORDS)
        );
    }

    public function doTestPasswordResetByToken(Entity $user)
    {
        $testData = & $this->testData['testPasswordResetByToken'];

        $password = str_random(10) . '1';

        $testData['request']['content']['email']                    = $user->getEmail();
        $testData['request']['content']['token']                    = $user->getPasswordResetToken();
        $testData['request']['content']['password']                 = $password;
        $testData['request']['content']['password_confirmation']    = $password;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

    }

    public function testPasswordResetByTokenWithSamePassword()
    {
        $resetAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
        ];

        $user = $this->fixtures->create('user', $resetAttributes);

        $this->doTestPasswordResetByToken($user);

        // Repeats same request against to assert attribute OLD_PASSWORD_2 is captured.
        $this->fixtures->edit('user', $user->getId(), $resetAttributes);
        $user = $this->getDbEntityById('user', $user->getId());

        $testData = & $this->testData['testPasswordResetByToken'];

        $testData['request']['content']['email']      = $user->getEmail();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_NEW_PASSWORD_SAME_AS_OLD_PASSWORD);
    }

    public function testPasswordResetByExpiredToken()
    {
        $user = $this->fixtures->create('user', [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp - 1,
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email']      = $user->getEmail();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testPasswordResetByUsedToken()
    {
        $user = $this->fixtures->create('user', [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email']      = $user->getEmail();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->ba->dashboardGuestAppAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $this->startTest();
    }

    public function testPasswordResetByTokenAndMobile()
    {
        $resetAttributes = [
            'contact_mobile'                 => '7349196832',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
        ];

        // create user with password reset token; reset password
        // and check if old_passwords are getting updated
        $user = $this->fixtures->create('user', $resetAttributes);
        $oldPassword2 = $user->getPassword();
        $this->doTestPasswordResetByToken($user);
        $user = $this->getDbEntityById('user', $user->getId());
        $this->assertNotNull($user->getAttribute(Entity::OLD_PASSWORDS));
        $this->assertEquals(
            [$oldPassword2, $user->getPassword()],
            $user->getAttribute(Entity::OLD_PASSWORDS)
        );

        // Repeats same request against to assert attribute OLD_PASSWORDS is captured.
        $this->fixtures->edit('user', $user->getId(), $resetAttributes);
        $user = $this->getDbEntityById('user', $user->getId());
        $oldPassword1 = $user->getPassword();
        $this->doTestPasswordResetByTokenAndMobile($user);
        $user = $this->getDbEntityById('user', $user->getId());
        $this->assertEquals(
            [$oldPassword2, $oldPassword1, $user->getPassword()],
            $user->getAttribute(Entity::OLD_PASSWORDS)
        );
    }

    public function doTestPasswordResetByTokenAndMobile(Entity $user)
    {
        $testData = & $this->testData['testPasswordResetByTokenAndMobile'];

        $password = str_random(10) . '1';

        $testData['request']['content']['contact_mobile']           = $user->getContactMobile();
        $testData['request']['content']['token']                    = $user->getPasswordResetToken();
        $testData['request']['content']['password']                 = $password;
        $testData['request']['content']['password_confirmation']    = $password;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

    }

    public function testPasswordResetByExpiredTokenAndMobile()
    {
        $user = $this->fixtures->create('user', [
            'contact_mobile'                 => '7349196832',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp - 1,
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['contact_mobile']      = $user->getContactMobile();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testPasswordResetByUsedTokenAndMobile()
    {
        $user = $this->fixtures->create('user', [
            'contact_mobile'        => '7349196832',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['contact_mobile']      = $user->getContactMobile();
        $testData['request']['content']['token']      = $user->getPasswordResetToken();

        $this->ba->dashboardGuestAppAuth();

        $this->makeRequestAndGetContent($testData['request']);

        $this->startTest();
    }

    public function testSendOtp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpForScanAndPay()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpVerifyUser()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpForViewOnlyRoleInX()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $mappingData = [
            'merchant_id' => '10000000000000',
            'user_id'     => UserFixture::MERCHANT_USER_ID,
            'role'        => 'view_only',
            'product'     => 'banking'
        ];

        $this->fixtures->user->createUserMerchantMapping($mappingData);

        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendXMobileAppDownloadLink()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['sms_id']);
    }

    public function testSendOtpForXSignupV2()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [UserEntity::CONFIRM_TOKEN => "testing123456789"]);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('x_verify_email', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.razorpayx.otp_email_verify', $mail->view);

            $mailSubject = "Verify your Email for RazorpayX";

            $this->assertEquals($mailSubject, $mail->subject);

            $this->assertEquals('x.support@razorpay.com', $mail->from[0]['address']);

            return true;
        });
    }

    public function testSendOtpWithContact()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpWithContactForVerifyingSupportContact()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData['testSendOtpWithContact'];

        $testData['request']['content'] = [
            'action'         => 'verify_support_contact',
            'contact_mobile' => '9876543210',
        ];

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testVerifyOtpWithToken()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpViaMail()
    {
        $this->createContact();
        $this->createFundAccount();

        Mail::fake();

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('create_payout', $mail->input['action']);
            $this->assertNotEmpty($mail->user);
            $this->assertNotEmpty($mail->otp);
            $this->assertEquals('emails.user.otp_create_payout', $mail->view);
            return true;
        });
    }

    public function testSmsTemplateSelection()
    {
        $this->createContact();
        $this->createFundAccount();

        $this->fixtures->edit('user', 'MerchantUser01',
                              ['contact_mobile'          => '1234567890',
                               'contact_mobile_verified' => 1
                              ]);

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $ravenMock = Mockery::mock('RZP\Services\Mock\Raven');

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $ravenMock->shouldReceive('sendSms')
                  ->andReturnUsing(function(array $request) {
                      $template = $request['template'];
                      $receiver = $request['receiver'];
                      self::assertEquals('Sms.User.Create_payout.V3', $template);
                      self::assertEquals('1234567890', $receiver);

                      return [];
                  });

        $this->app->instance('raven', $ravenMock);

        (new AdminService())->setConfigKeys([ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => [
            'Sms.User.Create_payout.V3' => '*',
        ]]);

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSmsTemplateSelectionMerchantAdded()
    {
        $this->createContact();
        $this->createFundAccount();

        $this->fixtures->edit('user', 'MerchantUser01',
                              ['contact_mobile'          => '1234567890',
                               'contact_mobile_verified' => 1
                              ]);

        $this->ba->proxyAuth();

        $testData = $this->testData['testSmsTemplateSelection'];

        $this->testData[__FUNCTION__] = $testData;

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $ravenMock = Mockery::mock('RZP\Services\Mock\Raven');

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $ravenMock->shouldReceive('sendSms')
                  ->andReturnUsing(function(array $request) {
                      $template = $request['template'];
                      $receiver = $request['receiver'];
                      self::assertEquals('Sms.User.Create_payout.V3', $template);
                      self::assertEquals('1234567890', $receiver);

                      return [];
                  });

        $this->app->instance('raven', $ravenMock);

        (new AdminService())->setConfigKeys([ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => [
            'Sms.User.Create_payout.V3' => ['10000000000000'],
        ]]);
        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSmsTemplateSelectionMerchantNotAdded()
    {
        $this->createContact();
        $this->createFundAccount();

        $this->fixtures->edit('user', 'MerchantUser01',
                              ['contact_mobile'          => '1234567890',
                               'contact_mobile_verified' => 1
                              ]);

        $this->ba->proxyAuth();

        $testData = $this->testData['testSmsTemplateSelection'];

        $this->testData[__FUNCTION__] = $testData;

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $ravenMock = Mockery::mock('RZP\Services\Mock\Raven');

        $ravenMock->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $ravenMock->shouldReceive('sendSms')
                  ->andReturnUsing(function(array $request) {
                      $template = $request['template'];
                      $receiver = $request['receiver'];
                      self::assertEquals('sms.user.create_payout', $template);
                      self::assertEquals('1234567890', $receiver);

                      return [];
                  });

        $this->app->instance('raven', $ravenMock);

        (new AdminService())->setConfigKeys([ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => [
            'Sms.User.Create_payout.V3' => ['10000000000001'],
        ]]);
        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpForCreatePayoutWithoutMobileNumberInReceiver()
    {
        $user = $this->getDbLastEntity('user');

        $this->fixtures->edit('user', $user->getId(), ['contact_mobile' => null]);

        $this->createContact();
        $this->createFundAccount();

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function mockRaven($expectedContext, $receiver, $source = 'api')
    {
        $ravenMock = Mockery::mock(\RZP\Services\Raven::class, [$this->app])->makePartial();

        $ravenMock->shouldReceive('generateOtp')
                  ->andReturnUsing(function(array $request) use ($expectedContext, $receiver, $source) {
                      self::assertEquals($request['receiver'], $receiver);
                      self::assertEquals($request['context'], $expectedContext);
                      self::assertEquals($request['source'], $source);

                      return [
                          'otp'        => '0007',
                          'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
                      ];
                  });

        $this->app->instance('raven', $ravenMock);
    }

    public function testSendOtpForOtherActionsWithSecureOTPExperimentActive()
    {
        $user = $this->getDbLastEntity('user');

        $this->fixtures->edit(
            'user',
            $user->getId(),
            [
                UserEntity::CONTACT_MOBILE          => '123456789',
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            ]);

        $this->createContact();

        $this->createFundAccount();

        $this->ba->proxyAuth();

        $testData = $this->testData['testSendOtpForCreatePayoutWithoutMobileNumberInReceiver'];

        $testData['request']['content']['action'] = 'create_workflow_config';

        $testData['request']['content']['token'] = 'QtrxYjsbrs';

        $expectedContext = sprintf('%s:%s:%s:%s',
                                   '10000000000000',
                                   $user->getId(),
                                   'create_workflow_config',
                                   'QtrxYjsbrs');

        $this->mockRaven($expectedContext, '123456789');

        $response = $this->startTest($testData);

        $this->assertNotEmpty($response['token']);
    }

    public function testBulkPayoutApproveSmsTemplateSelection()
    {
        $this->fixtures->edit('user', 'MerchantUser01',
                              ['contact_mobile'          => '1234567890',
                               'contact_mobile_verified' => 1
                              ]);

        $this->ba->proxyAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app]);

        $this->app->instance('stork_service', $storkMock);

        $this->app['stork_service']->shouldReceive('generateOtp')->andReturn(['otp' => '10000000000sms', 'expires_at' => 10000]);

        $this->app['stork_service']->shouldReceive('sendSms')
                                   ->andReturnUsing(function(string $mode, array $request) {

                                       $template = $request['templateName'];
                                       $receiver = $request['destination'];

                                       self::assertEquals('Sms.User.Bulk_payouts_approve.V1', $template);
                                       self::assertEquals('1234567890', $receiver);

                                       return [];
                                   });

        (new AdminService())->setConfigKeys([ConfigKey::SHIFT_BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT_SMS_TEMPLATE => [
            'bulk_payout_approve_to_bulk_approve_payout' => '*',
        ]]);

        (new AdminService())->setConfigKeys([ConfigKey::UPDATED_SMS_TEMPLATES_RECEIVER_MERCHANTS => [
            'Sms.User.Bulk_payouts_approve.V1' => '*',
            'Sms.User.Bulk_payouts_reject.V1' => '*',
            'Sms.User.Bulk_payouts_approve_reject_action.V2' => '*'
        ]]);

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpForCreatePayoutWithSecureOTP()
    {
        $user = $this->getDbLastEntity('user');

        $this->fixtures->edit(
            'user',
            $user->getId(),
            [
                UserEntity::CONTACT_MOBILE          => '123456789',
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            ]);

        $this->createContact();

        $this->createFundAccount();

        $this->ba->proxyAuth();

        $testData = $this->testData['testSendOtpForCreatePayoutWithoutMobileNumberInReceiver'];

        $testData['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $testData['request']['content']['token'] = 'QtrxYjsbrs';

        $expectedContext = sprintf('%s:%s:%s:%s:%s:%s:%s',
                                   $this->fundAccount->merchant->getId(),
                                   $user->getId(),
                                   Constants::CREATE_PAYOUT,
                                   'QtrxYjsbrs',
                                   10000,
                                   $this->fundAccount->getPublicId(),
                                   '1234567890');

        $expectedContext = hash('sha3-512', $expectedContext);

        $this->mockRaven($expectedContext, '123456789');

        $response = $this->startTest($testData);

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpForApprovePayoutWithSecureOTP()
    {
        $user = $this->getDbLastEntity('user');

        $this->fixtures->edit(
            'user',
            $user->getId(),
            [
                UserEntity::CONTACT_MOBILE          => '123456789',
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            ]);

        $this->createContact();

        $this->createFundAccount();

        $this->ba->proxyAuth();

        $testData = $this->testData['testSendOtpForCreatePayoutWithoutMobileNumberInReceiver'];

        $testData['request']['content'] = [
            'action'         => Constants::APPROVE_PAYOUT,
            'payout_id'      => 'pout_IxOlvTAXZIAduq',
            'amount'         => 100,
            'account_number' => '4564563559247998'
        ];

        $testData['request']['content']['token'] = 'QtrxYjsbrs';

        $expectedContext = sprintf('%s:%s:%s:%s:%s',
                                   $this->fundAccount->merchant->getId(),
                                   $user->getId(),
                                   Constants::APPROVE_PAYOUT,
                                   'QtrxYjsbrs',
                                   'pout_IxOlvTAXZIAduq');

        $expectedContext = hash('sha3-512', $expectedContext);

        $this->mockRaven($expectedContext, '123456789');

        $response = $this->startTest($testData);

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpForIpWhitelistWithSecureOTP()
    {
        $user = $this->getDbLastEntity('user');

        $this->fixtures->edit(
            'user',
            $user->getId(),
            [
                UserEntity::CONTACT_MOBILE          => '123456789',
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            ]);

        $this->ba->proxyAuth();

        $testData = $this->testData['testSendOtpForCreatePayoutWithoutMobileNumberInReceiver'];

        $testData['request']['content'] = [
            'action'         => Constants::IP_WHITELIST,
            'whitelisted_ips'      => ['1.1.1.1','2.2.2.2'],
        ];

        $testData['request']['content']['token'] = 'QtrxYjsbrs';

        $expectedContext = sprintf('%s:%s:%s:%s:%s',
            10000000000000,
            $user->getId(),
            Constants::IP_WHITELIST,
            'QtrxYjsbrs',
            json_encode(['1.1.1.1','2.2.2.2']));

        $expectedContext = hash('sha3-512', $expectedContext);

        $this->mockRaven($expectedContext, '123456789');

        $response = $this->startTest($testData);

        $this->assertNotEmpty($response['token']);
    }

    public function testSendOtpWithReplaceKeyAction()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpWithInvalidAction()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpViaMailToVerifyContact()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpToVerifyContactWhenAlreadyVerified()
    {
        $this->fixtures->edit(
            'user',
            UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE          => '123456789',
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpViaSmsWhenContactDoesNotExist()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSendOtpViaSmsWhenContactIsNotVerified()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testVerifyContactWithOtp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $this->mockHubSpotClient('trackHubspotEvent');

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($user->isContactMobileVerified());
    }

    public function testVerifyContactWithOtpWithAction()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $this->mockHubSpotClient('trackHubspotEvent');

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($user->isContactMobileVerified());
    }

    public function testVerifyOtpAndUpdateContactMobile()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $userDb1 = $this->getDbEntityById('user',  UserFixture::MERCHANT_USER_ID);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                'merchant_id' => $primaryMids[$i],
                'contact_mobile' => '123456789' . $i,
                'contact_email' => 'user'. $i. '@email.com',
            ]);
        }

        $this->ba->proxyAuth('rzp_test_10000000000000', UserFixture::MERCHANT_USER_ID);

        $this->startTest();

        $userDb = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($userDb->isContactMobileVerified());

        $this->assertEquals($userDb->getContactMobile(), "9123456789");

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+919123456789");
        }

        $this->assertCacheDataForUserContactMobileUpdate($userDb['id'], 1);
    }

    public function testVerifyOtpAndUpdateContactMobileWhenOwnerUserAssociatedWithMultipleMerchants()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $mappingData = [
            'user_id'     => UserFixture::MERCHANT_USER_ID,
            'merchant_id' => '10000000000001',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'test');

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'live');

        $userDb1 = $this->getDbEntityById('user',  UserFixture::MERCHANT_USER_ID);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                'merchant_id' => $primaryMids[$i],
                'contact_mobile' => '123456789' . $i,
                'contact_email' => 'user'. $i. '@email.com',
            ]);
        }

        $this->ba->proxyAuth();

        $this->startTest();

        $userDb = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($userDb->isContactMobileVerified());

        $this->assertEquals($userDb->getContactMobile(), "9123456789");

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+919123456789");
        }

        $this->assertCacheDataForUserContactMobileUpdate($userDb['id'], 1);
    }

    public function testVerifyOtpAndUpdateContactMobileWhenUserOwnerOfMerchantwithMultipleOwnerUsers()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $mappingData = [
            'user_id'     => UserFixture::MERCHANT_USER_ID,
            'merchant_id' => '10000000000001',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'test');

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'live');

        $this->fixtures->create('user', ['id' => 'MerchantUser02', UserEntity::CONTACT_MOBILE => '1234567890']);

        $mappingData = [
            'user_id'     => 'MerchantUser02',
            'merchant_id' => '10000000000001',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'test');

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'live');

        $userDb = $this->getDbEntityById('user',  UserFixture::MERCHANT_USER_ID);

        $primaryMids = $userDb->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                'merchant_id' => $primaryMids[$i],
                'contact_mobile' => '123456789' . $i,
                'contact_email' => 'user'. $i. '@email.com',
            ]);
        }

        $this->ba->proxyAuth();

        $this->startTest();

        $userDb1 = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $userDb2 = $this->getDbEntityById('user', 'MerchantUser02');

        $this->assertTrue($userDb1->isContactMobileVerified());

        $this->assertEquals($userDb1->getContactMobile(), "9123456789");

        $this->assertEquals($userDb2->getContactMobile(), "1234567890");

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            if ($mid === '10000000000001')
            {
                $this->assertNotEquals($merchantDetailDb->getContactMobile(), "+919123456789");

                $this->assertEquals($merchantDetailDb->getContactMobile(), "+911234567891");
            }

            else
            {
                $this->assertEquals($merchantDetailDb->getContactMobile(), "+919123456789");
            }
        }

        $this->assertCacheDataForUserContactMobileUpdate($userDb1['id'], 1);
    }

    public function testVerifyOtpAndUpdateContactMobileWhenNotOwnerRoleOfMerchant()
    {
        $user = $this->fixtures->create('user', [
            UserEntity::CONTACT_MOBILE          => '1234567890',
            UserEntity::CONTACT_MOBILE_VERIFIED => true,
        ]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['response']['content']['id'] = $user['id'];

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000001']);

        $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => '10000000000001',
            'contact_mobile' => '1234567891',
            'contact_email' => 'user10@email.com',
        ]);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000001',
            'role'        => 'manager',
            'product'     => 'primary',
        ];

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'test');

        $this->fixtures->user->createUserMerchantMapping($mappingData, 'live');

        $userDb1 = $this->getDbEntityById('user', $user['id']);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            $merchantDetail = $this->fixtures->merchant_detail->createAssociateMerchant([
                'merchant_id' => $primaryMids[$i],
                'contact_mobile' => '123456789' . $i,
                'contact_email' => 'user'. $i. '@email.com',
            ]);
        }

        $this->ba->proxyAuth(
            'rzp_test_' . $merchantIds[0],
            $user['id']);

        $this->startTest();

        $userDb = $this->getDbEntityById('user', $user['id']);

        $this->assertTrue($userDb->isContactMobileVerified());

        $this->assertEquals($userDb->getContactMobile(), "9123456789");

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+919123456789");
        }

        $merchantDetailDb = $this->getDbEntityById('merchant_detail', '10000000000001');

        $this->assertNotEquals($merchantDetailDb->getContactMobile(), "+919123456789");

        $this->assertEquals($merchantDetailDb->getContactMobile(), "+911234567891");

        $this->assertCacheDataForUserContactMobileUpdate($user['id'], 1);
    }

    public function testVerifyUpdateCacheValueForUpdateContactMobile()
    {
        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $userDb1 = $this->getDbEntityById('user',  UserFixture::MERCHANT_USER_ID);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                'merchant_id' => $primaryMids[$i],
                'contact_mobile' => '123456789' . $i,
                'contact_email' => 'user'. $i. '@email.com',
            ]);
        }

        $cacheKey = $this->getThrottleContactMobileCacheKey($user['id']);

        $app = App::getFacadeRoot();

        $this->app['cache']->put($cacheKey, 1);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();

        $userDb = $this->getDbEntityById('user', $user['id']);

        $this->assertTrue($userDb->isContactMobileVerified());

        $this->assertEquals($userDb->getContactMobile(),"9123456789");

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+919123456789");
        }

        $this->assertCacheDataForUserContactMobileUpdate($userDb['id'], 2);
    }
    protected function assertCacheDataForUserContactMobileUpdate($userId, $expectedCacheData)
    {
        $app = App::getFacadeRoot();

        $cacheKey = $this->getThrottleContactMobileCacheKey($userId);

        $cacheData = $app['cache']->get($cacheKey);

        $this->assertEquals($expectedCacheData, $cacheData);
    }

    protected function getThrottleContactMobileCacheKey($userId)
    {
        return sprintf(Constants::THROTTLE_UPDATE_CONTACT_MOBILE_CACHE_KEY_PREFIX, $userId);
    }

    public function testSendOtpLimitForUpdateContactMobileExceeded()
    {
        $user = $this->fixtures->create('user');

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->setRestricted(true, $merchantIds[0]);

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set(
            Constants::THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_PREFIX.$user['id'],
            Constants::THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_LIMIT
        );

        $this->ba->proxyAuth('rzp_test_' . $merchantIds[0], $user['id'], 'owner');

        $this->startTest();

    }

    public function testLimitForUpdateContactMobileExceeded()
    {
        $user = $this->fixtures->create('user');

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->setRestricted(true, $merchantIds[0]);

        $this->ba->proxyAuth('rzp_test_' . $merchantIds[0], $user['id'], 'owner');

        $this->mockRedisSuccess(__FUNCTION__, $user->getId());

        $cacheKey = $this->getThrottleContactMobileCacheKey($user['id']);

        $app = App::getFacadeRoot();

        $this->app['cache']->put($cacheKey, Constants::THROTTLE_UPDATE_CONTACT_MOBILE_LIMIT);

        $this->startTest();
    }

    public function testVerifyContactWithInvalidOtp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID, [UserEntity::CONTACT_MOBILE => '123456789']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testVerifyEmailWithOtp()
    {
        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
                                      [UserEntity::CONFIRM_TOKEN => 'testing123456789', UserEntity::EMAIL => 'abc@rzp.com']);

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($user->getConfirmedAttribute());
    }

    public function testVerifyEmailWithOtpInX()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [UserEntity::CONFIRM_TOKEN => 'testing123456789', UserEntity::EMAIL => 'abc@rzp.com']);

        $this->ba->proxyAuth();

        $this->startTest();

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $this->assertTrue($user->getConfirmedAttribute());
    }

    public function testVerifyEmailWithInvalidOtp()
    {
        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
                                              [UserEntity::CONFIRM_TOKEN => 'testing123456789', UserEntity::EMAIL => 'abc@rzp.com']);

        $this->ba->proxyAuth();

        $this->startTest();

        $this->assertTrue($user->getConfirmedAttribute()===false);
    }

    public function testVerifyEmailWithOtpAlreadyVerified()
    {
        $user = $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
                                      [UserEntity::CONFIRM_TOKEN => NULL, UserEntity::EMAIL => 'abc@rzp.com']);
        $this->ba->proxyAuth();

        $this->startTest();

        $this->assertTrue($user->getConfirmedAttribute());
    }


    public function testVerifyContactWithInvalidToken()
    {
        $this->markTestSkipped('Todo: Not possible with current implementation!');
    }

    public function testResetMerchantUserPassword()
    {
        $this->setPasswordResetTestData(__FUNCTION__);

        $this->startTest();
    }

    /**
     * Admin belongs to a different org than the one merchant
     * belongs to, admin has access to all merchants in his
     * org, and is not attached to the particular merchant
     */
    public function testResetDiffOrgMerchantUserPassword()
    {
        /** @var Admin\Entity $admin */
        $admin = $this->setPasswordResetTestData(__FUNCTION__);

        $admin->merchants()->detach('10000000000000');

        $admin->setAllowAllMerchants();

        $admin->saveOrFail();

        $org = $this->fixtures->create('org');

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $org['id']]);

        $this->startTest();
    }

    /**
     * Merchant not assigned to the admin, admin can view all
     * merchants in his org, both belong to same org
     */
    public function testResetNonLinkedMerchantUserPassword()
    {
        /** @var Admin\Entity $admin */
        $admin = $this->setPasswordResetTestData(__FUNCTION__);

        $admin->merchants()->detach('10000000000000');

        $admin->setAllowAllMerchants();

        $admin->saveOrFail();

        $this->startTest();
    }

    /**
     * Admin does not have the permission to take this action
     */
    public function testResetMerchantUserPasswordNoPermission()
    {
        $admin = $this->setPasswordResetTestData(__FUNCTION__);

        $role = $admin->roles()->first();

        $perms = ['user_password_reset'];

        $perm = (new Permission\Repository)->retrieveIdsByNames($perms)[0];

        $role->permissions()->detach($perm);

        $this->startTest();
    }

    /**
     * User does not belong to the merchant's team
     */
    public function testResetMerchantNonLinkedUserPassword()
    {
        $this->setPasswordResetTestData(__FUNCTION__);

        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'] . '/password';

        $this->startTest();
    }

    /**
     * User is not the owner of the merchant account and
     * he also belongs to another team
     */
    public function testResetMerchantNonOwnerUserPassword()
    {
        $this->setPasswordResetTestData(__FUNCTION__);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'finance');

        $merchant2 = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user['id'],
            'merchant_id' => $merchant2['id'],
            'role'        => 'owner',
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'] . '/password';

        $this->startTest();
    }

    public function testSetAccountLock()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->create('permission', ['name' => 'user_account_lock_unlock']);

        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users-admin/account/' . $user['id'] . '/lock';

        $testData['response']['content']['user_id'] = $user['id'];

        $this->startTest();

        $userDbRecord = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($userDbRecord['account_locked'], 1);
    }

    public function testSetAccountUnlock()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->create('permission', ['name' => 'user_account_lock_unlock']);

        $this->ba->adminAuth();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users-admin/account/' . $user['id'] . '/unlock';

        $testData['response']['content']['user_id'] = $user['id'];

        $this->startTest();

        $userDbRecord = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($userDbRecord['account_locked'], 0);
        $this->assertEquals($userDbRecord['wrong_2fa_attempts'], 0);
    }

    public function testSetAccountLockByMerchant()
    {
        $ownerUser = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $merchant->setRestricted(true);

        $merchant->saveOrFail();

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'manager',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/account/' . $user['id'] . '/lock';

        $this->startTest();
    }

    public function testSetAccountUnlockByMerchant()
    {
        $ownerUser = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $merchant->setRestricted(true);

        $merchant->saveOrFail();

        $mappingData = [
            'user_id'     => $ownerUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $ownerUser['id']);

        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'manager',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/account/' . $user['id'] . '/unlock';

        $testData['response']['content']['user_id'] = $user['id'];

        $this->startTest();

        $userDbRecord = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($userDbRecord['account_locked'], 0);
        $this->assertEquals($userDbRecord['wrong_2fa_attempts'], 0);
    }

    protected function setPasswordResetTestData(string $callee)
    {
        $testData = & $this->testData[$callee];

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $password = str_random(16) . 'a1';

        $testData['request']['content']['password'] = $password;

        $testData['request']['content']['password_confirmation'] = $password;

        return $admin;
    }

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');
    }

    protected function disableRazorXTreatmentCAC()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) {
                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }

                    return 'control';
                }));
    }

    protected function enableRazorXTreatmentForBlockBankingRoutes()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === 'block_banking_requests')
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    protected function enableRazorXTreatmentForRxAclDenyUnauthorized()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === 'razorpay_x_acl_deny_unauthorised')
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    public function enableRazorXTreatmentForRazorXForOrgLevel2Fa()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === 'org_level_2fa_enforced_functionality')
                    {
                        return 'on';
                    }

                    return 'off';
                }));
    }

    public function testPostSendOtpForContactMobileUpdate()
    {
        $user = $this->fixtures->create('user');

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->setRestricted(true, $merchantIds[0]);

        $this->ba->proxyAuth('rzp_test_' . $merchantIds[0], $user['id'], 'owner');

        $this->mockRedisSuccess(__FUNCTION__, $user->getId());

        $this->startTest();
    }

    public function testEditContactMobileByUser()
    {
        $user = $this->fixtures->create('user');

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->setRestricted(true, $merchantIds[0]);

        $this->ba->proxyAuth('rzp_test_' . $merchantIds[0], $user['id'], 'owner');

        $this->mockRedisSuccess(__FUNCTION__, $user->getId());

        $this->startTest();
    }

    public function testEditContactMobileByUserRestrictedForManagerRole()
    {
        $user = $this->fixtures->create('user');

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->setRestricted(true, $merchantIds[0]);

        $user2 = $this->fixtures->user->createUserForMerchant($merchantIds[0],[],'manager');

        $this->ba->proxyAuth('rzp_test_' . $merchantIds[0], $user2['id']);

        $this->mockRedisSuccess(__FUNCTION__, $user2->getId());

        $this->startTest();
    }

    public function testEditContactMobileByUserAndVerify()
    {
        $user = $this->fixtures->create('user', [
            UserEntity::CONTACT_MOBILE      => '9123456789',
        ]);

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->ba->dashboardGuestAppAuth();
        $this->ba->setAppAuthHeaders(['X-Dashboard-User-Id' => $user['id']]);

        $response = $this->startTest();

        $userDb = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($response['contact_mobile_verified'], true);

        $this->assertEquals(
            $response['contact_mobile_verified'],
            $userDb['contact_mobile_verified']);
    }

    public function test2faVerifyWrongOtp()
    {
        $user = $this->fixtures->create('user', [
            UserEntity::CONTACT_MOBILE      => '9123456789',
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->ba->setAppAuthHeaders(['X-Dashboard-User-Id' => $user['id']]);

        $this->startTest();

    }

    public function testVerifyOtpValidationFailure()
    {
        $user = $this->fixtures->create('user');

        $this->ba->dashboardGuestAppAuth();

        $this->ba->setAppAuthHeaders(['X-Dashboard-User-Id' => $user['id']]);

        $this->startTest();
    }

    public function testEditContactMobileWhichIsVerifiedByUser()
    {
        $user = $this->fixtures->create('user', [
            UserEntity::CONTACT_MOBILE          => '9123456789',
            UserEntity::CONTACT_MOBILE_VERIFIED => true,
        ]);

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->ba->proxyAuth(
            'rzp_test_' . $merchantIds[0],
            $user['id'],
            'owner');

        $this->mockRedisSuccess(__FUNCTION__, $user->getId());

        $this->startTest();
    }

    public function testUpdateContactMobile()
    {
        $user = $this->fixtures->create('user');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['user_id'] = $user->getId();

        $this->ba->adminAuth();

        $response = $this->startTest();

        $userDB = $this->getDbEntityById('user', $user->getId());

        $this->assertEquals($userDB['contact_mobile_verified'], false);

        $this->assertEquals($userDB['contact_mobile'], $response['contact_mobile']);
    }

    public function testGetForUsersWithBusinessBankingEnabled()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCdE',
            'account_number'        =>  '2224440041626998',
            'balance_id'            =>  $this->bankingBalance->getId(),
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes);

        $this->disableRazorXTreatmentCAC();

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000',
                                                            $attributes = ['id' => '30000000000000'],
                                                            $role = 'owner',
                                                            $mode = 'test');

        $this->ba->dashboardGuestAppAuth();

        $data = $this->startTest();

        $this->assertEquals($merchantUser['created_at'], $data['merchants'][0]['business_banking_signup_at']);

        Carbon::setTestNow();
    }

    public function testIDORBlockForGetUsersViaDashboardGuestAppAuth()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id'=>'20000000000000']);

        $this->startTest();
    }

    public function testGetForUsersWithBusinessBankingEnabledForRblCA()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->setUpMerchantForBusinessBanking(false, 1000000, AccountType::DIRECT,
        Channel::RBL);

        $this->disableRazorXTreatmentCAC();

        $basd = $this->fixtures->create('banking_account_statement_details', [
            'id'                      => 'xbasd000000003',
            'account_number'          => '2224440041626998',
            'status'                  => 'active',
            'merchant_id'             => '10000000000000',
            'balance_id'              => $this->bankingBalance->getId(),
            'gateway_balance'         => 2500,
            'balance_last_fetched_at' => 1565944927,
            'account_type'            => 'direct',
            'channel'                 => 'rbl',
        ]);

        $this->fixtures->user->createBankingUserForMerchant('10000000000000',
                                                            $attributes = ['id' => '30000000000000'],
                                                            $role = 'owner',
                                                            $mode = 'test');

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testGetForUsersWithBankingAccountForIciciCA()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->setUpMerchantForBusinessBanking(false, 1000000, AccountType::DIRECT,
                                               Channel::ICICI);

        $this->fixtures->user->createBankingUserForMerchant('10000000000000',
                                                            $attributes = ['id' => '30000000000000'],
                                                            $role = 'owner',
                                                            $mode = 'test');

        $this->disableRazorXTreatmentCAC();

        $this->fixtures->create('banking_account_statement_details',[
            Details\Entity::ID             => 'xbas0000000002',
            Details\Entity::MERCHANT_ID    => '10000000000000',
            Details\Entity::BALANCE_ID     => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER => '2224440041626905',
            Details\Entity::CHANNEL        => Details\Channel::ICICI,
            Details\Entity::STATUS         => Details\Status::ACTIVE,
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testGetForUsersWithBankingAccountForCAHavingGatewayBalance()
    {
        $this->setMockRazorxTreatment([RazorxTreatment::USE_GATEWAY_BALANCE    => 'on', RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_DISABLED => 'on', RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_ENABLED => 'off']);

        $this->setUpMerchantForBusinessBanking(false, 1000000, AccountType::DIRECT,
                                               Channel::ICICI);

        $this->fixtures->user->createBankingUserForMerchant('10000000000000',
                                                            $attributes = ['id' => '30000000000000'],
                                                            $role = 'owner',
                                                            $mode = 'test');

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => '10000000000000',
            Details\Entity::BALANCE_ID              => $this->bankingBalance->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '2224440041626905',
            Details\Entity::CHANNEL                 => Details\Channel::ICICI,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 30000000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetForUsersWithBusinessBankingEnabledForRblCAWithArchivedStatus()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->setUpMerchantForBusinessBanking(false, 1000000, AccountType::DIRECT,
            Channel::RBL);

        $basd = $this->fixtures->create('banking_account_statement_details', [
            'id'                      => 'xbasd000000003',
            'account_number'          => '2224440041626998',
            'status'                  => 'archived',
            'merchant_id'             => '10000000000000',
            'balance_id'              => $this->bankingBalance->getId(),
            'gateway_balance'         => 2500,
            'balance_last_fetched_at' => 1565944927,
            'account_type'            => 'direct',
            'channel'                 => 'rbl',
        ]);

        $this->fixtures->user->createBankingUserForMerchant('10000000000000',
            $attributes = ['id' => '30000000000000'],
            $role = 'owner',
            $mode = 'test');

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        Carbon::setTestNow();

        $this->assertArrayNotHasKey('ca_activation_status',$response['merchants'][0]);
    }

    public function testGetForUsersWithBusinessBankingEnabledWithTwoRblCAWhereOneIsArchived()
    {
        $oldDateTime = Carbon::create(2019, 7, 21, 12, 23, 41, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->setUpMerchantForBusinessBanking(false, 1000000, AccountType::DIRECT,
            Channel::RBL);

        $this->fixtures->edit('banking_account','ABCde1234ABCde',['status'=>'archived']);

        $this->fixtures->create('banking_account_statement_details', [
            'id'                      => 'xbasd000000003',
            'account_number'          => '2224440041626998',
            'status'                  => 'archived',
            'merchant_id'             => '10000000000000',
            'balance_id'              => $this->bankingBalance->getId(),
            'gateway_balance'         => 2500,
            'balance_last_fetched_at' => 1565944927,
            'account_type'            => 'direct',
            'channel'                 => 'rbl',
        ]);

        $this->fixtures->create('banking_account_statement_details', [
            'id'                      => 'xbasd000000002',
            'account_number'          => '2224440041626907',
            'status'                  => 'active',
            'merchant_id'             => '10000000000000',
            'balance_id'              => 'KmzovypWyOY1kS',
            'gateway_balance'         => 1600,
            'balance_last_fetched_at' => 1592556993,
            'account_type'            => 'direct',
            'channel'                 => 'rbl',
        ]);

        $this->fixtures->user->createBankingUserForMerchant('10000000000000',
            $attributes = ['id' => '30000000000000'],
            $role = 'owner',
            $mode = 'test');

        $this->fixtures->create('balance',
            [
                'id'             => 'KmzovypWyOY1kS',
                'type'           => 'banking',
                'account_type'   => 'direct',
                'account_number' => '2224440041626907',
                'merchant_id'    => '10000000000000',
                'balance'        => 30000
            ]);

        $this->fixtures->create('banking_account', [
            'id'                      => 'xba00000000002',
            'account_number'          => '2224440041626907',
            'status'                  => 'activated',
            'merchant_id'             => '10000000000000',
            'gateway_balance'         => 2500,
            'balance_last_fetched_at' => 1565944927,
            'account_type'            => 'direct',
            'balance_id'              => 'KmzovypWyOY1kS'
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        Carbon::setTestNow();
    }

    public function testGetBankingUserWithPermissions()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey(Constants::PERMISSIONS, $response['merchants'][1]);

        return $response;
    }

    public function testGetBankingUserWithPermissionsForSubMerchant()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('feature', [
            'name'        => 'assume_sub_account',
            'entity_id'   => $merchant->getId(),
            'entity_type' => 'merchant'
        ]);

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey(Constants::PERMISSIONS, $response['merchants'][1]);

        $subMerchantBlockedPermissions = UserRolePermissionsMap::$restrictedPermissions['account_sub_account']['sub_merchant'];

        foreach ($subMerchantBlockedPermissions as $permission)
        {
            $this->assertNotContains($permission, $response['merchants'][1]['permissions']);
        }
    }

    public function testGetPermissionsForCARoles()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => Role::CHARTERED_ACCOUNTANT,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertArraySelectiveEquals(UserRolePermissionsMap::getRolePermissions(Role::CHARTERED_ACCOUNTANT), $response['merchants'][1]['permissions']);
    }

    public function testGetBankingUserWithMerchantRulesWithPermissionNotPresent()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'operations',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => $merchant->getId(),
                'product'     => 'banking',
                'group'       => 'x_transaction_view',
                'type'        => 'operations',
                'value'       => 'true'
            ]);

        $merchantAttribute = $this->getDbLastEntity('merchant_attribute');

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();
    }

    public function testGetBankingUserWithMerchantRulesWithPermissionFalse()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'admin',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => $merchant->getId(),
                'product'     => 'banking',
                'group'       => 'x_transaction_view',
                'type'        => 'admin',
                'value'       => 'false'
            ]);

        $merchantAttribute = $this->getDbLastEntity('merchant_attribute');

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();
    }

    public function testGetBankingUserWithPermissionsNull()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'random_role',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://x.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertEquals([], $response['merchants'][1][Constants::PERMISSIONS]);
    }

    public function testVerifyUserThroughEmail()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testVerifyUserThroughMode()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testEditContactMobileByUserOnBankingWithoutAuthToken()
    {
        $user = $this->fixtures->user->createUserForMerchant('10000000000000', ['email' => 'test@razorpay.com']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->setRestricted(true, '10000000000000');

        $this->ba->proxyAuth('rzp_test_' . '10000000000000', $user['id'], 'owner');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->startTest();
    }
    protected function mockRedisSuccess($funcName, $userId)
    {
        $token = $this->app['token_service']->generate($userId);

        $this->testData[$funcName]['request']['content']['otp_auth_token'] = $token;
    }

    public function testEditContactMobileWhichIsVerifiedByUserOnBanking()
    {
        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
                UserEntity::CONTACT_MOBILE              => '9123456789',
                UserEntity::CONTACT_MOBILE_VERIFIED     => true,
            ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId(), 'owner');

        $this->mockRedisSuccess(__FUNCTION__, $user->getId());

        $response = $this->startTest();

        $userEntityFromDb = $this->getDbEntityById('user', $user->getId());

        $this->assertEquals(
            $response['contact_mobile_verified'],
            $userEntityFromDb['contact_mobile_verified']);

        $this->assertEquals(
            $response['contact_mobile'],
            $userEntityFromDb['contact_mobile']);

    }

    public function testEditContactMobileByUserOnBankingWithOauthToken()
    {
        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            UserEntity::CONTACT_MOBILE              => '9123456789',
            UserEntity::CONTACT_MOBILE_VERIFIED     => true,
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId(), 'owner');

        $this->mockRedisSuccess(__FUNCTION__, $user->getId());

        $response = $this->startTest();

        $userEntityFromDb = $this->getDbEntityById('user', $user->getId());

        $this->assertEquals(
            $response['contact_mobile_verified'],
            $userEntityFromDb['contact_mobile_verified']);

        $this->assertEquals(
            $response['contact_mobile'],
            $userEntityFromDb['contact_mobile']);
    }

    public function testEditContactMobileByUserAndVerifyForBanking()
    {
        $user = $this->fixtures->create('user', [
            UserEntity::CONTACT_MOBILE      => '8877666666',
        ]);

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->setRestricted(true, $merchantIds[0]);

        $this->ba->dashboardGuestAppAuth();
        $this->ba->setAppAuthHeaders(['X-Dashboard-User-Id' => $user['id']]);

        $response = $this->startTest();

        $userDb = $this->getDbEntityById('user', $user['id']);

        $this->assertEquals($response['contact_mobile_verified'], true);

        $this->assertEquals($response['contact_mobile_verified'], $userDb['contact_mobile_verified']);

        $this->assertEquals($response['contact_mobile'], $userDb['contact_mobile']);
    }

    public function testSendOtpViaEMail()
    {
        $this->createContact();
        $this->createFundAccount();

        Mail::fake();

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function($mail) {
            $this->assertEquals('create_payout', $mail->input['action']);
            $this->assertNotEmpty($mail->user);
            $this->assertNotEmpty($mail->otp);
            $this->assertTrue(strpos($mail->subject, (string) Carbon::now(Timezone::IST)->format('d-M (D)')) !== false);
            $this->assertEquals('emails.user.otp_create_payout', $mail->view);
            $this->assertTrue(($mail->otp['expires_at'] > (Carbon::now(Timezone::IST)->getTimestamp() + 19800)) === true);
            $this->assertTrue(($mail->otp['expires_at'] < (Carbon::now(Timezone::IST)->addMinutes(45)->getTimestamp() + 19800)) === true);

            return true;
        });
    }

    public function testSendBulkPayoutOtpViaEMail()
    {
        $this->createContact();
        $this->createFundAccount();

        Mail::fake();

        $this->ba->proxyAuth();

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $this->fundAccount->getPublicId();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function($mail) {
            $this->assertEquals('create_payout_batch', $mail->input['action']);
            $this->assertNotEmpty($mail->user);
            $this->assertNotEmpty($mail->otp);
            $this->assertEquals(10000, $mail->input['total_payout_amount']);
            $this->assertEquals('emails.user.otp_create_payout_batch', $mail->view);
            $this->assertTrue(($mail->otp['expires_at'] > (Carbon::now(Timezone::IST)->getTimestamp() + 19800)) === true);
            $this->assertTrue(($mail->otp['expires_at'] < (Carbon::now(Timezone::IST)->addMinutes(45)->getTimestamp() + 19800)) === true);

            return true;
        });
    }

    public function testSendBulkPayoutLinksOtpViaEMail()
    {
        Mail::fake();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(Otp::class, function($mail) {
            $this->assertEquals('create_bulk_payout_link', $mail->input['action']);
            $this->assertNotEmpty($mail->user);
            $this->assertNotEmpty($mail->otp);
            $this->assertEquals(10000, $mail->input['total_payout_link_amount']);
            $this->assertEquals('emails.user.otp_create_bulk_payout_link', $mail->view);
            $this->assertTrue(($mail->otp['expires_at'] > (Carbon::now(Timezone::IST)->getTimestamp() + 19800)) === true);
            $this->assertTrue(($mail->otp['expires_at'] < (Carbon::now(Timezone::IST)->addMinutes(45)->getTimestamp() + 19800)) === true);

            return true;
        });
    }

    public function testChangeBankingUserRole()
    {
        $this->ba->privateAuth();

        $input = ["experiment_id" => "JuzQGh5pQfqNU9", "id" => '10000000000000'];
        $output = ["response" => ["variant" => ["name" => 'enable']]];

        $this->mockSplitzTreatment($input, $output);

        $output = ["response" => ['variant' => ['name' => 'SyncDeviation Enabled', 'variables' => [['key' => 'enabled', 'value' => 'true']]]]];
        $splitzMock = $this->getSplitzMock();
        $splitzMock->shouldReceive('evaluateRequest')->zeroOrMoreTimes()->with(Mockery::hasKey('experiment_id'))->with(Mockery::hasValue('K1ZaAGS9JfAUHj'))->andReturn($output);

        $merchant = $this->fixtures->create('merchant');
        $user1 = $this->fixtures->create('user');
        $user2 = $this->fixtures->create('user');

        $mappingData = [
            'merchant_id' => $merchant->getId(),
            'user_id'     => $user1->getId(),
            'role'        => Role::OWNER,
            'product'     => 'banking',
        ];
        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $mappingData = [
            'merchant_id' => $merchant->getId(),
            'user_id'     => $user2->getId(),
            'role'        => Role::VIEWER,
            'product'     => 'banking',
        ];
        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['users_list'][0]['merchant_id'] = $merchant->getId();
        $data['request']['content']['users_list'][0]['user_id'] = $user1->getId();
        $data['request']['content']['users_list'][1]['merchant_id'] = $merchant->getId();
        $data['request']['content']['users_list'][1]['user_id'] = $user2->getId();
        $data['request']['content']['users_list'][2]['user_id'] = $user1->getId();
        $data['request']['content']['users_list'][3]['merchant_id'] = $merchant->getId();

        $data['response']['content']['affected_users'][0]['merchant_id'] = $merchant->getId();
        $data['response']['content']['affected_users'][0]['user_id'] = $user1->getId();
        $data['response']['content']['ignored_users'][0]['merchant_id'] = $merchant->getId();
        $data['response']['content']['ignored_users'][0]['user_id'] = $user2->getId();
        $data['response']['content']['ignored_users'][1]['user_id'] = $user1->getId();
        $data['response']['content']['ignored_users'][2]['merchant_id'] = $merchant->getId();

        $this->startTest($data);

        $mapping = $this->fixtures->user->getMerchantUserMapping($merchant->getId(), $user1->getId(), 'banking')->first();
        $this->assertEquals(Role::VIEW_ONLY, $mapping->role);
    }

    public function testChangeBankingUserRoleRevert()
    {
        $this->ba->privateAuth();

        $input = ["experiment_id" => "JuzQGh5pQfqNU9", "id" => '10000000000000'];
        $output = ["response" => ["variant" => ["name" => 'enable']]];

        $this->mockSplitzTreatment($input, $output);

        $output = ["response" => ['variant' => ['name' => 'SyncDeviation Enabled', 'variables' => [['key' => 'enabled', 'value' => 'true']]]]];
        $splitzMock = $this->getSplitzMock();
        $splitzMock->shouldReceive('evaluateRequest')->zeroOrMoreTimes()->with(Mockery::hasKey('experiment_id'))->with(Mockery::hasValue('K1ZaAGS9JfAUHj'))->andReturn($output);

        $merchant = $this->fixtures->create('merchant');
        $user = $this->fixtures->create('user');

        $mappingData = [
            'merchant_id' => $merchant->getId(),
            'user_id'     => $user->getId(),
            'role'        => Role::VIEW_ONLY,
            'product'     => 'banking',
        ];
        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['users_list'][0]['merchant_id'] = $merchant->getId();
        $data['request']['content']['users_list'][0]['user_id'] = $user->getId();

        $data['response']['content']['affected_users'][0]['merchant_id'] = $merchant->getId();
        $data['response']['content']['affected_users'][0]['user_id'] = $user->getId();

        $this->startTest($data);

        $mapping = $this->fixtures->user->getMerchantUserMapping($merchant->getId(), $user->getId(), 'banking')->first();
        $this->assertEquals(Role::OWNER, $mapping->role);
    }

    public function testGetUserAndCheckEnabledMethods()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->merchant->enableMethod('10000000000000', 'paypal');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
            ],
        ];

        $testData['request'] = $request;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertArrayHasKey('methods', $response['merchants'][0]);

        $this->assertArrayHasKey('paypal', $response['merchants'][0]['methods']);

        $this->assertEquals(1, $response['merchants'][0]['methods']['paypal']);
    }

    public function testOptOutForWhatsapp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE          => '9999999999',
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOptInStatusForWhatsapp()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE          => '9999999999',
            ]);

        $this->ba->proxyAuth();

        $this->expectStorkServiceRequestForAction('optInStatusForWhatsapp');

        $this->startTest();
    }

    public function testUserDetails()
    {
        $user = $this->fixtures->create('user');

        $merchant = $user->primaryMerchants()->first();

        // check the data for default test merchant
        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'GET',
                'url'       => '/users?email='.$user['email'],
            ],
            'response' => [
                'content' => [
                    'name'                      => $user->getName(),
                    'email'                     => $user->getEmail(),
                    'contact_mobile'            => NULL,
                    'contact_mobile_verified'   => FALSE,
                    'account_locked'            => FALSE,
                    'confirmed'                 => TRUE,
                    'merchants' => [
                        [
                            'gstin'             => NULL,
                            'pan'               => NULL,
                            'billing_address'   => NULL,
                            'id'                => $merchant->getId(),
                            'activated'         => FALSE,
                            'website'           => $merchant->getWebsite(),
                            'name'              => $merchant->getName(),
                            'description'       => NULL,
                            'billing_label'     => $merchant->getBillingLabelNotName(),
                            'purpose_code'      => $merchant->getPurposeCode(),
                            'purpose_code_desc' => $merchant->getPurposeCodeDescription(),
                            'iec_code'          => $merchant->getIecCode(),
                        ],
                    ],
                ],
            ]
        ];

        $this->ba->xpayrollAuth();

        $this->startTest();
    }

    public function testUserRolesInvalidMapping()
    {
        $this->ba->xpayrollAuth();

        $user = $this->fixtures->create('user');

        $merchant = $user->primaryMerchants()->first();

        // check the data for default test merchant
        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'GET',
                'url'       => '/users/EM6yk5JbG6L9rv/roles/' . $merchant->getId(),
            ],
            'response' => [
                'content' => [],
            ],
        ];

        $this->startTest();

        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'GET',
                'url'       => '/users/' . $user->getId() . '/roles/100000Razorpay',
            ],
            'response' => [
                'content' => [],
            ],
        ];

        $this->startTest();
    }

    public function testUserDetailsUnified()
    {
        $user = $this->fixtures->create('user');

        $merchant = $user->merchants()->first();

        // check the data for default test merchant
        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'GET',
                'url'       => '/users_unified?email='.$user['email'],
            ],
            'response' => [
                'content' => [
                    'id'                      => $user->getId(),
                    'name'                    => $user->getName(),
                    'email'                   => $user->getEmail(),
                    'merchants' => [
                        [
                            'id'                => $merchant->getId(),
                            'role'              => 'owner',
                        ],
                    ],
                ],
            ]
        ];

        $authServiceConfig = \Config::get('applications.auth_service');
        $pwd = $authServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $this->startTest();
    }

    public function testGetUserEntity()
    {
        $user = $this->fixtures->create('user', [
            'contact_mobile'    => '9876543210',
        ]);

        $cardsServiceConfig = \Config::get('applications.capital_cards_client');
        $pwd = $cardsServiceConfig['secret'];

        $this->ba->appAuth('rzp_'.'test', $pwd);

        $this->testData[__FUNCTION__]['request']['url'] .= $user['id'];

        $this->startTest();
    }

    public function testOrg2faEnforced()
    {
        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 1,
                UserEntity::CONTACT_MOBILE          => '9999999999',
                UserEntity::SECOND_FACTOR_AUTH      => 0,
                UserEntity::PASSWORD                => 'hello123',
            ]);

        $this->disableRazorXTreatmentCAC();

        $this->fixtures->edit('org', '100000razorpay', [OrgEntity::MERCHANT_SECOND_FACTOR_AUTH => 1]);

        $this->fixtures->edit('merchant', '10000000000000', [
            MerchantEntity::ORG_ID => '100000razorpay',
            MerchantEntity::SECOND_FACTOR_AUTH => 0
        ]);

        $user = $this->getDbEntityById('user', UserFixture::MERCHANT_USER_ID);

        $testData = & $this->testData[__FUNCTION__];

        $request = [
            'method'    => 'GET',
            'url'       => '/users/' . $user->getId(),
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
            ],
        ];

        $content = [
            'id' => UserFixture::MERCHANT_USER_ID
        ];

        $testData['request'] = $request;

        $testData['response']['content'] = $content;

        $this->ba->appAuth();

        $this->startTest();

        $this->assertTrue($user->isOrgEnforcedSecondFactorAuth());
 }

    public function testResetPasswordUnlocksAccountForOwner()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $resetAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
            'password'              => 'hello123',
            'account_locked'        => true,
            'second_factor_auth'    => true,
        ];

        $user = $this->fixtures->create('user', $resetAttributes);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        // Adding this since we want to revoke mobile oauth token in case of reset password
        $authServiceMock = $this->createAuthServiceMock(['getMultipleApplications']);

        $authServiceMock
            ->expects($this->exactly(1))
            ->method('getMultipleApplications')
            ->willReturn(['items'=>[]]);

        $this->doTestPasswordResetByToken($user);

        $user = $this->getDbEntityById('user', $user->getId());

        $this->assertFalse($user->isAccountLocked());

        $this->assertEquals(0, $user->getWrong2faAttempts());
    }
    // for nonrzp orgs, on "logging-in as merchant" from admin dashboard -> 'merchant_dashboard' app calls
    // user_admin_fetch in admin-auth. Test asserts that the requests succeeds
    public function testGetUserForAdminFromMerchantDashboardApp()
    {
        $userId = $this->fixtures->create('user')->getId();

        $appAuthCaller = 'appAuth' . studly_case('test');

        $this->ba->$appAuthCaller(\Config::get('applications.merchant_dashboard')['secret']);

        $this->ba->setType('admin');

        $this->ba->addAdminAuthHeaders();

        $this->testData[__FUNCTION__]['request']['url'] .= $userId;

        $this->testData[__FUNCTION__]['response']['content']['id'] .= $userId;

        $this->startTest();
    }

    // see test scenario for 'testGetUserForAdminFromMerchantDashboardApp'
    // because we have added 'user_fetch_admin' in 'merchant_dashboard' app, asserting in this test case that
    // merchant trying to hit this route in proxy auth should fail
    public function testGetUserForAdminInProxyAuthShouldFail()
    {
        $this->ba->proxyAuth();

        $userId = $this->fixtures->create('user')->getId();

        $this->testData[__FUNCTION__]['request']['url'] .= $userId;

        $this->startTest();
    }

    public function testUserAccessWithProductBankingViaFrontendGraphqlAuth()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $this->disableRazorXTreatmentCAC();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'content'   => [
                'merchant_id'   => $merchant->getId(),
            ],
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => config('applications.banking_service_url'),
            ],
        ];

        $testData['request'] = $request;

        $this->ba->frontendGraphqlAuth();

        $this->startTest();

        $this->assertEquals('banking', $this->app['basicauth']->getRequestOriginProduct());
    }

    public function testUserAccessWithProductPrimaryViaFrontendGraphqlAuth()
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

         $request =[
            'method'    => 'GET',
            'url'       => '/users/access',
            'content'   => [
                'merchant_id'   => $merchant->getId(),
            ],
            'server'     => [
                'HTTP_X-Dashboard-User-Id'      => $user->getId(),
                'HTTP_X-Request-Origin'         => 'https://dashboard.razorpay.com',
            ],
        ];

        $testData['request'] = $request;

        $this->ba->frontendGraphqlAuth();

        $this->startTest();

        $this->assertEquals('primary', $this->app['basicauth']->getRequestOriginProduct());
    }

    /**
     * Scenario: if 2FA is not enabled for the user and neither is org level 2fa enforced, then owner users should
     * not be able to unlock their accounts by resetting their password
     */
    public function testResetPasswordDoesNotUnlockOwnerUserIf2faNotEnabled()
    {
        Mail::fake();

        $this->fixtures->edit('org', '100000razorpay', [OrgEntity::MERCHANT_SECOND_FACTOR_AUTH => 0]);

        $userAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
            'password'              => 'hello123',
            'account_locked'        => true,
            'second_factor_auth'    => false,
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $authServiceMock = $this->createAuthServiceMock(['getMultipleApplications']);

        $authServiceMock
            ->expects($this->exactly(1))
            ->method('getMultipleApplications')
            ->willReturn(['items'=>[]]);

        $this->doTestPasswordResetByToken($user);

        $user = $this->getDbEntityById('user', $user->getId());

        $this->assertTrue($user->isAccountLocked());
    }

    /**
     * Scenario: if 2FA is enabled for the user, then owner users should be able to
     * unlock their accounts by resetting their password
     */
    public function testResetPasswordUnlocksAccountForOwner2FaEnabled()
    {
        Mail::fake();

        $this->fixtures->edit('org', '100000razorpay', [OrgEntity::MERCHANT_SECOND_FACTOR_AUTH => 0]);

        $userAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
            'password'              => 'hello123',
            'account_locked'        => true,
            'second_factor_auth'    => true,
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $authServiceMock = $this->createAuthServiceMock(['getMultipleApplications']);

        $authServiceMock
            ->expects($this->exactly(1))
            ->method('getMultipleApplications')
            ->willReturn(['items'=>[]]);

        $this->doTestPasswordResetByToken($user);

        $user = $this->getDbEntityById('user', $user->getId());

        $this->assertFalse($user->isAccountLocked());
    }

    /**
     * Scenario: if org level 2FA is enabled, then owner users should be able to
     * unlock their accounts by resetting their password
     */
    public function testResetPasswordUnlocksAccountForOwnerOrgLevel2FaEnabled()
    {
        Mail::fake();

        $this->fixtures->edit('org', '100000razorpay', [OrgEntity::MERCHANT_SECOND_FACTOR_AUTH => 1]);

        $userAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
            'password'              => 'hello123',
            'account_locked'        => true,
            'second_factor_auth'    => true,
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth();

        $authServiceMock = $this->createAuthServiceMock(['getMultipleApplications']);

        $authServiceMock
            ->expects($this->exactly(1))
            ->method('getMultipleApplications')
            ->willReturn(['items'=>[]]);

        $this->doTestPasswordResetByToken($user);

        $user = $this->getDbEntityById('user', $user->getId());

        $this->assertFalse($user->isAccountLocked());

    }

    public function testResetPasswordNotUnlocksAccountForNonOwner()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $resetAttributes = [
            'email'                 => 'resetpass@razorpay.com',
            'password_reset_token'  => str_random(50),
            'password_reset_expiry' => Carbon::now()->timestamp + Constants::PASSWORD_RESET_TOKEN_EXPIRY_TIME,
            'password'              => 'hello123',
            'account_locked'        => true,
            'second_factor_auth'    => true,
        ];

        $user = $this->fixtures->create('user', $resetAttributes);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'manager',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $authServiceMock = $this->createAuthServiceMock(['getMultipleApplications']);

        $authServiceMock
            ->expects($this->exactly(1))
            ->method('getMultipleApplications')
            ->willReturn(['items'=>[]]);

        $this->doTestPasswordResetByToken($user);

        $user = $this->getDbEntityById('user', $user->getId());

        $this->assertTrue($user->isAccountLocked());
    }

    public function testUserPurposeCodeDetails()
    {
        $merchant = $this->fixtures->create('merchant', [
            'email'        => 'test@razorpay.com'
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'email' => $merchant->getEmail(),
            'name' => $merchant->getName()
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'iec_code'=> 'iec_code_x',
        ]);

        // check the data for default test merchant
        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'GET',
                'url'       => '/users/purpose/code',
            ],
            'response' => [
                'content' => [
                    'name'                      => $user->getName(),
                    'email'                     => $user->getEmail(),
                    'contact_mobile'            => NULL,
                    'contact_mobile_verified'   => FALSE,
                    'account_locked'            => FALSE,
                    'confirmed'                 => TRUE,
                    'merchants' => [
                        [
                            'gstin'             => NULL,
                            'pan'               => NULL,
                            'id'                => $merchant->getId(),
                            'activated'         => FALSE,
                            'website'           => $merchant->getWebsite(),
                            'name'              => $merchant->getName(),
                            'description'       => NULL,
                            'billing_label'     => $merchant->getBillingLabelNotName(),
                            'purpose_code'      => $merchant->getPurposeCode(),
                            'purpose_code_desc' => $merchant->getPurposeCodeDescription(),
                            'iec_code'          => $merchant->getIecCode(),
                        ],
                    ],
                ],
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' .$merchant->getId(), $user['id'], 'owner');

        $this->startTest();
    }

    public function testGetIecCode()
    {
        $merchant = $this->fixtures->create('merchant', [
            'purpose_code' => PurposeCodeList::P0001,
            'email'        => 'test@razorpay.com'
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'email' => $merchant->getEmail(),
            'name' => $merchant->getName()
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'iec_code'=> 'iec_code_x',
        ]);

        // check the data for default test merchant
        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'GET',
                'url'       => '/users/purpose/code',
            ],
            'response' => [
                'content' => [
                    'name'                      => $user->getName(),
                    'email'                     => $user->getEmail(),
                    'contact_mobile'            => NULL,
                    'contact_mobile_verified'   => FALSE,
                    'account_locked'            => FALSE,
                    'confirmed'                 => TRUE,
                    'merchants' => [
                        [
                            'gstin'             => NULL,
                            'pan'               => NULL,
                            'id'                => $merchant->getId(),
                            'activated'         => FALSE,
                            'website'           => $merchant->getWebsite(),
                            'name'              => $merchant->getName(),
                            'description'       => NULL,
                            'billing_label'     => $merchant->getBillingLabelNotName(),
                            'purpose_code'      => $merchant->getPurposeCode(),
                            'purpose_code_desc' => $merchant->getPurposeCodeDescription(),
                            'iec_code'          => $merchant->getIecCode(),
                        ],
                    ],
                ],
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' .$merchant->getId(), $user['id'], 'owner');

        $this->startTest();
    }

    public function testUserPatchPurposeCode()
    {
        $iecCode = 'iec_code_x';

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'PATCH',
                'url'       => '/merchants/purpose/code',
                'content'   => [
                    'purpose_code' =>  PurposeCodeList::IEC_REQUIRED[0],
                    'iec_code'     => $iecCode,
                ],
            ],
            'response' => [
                'content' => [
                    'success' => true,
                ],
                'status_code' => 200,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $updatedMerchant = DB::table(Table::MERCHANT_DETAIL)
            ->where('merchant_id', '=', $merchantDetail['merchant_id'])
            ->first();

        self::assertEquals($iecCode, $updatedMerchant->iec_code);
    }

    public function testUserPatchPurposeCodeError()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', ['bank_branch_ifsc' => 'ICIC0000006']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->testData[__FUNCTION__] = [
            'request' => [
                'method'    => 'PATCH',
                'url'       => '/merchants/purpose/code',
                'content'   => [
                    'purpose_code' => PurposeCodeList::IEC_REQUIRED[0],
                ],
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'iec code required for given purpose code',
                    ],
                ],
                'status_code' => 400,
            ],
           'exception' => [
                'class'               => \RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testCapitalReferralFlowDuringLogin()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'confirm_token'  => null,
            'signup_via_email' => 1,
            'password' => 'hello123'
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller'], true);

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => '10000000000000',
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => '10000000000000',
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $this->mockCapitalPartnershipSplitzExperiment();

        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockCreateApplicationRequestOnLOSService($losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        $this->mockGetNoApplicationRequestOnLOSService($losServiceMock);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => 'hello123@gmail.com',
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'referral_code'         => 'teslacomikejzc'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test')
            ->toArray();

        $this->assertSame($merchant->getId(), $merchantAccessMap['merchant_id']);

        $this->assertSame('10000000000000', $merchantAccessMap['entity_owner_id']);

        $this->assertContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testCapitalInvalidReferralDuringLogin()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $testData = & $this->testData['testCapitalReferralFlowDuringLogin'];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'referral_code'         => 'invalidCode'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testCapitalReferralWhenMerchantLOCAppExistDuringLogin()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant->getId(), [
            'id'    => "FL0nl7kME8j3Dd",
            'email' => 'hello123@gmail.com',
            'confirm_token'  => null,
            'signup_via_email' => 1,
            'password' => 'hello123'
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller'], true);

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => '10000000000000',
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => '10000000000000',
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $this->mockCapitalPartnershipSplitzExperiment();

        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        $this->mockGetApplicationRequestOnLOSService($losServiceMock);

        $testData = & $this->testData['testCapitalReferralFlowDuringLogin'];

        $content = [
            'email'                 => 'hello123@gmail.com',
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'referral_code'         => 'teslacomikejzc'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testNonCapitalReferralDuringLogin()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'primary']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $testData = & $this->testData['testCapitalReferralFlowDuringLogin'];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'referral_code'         => 'teslacomikejzc'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testCapitalReferralWithIncompleteMerchantDetailDuringLogin()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> null,
            'business_type' => 2
        ]);

        $testData = & $this->testData['testCapitalReferralFlowDuringLogin'];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'referral_code'         => 'teslacomikejzc'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testCapitalReferralWithCapitalLOCTagOnMerchantDuringLogin()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        (new PartnerTest())->markBankingSubmerchantAsCapitalSubmerchant($merchant->getId(), '10000000000000');

        $testData = & $this->testData['testCapitalReferralFlowDuringLogin'];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'referral_code'         => 'teslacomikejzc'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function testCapitalPartnerExpDisabledDuringLogin()
    {
        $user = $this->fixtures->create('user',['password' => 'hello123']);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'reseller']);
        $this->fixtures->create('referrals', ["product" => 'capital']);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant->getId(),
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $testData = & $this->testData['testCapitalReferralFlowDuringLogin'];

        $content = [
            'email'                 => $user['email'],
            'password'              => 'hello123',
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'referral_code'         => 'teslacomikejzc'
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $merchantAccessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $merchant->getId()
            ], 'test');

        $this->assertEmpty($merchantAccessMap);

        $this->assertNotContains('Ref-' . '10000000000000', $merchant->tagNames());
    }

    public function mockCapitalPartnershipSplitzExperiment(): void
    {
        $input = [
            "experiment_id" => "L0rynez0HhIXHb",
            "id" => '10000000000000',
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];
        $this->mockSplitzTreatment($input, $output);
    }

    public function testOtpLoginVerifyWith2FA()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testOtpLoginVerifyWith2FAForMobileOauth()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $merchant = $this->fixtures->create('merchant', [
            'second_factor_auth' => true,
        ]);

        $merchantId = $merchant->getId();

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchantId);

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchantId);

        $this->authServiceMock
            ->expects($this->at(2))
            ->method('sendRequest')
            ->with('token','POST',[
                'client_id'             => 'client_id',
                'client_secret'         => 'client_secret',
                'grant_type'            => 'mobile_app_client_credentials',
                'scope'                 => 'x_mobile_app_2fa_token',
                'mode'                  => 'live',
                'user_id'               => $user->getId()
            ])
            ->willReturn([
                'public_token'       => 'rzp_test_oauth_10000000000000',
                'token_type'         => 'Bearer',
                'expires_in'         => 7862400,
                'access_token'       => 'access_token',
                'refresh_token'      => 'refresh_token',
                'client_id'          => 'client_id',
            ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testOtpLoginVerifyWith2FAWithoutPassword()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
                          ->setConstructorArgs([$this->app])
                          ->setMethods(['verifyOtp'])
                          ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp');

        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile'               => '73491987654454',
                'contact_mobile_verified'      => true,
                UserEntity::SECOND_FACTOR_AUTH => 1,
            ]
        );

        $user->setPasswordNull();

        $user->save();

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function test2faWithPassword()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        return $this->startTest();
    }

    public function test2faWithPasswordForMobileOauth()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $this->disableRazorXTreatmentCAC();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->dashboardGuestAppAuth();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchant->getId());

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchant->getId());

        $this->setAuthServiceMockForPostTokenForMobileApp($user->getId());

        return $this->startTest();
    }

    public function test2faWithPasswordForXReturnsOtpAuthToken()
    {
        $testData = & $this->testData['test2faWithPassword'];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $response = $this->test2faWithPassword();
        $this->assertNotNull($response['otp_auth_token']);
    }

    public function test2faWithOtpForXReturnsOtpAuthToken()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');
        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();
        $this->assertNotNull($response['otp_auth_token']);

    }

    public function test2faWithOtpForXReturnsOtpAuthTokenForMobileOAuth()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $this->disableRazorXTreatmentCAC();

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->setAuthServiceMockForGetApplicationForMobileApp($merchant->getId());

        $this->setAuthServiceMockForPostApplicationForMobileApp($merchant->getId());

        $this->setAuthServiceMockForPostTokenForMobileApp($user->getId());

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['otp_auth_token']);
    }

    public function test2faWithPasswordIncorrectPassword()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function test2faWithPasswordTooManyIncorrectPassword()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
                UserEntity::SECOND_FACTOR_AUTH => 1
            ]
        );

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($user['id'].'_2fa_password_count', Constants::INCORRECT_LOGIN_2FA_PASSWORD_THRESHOLD_COUNT);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testVerifyContactMobile()
    {
        $merchant1 = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant1['id'], ['contact_mobile' =>'1234567890','email'=>'bbsuhas-axis-2@test.com']);

        $merchant2 = $this->fixtures->create('merchant');

        $this->fixtures->user->createUserForMerchant($merchant2['id'], ['contact_mobile' =>'1234567890','email'=>'ajay.icici@icici.com']);

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testUserDeviceDetails()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
            ]
        );

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUserDeviceDetailsSignupSourceIosAppsflyerIdAbsent()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
            ]
        );

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUserDeviceDetailsSignupSourceAndroidAppsflyerIdAbsent()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
            ]
        );

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUserDeviceDetailsSignupSourceNonMobile()
    {
        $user = $this->fixtures->create(
            'user',
            [
                'contact_mobile' => '9012345678',
                'contact_mobile_verified' => true,
                'password' => 'hello123',
            ]
        );

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMobileSendVerificationOtpUnverifiedEmail()
    {
        $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => false, 'confirm_token' => 'notnull']);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'password'              => 'hello123',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileOtpLoginMobileAndEmailUnverified()
    {
        $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'contact_mobile_verified' => false, 'confirm_token' => "notnull"]);

        $testData = &$this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'                 => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testMobileLoginWithNewSmsTemplateAndSendsViaStork()
    {

        $this->fixtures->create('user', [
            'id'                      => '10000000000000',
            'email'                   => 'user@domain.com',
            'password'                => 'hello123',
            'second_factor_auth'      => true,
            'contact_mobile'          => '8766776666',
            'contact_mobile_verified' => true,
        ]);

        $this->ba->appAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.login_otp_v2', '8766776666', []);

        $this->startTest();
    }

    public function testMobileSignupWithNewSmsTemplateAndSendsViaStork()
    {

        $this->ba->appAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.signup_otp_v2', '8766776665', []);

        $this->startTest();
    }

    public function testMobileVerifyOtpForLoginWithNewSmsTemplate()
    {

        $this->fixtures->create('user', [
            'id'                      => '10000000000000',
            'password'                => 'hello123',
            'contact_mobile'          => '+918766776666',
            'contact_mobile_verified' => true,
        ]);

        $this->ba->appAuth();

        $smsPayload = [
            'success'    => true,
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => 'user_id:login_otp_v2:token',
            'origin'     => '@dashboard.razorpay.com'
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp')->with([
            'receiver'  => '+918766776666',
            'context'   => '10000000000000:login_otp_v2:10000000000000',
            'source'    => 'api.user.login_otp_v2',
            'otp'       => '0007'
        ])->willReturn($smsPayload);

        $this->startTest();
    }

    public function testMobileVerifyOtpForSignupWithNewSmsTemplate()
    {

        $this->ba->appAuth();

        $smsPayload = [
            'success'    => true,
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => 'user_id:signup_otp_v2:token',
            'origin'     => '@dashboard.razorpay.com'
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $ravenMock->expects($this->once())->method('verifyOtp')->with([
            'receiver'  => '+918766776664',
            'context'   => '+918766776664:signup_otp_v2:10000000000000',
            'source'    => 'api.user.signup_otp_v2',
            'otp'       => '0007'
        ])->willReturn($smsPayload);

        $this->startTest();
    }

    public function testMobileLoginForXWithNewSmsTemplateAndSendsViaStork()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('user', [
            'id'                      => '10000000000000',
            'email'                   => 'user@domain.com',
            'password'                => 'hello123',
            'second_factor_auth'      => true,
            'contact_mobile'          => '9999999999',
            'contact_mobile_verified' => true,
        ]);

        $this->ba->appAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.user.x_login_otp', '9999999999', []);

        $this->startTest();
    }

    public function testUserRegisterSendSignupOtpViaSms()
    {
        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:signup_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($smsPayload);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '+91 9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);
    }

    public function testUserRegisterSendSignupOtpViaSmsMobileExistsWithCountryCode()
    {
        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData['testUserRegisterSendSignupOtpViaSmsMobileExists'];

        $content = [
            'contact_mobile'        => '+91'. $user1["contact_mobile"],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);
    }

    public function testUserRegisterSendSignupOtpViaSmsMobileExists()
    {
        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9012345678', 'password' => 'hello123', 'contact_mobile_verified' => true]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => $user1["contact_mobile"],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

    }

    public function testUserRegisterSendSignupOtpViaEmail()
    {
        Mail::fake();

        $emailPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:signup_otp:token',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')
            ->willReturn($emailPayload);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'        => 'some.one@some.com',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['token']);

        Mail::assertQueued(OtpSignup::class, function ($mail)
        {
            $this->assertEquals('signup_otp', $mail->input['action']);
            $this->assertNotEmpty($mail->otp);
            $this->assertEquals('emails.user.otp_signup', $mail->view);
            return true;
        });
    }

    public function testUserRegisterFromVendorPortalInvitation()
    {
        Mail::fake();

        $this->fixtures->create('merchant',[ 'id' => '1DummyMerchant' ]);

        $invitation = $this->fixtures->create('invitation', [
            'email'       => 'vendorportal@razorpay.com',
            'merchant_id' => '1DummyMerchant',
            'role'        => 'vendor',
            'product'     => 'banking',
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['invitation'] = $invitation['token'];

        $vendorPortalServiceMock = $this->getMockBuilder(VendorPortalService::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['acceptInvite'])
            ->getMock();

        $vendorPortalServiceMock->expects($this->once())
            ->method('acceptInvite')
            ->willReturn([]);

        $this->app->instance('vendor-portal', $vendorPortalServiceMock);

        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $this->startTest();

        // Validate that invitation is accepted and deleted
        $invite = \DB::table('invitations')
            ->where('id', '=', $invitation['id'])
            ->whereNull('deleted_at')
            ->first();

        $this->assertNull($invite);

        // User is created for given email
        $user = \DB::table('users')
            ->where('email', '=', 'vendorportal@razorpay.com')
            ->first();

        $this->assertNotNull($user);

        // User is attached to given merchant on given role
        $merchants = DB::table('merchant_users')
            ->where('user_id', '=', $user->id)
            ->where('merchant_id', '1DummyMerchant')
            ->first();

        $this->assertEquals('vendor', $merchants->role);
    }

    public function testUserRegisterFoBankPocRole()
    {
        Mail::fake();

        $this->fixtures->create('merchant',[ 'id' => '1DummyMerchant' ]);

        $invitation = $this->fixtures->create('invitation', [
            'email'       => 'random@rbl.com',
            'merchant_id' => '1DummyMerchant',
            'role'        => BankingRole::BANK_MID_OFFICE_POC,
            'product'     => 'banking',
        ]);

        $merchant = $this->getDbEntityById('merchant', '1DummyMerchant');

        (new MerchantCore())->appendTag($merchant, \RZP\Models\Merchant\Constants::ENABLE_RBL_LMS_DASHBOARD);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['invitation'] = $invitation['token'];


        $this->ba->dashboardGuestAppAuth();

        $this->mockHubSpotClient('trackSignupEvent');

        $this->startTest();

        // Validate that invitation is accepted and deleted
        $invite = \DB::table('invitations')
                     ->where('id', '=', $invitation['id'])
                     ->whereNull('deleted_at')
                     ->first();

        $this->assertNull($invite);

        // User is created for given email
        $user = \DB::table('users')
                   ->where('email', '=', 'random@rbl.com')
                   ->first();

        $this->assertNotNull($user);

        // User is attached to given merchant on given role
        $merchants = DB::table('merchant_users')
                       ->where('user_id', '=', $user->id)
                       ->where('merchant_id', '1DummyMerchant')
                       ->first();

        $this->assertEquals(BankingRole::BANK_MID_OFFICE_POC, $merchants->role);
    }

    public function testUserRegisterSendSignupOtpViaEmailEmailExists()
    {
        $user1 = $this->fixtures->create('user', ['email' => 'some.one@some.com', 'password' => 'hello123', 'confirm_token' => null]);

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'        => $user1["email"],
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testUserRegisterSendSignupOtpUnsupportedCountryCode()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testUserRegisterVerifySignupOtpIncorrectOtp()
    {
        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'captcha'               => 'faked',
            'token'                 => 'token',
            'otp'                   => '0008',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

    }

    public function testUserRegisterSendSignupOtpViaEmailLimitReached()
    {
        $testData = & $this->testData[__FUNCTION__];

        $email = 'some.one@some.com';

        $content = [
            'email' => $email
        ];

        $testData['request']['content'] = $content;

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($email.Constants::SEND_EMAIL_SIGNUP_OTP_RATE_LIMIT_SUFFIX, Constants::EMAIL_SIGNUP_OTP_SEND_THRESHOLD);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $redis->del($email.Constants::SEND_EMAIL_SIGNUP_OTP_RATE_LIMIT_SUFFIX);
    }

    public function testUserRegisterSendSignupOtpViaSmsLimitReached()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['sendOtp', 'generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $smsPayload = [
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context' => 'user_id:signup_otp:token',
        ];

        $this->app['raven']->method('generateOtp')->willReturn($smsPayload);

        $storkMock->shouldReceive('sendSms')->andThrow(new BadRequestException(
            ErrorCode::BAD_REQUEST_RESOURCE_EXHAUSTED
        ));

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFetchMerchantIdsForUserContact()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '12345678901234']);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'],
                                                             ['contact_mobile' => '9091929394']);

        $this->ba->careAppAuth();

        $this->startTest();
    }

    public function testFetchPrimaryUserContact()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '12345678901234']);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id'],
                                                             ['contact_mobile' => '9091929394']);

        $this->ba->careAppAuth();

        $this->startTest();
    }

    public function testUserRegisterVerifySignupOtpIncorrectOtpLimitOnAnOtpReached()
    {
        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['verifyOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('verifyOtp')->willThrowException(
            new BadRequestException(ErrorCode::BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED)
        );

        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'captcha'               => 'faked',
            'token'                 => 'token',
            'otp'                   => '0008',
        ];

        $testData['request']['content'] = $content;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testUserRegisterVerifySignupOtpTotalIncorrectOtpLimitReached()
    {
        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'contact_mobile'        => '9012345678',
            'captcha'               => 'faked',
            'token'                 => 'token',
            'otp'                   => '0008',
        ];

        $testData['request']['content'] = $content;

        $redis = Redis::connection('mutex_redis')->client();

        $redis->set($content["contact_mobile"].Constants::VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX, Constants::SIGNUP_OTP_VERIFICATION_THRESHOLD);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $redis->del($content["contact_mobile"].Constants::VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX);
    }

    public function testUserRegisterVerifySignupOtpSms()
    {
        Config::set('applications.test_case.execution', false);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->ba->dashboardGuestAppAuth();

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(NotifyRas::class);

        $merchant = $this->getLastEntity('merchant', true);

        $this->assertEquals($merchant["signup_via_email"], 0);
    }

    public function testUserRegisterVerifySignupOtpSmsEasyOnboardingSplitzOn()
    {
        Config::set('applications.test_case.execution', false);

        $this->ba->dashboardGuestAppAuth();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(NotifyRas::class);

        $merchant = $this->getLastEntity('merchant', true);

        $businessDetail = $this->getLastEntity('merchant_business_detail', true);

        $websiteDetails = $businessDetail['website_details'];

        $this->assertEquals($websiteDetails['social_media'], 1);

        $this->assertEquals($websiteDetails['physical_store'], 1);

        $this->assertEquals($websiteDetails['live_website_or_app'], '');

        $this->assertEquals($merchant["signup_via_email"], 0);
    }

    public function testUserRegisterVerifySignupOtpSmsEasyOnboardingSplitzOff()
    {
        Config::set('applications.test_case.execution', false);

        $this->ba->dashboardGuestAppAuth();

        $this->mockAllSplitzTreatment([
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ]);

        Queue::fake();

        $this->startTest();

        Queue::assertNotPushed(NotifyRas::class);
    }

    public function testUserRegisterVerifySignupOtpSmsSevenSeriesNumber()
    {

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['content']['contact_mobile'] = '7877665544';
        $testData['response']['content']['contact_mobile'] = '7877665544';

        $this->testUserRegisterVerifySignupOtpSms();

    }

    public function testUserRegisterVerifySignupOtpEmail()
    {
        $testData = & $this->testData[__FUNCTION__];

        $content = [
            'email'                 => 'some.one@some.com',
            'captcha'               => 'faked',
            'token'                 => 'token',
            'otp'                   => '0007',
        ];

        $testData['request']['content'] = $content;

        $testData['response']['content'] = [
            "email"                     => $content["email"],
            "signup_via_email"          => 1,
            "confirmed"                 => true,
            "email_verified"            => true,
            "contact_mobile_verified"   => false,
            "contact_mobile"            => null
        ];

        $this->ba->dashboardGuestAppAuth();

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(NotifyRas::class);

        $merchant = $this->getLastEntity('merchant', true);

        $this->assertEquals($merchant["signup_via_email"], 1);

    }

    public function testSendOTPForAddingEmailFromProfileSection()
    {
        Mail::fake();

        $testData = & $this->testData[__FUNCTION__];

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0, 'email' => null]);

        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchant['id'], 'contact_email' => null]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 0, 'email' => null, 'confirm_token' => 'notnull']
        );

        $testData['request']['content']['otp_auth_token'] = $this->app['token_service']->generate($merchantUser->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        Mail::assertQueued(Otp::class, function ($mail)
        {
            $this->assertEquals('verify_user', $mail->input['action']);

            $this->assertNotEmpty($mail->user);

            $this->assertNotEmpty($mail->otp);

            $this->assertEquals('emails.user.verify_user', $mail->view);

            $mailSubject = "Razorpay | OTP to verify email";

            $this->assertEquals($mailSubject, $mail->subject);

            return true;
        });

    }

    public function testAddEmailFromProfileSection()
    {
        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0, 'email' => null]);

        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchant['id'], 'contact_email' => null]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 0, 'email' => null]
        );

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testAddEmailFromProfileSectionNotOwner()
    {

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0, 'email' => null]);

        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchant['id'], 'contact_email' => null]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 0, 'email' => null], 'admin'
        );

        $mappingData = [
            'user_id'     => $merchantUser['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'admin',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testAddEmailFromProfileSectionEmailAlreadyPresent()
    {

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0, 'email' => 'someuser@some.com']);

        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchant['id'], 'contact_email' => 'someuser@some.com']);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 0, 'email' => 'someuser@some.com']
        );

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testAddEmailFromProfileSectionEmailAlreadyTaken()
    {

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0, 'email' => null]);

        $merchantDetail = $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant['id'], 'contact_email' => null]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 0, 'email' => null]
        );

        $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 1, 'email' => 'someuser@some.com']
        );

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testAddEmailFromProfileSectionForXNonOwnerUser()
    {
        $testData = & $this->testData['testAddEmailFromProfileSectionNotOwner'];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $testData['response'] = [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION,
                ],
            ],
            'status_code' => 400,
        ];

        $testData['exception'] = [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION,
        ];

        $this->testAddEmailFromProfileSectionNotOwner();
    }

    public function testSendOTPForAddingEmailFromProfileSectionForX()
    {
        $testData = & $this->testData['testSendOTPForAddingEmailFromProfileSection'];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->testSendOTPForAddingEmailFromProfileSection();

    }

    public function testAddEmailFromProfileSectionForXOwnerUser()
    {
        $testData = & $this->testData['testAddEmailFromProfileSection'];
        $testData['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->testAddEmailFromProfileSection();
    }

    public function testUpdateContactMobileAlreadyVerifiedFailure()
    {
        $userAttributes = [
            'contact_mobile'            => '9876543210',
            'contact_mobile_verified'   => true,
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchantId,
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->fixtures->merchant->setRestricted(true, $merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

        $this->startTest();
    }

    public function testUserContactMobileAlreadyTakenFailure()
    {
        $user1Attributes = [
            'contact_mobile'            => '1234567890',
            'contact_mobile_verified'   => true,
        ];

        $user1 = $this->fixtures->create('user', $user1Attributes);

        $merchant1 = $this->fixtures->create('merchant');

        $merchantId1 = $merchant1->getId();

        $mappingData1 = [
            'user_id'     => $user1->getId(),
            'merchant_id' => $merchantId1,
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData1);

        $user2Attributes = [
            'contact_mobile'            => '9876543210',
            'contact_mobile_verified'   => true,
        ];

        $user2 = $this->fixtures->create('user', $user2Attributes);

        $merchant2 = $this->fixtures->create('merchant');

        $merchantId2 = $merchant2->getId();

        $mappingData2 = [
            'user_id'     => $user2->getId(),
            'merchant_id' => $merchantId2,
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData2);

        $this->fixtures->merchant->setRestricted(true, $merchantId1);

        $this->fixtures->merchant->setRestricted(true, $merchantId2);

        $this->ba->proxyAuth('rzp_test_' . $merchantId1, $user1['id']);

        $this->startTest();
    }

    public function testAdminPurposeCodeDetailsFetch()
    {
        $this->testData[__FUNCTION__] = [
            'request' => [
                'url' => '/purpose/code',
                'method' => 'GET',
                'content' => [],
            ],
            'response' => [
                'content' => [],
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertNotNull($response);
    }

    public function testAdminPurposeCodeDetailsPatch()
    {
        $merchant1 = $this->fixtures->create('merchant');

        $this->testData[__FUNCTION__] = [
            'request' => [
                'url' => '/purpose/code',
                'method' => 'PATCH',
                'content' => [
                    'purpose_code' => 'P0103',
                    'iec_code' => '1231234123',
                    'merchant_id' => $merchant1->getId(),
                ],
            ],
            'response' => [
                'content' => [],
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertNotNull($response);
    }

    public function testMerchantGetTagsRouteViaBankingProductWithBlockingFeatureEnabled()
    {
        $this->enableRazorXTreatmentForBlockBankingRoutes();

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testCurrencyFetchAllProxyRouteViaBankingProductWithBlockingFeatureEnabled()
    {
        $this->enableRazorXTreatmentForBlockBankingRoutes();

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testMerchantPartnerConfigsFetchProxyRouteViaBankingProductWithBlockingFeatureEnabled()
    {
        $this->enableRazorXTreatmentForBlockBankingRoutes();

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testSettlementHolidaysRouteViaBankingProductWithBlockingFeatureEnabled()
    {
        $this->enableRazorXTreatmentForBlockBankingRoutes();

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testSettlementAmountRouteViaBankingProductWithBlockingFeatureEnabled()
    {
        $this->enableRazorXTreatmentForBlockBankingRoutes();

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testUserFetchPurposeCodeRouteViaBankingProductWithBlockingFeatureEnabled()
    {
        $this->enableRazorXTreatmentForBlockBankingRoutes();

        $user = $this->fixtures->user->createBankingUserForMerchant('10000000000000');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testWhatsAppOptInForX()
    {
        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            'contact_mobile' => '9876543210',
            'id'             => '2abcd000000000',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app]);

        $this->app->instance('stork_service', $storkMock);

        $this->app['stork_service']->shouldReceive('optInForWhatsapp')->once()->with('test','9876543210',[
            'source'           => 'x',
            'business_account' => 'razorpayx'
        ])->andReturn([
            'optin_status'  => true
        ]);

        $this->startTest();
    }

    public function testWhatsAppOptInStatusForX()
    {
        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            'contact_mobile' => '9876543210',
            'id'             => '2abcd000000000',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app]);

        $this->app->instance('stork_service', $storkMock);

        $this->app['stork_service']->shouldReceive('optInStatusForWhatsapp')->once()->with('test','9876543210','x','razorpayx')->andReturn([
            'phone_number'  => '9876543210'
        ]);

        $this->startTest();
    }

    public function testWhatsAppOptOutForX()
    {
        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            'contact_mobile' => '9876543210',
            'id'             => '2abcd000000000',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app]);

        $this->app->instance('stork_service', $storkMock);

        $this->app['stork_service']->shouldReceive('optOutForWhatsapp')->once()->with('test','9876543210','x','razorpayx')->andReturn([
            'optin_status'  => false
        ]);

        $this->startTest();
    }

    public function createUserWithMerchant()
    {
        $user = $this->fixtures->create('user', [
            'name' => 'Razorpay user'
        ]);

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_'.$merchant['id'], $user['id']);
    }

    public function testChangeUserName()
    {
        $this->createUserWithMerchant();

        $this->startTest();
    }

    public function testChangeUserNameByPassingSameName()
    {
        $this->createUserWithMerchant();

        $this->startTest();
    }

    public function testChangeUserNameByPassingInvalidName()
    {
        $this->createUserWithMerchant();

        $this->startTest();
    }

}
