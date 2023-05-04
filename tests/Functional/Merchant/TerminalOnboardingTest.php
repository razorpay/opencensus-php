<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Pricing\Repository as PricingRepo;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Base as BaseFixture;

class TerminalOnboardingTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalOnboardingTestData.php';

        parent::setUp();
    }

    public function testInitiateOnboardingProxyRoute()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();
    }

    public function testInitiateOnboardingProxyRouteForPaytm()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testInitiateOnboardingProxyRouteForPhonepe()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testInitiateOnboardingWithNoGatewayInInput()
    {
        $this->ba->proxyAuth();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage(
            'The gateway field is required.');

        $this->startTest();
    }

    public function testInitiateOnboardingProxyRouteInvalidGateway()
    {
        $this->ba->proxyAuth();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);

        $this->expectExceptionMessage(
            'The selected gateway is invalid.');

        $this->startTest();
    }

    public function testInitiateOnboardingAxisAdminRoutePaysecureAxis()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'external_org_create_terminals']);

        $role->permissions()->attach($perm->getId());

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $this->startTest();
    }

    public function testInitiateOnboardingNonAxisOrgAdminRoutePaysecureAxis() // not adding the feature flag for axis org
    {
        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'external_org_create_terminals']);

        $role->permissions()->attach($perm->getId());

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);

        $this->expectExceptionMessage(
            'Org not allowed');

        $this->startTest();
    }

    public function testInitiateOnboardingAdminRoutePaysecureAxisExtraFieldsValidationFailure()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'external_org_create_terminals']);

        $role->permissions()->attach($perm->getId());

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED);

        $this->expectExceptionMessage(
            'currency_code is/are not required and should not be sent');

        $this->startTest();
    }

    public function testInitiateOnboardingAdminRoutePaysecureAxisValidationFailureInvalidAcquirer()
    {
        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'hdfcbank.com');

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => 'external_org_create_terminals']);

        $role->permissions()->attach($perm->getId());

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);

        $this->expectExceptionMessage(
            'The selected gateway acquirer is invalid.');

        $this->startTest();
    }


    public function testInitiateOnboardingAdminRouteFulcrum()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testInitiateOnboardingAdminRouteExtraFieldsFulcrumValidationFailure()
    {
        $this->ba->adminAuth();

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED);

        $this->expectExceptionMessage(
            'gateway_input is/are not required and should not be sent');

        $this->startTest();
    }

    public function testInitiateOnboardingAdminRouteInvalidGateway()
    {
        $this->ba->adminAuth();

        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);

        $this->expectExceptionMessage(
            'The selected gateway is invalid.');

        $this->startTest();
    }

    /**
     * In PayPal onboarding, terminals service calls api to add default paypal rule in merchant's pricing
     */
    public function testMerchantPricingPaypalPlanRule()
    {
        $this->ba->terminalsAuth();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $merchant = $this->getDbLastEntity('merchant' );

        $pricingRules = (new PricingRepo)->getPlanByIdOrFailPublic('1A0Fkd38fGZPVC');

        $ruleCount = $pricingRules->count();

        $this->startTest();

        $pricingRules = (new PricingRepo)->getPlanByIdOrFailPublic('1A0Fkd38fGZPVC');
        $this->assertEquals($ruleCount+1, $pricingRules->count());

        $pricingRule = $this->getDbLastEntity('pricing');
        $this->assertEquals('1A0Fkd38fGZPVC', $pricingRule->getPlanId());
        $this->assertEquals('wallet', $pricingRule->getPaymentMethod());
        $this->assertEquals('paypal', $pricingRule->getPaymentNetwork());
        $this->assertEquals('org_100000razorpay', $pricingRule->getOrgId());


    }

    public function testMerchantPricingPaypalPlanRuleAlreadyExist()
    {
        $this->ba->terminalsAuth();

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1A0Fkd38fGZPVC']);

        $pricingRules = (new PricingRepo)->getPlanByIdOrFailPublic('1A0Fkd38fGZPVC');
        $ruleCount = $pricingRules->count();

        $this->startTest();

        $pricingRules = (new PricingRepo)->getPlanByIdOrFailPublic('1A0Fkd38fGZPVC');
        $this->assertEquals($ruleCount + 1, $pricingRules->count());
    }

    // Terminals Service call api to enable PayPal on activation of PayPal onboarding terminal
    public function testEnablePaypalMethodInternal()
    {
        $this->ba->terminalsAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');
        $this->assertTrue($merchant->methods->isPaypalEnabled());
    }

    public function testEnablePaypalMethodInternalWithWrongMerchantIdInInput()
    {
        $this->ba->terminalsAuth();

        $this->testData[__FUNCTION__] = $this->testData['testEnablePaypalMethodInternal'];

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/1001230000000000/methods';

        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_INVALID_ID);

        $this->expectExceptionMessage(
            'The id provided does not exist');

        $this->startTest();
    }
    // Terminals Service call api to enable Upi on activation of Upi onboarding terminal
    public function testEnableUpiMethodInternal()
    {
        $this->ba->terminalsAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');
        $this->assertTrue($merchant->methods->isUpiEnabled());
    }
}
