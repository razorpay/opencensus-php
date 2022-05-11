<?php

namespace RZP\Tests\Functional\Payment;

use Cache;
use Mockery;
use Illuminate\Database\Eloquent\Factory;

use RZP\Error\ErrorCode;
use RZP\Services\RazorXClient;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Currency\Currency;
use RZP\Models\Admin\Role\TenantRoles;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Carbon\Carbon;
use RZP\Models\Payment;

/**
 * Covers Base/Fetch implementation. Currently it's not enabled for Payment
 * model but this asserts that existing flow is working fine as well. Later
 * when Payment model is enabled for new flow this will be still there.
 *
 */
class PaymentFetchTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use CustomBrandingTrait;

    protected function setUp(): void
    {
        ConfigKey::resetFetchedKeys();

        $this->testDataFilePath = __DIR__.'/helpers/PaymentFetchTestData.php';

        parent::setUp();

        $this->repo = (new Payment\Repository);

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }


    public function testFetchRuleCascadingForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('payment');

        $this->startTest();
    }

    public function testFetchRuleCascadingForAdminAuthRestricted()
    {
        $this->fixtures->edit('org', '100000razorpay', ['type' => 'restricted']);

        $this->ba->adminAuth();

        $pay1 = $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp']);

        // Card payment should not be fetched due to forced netbanking method param
        $this->fixtures->create('payment', ['method' => 'card']);

        // Other bank payment should not be fetched due to forced restricted org bank param
        $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzpnot']);

        $content = $this->startTest();

        $this->assertEquals('pay_' . $pay1['id'], $content['items'][0]['id']);
    }

    /**
     * RX/PG Isolation: Negative test.
     * - Admin trying to fetch the payment entity
     * - Admin does NOT have tenant:payments role
     * - The payment entity is mapped to the role, as in scope
     *
     * Access should be denied.
     */
    public function testFetchPaymentsWithNoTenantRole()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp']);

        $store = Cache::store('redis');

        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([
                             E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS],
                             E::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
                         ]);

        $this->startTest();
    }

    /**
     * RX/PG Isolation: Negative test.
     * - Admin trying to fetch a single payment entity
     * - Admin does NOT have tenant:payments role
     * - The payment entity is mapped to the role, as in scope
     *
     * Access should be denied.
     */
    public function testFetchSinglePaymentWithNoTenantRole()
    {
        $this->ba->adminAuth();

        $payment = $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp']);

        $store = Cache::store('redis');

        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([
                             E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS],
                             E::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
                         ]);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/payment/' . $payment['id'];
        $this->startTest();
    }

    /**
     * RX/PG Isolation: Positive test.
     * - Admin trying to fetch the payment entity
     * - Admin has the tenant:payments role
     * - The payment entity is mapped to the role, as in scope
     *
     * Access should be allowed.
     */
    public function testFetchPaymentsWithTenantRole()
    {
        $this->ba->adminAuth();

        $paymentsTenantRole = $this->fixtures->create('role', [
            'org_id' => Org::RZP_ORG,
            'name'   => TenantRoles::ENTITY_PAYMENTS,
        ]);

        $this->ba->getAdmin()->roles()->attach($paymentsTenantRole);

        $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp']);

        $store = Cache::store('redis');

        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([
                             E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS],
                             E::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
                         ]);

        $this->startTest();
    }

    /**
     * RX/PG Isolation: Positive test.
     * - Admin trying to fetch a single payment entity
     * - Admin has the tenant:payments role
     * - The payment entity is mapped to the role, as in scope
     *
     * Access should be allowed.
     */
    public function testFetchSinglePaymentWithTenantRole()
    {
        $this->ba->adminAuth();

        $paymentsTenantRole = $this->fixtures->create('role', [
            'org_id' => Org::RZP_ORG,
            'name'   => TenantRoles::ENTITY_PAYMENTS,
        ]);

        $this->ba->getAdmin()->roles()->attach($paymentsTenantRole);

        $payment = $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp']);

        $store = Cache::store('redis');
        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([
                             E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS],
                             E::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
                         ]);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/admin/payment/' . $payment['id'];
        $this->startTest();
    }

    /**
     * RX/PG Isolation: Positive test on Route access.
     * - Admin trying to access an admin route: payment_verify
     * - Admin has the tenant:payments role
     * - The payment_verify route is marked to the tenant:payments role, as in scope
     *
     * Access should be allowed.
     */
    public function testPaymentVerifyAdminWithTenantRole()
    {
        $this->ba->adminAuth();

        $paymentsTenantRole = $this->fixtures->create('role', [
            'org_id' => Org::RZP_ORG,
            'name'   => TenantRoles::ENTITY_PAYMENTS,
        ]);

        $this->ba->getAdmin()->roles()->attach($paymentsTenantRole);

        $payment = $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp']);

        $store = Cache::store('redis');
        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ROUTES)
             ->andReturn(['payment_verify' => [TenantRoles::ENTITY_PAYMENTS]]);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/' . $payment['id'] . '/verify';
        $this->startTest();
    }

    /**
     * RX/PG Isolation: Negative test on Route access.
     * - Admin trying to access an admin route: payment_verify
     * - Admin does not have the tenant:payments role
     * - The payment_verify route is marked to the tenant:payments role, as in scope
     *
     * Access should be denied.
     */
    public function testPaymentVerifyAdminWithNoTenantRole()
    {
        $this->ba->adminAuth();

        $payment = $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp']);

        $store = Cache::store('redis');
        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ROUTES)
             ->andReturn(['payment_verify' => [TenantRoles::ENTITY_PAYMENTS]]);

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/payments/' . $payment['id'] . '/verify';
        $this->startTest();
    }

    public function testFetchForAdminAuthRestrictedFilterAcquirerData()
    {
        $this->fixtures->edit('org', '100000razorpay', ['type' => 'restricted']);

        $this->ba->adminAuth();

        $pay1 = $this->fixtures->create('payment', ['method' => 'netbanking', 'bank' => 'rzp', 'reference1' => '1234']);

        // Card payment should not be fetched due to forced netbanking method param
        $this->fixtures->create('payment', ['method' => 'card', 'reference2' => '1234']);

        $content = $this->startTest();

        $this->assertEquals('pay_' . $pay1['id'], $content['items'][0]['id']);
    }

    public function testAdminAuthPaymentFetch()
    {
        $this->ba->adminAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testAdminAuthPaymentFetchWithDefaultMccFields()
    {
        $this->ba->adminAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testFetchRuleVPAFilterForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('payment', [
            'method' => 'upi',
            'vpa'    => 'success1@razorpay',
        ]);

        $this->fixtures->create('payment', [
            'method' => 'upi',
            'vpa'    => 'success2@razorpay',
        ]);

        $this->startTest();
    }

    public function testFetchCardQueryParams()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('payment', [
            'card_id' => '100000001lcard'
        ]);

        $this->startTest();
    }

    public function testFetchRulesForPrivateWithExtraFieldsError()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchRuleswithCustomerIdError()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchRulesCascadingForProxyAuth()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->fixtures->create(
            'payment',
            [
                'email' => $testData['request']['content']['email'],
            ]);

        $this->startTest();
    }

    public function testFetchRulesWithSignedIdForPrivateAuth()
    {
        $this->ba->privateAuth();

        $order = $this->fixtures->create('order');

        $this->fixtures->create('payment', ['order_id' => $order->getId()]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testFetchResponseForLateAuthFlagResponse()
    {
        $partner = $this->fixtures->create('merchant');

        $partnerId = $partner->getId();

        $this->fixtures->edit('merchant', $partnerId, ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => '10000000000000',
            'entity_owner_id' => $partnerId,
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $this->fixtures->merchant->addFeatures(
            [Feature::SEND_PAYMENT_LATE_AUTH],
            $partnerId
        );

        $this->ba->privateAuth();

        $order = $this->fixtures->create('order');

        $this->fixtures->create('payment', ['order_id' => $order->getId()]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $response = $this->startTest();

        $this->assertArrayHasKey('late_authorized', $response['items'][0]);
    }

    public function testFetchWithExpandsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email'] = $payment->getEmail();

        $this->startTest();
    }

    public function testFindWithExpandsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();

        $this->assertArrayNotHasKey('authorized_at', $content);
        $this->assertArrayNotHasKey('captured_at', $content);
        $this->assertArrayNotHasKey('late_authorized', $content);
        $this->assertArrayNotHasKey('auto_captured', $content);

        $this->assertArrayNotHasKey('dcc', $content);
        $this->assertArrayNotHasKey('forex_rate', $content);
        $this->assertArrayNotHasKey('gateway_amount', $content);
        $this->assertArrayNotHasKey('gateway_currency', $content);
        $this->assertArrayNotHasKey('dcc_offered', $content);
        $this->assertArrayNotHasKey('dcc_mark_up_percent', $content);
    }

    public function testGpayPaymentFetchWithUnselectedMethod()
    {
        $this->ba->proxyAuth();

        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);

        $payment->setAuthenticationGateway('google_pay');
        $payment['method'] = 'unselected';
        $this->repo->saveOrFail($payment);
        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();

        $this->assertArrayHasKey('provider', $content);
    }

    public function testGpayPaymentFetchWithCardMethod()
    {
        $this->ba->proxyAuth();

        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);

        $payment->setAuthenticationGateway('google_pay');
        $payment['method'] = 'card';
        $this->repo->saveOrFail($payment);
        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();

        $this->assertArrayHasKey('provider', $content);
    }

    public function testGpayPaymentFetchWithUpiMethod()
    {
        $this->ba->proxyAuth();

        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);

        $payment->setAuthenticationGateway('google_pay');
        $payment['method'] = 'upi';
        $this->repo->saveOrFail($payment);
        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();

        $this->assertArrayHasKey('provider', $content);
    }

    public function testNonGpayPaymentFetch()
    {
        $this->ba->proxyAuth();

        $payment = $this->fixtures->create('payment', ['amount' => 1234,]);

        $this->repo->saveOrFail($payment);
        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();

        $this->assertArrayNotHasKey('provider', $content);
    }

    public function testFindWithExpandsForPrivateAuthWithExtraAttributesExposed()
    {
        $this->ba->privateAuth();

        $org = $this->fixtures->create('org');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => $org->getId(),
            'name'          => 'show_late_auth_attributes',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'    => $org->getId(),
        ]);

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();

        $this->assertArrayHasKey('authorized_at', $content);
        $this->assertArrayHasKey('captured_at', $content);
        $this->assertArrayHasKey('late_authorized', $content);
        $this->assertArrayHasKey('auto_captured', $content);

        $this->assertArrayNotHasKey('dcc', $content);
        $this->assertArrayNotHasKey('forex_rate', $content);
        $this->assertArrayNotHasKey('gateway_amount', $content);
        $this->assertArrayNotHasKey('gateway_currency', $content);
        $this->assertArrayNotHasKey('dcc_offered', $content);
        $this->assertArrayNotHasKey('dcc_mark_up_percent', $content);
    }

    public function testFetchByIdForExpressAuth()
    {
        $this->ba->expressAuth();

        $order = $this->fixtures->create('order', [
            'amount'   => 50000,
            'currency' => 'INR',
            'receipt'  => 'rcptid42',
        ]);

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);
        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId(), 'order_id' => $order->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();
    }

    public function testFetchByIdNotExpressAuthError()
    {
        $this->ba->privateAuth();

        $order = $this->fixtures->create('order', [
            'amount'   => 50000,
            'currency' => 'INR',
            'receipt'  => 'rcptid42',
        ]);

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);
        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId(), 'order_id' => $order->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $content = $this->startTest();
    }

    public function testFetchStatusCountForPrivateAuth()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        //create 3 dummy payments
        $this->doAuthAndCapturePayment($paymentArray);

        $this->doAuthAndCapturePayment($paymentArray);

        $this->doAuthAndGetPayment($paymentArray);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['to'] = Carbon::now()->getTimestamp() + 20;

        $testData['request']['content']['from'] = Carbon::now()->getTimestamp() - 20;

        $this->fixtures->merchant->addFeatures([Feature::PAYMENT_STATUS_AGGREGATE]);

        $this->startTest();
    }

    public function testFetchStatusCountForPrivateAuthError()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures([Feature::PAYMENT_STATUS_AGGREGATE]);

        $this->startTest();
    }

    public function testFetchStatusCountForPrivateAuthFeatureOff()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFindWithEmiAsExpandsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $emiPlan = $this->fixtures->create('emi_plan', ['bank' => 'HDFC', 'duration' => '6', 'rate' => '1399']);

        $payment = $this->fixtures->create('payment', ['emi_plan_id' => $emiPlan->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testFindWithEmiPlanAsExpandsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $emiPlan = $this->fixtures->create('emi_plan', ['bank' => 'HDFC', 'duration' => '6', 'rate' => '1399']);

        $payment = $this->fixtures->create('payment', ['emi_plan_id' => $emiPlan->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testPaymentFetchWithExpandRefunds()
    {
        $this->ba->privateAuth();

        $paymentArray = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($paymentArray);

        $this->refundPayment($response['id'], '10000');
        $this->refundPayment($response['id'], '20000');

        $paymentFetchResponse = $this->fetchPayment($response['id'], ['expand' => [
                'refunds'
            ]]);

        $this->assertEquals('30000', $paymentFetchResponse['amount_refunded']);

        $this->assertArrayHasKey('refunds', $paymentFetchResponse);

        $refundsFromResponse = $paymentFetchResponse['refunds'];

        $this->assertEquals(2, $refundsFromResponse['count']);

        foreach ($refundsFromResponse['items'] as $refund)
        {
            $this->assertEquals($response['id'], $refund['payment_id']);
        }
    }

    public function testPaymentFetchWithoutExpandRefunds()
    {
        $this->ba->privateAuth();

        $paymentArray = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($paymentArray);

        $this->refundPayment($response['id'], '10000');
        $this->refundPayment($response['id'], '20000');

        $paymentFetchResponse = $this->fetchPayment($response['id']);

        $this->assertArrayNotHasKey('refunds', $paymentFetchResponse);
    }

    public function testFetchWithExpandsTransfer()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFindWithExpandsForPrivateAuthWithInvalidExpand()
    {
        $this->ba->privateAuth();

        $payment = $this->fixtures->create('payment');

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testFetchWithDisputes()
    {
        $this->ba->proxyAuth();

        $payment = $this->fixtures
                        ->create(
                            'payment:captured',
                            [
                                'disputed'  => 1,
                                'fee'       => 0,
                                'email'     => 'abc@email.com',
                            ]);

        $this->fixtures->times(2)->create('dispute', ['payment_id' => $payment->getId()]);

        $this->startTest();
    }

    public function testFetchPaymentByRecurringFilter()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');

        $this->mockCardVault();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPaymentFetchWithPartnerAuthWithoutAccountIdInHeader()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->createMerchantApplication('10000000000000', 'aggregator', $client->getApplicationId());

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->methods->createDefaultMethods(['merchant_id' => $sub->getId()]);

        $response = $this->doS2SPartnerAuthPayment($payment, $client, 'acc_' . $sub->getId());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $pay = $this->getLastEntity('payment', true);

        $paymentId = $pay['id'];

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/' . $paymentId;

        $resp = $this->startTest($testData);

        $signedAccountId = 'acc_' . $sub->getId();

        $this->assertEquals($signedAccountId, $resp['account_id']);

        $this->assertEquals($paymentId, $resp["id"]);
    }

    public function testPaymentFetchWithDifferentPartnerAuthWithoutAccountIdInHeader()
    {
        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type' => 'partner',
                'id'   => 'AwtIC8XQqM0Wet'
            ]);

        $this->mockCardVault();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        $sub = $this->fixtures->merchant->createWithBalance();

        $this->fixtures->feature->create([
            'entity_type' => 'application', 'entity_id'  => 'AwtIC8XQqM0Wet', 'name' => 's2s']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $sub->getId(),
            ]
        );

        $merchant = $this->fixtures->create('merchant');

        $paymentAttributes = [
            'merchant_id' => $merchant->getId(),
            'amount'      => 4000 * 100,
        ];

        $payment = $this->fixtures->create('payment:authorized', $paymentAttributes);

        $paymentId = $payment->getId();

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/pay_' . $paymentId;

        $this->startTest($testData);
    }

    public function testPaymentFetchINRCurrency()
    {
        $paymentArray = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($paymentArray);

        $paymentId = $response['id'];

        $paymentFetchResponse = $this->fetchPayment($paymentId);

        foreach (['base_amount', 'base_currency'] as $key)
        {
            $this->assertArrayNotHasKey($key, $paymentFetchResponse);
        }
    }

    public function testPaymentFetchFromPG()
    {
        $this->enablePgRouterConfig();
        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                    'body' => [
                        "data" => [
                            "payment" => [
                                'id' => 'GfnBMH2PXyCDVE',
                                'amount' => 50000,
                                'currency' => 'INR',
                                'status' =>'captured',
                                'order_id' => NULL,
                                'invoice_id' => NULL,
                                'international' => FALSE,
                                'method' => 'card',
                                'amount_refunded' => 0,
                                'refund_status' => NULL,
                                'captured' => TRUE,
                                'description' => 'random description',
                                'card_id' => 'GfnBMH2PXyCDVZ',
                                'bank' => NULL,
                                'wallet' => NULL,
                                'vpa' => NULL,
                                'email' => 'a@b.com',
                                'contact' => '+919918899029',
                                'notes' => [
                                    'merchant_order_id' => 'random order id',
                                ],
                                'fee' => 1000,
                                'tax' =>  0,
                                'reference_2' => '599962',
                                'created_at' => 1614252933,
                                'authorized_at' => 1614252933,
                                'merchant_id' => '10000000000000'
                            ]
                        ]
                    ],
                ];
            });

        $paymentFetchResponse = $this->fetchPayment('pay_GfnBMH2PXyCDVE');

        $this->assertEquals('pay_GfnBMH2PXyCDVE', $paymentFetchResponse['id']);

        $this->assertEquals('599962', $paymentFetchResponse['acquirer_data']['auth_code']);

        $this->assertNull($paymentFetchResponse['error_code']);

        $this->assertNull($paymentFetchResponse['error_description']);
    }

    public function testPaymentFetchExternalCallDisabled()
    {
        $this->disablePgRouterConfig();

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_INVALID_ID);

        $this->expectExceptionMessage('The id provided does not exist');

        $this->fetchPayment('TestPaymentID');

    }

    public function testPaymentFetchNonINRCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $paymentArray = $this->getDefaultPaymentArray();

        $paymentArray['currency'] = Currency::USD;

        $response = $this->doAuthAndCapturePayment($paymentArray, $paymentArray['amount'], Currency::USD);

        $paymentId = $response['id'];

        $paymentFetchResponse = $this->fetchPayment($paymentId);

        $this->assertEquals('50000', $paymentFetchResponse['amount']);

        $this->assertEquals(Currency::USD, $paymentFetchResponse['currency']);

        $this->assertArrayHasKey('base_amount', $paymentFetchResponse);

        $this->assertArrayHasKey('base_currency', $paymentFetchResponse);

        $this->assertEquals('500000', $paymentFetchResponse['base_amount']);

        $this->assertEquals(Currency::INR, $paymentFetchResponse['base_currency']);

    }

    public function testAdminPaymentFetchINRCurrency()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

        $paymentArray = $this->getDefaultPaymentArray();

        $response = $this->doAuthAndCapturePayment($paymentArray);

        $paymentId = $response['id'];

        $paymentFromAdminFetch = $this->getEntityById('payment', $paymentId, true);

        $this->assertArrayHasKey('base_amount', $paymentFromAdminFetch);

        $this->assertArrayNotHasKey('base_currency', $paymentFromAdminFetch);
    }

    public function testPrivateAuthPaymentFetchFeeBearerAttribute()
    {
        $payment = $this->fixtures->create('payment', []);

        $testData['request']['url'] = '/payments/pay_' . $payment['id'];

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey('fee_bearer', $response['items']['0']);
    }

    public function testAdminAuthPaymentFetchFeeBearerAttribute()
    {
        $payment = $this->fixtures->create('payment', []);

        $paymentEntity = $this->getLastPayment(true);

        $this->assertArrayHasKey('fee_bearer', $paymentEntity);
    }


    public function testProxyAuthPaymentFetchFeeBearerAttribute()
    {
        $payment = $this->fixtures->create('payment:authorized', []);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/pay_' . $payment['id'];

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotNull($response);

        $this->assertArrayHasKey('fee_bearer', $response);

        $this->assertEquals('platform', $response['fee_bearer']);
    }

    /**
     * Fix for : https://razorpay.slack.com/archives/C0156ULAEFQ/p1633870042148700?thread_ts=1632807756.453700&cid=C0156ULAEFQ
     */
    public function testProxyAuthPaymentFetchFeeBearerAttributeWithDifferentMerchantFeeBearer()
    {
        $payment = $this->fixtures->create('payment:authorized', [
            'fee_bearer' => 'customer',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['testProxyAuthPaymentFetchFeeBearerAttribute'];

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/pay_' . $payment['id'];

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals('customer', $response['fee_bearer']);
    }

    public function testProxyAuthPaymentWithExtraAttributesExposed()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => $org->getId(),
            'name'          => 'show_late_auth_attributes',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', [
            'org_id'    => $org->getId(),
        ]);

        $payment = $this->fixtures->create('payment:authorized', []);

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/pay_' . $payment['id'];

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotNull($response);

        $this->assertArrayHasKey('fee_bearer', $response);

        $this->assertEquals('platform', $response['fee_bearer']);
    }

    public function testFetchPaymentFromPgRouterWithPrivateAuth()
    {
        $this->enablePgRouterConfig();
        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                    'body' => [
                        'data'=>[
                            'payment'=>[
                                'id'=>'GrClIcbRtTUxxb',
                                'contact'=>'9891337297',
                                'email'=>'qa1610364215uduuazxbwyuxqrjl@example.com',
                                'merchant_id'=>'10000000000000',
                                'status'=>'authorized',
                                'gateway'=>'cybersource',
                                'description'=>'',
                                'gateway_captured'=>false,
                                'captured_at'=>0,
                                'recurring'=>false,
                                'international'=>false,
                                'terminal_id'=>'',
                                'recurring_type'=>'',
                                'authorized_at'=>0,
                                'authentication_gateway'=>'cybersource',
                                'verify_at'=>0,
                                'amount'=>100,
                                'otp_count'=>null,
                                'currency'=>'INR',
                                'method'=>'card',
                                'auth_type'=>'3ds',
                                'card_id'=>'GrClJNBzyquD7E',
                                'base_amount'=>null,
                                'authorized_amount'=>1,
                                'settled_by'=>'',
                                'updated_at'=>1616745601,
                                'created_at'=>1616744756,
                                'captured'=>false,
                                'receiver_type'=>'',
                                'convert_currency'=>false,
                                'preferred_auth'=>null,
                                'acquirer_data'=>[
                                    'auth_code'=>'83100'
                                ],
                                'internal_error_code'=>'',
                                'error_description'=>'',
                                'notes'=> [],
                                'two_factor_auth'=>'passed',
                                'invoice_id'=> null,
                                'transfer_id'=>'',
                                'payment_link_id'=>'',
                                'amount_refunded'=>null,
                                'base_amount_refunded'=>null,
                                'amount_paidout'=>null,
                                'amount_transferred'=>null,
                                'refund_status'=>null,
                                'bank'=>null,
                                'wallet'=>null,
                                'vpa'=>null,
                                'on_hold'=>null,
                                'on_hold_until'=>null,
                                'emi_plan_id'=>'',
                                'error_code'=>'',
                                'cancellation_reason'=>'',
                                'global_customer_id'=>'',
                                'receiver_id'=>'',
                                'app_token'=>'',
                                'emi_subvention'=>'',
                                'acknowledged_at'=>null,
                                'refund_at'=>null,
                                'reference13'=>null,
                                'reference16'=>null,
                                'reference17'=>null,
                                'global_token_id'=>'',
                                'transaction_id'=>'',
                                'auto_captured'=>false,
                                'reference1'=>null,
                                'reference2'=>null,
                                'cps_route'=>null,
                                'batch_id'=>'',
                                'signed'=>false,
                                'verified'=>1,
                                'verify_bucket'=>null,
                                'callback_url'=>'',
                                'fee'=>0,
                                'mdr'=>0,
                                'tax'=>0,
                                'otp_attempts'=>null,
                                'save'=>false,
                                'late_authorized'=>false,
                                'disputed'=>false,
                                'entity'=>'payments',
                                'fee_bearer'=>'platform',
                                'error_source'=>'',
                                'error_step'=>'',
                                'error_reason'=>'',
                                'gateway_amount'=>0,
                                'gateway_currency'=>'',
                                'forex_rate'=>0,
                                'dcc_offered'=>0,
                                'dcc_mark_up_percent'=>0,
                                'action_type'=>'',
                                'reference_id'=>'',
                                'dcc'=>false,
                                'dcc_markup_amount'=>0,
                                'mcc'=>false,
                                'forex_rate_received'=>0,
                                'forex_rate_applied'=>0,
                                'admin'=>true,
                                'mode'=>'test'
                            ]
                        ]
                    ]
                ];
            });

        $this->ba->privateAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/pay_AqQFBCdRFFwmB4';

        $response = $this->startTest();

        $this->assertEquals($response['id'], 'pay_GrClIcbRtTUxxb');
    }

    public function testFetchPaymentFromPgRouterWithPrivateAuthFailure()
    {
        $this->enablePgRouterConfig();
        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                    'body' => []
                ];
            });

        $this->ba->privateAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/pay_AqQFBCdRFFwmB4';

        $this->startTest();
    }

    public function testFetchPaymentFromPgRouterWithAdminAuth() {
        $this->enablePgRouterConfig();
        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                    'body' => [
                        'data' => [
                            'payment' => [
                                'id' => 'AqQFBCdRFFwmB4',
                                'amount' => 50000,
                                'currency' => 'INR',
                                'status' =>'captured',
                                'order_id' => NULL,
                                'invoice_id' => NULL,
                                'international' => FALSE,
                                'method' => 'card',
                                'amount_refunded' => 0,
                                'refund_status' => NULL,
                                'captured' => TRUE,
                                'description' => 'random description',
                                'card_id' => 'GfnBMH2PXyCDVZ',
                                'bank' => NULL,
                                'wallet' => NULL,
                                'vpa' => NULL,
                                'email' => 'a@b.com',
                                'contact' => '+919918899029',
                                'notes' => [
                                    'merchant_order_id' => 'random order id',
                                ],
                                'acquirer_data' => [
                                    'auth_code' => '599962'
                                ],
                                'fee' => 1000,
                                'tax' =>  0,
                                'error_code' => NULL,
                                'error_description' => NULL,
                                'error_source' => NULL,
                                'error_step' => NULL,
                                'error_reason' => NULL,
                                'created_at' => 1614252933,
                                'captured_at' => 1614252933,
                                'authorized_at' => 1614252933,
                                'merchant_id' => '10000000000000'
                            ]
                        ]
                    ]
                ];
            });

        $this->ba->adminAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] .= '/pay_AqQFBCdRFFwmB4';

        $response = $this->startTest();

        $this->assertEquals($response['id'], 'pay_AqQFBCdRFFwmB4');
    }

    public function testFetchPaymentFromPgRouterWithExpandsCard()
    {
        $this->enablePgRouterConfig();
        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $this->fixtures->create('card', ['id' => 'GrClJNBzyquD7E', 'name' => 'Test Name']);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                    'body' => [
                        'data'=>[
                            'payment'=>[
                                'id'=>'GrClIcbRtTUxxb',
                                'contact'=>'9891337297',
                                'email'=>'qa1610364215uduuazxbwyuxqrjl@example.com',
                                'merchant_id'=>'10000000000000',
                                'status'=>'authorized',
                                'gateway'=>'cybersource',
                                'description'=>'',
                                'gateway_captured'=>false,
                                'captured_at'=>0,
                                'recurring'=>false,
                                'international'=>false,
                                'terminal_id'=>'',
                                'recurring_type'=>'',
                                'authorized_at'=>0,
                                'authentication_gateway'=>'cybersource',
                                'verify_at'=>0,
                                'amount'=>3000,
                                'otp_count'=>null,
                                'currency'=>'INR',
                                'method'=>'card',
                                'auth_type'=>'3ds',
                                'card_id'=>'GrClJNBzyquD7E',
                                'base_amount'=>null,
                                'authorized_amount'=>1,
                                'settled_by'=>'',
                                'updated_at'=>1616745601,
                                'created_at'=>1616744756,
                                'captured'=>false,
                                'receiver_type'=>'',
                                'convert_currency'=>false,
                                'preferred_auth'=>null,
                                'acquirer_data'=>[
                                    'auth_code'=>'83100'
                                ],
                                'internal_error_code'=>'',
                                'error_description'=>'',
                                'notes'=> [],
                                'two_factor_auth'=>'passed',
                                'invoice_id'=> null,
                                'transfer_id'=>'',
                                'payment_link_id'=>'',
                                'amount_refunded'=>null,
                                'base_amount_refunded'=>null,
                                'amount_paidout'=>null,
                                'amount_transferred'=>null,
                                'refund_status'=>null,
                                'bank'=>null,
                                'wallet'=>null,
                                'vpa'=>null,
                                'on_hold'=>null,
                                'on_hold_until'=>null,
                                'emi_plan_id'=>'',
                                'error_code'=>'',
                                'cancellation_reason'=>'',
                                'global_customer_id'=>'',
                                'receiver_id'=>'',
                                'app_token'=>'',
                                'emi_subvention'=>'',
                                'acknowledged_at'=>null,
                                'refund_at'=>null,
                                'reference13'=>null,
                                'reference16'=>null,
                                'reference17'=>null,
                                'global_token_id'=>'',
                                'transaction_id'=>'',
                                'auto_captured'=>false,
                                'reference1'=>null,
                                'reference2'=>null,
                                'cps_route'=>null,
                                'batch_id'=>'',
                                'signed'=>false,
                                'verified'=>1,
                                'verify_bucket'=>null,
                                'callback_url'=>'',
                                'fee'=>0,
                                'mdr'=>0,
                                'tax'=>0,
                                'otp_attempts'=>null,
                                'save'=>false,
                                'late_authorized'=>false,
                                'disputed'=>false,
                                'entity'=>'payments',
                                'fee_bearer'=>'platform',
                                'error_source'=>'',
                                'error_step'=>'',
                                'error_reason'=>'',
                                'gateway_amount'=>0,
                                'gateway_currency'=>'',
                                'forex_rate'=>0,
                                'dcc_offered'=>0,
                                'dcc_mark_up_percent'=>0,
                                'action_type'=>'',
                                'reference_id'=>'',
                                'dcc'=>false,
                                'dcc_markup_amount'=>0,
                                'mcc'=>false,
                                'forex_rate_received'=>0,
                                'forex_rate_applied'=>0,
                                'admin'=>true,
                                'mode'=>'test'
                            ]
                        ]
                    ]
                ];
            });

        $this->ba->privateAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/pay_AqQFBCdRFFwmB4';

        $response = $this->startTest();

        $this->assertEquals($response['id'], 'pay_GrClIcbRtTUxxb');
    }

    public function testFetchPaymentFromPgRouterWithCard()
    {
        $this->enablePgRouterConfig();
        $pgService = \Mockery::mock('RZP\Services\PGRouter')->shouldAllowMockingProtectedMethods()->makePartial();

        $this->app->instance('pg_router', $pgService);

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry)
            {
                return [
                    'body' => [
                        'data'=>[
                            'payment'=>[
                                'id'=>'GrClIcbRtTUxxb',
                                'contact'=>'9891337297',
                                'email'=>'qa1610364215uduuazxbwyuxqrjl@example.com',
                                'merchant_id'=>'10000000000000',
                                'status'=>'authorized',
                                'gateway'=>'cybersource',
                                'description'=>'',
                                'gateway_captured'=>false,
                                'captured_at'=>0,
                                'recurring'=>false,
                                'international'=>false,
                                'terminal_id'=>'',
                                'recurring_type'=>'',
                                'authorized_at'=>0,
                                'authentication_gateway'=>'cybersource',
                                'verify_at'=>0,
                                'amount'=>3000,
                                'otp_count'=>null,
                                'currency'=>'INR',
                                'method'=>'card',
                                'auth_type'=>'3ds',
                                'card' => [
                                    'merchant_id'       =>  '10000000000000',
                                    'name'              =>  'test',
                                    'network'           =>  'RuPay',
                                    'expiry_month'      =>  '12',
                                    'expiry_year'       =>  '2100',
                                    'iin'               =>  '607384',
                                    'last4'             =>  '1111',
                                    'vault_token'       => 'NjA3Mzg0OTcwMDAwNDk0Nw==',
                                    'vault'             => 'rzpvault',
                                ],
                                'base_amount'=>null,
                                'authorized_amount'=>1,
                                'settled_by'=>'',
                                'updated_at'=>1616745601,
                                'created_at'=>1616744756,
                                'captured'=>false,
                                'receiver_type'=>'',
                                'convert_currency'=>false,
                                'preferred_auth'=>null,
                                'acquirer_data'=>[
                                    'auth_code'=>'83100'
                                ],
                                'internal_error_code'=>'',
                                'error_description'=>'',
                                'notes'=> [],
                                'two_factor_auth'=>'passed',
                                'invoice_id'=> null,
                                'transfer_id'=>'',
                                'payment_link_id'=>'',
                                'amount_refunded'=>null,
                                'base_amount_refunded'=>null,
                                'amount_paidout'=>null,
                                'amount_transferred'=>null,
                                'refund_status'=>null,
                                'bank'=>null,
                                'wallet'=>null,
                                'vpa'=>null,
                                'on_hold'=>null,
                                'on_hold_until'=>null,
                                'emi_plan_id'=>'',
                                'error_code'=>'',
                                'cancellation_reason'=>'',
                                'global_customer_id'=>'',
                                'receiver_id'=>'',
                                'app_token'=>'',
                                'emi_subvention'=>'',
                                'acknowledged_at'=>null,
                                'refund_at'=>null,
                                'reference13'=>null,
                                'reference16'=>null,
                                'reference17'=>null,
                                'global_token_id'=>'',
                                'transaction_id'=>'',
                                'auto_captured'=>false,
                                'reference1'=>null,
                                'reference2'=>null,
                                'cps_route'=>null,
                                'batch_id'=>'',
                                'signed'=>false,
                                'verified'=>1,
                                'verify_bucket'=>null,
                                'callback_url'=>'',
                                'fee'=>0,
                                'mdr'=>0,
                                'tax'=>0,
                                'otp_attempts'=>null,
                                'save'=>false,
                                'late_authorized'=>false,
                                'disputed'=>false,
                                'entity'=>'payments',
                                'fee_bearer'=>'platform',
                                'error_source'=>'',
                                'error_step'=>'',
                                'error_reason'=>'',
                                'gateway_amount'=>0,
                                'gateway_currency'=>'',
                                'forex_rate'=>0,
                                'dcc_offered'=>0,
                                'dcc_mark_up_percent'=>0,
                                'action_type'=>'',
                                'reference_id'=>'',
                                'dcc'=>false,
                                'dcc_markup_amount'=>0,
                                'mcc'=>false,
                                'forex_rate_received'=>0,
                                'forex_rate_applied'=>0,
                                'admin'=>true,
                                'mode'=>'test'
                            ]
                        ]
                    ]
                ];
            });

        $this->ba->privateAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/payments/pay_AqQFBCdRFFwmB4';

        $response = $this->startTest();

        $this->assertEquals($response['id'], 'pay_GrClIcbRtTUxxb');
    }

    public function testProxyAuthFetchPaymentOnTerminalId()
    {
        $payment = $this->fixtures->create('payment:authorized', []);

        $this->testData[__FUNCTION__]['request']['content']['terminal_id'] = $payment['terminal_id'];

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxyAuthFetchPaymentByIdForOptimiser()
    {
        $merchant = $this->fixtures->on('live')->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->on('live')->create('feature', [
            'name'          => Feature::RAAS,
            'entity_id'     => $merchantId,
            'entity_type'   => 'merchant',
        ]);

        $this->fixtures->on('live')->create('feature', [
            'name'          => Feature::ENABLE_SINGLE_RECON,
            'entity_id'     => $merchantId,
            'entity_type'   => 'merchant',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', 'live');

        $payment = $this->fixtures->on('live')->create('payment:authorized', [
            'merchant_id' => $merchantId,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->ba->proxyAuth('rzp_live_'.$merchantId, $merchantUser->getId());

        $this->startTest();
    }

    private function mockRazorxWith(string $featureUnderTest, string $value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')->will(
            $this->returnCallback(
                function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                {
                    return $feature === $featureUnderTest ? $value : 'control';
                }
            ));
    }
}
