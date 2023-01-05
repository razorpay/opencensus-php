<?php

namespace RZP\Tests\Functional\Typeform;

use DB;
use Request;
use ApiResponse;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Tests\Functional\Helpers\Salesforce\SalesforceTrait;

class TypeformTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use EntityActionTrait;
    use WorkflowTrait;
    use FreshdeskTrait;
    use EventsTrait;
    use SalesforceTrait;

    protected function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->willReturn('notify');
    }

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TypeformTestData.php';

        parent::setUp();
    }

    public function testFailureTypeformWebhookConsumptionSecurity()
    {
        $this->startTest();
    }

    public function testInvalidDataTypeformWebhookConsumption()
    {
        $this->startTest();
    }

    public function testSuccessTypeformWebhookConsumptionSecurity()
    {
        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();
    }

    protected function setupWorkflow($workflowName, $permissionName, $mode ='live'): void
    {
        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $workflow = $this->createWorkflow([
                                              'org_id' => '100000razorpay',
                                              'name' => $workflowName,
                                              'permissions' => [ $permissionName ],
                                              'levels' => [
                                                  [
                                                      'level' => 1,
                                                      'op_type' => 'or',
                                                      'steps' => [
                                                          [
                                                              'reviewer_count' => 1,
                                                              'role_id' => Org::ADMIN_ROLE,
                                                          ],
                                                      ],
                                                  ],
                                              ],
                                          ],$mode);

    }

    public function testApprovalTypeformWebhookConsumption()
    {
        $this->markTestSkipped('Skipping as doesnt take into account the actual workflow creation');

        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertTrue($merchant->getInternationalAttribute());

        $this->assertEquals('1000', $merchant->getProductInternational());
    }

    public function testApprovalTypeformWebhookConsumptionMobileSignUp()
    {
        $this->mockRazorx();

        $this->setUpFreshdeskClientMock();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt',
                                                         'signup_via_email' => 0,]);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist',
            'contact_email'                 => 'test124@rzp.com',
            'contact_mobile'                => '9991119991',]);

        $this->fixtures->create('merchant_email', [
            'merchant_id' => $merchant->getId(),
            'type'  => 'chargeback',
            'email' => null,
        ]);

        $this->ba->directAuth();

        $this->setupWorkflow('edit_merchant_pg_international', 'edit_merchant_pg_international');

        $request = [
            'server'  => [
                'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
            ],
            'method'  => 'POST',
            'url'     => '/typeform/webhook_consumption',
            'content' => [
                "event_id"      => "LtWXD3crgy",
                "event_type"    => "form_response",
                "form_response" => [
                    "form_id"      => "lT4Z3j",
                    "token"        => "a3a12ec67a1365927098a606107fac15",
                    "submitted_at" => "2018-01-18T18:17:02Z",
                    "landed_at"    => "2018-01-18T18:07:02Z",
                    "hidden"       => [
                        "mid" => "EV7j5qM0qca1U3"
                    ],
                    "calculated"   => [
                        "score" => 9
                    ],
                    "definition"   => [
                        "id"     => "lT4Z3j",
                        "title"  => "Webhooks example",
                        "fields" => [
                            [
                                "id"                        => "DlXFaesGBpoF",
                                "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                "type"                      => "long_text",
                                "ref"                       => "[readable_ref_long_text",
                                "allow_multiple_selections" => false,
                                "allow_other_choice"        => false
                            ],
                            [
                                "id"                        => "SMEUb7VJz92Q",
                                "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                "type"                      => "email",
                                "ref"                       => "readable_ref_email",
                                "allow_multiple_selections" => false,
                                "allow_other_choice"        => false
                            ],
                        ]
                    ],
                    "answers"      => [
                        [
                            "type"  => "text",
                            "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                            "field" => [
                                "id"   => "DlXFaesGBpoF",
                                "type" => "long_text"
                            ]
                        ],
                        [
                            "type"  => "email",
                            "email" => "laura@example.com",
                            "field" => [
                                "id"   => "SMEUb7VJz92Q",
                                "type" => "email"
                            ]
                        ],
                    ]
                ]
            ],
        ];

        $expectedContent = [
            'group_id'        => 82000147768,
            'tags'            => ['intl_auto_mailer', 'intl_approved'],
            'priority'        => 1,
            'phone'           => '+919991119991',
            'custom_fields'   => [
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => 'International Enablement',
                'cf_product'                    => 'Payment Gateway',
                'cf_created_by'                 =>  'agent',
                'cf_merchant_id_dashboard'      => 'merchant_dashboard_EV7j5qM0qca1U3',
                'cf_merchant_id'                => 'EV7j5qM0qca1U3',
                'cf_merchant_activation_status' => 'undefined',
            ],
            'subject'         => 'Razorpay | International Payment Acceptance Request',
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->makeRequestAndGetContent($request);

        $this->startTest();
    }

    public function testProd2ApprovalTypeformWebhookConsumption()
    {
        $this->markTestSkipped('Skipping as doesnt take into account the actual workflow creation');

        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '0222',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertTrue($merchant->getInternationalAttribute());

        $this->assertEquals('0111', $merchant->getProductInternational());
    }

    public function testOldWorkflowsExecution()
    {
        $this->markTestSkipped('Skipping as doesnt take into account the actual workflow creation');

        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '0000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', ['merchant_id'     => $merchant->getId(),
                                                                'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertTrue($merchant->getInternationalAttribute());

        $this->assertEquals('1111', $merchant->getProductInternational());
    }

    public function testApprovalTypeformWebhookConsumptionInvalidWebsite()
    {
        $this->ba->directAuth();

        $merchant = $this->fixtures->create('merchant',
                                            ['id'                    => 'EV7j5qM0qca1U3',
                                             'product_international' => '0222',
                                             'website'               => '',
                                             'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->create('merchant_detail', ['merchant_id'                   => $merchant->getId(),
                                                    'international_activation_flow' => 'whitelist']);

        $this->startTest();

    }

    public function testWorkflowCreationTypeformWebhook()
    {
        $this->ba->directAuth();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->startTest();

        $merchant = $this->getDbEntity('merchant', ['id' => 'EV7j5qM0qca1U3'], 'live');

        $this->assertFalse($merchant->getInternationalAttribute());

        $this->assertEquals('0000', $merchant->getProductInternational());
    }

    public function testApprovalTypeformWebhookConsumptionNotification()
    {
        $this->mockRazorx();

        $this->mockRaven();

        $this->setUpFreshdeskClientMock();

        $this->setUpSalesforceMock();

        $merchant = $this->fixtures->on('live')->create('merchant',
                                                        ['id'                    => 'EV7j5qM0qca1U3',
                                                         'product_international' => '2000',
                                                         'pricing_plan_id'       => '1hDYlICobzOCYt']);

        $merchantId=$merchant->getId();

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchantId);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchantId,
            'international_activation_flow' => 'whitelist',
            'contact_email'                 => 'test124@rzp.com',
            'contact_mobile'                => '9991119991',
            'business_name'                 => 'helloWorld',
        ]);

        $this->fixtures->create('merchant_email', [
            'merchant_id' => $merchantId,
            'type'  => 'chargeback',
            'email' => 'chargeback1@gmail.com,chargeback2@gmail.com',
        ]);

        $this->ba->directAuth();

        $this->setupWorkflow('edit_merchant_pg_international', 'edit_merchant_pg_international');

        $request = [
            'server'  => [
                'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
            ],
            'method'  => 'POST',
            'url'     => '/typeform/webhook_consumption',
            'content' => [
                "event_id"      => "LtWXD3crgy",
                "event_type"    => "form_response",
                "form_response" => [
                    "form_id"      => "lT4Z3j",
                    "token"        => "a3a12ec67a1365927098a606107fac15",
                    "submitted_at" => "2018-01-18T18:17:02Z",
                    "landed_at"    => "2018-01-18T18:07:02Z",
                    "hidden"       => [
                        "mid" => "EV7j5qM0qca1U3"
                    ],
                    "calculated"   => [
                        "score" => 9
                    ],
                    "definition"   => [
                        "id"     => "lT4Z3j",
                        "title"  => "Webhooks example",
                        "fields" => [
                            [
                                "id"                        => "DlXFaesGBpoF",
                                "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                "type"                      => "long_text",
                                "ref"                       => "[readable_ref_long_text",
                                "allow_multiple_selections" => false,
                                "allow_other_choice"        => false
                            ],
                            [
                                "id"                        => "SMEUb7VJz92Q",
                                "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                "type"                      => "email",
                                "ref"                       => "readable_ref_email",
                                "allow_multiple_selections" => false,
                                "allow_other_choice"        => false
                            ],
                        ]
                    ],
                    "answers"      => [
                        [
                            "type"  => "text",
                            "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                            "field" => [
                                "id"   => "DlXFaesGBpoF",
                                "type" => "long_text"
                            ]
                        ],
                        [
                            "type"  => "email",
                            "email" => "laura@example.com",
                            "field" => [
                                "id"   => "SMEUb7VJz92Q",
                                "type" => "email"
                            ]
                        ],
                    ]
                ]
            ],
        ];

        $expectedContent = [
            'group_id'        => 82000147768,
            'tags'            => ['intl_auto_mailer', 'intl_approved'],
            'priority'        => 1,
            'custom_fields'   => [
                'cf_ticket_queue'           => 'Merchant',
                'cf_category'               => 'Risk Report_Merchant',
                'cf_subcategory'            => 'International Enablement',
                'cf_product'                => 'Payment Gateway',
            ],
            'cc_emails' => ['chargeback1@gmail.com','chargeback2@gmail.com','sales.poc@gmail.com'],
            'subject'         => 'Razorpay | International Payment Acceptance Request',
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email',
                                                    'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->mockSalesforceRequest('EV7j5qM0qca1U3','sales.poc@gmail.com');

        $this->makeRequestAndGetContent($request);

        $this->startTest();

        $this->assertRavenRequest(function($input) use ($merchant)
        {
            $this->assertArraySubset([
                'receiver' => $merchant->merchantDetail->getContactMobile(),
                'source'   => 'api.live.international_enablement',
                'template' => 'sms.internation_enablement.approved',
                'params'   => [
                    'merchant_id'   => $merchant->getId(),
                    'merchantName'  => $merchant->getName(),
                    'business_name' => $merchant->merchantDetail->getBusinessName(),
                ],
                'stork'    => [
                    'context' => [
                        'org_id' => $merchant->getOrgId(),
                    ],
                ],
                ], $input);
        });

    }
}
