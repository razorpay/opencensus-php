<?php

namespace Functional\Risk;

use RZP\Models\Workflow\Action;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;

class MerchantRiskAlertsServiceTest extends TestCase
{
    use WorkflowTrait;
    use FreshdeskTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

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

        $this->fixtures->create('merchant_email', [
            'type'  => 'chargeback',
            'email' => 'chargeback.poc1@gmail.com,chargeback.poc2@gmail.com',
        ]);

        $this->setUpFreshdeskClientMock();
    }

    /**
     * Feature: https://docs.google.com/document/d/1DH4lbyePwYk8ngm-g6FwRXeAnnCg8HmpyasqM36LxRE/edit#
     */
    public function testExecuteFoHWorkflowShouldNotifyChargebackPoC()
    {

        $this->fixtures->merchant->addFeatures('apps_exempt_risk_check');

        $this->expectFreshdeskRequestAndRespondWith('tickets/outbound_email', 'post',
            [
                'subject'   => 'Razorpay Account Review: test merchant | 10000000000000 | Funds under Review',
                'cc_emails' => ['chargeback.poc1@gmail.com', 'chargeback.poc2@gmail.com'],
            ],
            [
                'id' => '1234',
            ]);

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

        $response = $this->performWorkflowAction($workflowActionId, true);

        $this->assertContains('ras-managed-merchant', $response['tagged']);
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

    public function testTriggerExplicitNCEmailForFoHWorkflow()
    {
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

        $workflowActionId = $this->createMerchantRiskAlertsFoHWorkflow();

        $response = $this->triggerNeedsClarificationRequestFromAdmin($workflowActionId);

        $this->assertEquals(['success' => true], $response);

        $comment = $this->getDbEntities('comment', ['entity_id' => Action\Entity::verifyIdAndStripSign($workflowActionId)])
            ->firstOrFail()
            ->toArray();

        $this->assertEquals('RAS NC Outbound email freshdesk ticket url: https://razorpay-ind.freshdesk.com/a/tickets/1234', $comment['comment']);

    }

    public function testTriggerExplicitNCEmailForFoHWorkflowSecondAttemptShouldFail()
    {
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