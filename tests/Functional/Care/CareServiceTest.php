<?php


namespace Functional\Care;

use Mail;

use Mockery;
use RZP\Mail\Merchant\RejectionSettlement;
use RZP\Trace\TraceCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use WpOrg\Requests\Response;


class CareServiceTest extends TestCase
{
    const AUTH                                = 'auth';
    const API_ROUTE                           = 'api_route';
    const EXPECTED_CARE_SERVICE_ROUTE         = 'expected_care_service_route';
    const EXPECTED_CARE_SERVICE_REQUEST       = 'expected_care_service_request';
    const ACTUAL_CARE_SERVICE_RESPONSE_BODY   = 'actual_care_service_response_body';
    const ACTUAL_CARE_SERVICE_RESPONSE_STATUS = 'actual_care_service_response_status';
    const API_REQUEST_BODY                    = 'API_REQUEST_BODY';
    const METHOD                              = 'method';
    const PERMISSIONS                         = 'permissions';

    use WorkflowTrait;
    use RequestResponseFlowTrait;

    protected $careServiceMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CareServiceTestData.php';

        parent::setUp();

        $this->setUpCareServiceMock();

        $this->addPermissionToBaAdmin('manager_care_service_callback');
    }

    public function testInternalMerchantFetch()
    {
        $this->ba->careAppAuth();

        $this->startTest();
    }

    public function testInternalMerchantGetFirstSubmissionDate()
    {
        $this->fixtures->on('live')->create('state', [
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review',
            'created_at'  =>  1539543931
        ]);

        $this->fixtures->on('live')->create('state', [
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review',
            'created_at'  =>  1539543989
        ]);

        $this->ba->careAppAuth();

        $this->startTest();
    }

    public function testInternalMerchantGetFirstSubmissionDateWithDifferentStatus()
    {
        $this->fixtures->on('live')->create('state', [
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review',
            'created_at'  =>  1539543931
        ]);

        $this->fixtures->on('live')->create('state', [
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant_detail',
            'name'        => 'activated_mcc_pending',
            'created_at'  =>  1539543929
        ]);

        $this->ba->careAppAuth();

        $this->startTest();
    }

    protected function setUpCareServiceMock()
    {
        $this->careServiceMock = Mockery::mock('RZP\Services\CareServiceClient', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app['care_service'] = $this->careServiceMock;
    }

    protected function expectCareServiceRequestAndRespondWith($expectedPath, $expectedContent, $respondWithBody, $respondWithStatus)
    {
        $this->careServiceMock
            ->shouldReceive('sendRequest')
            ->times(1)
            ->with(Mockery::on(function ($actualPath) use ($expectedPath)
            {
                return $expectedPath === $actualPath;
            }), Mockery::on(function ($actualMethod)
            {
                return strtolower($actualMethod) === 'post';
            }),
            Mockery::on(function ($actualContent) use ($expectedContent)
            {
                    return $expectedContent === $actualContent;
            }))
            ->andReturnUsing(function () use ($respondWithBody, $respondWithStatus)
            {
                $response = new Response;

                $response->body = json_encode($respondWithBody);

                $response->status_code = $respondWithStatus;

                return $response;
            });
    }

    public function testCareProxy()
    {
        $testCases = [
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/dark/admin',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-dark-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/GetCallback',
                self::API_REQUEST_BODY                    => [
                    'path' => 'twirp/rzp.care.callback.v1.CallbackService/GetCallback',
                    'body' => ['key' => 'value'],
                ],
                self::EXPECTED_CARE_SERVICE_REQUEST       => ['key' => 'value'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
                self::PERMISSIONS                         => ['care_service_dark_proxy'],
            ],
            [
                self::AUTH                                => 'yellowmessenger',
                self::API_ROUTE                           => '/care_service/chat/twirp/rzp.care.chat.v1.ChatService/FetchMerchant',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.chat.v1.ChatService/FetchMerchant',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'yellowmessenger',
                self::API_ROUTE                           => '/care_service/chat/twirp/rzp.care.nc.v1.NcService/FetchNc',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.nc.v1.NcService/FetchNc',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'yellowmessenger',
                self::API_ROUTE                           => '/care_service/chat/twirp/rzp.care.nc.v1.NcService/UploadNc',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.nc.v1.NcService/UploadNc',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.chat.v1.ChatService/GetChatTimingsConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.chat.v1.ChatService/GetChatTimingsConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['manage_freshchat'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.chat.v1.ChatService/PutChatTimingsConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.chat.v1.ChatService/PutChatTimingsConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['manage_freshchat'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.chat.v1.ChatService/GetChatHolidays',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.chat.v1.ChatService/GetChatHolidays',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['manage_freshchat'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.chat.v1.ChatService/PutChatHolidays',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.chat.v1.ChatService/PutChatHolidays',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['manage_freshchat'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.callback.v1.CallbackService/UpsertOperator',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/UpsertOperator',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/PostTicketReply',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/PostTicketReply',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTicketConversations',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTicketConversations',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/AddNoteToTicket',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/AddNoteToTicket',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/PostOtp',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/PostOtp',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/PatchTicketInternal',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/PatchTicketInternal',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.csm.v1.CsmService/GetKeyAccountOwners',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.csm.v1.CsmService/GetKeyAccountOwners',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.chat.v1.ChatService/CheckChatAvailability',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.chat.v1.ChatService/CheckChatAvailability',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.chat.v1.ChatService/SendPostOnboardingNotification',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.chat.v1.ChatService/SendPostOnboardingNotification',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckInstantCallbackEligibility',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/CheckInstantCallbackEligibility',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibilityV2',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/CheckEligibilityV2',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'cron',
                self::API_ROUTE                           => '/care_service/cron/twirp/rzp.care.callback.v1.CallbackService/InitSlots',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/InitSlots',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'cron',
                self::API_ROUTE                           => '/care_service/cron/twirp/rzp.care.callback.v1.CallbackService/HandleChangeInVisibleSlotSize',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/HandleChangeInVisibleSlotSize',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'cron',
                self::API_ROUTE                           => '/care_service/cron/twirp/rzp.care.callback.v1.CallbackService/PushCallbacksToQueue',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/PushCallbacksToQueue',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/GetSlots',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/GetSlots',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CreateCallback',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/CreateCallback',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CreateInstantCallback',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/CreateInstantCallback',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/GetCallback',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/GetCallback',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'myoperator',
                self::API_ROUTE                           => '/care_service/myoperator_webhook/twirp/rzp.care.callback.v1.CallbackService/InCallWebhook',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/InCallWebhook',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'myoperator' => "{ \"users\": [ \"918586848544\" ], \"client_ref_id\": \"fdfdfdf\"}",
                ],
                self::API_REQUEST_BODY                    => [
                    'myoperator' => "{ \"users\": [ \"918586848544\" ], \"client_ref_id\": \"fdfdfdf\"}",
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/GetClickToCallTimingConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/GetClickToCallTimingConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['click_to_call_timing_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/PutClickToCallTimingConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/PutClickToCallTimingConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['click_to_call_timing_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/GetClickToCallHolidays',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/GetClickToCallHolidays',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['click_to_call_timing_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/PutClickToCallHolidays',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/PutClickToCallHolidays',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['click_to_call_timing_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/getDateSlotConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/getDateSlotConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['callback_slot_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/getWeekSlotConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/getWeekSlotConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ],
                ],
                self::API_REQUEST_BODY                    => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['callback_slot_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/editDateSlotConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/editDateSlotConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['callback_slot_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/editWeekSlotConfig',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.admin.v1.CallbackConfigService/editWeekSlotConfig',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['callback_slot_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/CreateSubCategory',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/CreateSubCategory',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/CreateItem',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/CreateItem',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/FetchSubCategory',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/FetchSubCategory',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/EditSubCategory',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/EditSubCategory',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/FetchItem',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/FetchItem',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/EditItem',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/EditItem',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateRankSubCategory',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateRankSubCategory',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateRankItem',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateRankItem',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'myoperator',
                self::API_ROUTE                           => '/care_service/myoperator_webhook/twirp/rzp.care.callback.v1.CallbackService/AfterCallWebhook',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/AfterCallWebhook',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'myoperator' => "{ \"_cri\": \"fdfdfdfd\", \"_ld\": [{\"_rst\": \"2020-07-17 07:12:28\", \"_su\": \"1\", \"_ac\": \"received\"}] }"
                ],
                self::API_REQUEST_BODY                    => [
                    'myoperator' => "{ \"_cri\": \"fdfdfdfd\", \"_ld\": [{\"_rst\": \"2020-07-17 07:12:28\", \"_su\": \"1\", \"_ac\": \"received\"}] }"
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/CreateFaq',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/CreateFaq',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/FetchFaqs',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/FetchFaqs',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaqStatus',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaqStatus',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaq',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaq',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/DeleteFaq',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/DeleteFaq',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/CreateDashboardGuide',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/CreateDashboardGuide',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/UpdateDashboardGuide',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/UpdateDashboardGuide',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/DeleteDashboardGuide',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/DeleteDashboardGuide',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/FetchDashboardGuide',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/FetchDashboardGuide',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_view'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.quicklink.v1.QuicklinkService/CreateQuickLinks',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.quicklink.v1.QuicklinkService/CreateQuickLinks',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['quicklink_create'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.quicklink.v1.QuicklinkService/RetrieveQuickLinks',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.quicklink.v1.QuicklinkService/RetrieveQuickLinks',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateSubCategoryStatus',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateSubCategoryStatus',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateItemStatus',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/UpdateItemStatus',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/DeleteSubCategory',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/DeleteSubCategory',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.ticket.v1.TicketConfigService/DeleteItem',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.ticket.v1.TicketConfigService/DeleteItem',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['ticket_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/AddFaqRanking',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/AddFaqRanking',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/DeleteFaqRanking',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/DeleteFaqRanking',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'admin',
                self::API_ROUTE                           => '/care_service/admin/twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaqRanking',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.faq.v1.FaqConfigService/UpdateFaqRanking',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'key'   => 'value',
                    'admin' => [
                        'id' => 'RzrpySprAdmnId',
                    ]
                ],
                self::API_REQUEST_BODY                    => ["key" => "value"],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::PERMISSIONS                         => ['faq_config_edit'],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTicket',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTicket',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::METHOD                              => 'GET',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.merchantNavigation.v1.MerchantNavigationService/GetMerchantNavigationList',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.merchantNavigation.v1.MerchantNavigationService/GetMerchantNavigationList',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTickets',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/GetTickets',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'cron',
                self::API_ROUTE                           => '/care_service/cron/twirp/rzp.care.merchantNavigation.v1.MerchantNavigationService/PostMerchantPopularProducts',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.merchantNavigation.v1.MerchantNavigationService/PostMerchantPopularProducts',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
            [
                self::AUTH                                => 'proxy',
                self::API_ROUTE                           => '/care_service/merchant/twirp/rzp.care.freshdesk.v1.FreshdeskService/PostTicketGrievance',
                self::EXPECTED_CARE_SERVICE_ROUTE         => 'https://care-int.razorpay.com/twirp/rzp.care.freshdesk.v1.FreshdeskService/PostTicketGrievance',
                self::EXPECTED_CARE_SERVICE_REQUEST       => [
                    'merchant' => [
                        'id' => '10000000000000',
                        'user_id' => User::MERCHANT_USER_ID,
                        'user_email' => 'merchantuser01@razorpay.com'
                    ],
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_BODY   => [
                    'key' => 'value',
                ],
                self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS => 200,
            ],
        ];

        foreach ($testCases as $testCase)
        {
            $this->expectCareServiceRequestAndRespondWith(
                $testCase[self::EXPECTED_CARE_SERVICE_ROUTE],
                $testCase[self::EXPECTED_CARE_SERVICE_REQUEST],
                $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_BODY],
                $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS]
            );

            if (isset($testCase[self::API_REQUEST_BODY]) === true)
            {
                $this->testData[__FUNCTION__]['request']['content'] = $testCase[self::API_REQUEST_BODY];
            }
            else
            {
                $this->testData[__FUNCTION__]['request']['content'] = [];
            }

            $this->testData[__FUNCTION__]['request']['url'] = $testCase[self::API_ROUTE];
            $this->testData[__FUNCTION__]['request']['method'] = $testCase[self::METHOD] ?? 'POST';

            $this->testData[__FUNCTION__]['response']['content']     = $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_BODY];
            $this->testData[__FUNCTION__]['response']['status_code'] = $testCase[self::ACTUAL_CARE_SERVICE_RESPONSE_STATUS];

            switch ($testCase[self::AUTH])
            {
                case 'proxy':
                    $this->ba->proxyAuth('rzp_test_10000000000000', User::MERCHANT_USER_ID);
                    break;
                case 'cron':
                    $this->ba->cronAuth();
                    break;
                case 'myoperator':
                    $this->ba->myOperatorAuth();
                    break;
                case 'yellowmessenger':
                    $this->ba->yellowMessengerAuth();
                    break;
                case 'admin':
                    $this->ba->adminAuth();
            }

            if (empty($testCase[self::PERMISSIONS]) === false)
            {
                foreach ($testCase[self::PERMISSIONS] as $permissonName)
                {
                    $hasPerm = $this->ba->getAdmin()->hasPermission($permissonName);

                    if ($hasPerm === false)
                    {
                        $this->addPermissionToBaAdmin($permissonName);
                    }
                }
            }

            $this->app['trace']->info(TraceCode::MISC_TRACE_CODE, $this->testData[__FUNCTION__]);

            $this->startTest();
        }
    }

    public function testDashboardProxyInvalidRoute()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxy400Exception()
    {
        $this->ba->proxyAuth();

        $this->expectCareServiceRequestAndRespondWith(
            'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
            [
                'merchant' => [
                    'id' => '10000000000000',
                    'user_id' => User::MERCHANT_USER_ID,
                    'user_email' => 'merchantuser01@razorpay.com'
                    ],
            ],
            [
                'code' => 'internal',
                'msg'  => 'error message',
            ],
            400
        );

        $this->startTest();
    }

    public function testProxy500Exception()
    {
        $this->ba->proxyAuth();

        $this->expectCareServiceRequestAndRespondWith(
            'https://care-int.razorpay.com/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
            [
                'merchant' => [
                    'id' => '10000000000000',
                    'user_id' => User::MERCHANT_USER_ID,
                    'user_email' => 'merchantuser01@razorpay.com'
                ],
            ],
            [
                'code' => 'internal',
                'msg'  => 'error message',
            ],
            500
        );

        $this->startTest();
    }

    public function testInvalidPermission()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testInternalMerchantGetRejectionReasons()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->makeRequestAndGetContent([
            'method'  => 'PATCH',
            'url'     => '/merchant/activation/10000000000000/activation_status',
            'content' => [
                'activation_status' => 'rejected',
                'rejection_reasons' => [
                    [
                        'reason_category' => 'risk_related_rejections',
                        'reason_code'     => 'reject_on_risk_remarks',
                    ]
                ],
            ],
        ]);

        $this->ba->careAppAuth();

        $this->startTest();
    }
}
