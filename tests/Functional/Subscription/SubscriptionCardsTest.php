<?php

namespace RZP\Tests\Functional\Subscription;

use Carbon\Carbon;
use Mockery;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Item;
use RZP\Models\Plan\Subscription\Addon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SubscriptionCardsTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    public function testPreferencesWithCustomerIdInInput()
    {
        $exceptionDetails = [
            'error' => [
                'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                'description' => 'customer_id should not be sent in the input for subscription payment'
            ],
            'exception' => [
                    'class'               => 'RZP\Exception\BadRequestException',
                    'internal_error_code' => ErrorCode::BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT,
            ],
            'status_code' => 400
        ];

        $response = $this->makePreferencesCall(['customer_id' => 'justCheckinBro'], $exceptionDetails);
    }

    public function testPreferencesWithLocalCustomerHasTokens()
    {
        $response = $this->makePreferencesCall();

        $subscription = $this->getLastEntity('subscription', true);

        $customer = $this->getEntityById('customer', $subscription['customer_id'], true);

        $this->assertGreaterThanOrEqual(1, $response['customer']['tokens']['count']);
        $this->assertEquals($customer['id'], $response['customer']['customer_id']);
        $this->assertEquals(2000, $response['subscription']['amount']);
        $this->assertTrue($response['options']['remember_customer']);
    }

    public function testPreferencesWithGlobalCustomerNoFlashCheckoutEnabled()
    {
        $this->fixtures->merchant->addFeatures(['noflashcheckout']);

        $exceptionDetails = [
            'error' => [
                'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                'description' => 'Subscription payment cannot be made with Flash Checkout disabled'
            ],
            'exception' => [
                'class'               => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_SUBSCRIPTION_SAVE_CARD_DISABLED,
            ],
            'status_code' => 400
        ];

        $response = $this->makePreferencesCall([], $exceptionDetails, false);
    }

    public function testPreferencesWithGlobalCustomerHasTokens()
    {
        $response = $this->makePreferencesCall(['app_token' => 'capp_1000000custapp'], [], false);

        $this->assertGreaterThanOrEqual(1, $response['customer']['tokens']['count']);
        $this->assertArrayNotHasKey('customer_id', $response['customer']);
        $this->assertEquals(2000, $response['subscription']['amount']);
        $this->assertTrue($response['options']['remember_customer']);
    }

    public function testPreferencesChangeCardGlobalCustomer()
    {
        $globalCustomer = $this->fixtures->create('customer');
        $localCustomer = $this->fixtures->create('customer', ['global_customer_id' => $globalCustomer['id']]);

        $response = $this->makePreferencesCall(
            [
                'app_token' => 'capp_1000000custapp',
                'customer_id' => $localCustomer['id']
            ],
            [],
            false);
    }

    protected function makePreferencesCall($requestParams = [], $exceptionDetails = [], $createCustomer = true)
    {
        $subscription = $this->createSubscription(false, [], [], false, false, $createCustomer);

        $this->ba->publicAuth();

        $this->fixtures->merchant->activate('10000000000000');

        $requestContent = $this->testData[__FUNCTION__];

        $requestContent['request']['content']['subscription_id'] = $subscription['id'];

        if (empty($requestParams) === false)
        {
            $requestContent['request']['content'] = array_merge(
                $requestContent['request']['content'], $requestParams);
        }

        if (empty($exceptionDetails) === false)
        {
            $requestContent['response']['content']['error'] = $exceptionDetails['error'];
            $requestContent['response']['status_code'] = $exceptionDetails['status_code'];
            $requestContent['exception'] = $exceptionDetails['exception'];
        }

        $response = $this->startTest($requestContent);

        return $response;
    }
}
