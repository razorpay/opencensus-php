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

    private $freshdeskConfig;

    const SUBJECT = [
        'disable_live'                    => 'Razorpay Account disabled: test merchant | 10000000000000',
        'suspend'                         => 'Razorpay Account disabled: test merchant | 10000000000000',
        'hold_funds'                      => 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review',
        'disable_international_temporary' => 'Razorpay Account Review:  test merchant | 10000000000000 | International Payment Acceptance Paused',
        'disable_international_permanent' => 'Razorpay Account Review:  test merchant | 10000000000000 | International Disablement',
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

        $this->freshdeskConfig = $this->app['config']->get('applications.freshdesk');

        $this->setUpFreshdeskClientMock();
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

        $this->startTest();
    }

    public function testSuspendEmailBulkWorkflow()
    {
        $expectedContent = $this->getExpectedContent('suspend');

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

        $expectedContent = $this->getExpectedContent('disable_live');

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $this->startTest();
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

        $this->startTest();
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

        $this->startTest();
    }


    function getExpectedContent($action)
    {
        $subject = self::SUBJECT[$action];
        $tag     = ['bulk_workflow_email'];
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
                'cf_subcategory'  => 'Funds on hold',
                'cf_product'      => 'Payment Gateway',
            ],
            'subject'         => $subject,
        ];
    }
}
