<?php

namespace Functional\Risk;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Services\FreshdeskTicketClient;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Models\Workflow\Action\Repository as ActionRepository;
use RZP\Models\Admin\Permission\Repository as PermissionRepository;

class CyberHelpdeskTest extends TestCase
{
    use RequestResponseFlowTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;

    protected $client;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CyberHelpdeskTestData.php';
        parent::setUp();
    }

    public function testProxyRequest()
    {

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $request = $this->testData['testProxyRequest']['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($request['content'], $response['json']);

        $this->assertEquals("superadmin@razorpay.com", $response['headers']['admin_email_id']);
    }

    public function testSendMailToLEAFromCyberCrimeHelpdesk()
    {
        $this->ba->cyberCrimeHelpDeskAppAuth();

        $this->mockFreshdesk(1);

        $this->startTest();
    }

    protected function mockFreshdesk(int $expectFdCallCount): void
    {
        $freshdeskClientMock = $this->getMockBuilder(FreshdeskTicketClient::class)
                                    ->setConstructorArgs([$this->app])
                                    ->onlyMethods(['sendOutboundEmail'])
                                    ->getMock();

        $freshdeskClientMock
            ->expects($this->exactly($expectFdCallCount))
            ->method('sendOutboundEmail')
            ->willReturn(['id' => '123']);

        $this->app->instance('freshdesk_client', $freshdeskClientMock);
    }

    public function createCyberHelpDeskWorkflowAction($makerDetails, $makerEmailAsRequesterEmail = false)
    {
        $this->fixtures->create('admin', $makerDetails);

        $this->setupWorkflow("create_cyber_helpdesk_workflow", PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW);

        if  ($makerEmailAsRequesterEmail === true)
        {
            $this->testData[__FUNCTION__]['request']['content']['requester_mail'] = $makerDetails['email'];
        }

        $this->ba->cyberCrimeHelpDeskAppAuth();

        $response = $this->startTest();

        $permission = (new PermissionRepository)->findByOrgIdAndPermission(
            Org::RZP_ORG, PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW
        );

        $workflowActions = (new ActionRepository)->getOpenActionOnEntityOperation(
            'ticket1', 'freshdesk_ticket', $permission->getId()
        );

        $this->assertNotEmpty($workflowActions);

        return $response;
    }

    public function testCreateAndApproveCyberHelpdeskWorkflowDefaultMaker()
    {
        $currentTime = Carbon::now();

        Carbon::setTestNow($currentTime);

        $this->createEntitiesInDb();

        $workflowMakerEmail = \Config::get('applications.cyber_crime_helpdesk')['maker_email'];

        $workflowAction = $this->createCyberHelpDeskWorkflowAction( [
            'id' => '6dLbNSpv5Ybbbd',
            'email' => $workflowMakerEmail,
            'name' => 'test_agent',
            'org_id' => Org::RZP_ORG,
        ]);

        \Config::set('applications.freshdesk.mock', true);

        $this->ba->adminAuth();
        $admin = $this->ba->getAdmin();
        $admin->getId();
        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW]);

        $role->permissions()->attach($perm->getId());

        $this->addComments($workflowAction['id'], "agent_approved_payment_details_[{\"request_id\":\"abcd1234567890\",\"share_beneficary_account_details\":0,\"put_settlement_on_hold\":1,\"payment_id\":\"JCTRhsU4aiY0t1\",\"fraud_type\":\"3\"},{\"request_id\":\"abcd1234567891\",\"share_beneficary_account_details\":0,\"put_settlement_on_hold\":0,\"payment_id\":\"JCTRhsU4aiY0t2\"}]");

        $this->assertArraySelectiveEquals(['maker_id' => 'admin_6dLbNSpv5Ybbbd'], $workflowAction);

        $this->performWorkflowAction($workflowAction['id'], true);

        $fraudEntity1 = $this->getDbEntity('payment_fraud',[
            'payment_id' => 'JCTRhsU4aiY0t1',
            'type' => '3',
            'amount' => 1000,
            'currency' => 'INR',
            'base_amount' => 1000,
            'reported_to_razorpay_at' =>  $currentTime->getTimestamp(), // current time
            'reported_to_issuer_at' => 1618191015,   // payment creation time
            'chargeback_code' => null,
            'is_account_closed' => 0,
            'reported_by' => 'Cybercell',
        ]);

        $fraudEntity2 = $this->getDbEntity('payment_fraud',[
            'payment_id' => 'JCTRhsU4aiY0t2',
            'type' => '3',
            'amount' => 1000,
            'currency' => 'INR',
            'base_amount' => 1000,
            'reported_to_razorpay_at' =>  $currentTime->getTimestamp(), // current time
            'reported_to_issuer_at' => 1618191011,   // payment creation time
            'chargeback_code' => null,
            'is_account_closed' => 0,
            'reported_by' => 'Cybercell',
        ]);

        $this->assertNotEmpty($fraudEntity1);

        $this->assertNotEmpty($fraudEntity2);
    }

    public function testCreateAndApproveCyberHelpdeskWorkflowRequesterAdminAsMaker()
    {
        $this->createEntitiesInDb();

        $workflowAction = $this->createCyberHelpDeskWorkflowAction( [
            'id' => '6dLbNSpv5Ycccc',
            'email' => 'requesteradmin@razorpay.com',
            'name' => 'test_agent',
            'org_id' => Org::RZP_ORG,
        ], true);

        \Config::set('applications.freshdesk.mock', true);

        $this->ba->adminAuth();
        $admin = $this->ba->getAdmin();
        $admin->getId();
        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::CREATE_CYBER_HELPDESK_WORKFLOW]);

        $role->permissions()->attach($perm->getId());

        $this->addComments($workflowAction['id'], "agent_approved_payment_details_[{\"request_id\":\"abcd1234567890\",\"share_beneficary_account_details\":0,\"put_settlement_on_hold\":1,\"payment_id\":\"JCTRhsU4aiY0t1\",\"fraud_type\":\"3\"},{\"request_id\":\"abcd1234567891\",\"share_beneficary_account_details\":0,\"put_settlement_on_hold\":0,\"payment_id\":\"JCTRhsU4aiY0t2\"}]");

        $this->assertArraySelectiveEquals(['maker_id' => 'admin_6dLbNSpv5Ycccc'], $workflowAction);

        $this->performWorkflowAction($workflowAction['id'], true);

        $fraudEntity1 = $this->getDbEntity('payment_fraud',[
            'payment_id' => 'JCTRhsU4aiY0t1',
            'type' => '3',
            'amount' => 1000,
            'currency' => 'INR',
            'base_amount' => 1000,
            'reported_to_razorpay_at' => 1603882970, // fd ticket creation time
            'reported_to_issuer_at' => 1618191015,   // payment creation time
            'chargeback_code' => null,
            'is_account_closed' => 0,
            'reported_by' => 'Cybercell',
        ]);

        $fraudEntity2 = $this->getDbEntity('payment_fraud',[
            'payment_id' => 'JCTRhsU4aiY0t2',
            'type' => '3',
            'amount' => 1000,
            'currency' => 'INR',
            'base_amount' => 1000,
            'reported_to_razorpay_at' => 1603882970, // fd ticket creation time
            'reported_to_issuer_at' => 1618191011,   // payment creation time
            'chargeback_code' => null,
            'is_account_closed' => 0,
            'reported_by' => 'Cybercell',
        ]);

        $this->assertNotEmpty($fraudEntity1);

        $this->assertNotEmpty($fraudEntity2);
    }

    protected function createEntitiesInDb()
    {
        $this->fixtures->edit('merchant', '10000000000000', [
            'name' => 'Test Merchant',
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'      => '10000000000000',
            'business_name'    => 'Test Merchant',
            'contact_name'     => 'Test Merchant',
            'contact_email'    => 'testmerchant@gmail.com',
            'contact_mobile'   => '8114455061',
            'business_website' => 'testmerchant.com'
        ]);

        $this->fixtures->create('bank_account', [
            'id'               => 'bankAccount000',
            'beneficiary_name' => 'Test Merchant',
            'account_number'   => '12345678990',
            'ifsc_code'        => 'SBIN00001',
            'type'           =>  'merchant',
            'merchant_id' => '10000000000000',
        ]);

        $payment1 = $this->fixtures->create('payment', [
            'id' => 'JCTRhsU4aiY0t1',
            'currency' => 'INR',
            'method'      => 'upi',
            'base_amount' => 100000,
            'amount' => 1000,
            'email'       => 'customer1@gmail.com',
            'contact'     => '8114455012',
            'created_at'  => 1618191015,
            'merchant_id' => '10000000000000',
            'status' => 'captured',
        ]);

        $this->fixtures->create('transaction', [
            'id' => 'TCTRhsU4aiY0t1',
            'entity_id' => $payment1->getId(),
            'merchant_id' => '10000000000000',
            'type' => 'payment',
            'settled' => 1,
        ]);

        $txn = $this->fixtures->create('transaction', [
            'id' => 'TCTRhsU4aiY0t2',
            'entity_id' => 'JCTRhsU4aiY0t2',
            'merchant_id' => '10000000000000',
            'type' => 'payment',
            'settled' => 0,
        ]);

        $payment2 = $this->fixtures->create('payment', [
            'id' => 'JCTRhsU4aiY0t2',
            'currency' => 'INR',
            'method'      => 'netbanking',
            'base_amount' => 100000,
            'amount' => 1000,
            'email'       => 'customer2@gmail.com',
            'contact'     => '8114455062',
            'created_at'  => 1618191011,
            'merchant_id' => '10000000000000',
            'status' => 'captured',
            'reference1' => '123456',
            'transaction_id' => 'TCTRhsU4aiY0t2',
        ]);

        $this->fixtures->create('payment_analytics', [
            'ip' => '127.0.0.1',
            'payment_id' => $payment2->getId(),
        ]);
    }
}
