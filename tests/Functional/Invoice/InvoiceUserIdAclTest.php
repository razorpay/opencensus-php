<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvoiceUserIdAclTest extends TestCase
{
    use InvoiceTestTrait;
    use RequestResponseFlowTrait;

    protected $merchantUser = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/InvoiceUserIdAclTestData.php';

        parent::setUp();

        $this->fixtures->create('user', ['id' => '10000000UserId']);
        $this->fixtures->create('user', ['id' => '10000001UserId']);

        $this->merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'sellerapp');

        $this->ba->proxyAuth('rzp_test_10000000000000', $this->merchantUser->getId());
    }

    public function testCreateInvoiceWithUserId()
    {
        $this->fixtures->create('feature', [
            'name'        => Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
        'name'          => Constants::WHITE_LABELLED_INVOICES,
        'entity_id'     => '100000razorpay',
        'entity_type'   => 'org',
    ]);
        $testData = & $this->testData[__FUNCTION__];

        $testData['response']['content']['user_id'] = $this->merchantUser->getId();

        $this->startTest();
        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    // feature present at org and merchant both
    public function testCreateInvoiceOrgAndMerchantFeature()
    {
        $this->fixtures->create('feature', [
            'name'        => Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_INVOICES,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);
        $testData = & $this->testData[__FUNCTION__];

        $testData['response']['content']['user_id'] = $this->merchantUser->getId();

        $response = $this->startTest();
        $this->assertEquals('invoice', $response['entity']);
    }
    // feature doesn't present in merchant , just present at org level
    public function testFailCreateInvoiceMerchantFeature()
    {

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_INVOICES,
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $this->startTest();
    }

    // feature doesn't present at org level, just present for merchant
    public function testFailCreateInvoiceOrgFeature()
    {
        $this->fixtures->create('feature', [
            'name'        => Constants::WHITE_LABELLED_INVOICES,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);


        $testData = & $this->testData[__FUNCTION__];

        $testData['response']['content']['user_id'] = $this->merchantUser->getId();

        $this->startTest();
        $response = $this->startTest();

        $this->assertEquals('invoice', $response['entity']);
    }

    public function testGetInvoiceWithUserIdHeaderSuccess()
    {
        $this->createDraftInvoice(['user_id' => $this->merchantUser->getId()]);

        $this->startTest();
    }

    public function testGetInvoiceWithUserIdHeaderForbidden()
    {
        $this->createDraftInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testGetInvoiceWithUserIdAndDifferentRoleHeaderSuccess()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner');

        $this->createDraftInvoice(['user_id' => $merchantUser->getId()]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser->getId());

        $this->startTest();
    }

    public function testListInvoiceWithUserIdHeader()
    {
        $merchantUserId = $this->merchantUser->getId();

        $this->createDraftInvoice(['user_id' => $merchantUserId]);
        $this->createDraftInvoice(['user_id' => $merchantUserId, 'id' => '1000001invoice']);
        $this->createDraftInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->startTest();
    }

    public function testListInvoiceWithUserIdHeaderAndEsParams()
    {
        // Need to just assert that it doesn't throw any validation errors.

        $this->startTest();
    }

    public function testListInvoiceWithoutUserIdHeader()
    {
        $this->createDraftInvoice(['user_id' => '10000000UserId']);
        $this->createDraftInvoice(['user_id' => '10000000UserId', 'id' => '1000001invoice']);
        $this->createDraftInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testListInvoiceWithUserIdAndDifferentRoleHeader()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner');

        $this->createDraftInvoice(['user_id' => $merchantUser->getId()]);
        $this->createDraftInvoice(['user_id' => $merchantUser->getId(), 'id' => '1000001invoice']);
        $this->createDraftInvoice(['user_id' => '10000001UserId', 'id' => '1000002invoice']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser->getId());

        $this->startTest();
    }

    public function testUpdateInvoiceWithUserIdHeaderSuccess()
    {
        $this->createDraftInvoice(['user_id' => $this->merchantUser->getId()]);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testUpdateInvoiceWithUserIdHeaderForbidden()
    {
        $this->createDraftInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testUpdateInvoiceWithAgentUserIdHeaderSuccess()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $this->createDraftInvoice(['user_id' => '100AgentUserId']);

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId');

        $this->startTest();
    }

    public function testUpdateInvoiceWithAgentUserIdHeaderForbidden()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '101AgentUserId'], 'agent');

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $this->createDraftInvoice(['user_id' => '100AgentUserId']);

        $this->ba->proxyAuth('rzp_test_10000000000000', '101AgentUserId');

        $this->startTest();
    }

    public function testDeleteInvoiceWithUserIdHeaderSuccess()
    {
        $this->createDraftInvoice(['user_id' => $this->merchantUser->getId()]);

        $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertNull($invoice);
    }

    public function testDeleteInvoiceWithUserIdHeaderForbidden()
    {
        $this->createDraftInvoice(['user_id' => '10000001UserId']);

        $this->startTest();
    }

    public function testDeleteInvoiceWithAgentUserIdHeaderSuccess()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $this->createDraftInvoice(['user_id' => '100AgentUserId']);

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId');

        $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertNull($invoice);
    }

    public function testDeleteInvoiceWithAgentUserIdHeaderForbidden()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '101AgentUserId'], 'agent');

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $this->createDraftInvoice(['user_id' => '100AgentUserId']);

        $this->ba->proxyAuth('rzp_test_10000000000000', '101AgentUserId', 'agent');

        $this->startTest();
    }

    public function testCancelInvoiceWithUserIdHeaderSuccess()
    {
        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(['user_id' => $this->merchantUser->getId(), 'order_id' => $order->getId()]);

        $this->startTest();

        $this->assertResponseWithLastEntity('invoice', __FUNCTION__);
    }

    public function testCancelInvoiceWithUserIdHeaderForbidden()
    {
        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(['user_id' => '10000000UserId', 'order_id' => $order->getId()]);

        $this->startTest();
    }

    public function testCancelInvoiceWithAgentUserIdHeaderSuccess()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(['user_id' => '100AgentUserId', 'order_id' => $order->getId()]);

        $this->ba->proxyAuth('rzp_test_10000000000000', '100AgentUserId', 'agent');

        $this->startTest();
    }

    public function testCancelInvoiceWithAgentUserIdHeaderForbidden()
    {
        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '101AgentUserId'], 'agent');

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '100AgentUserId'], 'agent');

        $order = $this->fixtures->create('order');

        $this->createIssuedInvoice(['user_id' => '100AgentUserId', 'order_id' => $order->getId()]);

        $this->ba->proxyAuth('rzp_test_10000000000000', '101AgentUserId');

        $this->startTest();
    }
}
