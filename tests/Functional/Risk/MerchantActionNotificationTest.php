<?php

namespace Functional\Risk;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;

class MerchantActionNotificationTest extends TestCase
{
    use WorkflowTrait;
    use FreshdeskTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const DISABLE_LIVE_SUBJECT = 'Razorpay Account disabled: test merchant | 10000000000000';
    const FOH_SUBJECT = 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review';

    const FOH_EXPECTED_CONTENT = [
        'status'          => 6, // 6->wating on customer,
        'group_id'        => 82000147768,
        'tags'            => ['bulk_workflow_email'],
        'priority'        => 1,
        'email'           => 'merchant.email@gmail.com',
        'email_config_id' => 82000078541,
        'custom_fields'   => [
            'cf_ticket_queue' => 'Merchant',
            'cf_category'     => 'Risk Report_Merchant',
            'cf_subcategory'  => 'Funds on hold',
            'cf_product'      => 'Payment Gateway',
        ],
        'subject'         => 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review',
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
        ]);

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->setUpFreshdeskClientMock();
    }

    public function testFOHEmailBulkWorkflow()
    {
        $expectedContent = self::FOH_EXPECTED_CONTENT;

        $expectedContent['email_config_id'] = 82000098428;

        $expectedContent['group_id'] = 82000655429;


        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->startTest();
    }

    public function testSuspendEmailBulkWorkflow()
    {
        $expectedContent = self::FOH_EXPECTED_CONTENT;
        $expectedContent['subject'] = self::DISABLE_LIVE_SUBJECT;

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->startTest();
    }

    public function testDisableLiveEmailBulkWorkflow()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => true, 'activated' => 1]);

        $expectedContent = self::FOH_EXPECTED_CONTENT;
        $expectedContent['subject'] = self::DISABLE_LIVE_SUBJECT;

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->startTest();
    }
}
