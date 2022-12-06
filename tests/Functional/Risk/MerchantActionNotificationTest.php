<?php

namespace Functional\Risk;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Tests\Functional\Helpers\Salesforce\SalesforceTrait;

class MerchantActionNotificationTest extends TestCase
{
    use WorkflowTrait;
    use FreshdeskTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use SalesforceTrait;
    use EventsTrait;

    private $freshdeskConfig;

    const SUBJECT = [
        'disable_live'                    => 'Razorpay Account disabled: test merchant | 10000000000000',
        'suspend'                         => 'Razorpay Account disabled: test merchant | 10000000000000',
        'hold_funds'                      => 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review',
        'disable_international_temporary' => 'Razorpay Account Review:  test merchant | 10000000000000 | International Payment Acceptance Paused',
        'disable_international_permanent' => 'Razorpay Account Review:  test merchant | 10000000000000 | International Disablement',
    ];

    const SMS_TEMPLATE_FOR_EMAIL_SIGNUP = [
        'disable_live'                    => 'sms.merchant_risk_actions.disable_live',
        'suspend'                         => 'sms.merchant_risk_actions.suspend',
        'hold_funds'                      => 'sms.merchant_risk.generic.funds_on_hold.confirmation',
        'disable_international_temporary' => 'sms.risk.international_disablement_email_signup',
        'disable_international_permanent' => 'sms.risk.international_disablement_email_signup',
    ];

    const SMS_TEMPLATE_FOR_MOBILE_SIGNUP = [
        'hold_funds'                      => 'sms.risk.foh_confirmation_mobile_signup',
    ];

    const FD_SUBCATEGORY = [
        'disable_live' => 'Disable live',
        'suspend'      => 'Suspended Merchants',
    ];

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantActionNotificationTestData.php';

        parent::setUp();

        $this->ba->adminAuth();

        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'test merchant',
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'contact_email' => 'merchant.email@gmail.com',
            'contact_mobile' => '9991119991',
            'business_name'  => 'test business',
        ]);

        $this->freshdeskConfig = $this->app['config']->get('applications.freshdesk');

        $this->setUpFreshdeskClientMock();

        $this->setUpSalesforceMock();

        $this->mockRaven();
    }

    public function testFOHEmailBulkWorkflow()
    {
        $expectedContent = $this->getExpectedContent('hold_funds');

        $expectedContent['email_config_id'] = (int) $this->freshdeskConfig['email_config_ids']['rzpind']['foh_notification'];

        $expectedContent['group_id'] = (int) $this->freshdeskConfig['group_ids']['rzpind']['foh'];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->startTest();

        $this->assertRavenRequestForMerchantActionNotification('hold_funds');
    }

    public function testSuspendEmailBulkWorkflow()
    {
        $expectedContent = $this->getExpectedContent('suspend');

        $expectedContent['email_config_id'] = (int) $this->freshdeskConfig['email_config_ids']['rzpind']['foh_notification'];

        $expectedContent['group_id'] = (int) $this->freshdeskConfig['group_ids']['rzpind']['foh'];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->startTest();
    }

    public function testFOHBulkWorkflowFdTicketCreate()
    {
        $expectedContent =$this->getExpectedContentForFDTicket('hold_funds');
        $expectedContent['group_id'] = (int) $this->freshdeskConfig['group_ids']['rzpind']['foh'];

        $this->createMerchant();

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                    $expectedContent, ['id' => '1234'], 1, true);

        $this->startTest();

        $this->assertRavenRequestForMerchantActionNotification('hold_funds', true);
    }

    public function testDisableLiveEmailBulkWorkflow()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => true, 'activated' => 1]);

        $expectedContent = $this->getExpectedContent('disable_live');

        $expectedContent['email_config_id'] = (int) $this->freshdeskConfig['email_config_ids']['rzpind']['foh_notification'];

        $expectedContent['group_id'] = (int) $this->freshdeskConfig['group_ids']['rzpind']['foh'];

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);
        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->startTest();

        $this->assertRavenRequestForMerchantActionNotification('disable_live');
    }

    public function testDisableInternationalTemporaryEmailBulkWorkflow()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => true, 'activated' => 1]);

        $expectedContent = $this->getExpectedContent('disable_international_temporary');

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);
        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->startTest();

        $this->assertRavenRequestForMerchantActionNotification('disable_international_temporary');
    }

    public function testDisableInternationalPermanentEmailBulkWorkflow()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => true, 'activated' => 1]);

        $expectedContent = $this->getExpectedContent('disable_international_permanent');

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->mockSalesforceRequest('10000000000000','abc@gmail.com');

        $this->startTest();

        $this->assertRavenRequestForMerchantActionNotification('disable_international_permanent');
    }

    public function testInternationalDisableBulkWorkflowFdTicketCreate()
    {
        $expectedContent = $this->getExpectedContentForFDTicket('disable_international_temporary');

        $this->createMerchant();

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->startTest();
    }

    public function testInternationalDisablePermanentBulkWorkflowFdTicketCreate()
    {
        $expectedContent = $this->getExpectedContentForFDTicket('disable_international_permanent');

        $this->createMerchant();

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->startTest();
    }

    function getExpectedContent($action)
    {
        $subject  = self::SUBJECT[$action];
        $tag      = ['bulk_workflow_email'];
        $ccEmails = ['chargeback.poc1@gmail.com', 'chargeback.poc2@gmail.com', 'abc@gmail.com'];
        if ($action == 'disable_international_temporary' or $action == 'disable_international_permanent')
        {
            $tag = ['bulk_workflow_email', 'international_disablement'];
        }

        return [
            'status'          => 6, // 6->wating on customer,
            'group_id'        => (int) $this->freshdeskConfig['group_ids']['rzpind']['merchant_risk'],
            'tags'            => $tag,
            'priority'        => 1,
            'email'           => 'merchant.email@gmail.com',
            'email_config_id' => (int) $this->freshdeskConfig['email_config_ids']['rzpind']['risk_notification'],
            'custom_fields'   => [
                'cf_ticket_queue' => 'Merchant',
                'cf_category'     => 'Risk Report_Merchant',
                'cf_subcategory'  => self::FD_SUBCATEGORY[$action] ?? 'Funds on hold',
                'cf_product'      => 'Payment Gateway',
            ],
            'subject'         => $subject,
            'cc_emails'       => $ccEmails,
        ];
    }

    private function assertRavenRequestForMerchantActionNotification($action, $mobileSignUp = false)
    {
        $template = self::SMS_TEMPLATE_FOR_EMAIL_SIGNUP[$action];
        if ($mobileSignUp)
        {
            $template = self::SMS_TEMPLATE_FOR_MOBILE_SIGNUP[$action];
        }

        $this->assertRavenRequest(function($input) use ($template)
        {
            $this->assertArraySubset([
                'receiver' => '+919991119991',
                'source'   => 'api.bulk.risk.actions',
                'template' => $template,
                'params'   => [
                    'merchant_id'   => '10000000000000',
                    'merchant_name' => 'test merchant',
                    'merchantName'  => 'test merchant',
                    'business_name'  => 'test business',
                ],
                'stork'    => [
                    'context' => [
                        'org_id' => '100000razorpay',
                    ],
                ],
                ], $input);
        });
    }
    public function getExpectedContentForFDTicket($action)
    {
        $tag = ['bulk_workflow_email'];
        if ($action == 'disable_international_temporary' or $action == 'disable_international_permanent')
        {
            $tag = ['bulk_workflow_email', 'international_disablement'];
        }

        return [
            'group_id'      => (int) $this->freshdeskConfig['group_ids']['rzpind']['merchant_risk'],
            'tags'          => $tag,
            'priority'      => 1,
            'phone'         => '+919991119991',
            'custom_fields' => [
                'cf_ticket_queue'               => 'Merchant',
                'cf_category'                   => 'Risk Report_Merchant',
                'cf_subcategory'                => self::FD_SUBCATEGORY[$action] ?? 'Funds on hold',
                'cf_product'                    => 'Payment Gateway',
                'cf_created_by'                 => 'agent',
                'cf_merchant_id_dashboard'      => 'merchant_dashboard_10000000000000',
                'cf_merchant_id'                => '10000000000000',
                'cf_merchant_activation_status' => 'undefined',
            ],
            'subject'       => self::SUBJECT[$action],
        ];
    }

    public function createMerchant()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'signup_via_email' => 0,
        ]);

        $this->fixtures->on('live')->edit('merchant_detail', '10000000000000', [
            'contact_mobile' => '9991119991',
        ]);

        $this->fixtures->on('test')->edit('merchant_detail', '10000000000000', [
            'contact_mobile' => '9991119991',
        ]);

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => null,
        ]);
    }
}
