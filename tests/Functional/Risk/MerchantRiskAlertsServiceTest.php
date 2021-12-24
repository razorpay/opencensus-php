<?php

namespace Functional\Risk;

use RZP\Constants;
use RZP\Services\RazorXClient;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;
use RZP\Models\MerchantRiskAlert;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
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
    use EventsTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantRiskAlertServiceTestData.php';

        parent::setUp();

        $this->ba->merchantRiskAlertsAppAuth();

        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'test merchant',
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'contact_email' => 'merchant.email@gmail.com',
            'contact_mobile' => '9991119991',
        ]);

        $this->setUpFreshdeskClientMock();

        $this->setUpSalesforceMock();
    }

    protected function rulesRouteSetUp()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $this->app['config']->set('services.merchant_risks_alerts.mock', true);

        $upsertPerm = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::MERCHANT_RISK_ALERT_UPSERT_RULE]);

        $deletePerm = $this->fixtures->create(Constants\Entity::PERMISSION, [Permission\Entity::NAME => Permission\Name::MERCHANT_RISK_ALERT_DELETE_RULE]);

        $role->permissions()->attach($upsertPerm->getId());

        $role->permissions()->attach($deletePerm->getId());
    }

    public function testCreateRule()
    {
        $this->rulesRouteSetUp();

        $input = [
            'method'  => 'post',
            'url'     => '/merchant_risk_alerts/rules/create',
            'content' => [
                'rule_group'    => 'sign_up_checker',
                'expression'    => "client_ip == '127.0.0.1'",
            ],
        ];

        $this->assertEquals([], $this->makeRequestAndGetContent($input));
    }

    public function testUpdateRule()
    {
        $this->rulesRouteSetUp();

        $input = [
            'method'  => 'post',
            'url'     => '/merchant_risk_alerts/rules/random_id/update',
            'content' => [
                'rule_group'    => 'sign_up_checker',
                'expression'    => "client_ip == '127.0.0.1'",
            ],
        ];

        $this->assertEquals([], $this->makeRequestAndGetContent($input));
    }

    public function testDeleteRule()
    {
        $this->rulesRouteSetUp();

        $input = [
            'method'  => 'post',
            'url'     => '/merchant_risk_alerts/rules/random_id/delete',
        ];

        $this->assertEquals([], $this->makeRequestAndGetContent($input));
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

        $this->mockRaven();

        $response = $this->performWorkflowAction($workflowActionId, true);

        $this->assertContains('ras-managed-merchant', $response['tagged']);

        $this->assertRavenRequestForRiskAlertService();
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

        $this->mockRaven();

        $response = $this->performWorkflowAction($workflowActionId, true);

        $this->assertContains('ras-managed-merchant', $response['tagged']);

        $this->assertRavenRequestForRiskAlertService();
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

    private function assertRavenRequestForRiskAlertService()
    {

        $this->assertRavenRequest(function($input)
        {
            $this->assertArraySubset([
                'receiver' => '9991119991',
                'source'   => 'api.merchant.risk.alert',
                'params'   => [
                    'merchantName'  => 'test merchant',
                ],
                'stork'    => [
                    'context' => [
                        'org_id' => '100000razorpay',
                    ],
                ],
                ], $input);
        });
    }
  
    protected function setMerchantDedupeKey()
    {
        $request = [
            'method'  => 'post',
            'url'     => '/merchant_risk_alerts/merchant/10000000000000/dedupe',
            'content' => [],
        ];

        $this->ba->merchantRiskAlertsAppAuth();

        $this->makeRequestAndGetContent($request);
    }

    protected function assertTagsAndFraudType($merchant)
    {
        $this->assertEquals('risk_review_suspend_tag', $merchant->merchantDetail->getFraudType());

        $this->assertNotNull($this->app['cache']->connection()->hget(MerchantRiskAlert\Constants::REDIS_DEDUPE_SIGNUP_CHECKER_MAP, '10000000000000'));

        $this->assertArraySelectiveEquals([
                                              'Risk_review_suspend',
                                              'Dedupe_blocked',
                                          ], $merchant->tagNames());
    }

    public function testRasSignupFraudMerchant()
    {
        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->edit('merchant', '10000000000000', [
            'pricing_plan_id' => '1hDYlICobzOCYt',
        ]);

        $this->setMerchantDedupeKey();

        $this->fixtures->on('live')->edit('merchant_detail', '10000000000000', [
            'promoter_pan'      => 'EBPPK8222K',
            'promoter_pan_name' => 'User 1',
        ]);

        $this->fixtures->on('test')->edit('merchant_detail', '10000000000000', [
            'promoter_pan'      => 'EBPPK8222K',
            'promoter_pan_name' => 'User 1',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant();

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser['id']);

        $this->startTest();

        $this->assertTagsAndFraudType($merchant);

        $this->assertFalse($merchant->merchantDetail->isLocked());
    }

    protected function mockRazorxTreatment(string $returnValue = 'ok')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->onlyMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn($returnValue);
    }
}
