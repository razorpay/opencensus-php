<?php

namespace Functional\Risk;

use RZP\Models\Workflow\Action;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Tests\Functional\Helpers\Salesforce\SalesforceTrait;

class MerchantRiskAlertsServiceTest extends TestCase
{
    use WorkflowTrait;
    use FreshdeskTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use SalesforceTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->ba->merchantRiskAlertsAppAuth();

        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'test merchant',
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'contact_email' => 'merchant.email@gmail.com',
        ]);

        $this->setUpFreshdeskClientMock();

        $this->setUpSalesforceMock();
    }

    /**
     * Feature: https://docs.google.com/document/d/1DH4lbyePwYk8ngm-g6FwRXeAnnCg8HmpyasqM36LxRE/edit#
     */
    //if salesPOC is businessops@razorpay.com then we have to exclude it from cc.
    public function testExecuteFoHWorkflowShouldNotifyChargebackPoCAndExcludeSalesPOC()
    {

        $this->fixtures->merchant->addFeatures('apps_exempt_risk_check');

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
            [
                'subject'   => 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review',
                'cc_emails' => ['chargeback.poc1@gmail.com', 'chargeback.poc2@gmail.com'],
            ],
            [
                'id' => '1234',
            ]);

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

        $this->mockSalesforceRequest('10000000000000','businessops@razorpay.com');

        $response = $this->performWorkflowAction($workflowActionId, true);

        $this->assertContains('ras-managed-merchant', $response['tagged']);
    }

    public function testExecuteFoHWorkflowShouldNotifyChargebackPoCAndSalesPOC()
    {

        $this->fixtures->merchant->addFeatures('apps_exempt_risk_check');

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
                                                    [
                                                        'subject'   => 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review',
                                                        'cc_emails' => ['chargeback.poc1@gmail.com', 'chargeback.poc2@gmail.com', 'sales.poc@gmail.com'],
                                                    ],
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

        $this->mockSalesforceRequest('10000000000000','sales.poc@gmail.com');

        $response = $this->performWorkflowAction($workflowActionId, true);

        $this->assertContains('ras-managed-merchant', $response['tagged']);
    }

    public function testExecuteFoHWorkflowNotifyMobileSignup()
    {
        $this->fixtures->merchant->addFeatures('apps_exempt_risk_check');

        $this->fixtures->edit('merchant', '10000000000000', [
            'signup_via_email' => 0,
        ]);

        $this->fixtures->edit('merchant_detail', '10000000000000', [
            'contact_mobile' => '9991119991',
        ]);

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => null,
        ]);

        $expectedContent = [
            'group_id'        => 82000655429,
            'tags'            => ['RAS_FOH', 'RAS_CUSTOMER_FLAG_FOH'],
            'type'            => 'Question',
            'priority'        => 1,
            'phone'           => '9991119991',
            'custom_fields'   => [
                'cf_ticket_queue'           => 'Merchant',
                'cf_category'               => 'Risk Report_Merchant',
                'cf_subcategory'            => 'Funds on hold',
                'cf_product'                => 'Payment Gateway',
                'cf_created_by'             => 'agent',
                'cf_merchant_id_dashboard'  => 'merchant_dashboard_10000000000000',
                'cf_merchant_id'            => '10000000000000',
            ],
            'subject'         => 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review',
        ];

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                    $expectedContent,
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

        $this->performWorkflowAction($workflowActionId, true);
    }

    protected function createMerchantRiskAlertsFoHWorkflow()
    {
        $this->setupWorkflow('foh manual workflow', 'merchant_risk_alert_foh');

        return $this->makeRequestAndGetContent($this->getMerchantRiskAlertsCreateWorkflowRequest())['id'];
    }

    protected function getMerchantRiskAlertsCreateWorkflowRequest($input = [])
    {
        $defaultInput = [
            'method'  => 'post',
            'url'     => '/merchant_risk_alerts/merchant/foh/workflow',
            'content' => [
                'action'      => 'manual',
                'merchant_id' => '10000000000000',
                'tags'        => [
                    'ras_trigger_reason' => 'customer_flag',
                    'rule_outcome'       => 'HnPQrJoXX88mMK',
                ],
            ],
        ];

        return array_merge($defaultInput, $input);
    }

    protected function triggerNeedsClarificationRequestFromAdmin($workflowActionId)
    {
        $this->ba->adminAuth();

        $url = sprintf('/merchant_risk_alerts/merchant/foh/workflow/%s/needs_clarification', $workflowActionId);

        $request = [
            'method' => 'POST',
            'url'    => $url,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    //No chargeback and No Salesforce
    public function testTriggerExplicitNCEmailForFoHWorkflow()
    {
        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
            [
                'status'          => 6, // 6->wating on customer,
                'group_id'        => 82000147768,
                'tags'            => ['RAS_FOH', 'RAS_NC_FLOW_FOH'],
                'priority'        => 1,
                'email'           => 'merchant.email@gmail.com',
                'email_config_id' => 82000098661,
                'custom_fields'   => [
                    'cf_ticket_queue' => 'Merchant',
                    'cf_category'     => 'Risk Report_Merchant',
                    'cf_subcategory'  => 'Fraud alerts',
                    'cf_product'      => 'Payment Gateway',
                ],
                'subject'         => 'Razorpay Account Review: test merchant | 10000000000000 | Risk Clarification',
            ],
            [
                'id' => '1234',
            ]);

        $this->mockSalesforceRequest('10000000000000','businessops@razorpay.com');

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

        $response = $this->triggerNeedsClarificationRequestFromAdmin($workflowActionId);

        $this->assertEquals(['success' => true], $response);

        $comment = $this->getDbEntities('comment', ['entity_id' => Action\Entity::verifyIdAndStripSign($workflowActionId)])
            ->firstOrFail()
            ->toArray();

        $this->assertEquals('RAS NC Outbound email freshdesk ticket url: https://razorpay-ind.freshdesk.com/a/tickets/1234', $comment['comment']);

    }

    public function testTriggerExplicitNCMobileSignupFoHWorkflow()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'signup_via_email' => 0,
        ]);

        $this->fixtures->edit('merchant_detail', '10000000000000', [
            'contact_mobile' => '9991119991',
        ]);

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => null,
        ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets', 'post',
                                                    [
                                                        'group_id'        => 82000147768,
                                                        'tags'            => ['RAS_FOH', 'RAS_NC_FLOW_FOH'],
                                                        'priority'        => 1,
                                                        'phone'           => '9991119991',
                                                        'custom_fields'   => [
                                                            'cf_ticket_queue'           => 'Merchant',
                                                            'cf_category'               => 'Risk Report_Merchant',
                                                            'cf_subcategory'            => 'Fraud alerts',
                                                            'cf_product'                => 'Payment Gateway',
                                                            'cf_created_by'             => 'agent',
                                                            'cf_merchant_id_dashboard'  => 'merchant_dashboard_10000000000000',
                                                            'cf_merchant_id'            => '10000000000000',
                                                        ],
                                                        'subject'         => 'Razorpay Account Review: test merchant | 10000000000000 | Risk Clarification',
                                                    ],
                                                    [
                                                        'id' => '1234',
                                                    ]);

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

         $this->triggerNeedsClarificationRequestFromAdmin($workflowActionId);
    }

    public function testTriggerExplicitNCEmailForFoHWorkflowSecondAttemptShouldFail()
    {
        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

        $this->triggerNeedsClarificationRequestFromAdmin($workflowActionId);

        $this->expectException(BadRequestValidationFailureException::class);

        $response = $this->triggerNeedsClarificationRequestFromAdmin($workflowActionId);

        $this->assertEquals([
            'error' => [
                'description' => 'Needs clarification email for RAS FoH workflow may only be triggered once',
            ]], $response);

    }
}
