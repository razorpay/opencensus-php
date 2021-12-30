<?php

namespace RZP\Tests\Functional\Request;

use Razorpay\OAuth\Exception\DBQueryException;
use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\KeylessPublicAuth;
use RZP\Tests\Unit\Request\Traits\MocksRequest;

/**
 * Tests functionality of KeslessPublicAuth.php class. Testing the class's functionality separately is way easier than
 * having to actually hit various routes(which requires lot of pre setup). We just mock a request and invoke the method
 * of the class and assert it's working fine.
 *
 * There would be additional test which does fully functional tests for a few routes(starting from HTTP api call).
 */
class KeylessPublicAuthClassTest extends TestCase
{
    use MocksRequest;
    use Traits\KeylessPublicAuthTrait;

    const DEFAULT_PAYMENT_ID      = 'pay_1000000payment';
    const DEFAULT_ORDER_ID        = 'order_100000000order';
    const DEFAULT_INVOICE_ID      = 'inv_1000000invoice';
    const DEFAULT_CUSTOMER_ID     = 'cust_110000customer';
    const DEFAULT_MERCHANT_ID     = '10000000000000';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/KeylessPublicAuthClassTestData.php';

        parent::setUp();
    }

    /**
     * Tests various public routes which have x-entity-id in route path.
     */
    public function testPublicRoutesWithXEntityIdInRouteParam()
    {
        $this->assertForPublicRouteWithXEntityIdInRouteParamByMode(Mode::LIVE);
    }

    /**
     * Tests various public routes which have x-entity-id in route path, for test mode.
     */
    public function testPublicRoutesWithXEntityIdInRouteParamInTestMode()
    {
        $this->assertForPublicRouteWithXEntityIdInRouteParamByMode(Mode::TEST);
    }

    public function testPublicRouteWithXEntityIdInQueryParam()
    {
        $this->createOrder();

        $query = [KeylessPublicAuth::X_ENTITY_ID_QUERY_KEY => self::DEFAULT_ORDER_ID];
        $args  = ['checkout', null, [], [], $query];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testPublicRouteWithXEntityIdInHeader()
    {
        $this->createOrder();

        $headers = ['HTTP_' . KeylessPublicAuth::X_ENTITY_ID_HEADER_KEY => self::DEFAULT_ORDER_ID];
        $args    = ['checkout', null, [], [], [], [], $headers];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testPublicRouteWithNoXEntityId()
    {
        $this->createOrder();
        $this->mockRequestAndAssertKeylessAuthMerchant(['checkout'], false);
    }

    /**
     * Case: X entity id has invalid sign. In such cases, keyless layer will return null as merchant's value and lets
     * usual flow take over where it will throw 401.
     */
    public function testPublicRouteWithInvalidXEntityId1()
    {
        $this->createOrder();

        $query = [KeylessPublicAuth::X_ENTITY_ID_QUERY_KEY => 'ord_100000000order'];
        $args  = ['checkout', null, [], [], $query];
        $this->mockRequestAndAssertKeylessAuthMerchant(['checkout'], false);
    }

    /**
     * Case: X entity id's value itself is invalid. In such cases keyless layer will throw 400.
     */
    public function testPublicRouteWithInvalidXEntityId2()
    {
        $this->expectException(\RZP\Exception\DbQueryException::class);

        $this->createOrder();

        $query = [KeylessPublicAuth::X_ENTITY_ID_QUERY_KEY => 'order_100000001order'];
        $args  = ['checkout', null, [], [], $query];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testCheckoutPreferencesRouteWithXEntityIdInQuery()
    {
        $this->createOrder();

        $query = [KeylessPublicAuth::X_ENTITY_ID_QUERY_KEY => self::DEFAULT_ORDER_ID];
        $args  = ['merchant_checkout_preferences', null, [], [], $query];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testCheckoutPreferencesRouteWithOrderIdInQuery()
    {
        $this->createOrder();

        $query = ['order_id' => self::DEFAULT_ORDER_ID];
        $args  = ['merchant_checkout_preferences', null, [], [], $query];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testCheckoutPreferencesRouteWithInvoiceIdInQuery()
    {
        $this->createInvoice();

        $query = ['invoice_id' => self::DEFAULT_INVOICE_ID];
        $args  = ['merchant_checkout_preferences', null, [], [], $query];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testPaymentCreateRouteWithXEntityIdInInput()
    {
        $this->createOrder();

        $input = [KeylessPublicAuth::X_ENTITY_ID_QUERY_KEY => self::DEFAULT_ORDER_ID];
        $args  = ['payment_create', null, [], [], [], $input];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testPaymentCreateRouteWithOrderIdInInput()
    {
        $this->createOrder();

        $input = ['order_id' => self::DEFAULT_ORDER_ID];
        $args  = ['payment_create', null, [], [], [], $input];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    public function testPaymentCreateRouteWithInvoiceIdInInput()
    {
        $this->createInvoice();

        $input = ['invoice_id' => self::DEFAULT_INVOICE_ID];
        $args  = ['payment_create', null, [], [], [], $input];
        $this->mockRequestAndAssertKeylessAuthMerchant($args);
    }

    /**
     * Makes assertions for a list of public routes with x_enitity_id as route param, for a given mode.
     * @param  string $mode
     */
    protected function assertForPublicRouteWithXEntityIdInRouteParamByMode(string $mode)
    {
        $this->createPayment($mode);
        $this->createInvoice($mode);
        $this->createCustomer($mode);

        foreach ($this->testData['public_x_entity_id_routes'] as $route)
        {
            $this->mockRouteRequest($route['name'], $route['path']);

            list($mode, $merchant, $entityId) = (new KeylessPublicAuth)->retrieveModeMerchantAndXEntityId();

            $this->assertEquals(self::DEFAULT_MERCHANT_ID, $merchant->getId());
            $this->assertEquals($mode, $merchant->getConnectionName());
            $this->assertEquals($route['expected_x_entity_id'], $entityId);
        }
    }

    /**
     * Mocks request with given arguments and assert if merchant should be set by keyless layer or not.
     * @param  array        $requestArgs
     * @param  bool|boolean $merchantMustExist
     */
    protected function mockRequestAndAssertKeylessAuthMerchant(array $requestArgs = [], bool $merchantMustExist = true)
    {
        $this->mockRouteRequest(...$requestArgs);

        list($mode, $merchant, $entityId) = (new KeylessPublicAuth)->retrieveModeMerchantAndXEntityId();

        if ($merchantMustExist === true)
        {
            $this->assertNotNull($merchant);
            $this->assertEquals($mode, $merchant->getConnectionName());
        }
        else
        {
            $this->assertNull($merchant);
            $this->assertNull($mode);
            $this->assertNull($entityId);
        }
    }
}
