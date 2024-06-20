<?php

namespace Functional\Customer;

use Carbon\Carbon;
use DateInterval;
use DateTimeZone;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Builder;
use RZP\Models\Address\AddressConsent1cc\Entity as AddressConsent1ccEntity;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Address\Type;
use RZP\Models\Customer\CustomerConsent1cc\Entity as CustomerConsent1ccEntity;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\Account;
use RZP\Modules\Acs\Wrapper\Constant as WrapperConstants;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class CustomerDetailsForCheckoutTest extends TestCase
{
    use RequestResponseFlowTrait;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CustomerDetailsForCheckoutTestData.php';

        parent::setUp();
    }

    public function testGetGlobalCustomerDetailsForCheckoutService(): void
    {
        $this->mockSession();

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testGetGlobalCustomerDetailsForCheckoutServiceUsingPassportJWT(): void
    {
        $sysClock = new SystemClock(new DateTimeZone('UTC'));
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates()))
            // Reserved/standard claims follows.
            ->issuedBy('https://edge.razorpay.com')
            ->permittedFor('https://api.razorpay.com')
            ->identifiedBy('per-req-uuid')
            ->issuedAt($sysClock->now())
            ->canOnlyBeUsedAfter($sysClock->now())
            ->expiresAt($sysClock->now()->add(new DateInterval('P2D')))
            ->withHeader('kid', 'edgev1')
            // Custom claims follows.
            ->withClaim('identified', true)
            ->withClaim('authenticated', true)
            ->withClaim('mode', 'test')
            ->withClaim('domain', 'razorpay')
            ->withClaim('consumer', ['id' => '10000000000000', 'type' => 'merchant'])
            ->withClaim('additional_identities', [
                'customer' => [
                    [
                        'id' => '10000gcustomer',
                        'type' => 'customer',
                    ],
                ],
            ]);

        $this->testData[__FUNCTION__]['request']['headers']['X-Passport-JWT-V1'] = $this->samplePassportJwt($tokenBuilder);

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testGetLocalCustomerDetailsForCheckoutService(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $customerId = 'zMRVsGfjoQyc9w';

        $this->fixtures->create('customer', [
            'id' => $customerId,
            'merchant_id' => Account::TEST_ACCOUNT,
            'contact' => '+919876543210',
            'email' => 'testlocalcustomer@razorpay.com',
        ]);

        $card = $this->fixtures->create('card', [
                'merchant_id'   => Account::TEST_ACCOUNT,
                'issuer'        => 'HDFC',
                'network'       => 'Visa',
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => 'visa',
                'vault_token'   => 'test_token',
            ]
        );

        $this->fixtures->create('token', [
                'customer_id'     => $customerId,
                'token'           => '1000lcardtoken',
                'method'          => 'card',
                'card_id'         => $card->getId(),
                'used_at'         => Carbon::now()->getTimestamp(),
                'merchant_id'     => Account::TEST_ACCOUNT,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => '9999999999',
                'status'          => 'active',
            ]
        );

        $this->startTest();
    }


    public function testFindOrCreateGlobalCustomerForANewCustomerOnStandardCheckout(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['customer']['id']);
    }

    public function testFindOrCreateGlobalCustomerForAnExistingCustomerOnStandardCheckout(): void
    {
        $customerId = 'zMRVsEqPxuwiGl';

        $timestamp = 1688365852;

        $this->fixtures->create('customer', [
            'id'          => $customerId,
            'merchant_id' => Account::SHARED_ACCOUNT,
            'contact'     => '+919878543210',
            'email'       => 'testexistingglobalcustomer@razorpay.com',
        ]);

        $card = $this->fixtures->create(
            'card',
            [
                'created_at'  => $timestamp,
                'issuer'      => 'HDFC',
                'last4'       => '1111',
                'merchant_id' => Account::TEST_ACCOUNT,
                'network'     => 'Visa',
                'type'        => 'debit',
                'vault'       => 'visa',
                'vault_token' => 'test_token',
            ],
        );

        $this->fixtures->create(
            'token',
            [
                'id'              => 'M9EQ5OztDvu5oh',
                'acknowledged_at' => $timestamp,
                'card_id'         => $card->getId(),
                'created_at'      => $timestamp,
                'customer_id'     => $customerId,
                'expired_at'      => '1706725799',
                'merchant_id'     => Account::TEST_ACCOUNT,
                'method'          => 'card',
                'status'          => 'active',
                'token'           => '1000lcardtoken',
                'used_at'         => $timestamp,
            ],
        );

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testFindOrCreateGlobalCustomerForAnExistingCustomerOnOneClickCheckout(): void
    {
        $customerId = 'zMRVsEqPxuwiGl';

        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);

        $timestamp = 1688365852;

        $this->fixtures->create('customer', [
            'id'          => $customerId,
            'merchant_id' => Account::SHARED_ACCOUNT,
            'contact'     => '+919878543210',
            'email'       => 'testexistingglobalcustomer@razorpay.com',
        ]);

        $this->fixtures->create('address', [
            AddressEntity::ID => 'M9ICOyJ139iODr',
            AddressEntity::ENTITY_ID => $customerId,
            AddressEntity::ENTITY_TYPE => 'customer',
            AddressEntity::SOURCE_TYPE => 'shopify',
            AddressEntity::TYPE => Type::BILLING_ADDRESS,
            AddressEntity::LINE1 => 'billing address line 1',
            AddressEntity::CITY => 'Bengaluru',
            AddressEntity::STATE => 'Karnataka',
            AddressEntity::ZIPCODE => '560030',
            AddressEntity::CREATED_AT => $timestamp,
        ]);

        // Third Party Address
        $this->fixtures->create('address', [
            AddressEntity::ID => 'M9ICOzBhiakey8',
            AddressEntity::ENTITY_ID => $customerId,
            AddressEntity::ENTITY_TYPE => 'customer',
            AddressEntity::SOURCE_TYPE => 'payment_pages',
            AddressEntity::TYPE => Type::SHIPPING_ADDRESS,
            AddressEntity::LINE1 => 'shipping address line 1',
            AddressEntity::CITY => 'Bengaluru',
            AddressEntity::STATE => 'Karnataka',
            AddressEntity::ZIPCODE => '560029',
            AddressEntity::CREATED_AT => $timestamp,
        ]);

        $this->fixtures->create('address_consent_1cc', [
            AddressConsent1ccEntity::CUSTOMER_ID => $customerId,
            AddressConsent1ccEntity::CREATED_AT => $timestamp,
        ]);

        $this->fixtures->create('customer_consent_1cc', [
            CustomerConsent1ccEntity::CONTACT => '+919878543210',
            CustomerConsent1ccEntity::MERCHANT_ID => Account::TEST_ACCOUNT,
            CustomerConsent1ccEntity::STATUS => true,
        ]);

        $card = $this->fixtures->create(
            'card',
            [
                'created_at'  => $timestamp,
                'issuer'      => 'HDFC',
                'last4'       => '1111',
                'merchant_id' => Account::TEST_ACCOUNT,
                'network'     => 'Visa',
                'type'        => 'debit',
                'vault'       => 'visa',
                'vault_token' => 'test_token',
            ],
        );

        $this->fixtures->create(
            'token',
            [
                'id'              => 'M9EQ5OztDvu5oh',
                'acknowledged_at' => $timestamp,
                'card_id'         => $card->getId(),
                'created_at'      => $timestamp,
                'customer_id'     => $customerId,
                'expired_at'      => '1706725799',
                'merchant_id'     => Account::TEST_ACCOUNT,
                'method'          => 'card',
                'status'          => 'active',
                'token'           => '1000lcardtoken',
                'used_at'         => $timestamp,
            ],
        );

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testFindOrCreateGlobalCustomerForAnExistingCustomerOnOneClickCheckoutForNoTripleConsent(): void
    {
        $customerId = 'zMRVsEqPxuwiGl';

        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);

        $timestamp = 1688365852;

        $this->fixtures->create('customer', [
            'id'          => $customerId,
            'merchant_id' => Account::SHARED_ACCOUNT,
            'contact'     => '+919878543210',
            'email'       => 'testexistingglobalcustomer@razorpay.com',
        ]);

        $this->fixtures->create('address', [
            AddressEntity::ID => 'M9ICOyJ139iODr',
            AddressEntity::ENTITY_ID => $customerId,
            AddressEntity::ENTITY_TYPE => 'customer',
            AddressEntity::SOURCE_TYPE => 'shopify',
            AddressEntity::TYPE => Type::BILLING_ADDRESS,
            AddressEntity::LINE1 => 'billing address line 1',
            AddressEntity::CITY => 'Bengaluru',
            AddressEntity::STATE => 'Karnataka',
            AddressEntity::ZIPCODE => '560030',
            AddressEntity::CREATED_AT => $timestamp,
        ]);

        // Third Party Address
        $this->fixtures->create('address', [
            AddressEntity::ID => 'M9ICOzBhiakey8',
            AddressEntity::ENTITY_ID => $customerId,
            AddressEntity::ENTITY_TYPE => 'customer',
            AddressEntity::SOURCE_TYPE => 'payment_pages',
            AddressEntity::TYPE => Type::SHIPPING_ADDRESS,
            AddressEntity::LINE1 => 'shipping address line 1',
            AddressEntity::CITY => 'Bengaluru',
            AddressEntity::STATE => 'Karnataka',
            AddressEntity::ZIPCODE => '560029',
            AddressEntity::CREATED_AT => $timestamp,
        ]);

        $this->fixtures->create('address_consent_1cc', [
            AddressConsent1ccEntity::CUSTOMER_ID => $customerId,
            AddressConsent1ccEntity::CREATED_AT => $timestamp,
        ]);

        $this->fixtures->create('customer_consent_1cc', [
            CustomerConsent1ccEntity::CONTACT => '+919878543210',
            CustomerConsent1ccEntity::MERCHANT_ID => Account::TEST_ACCOUNT,
            CustomerConsent1ccEntity::STATUS => true,
            CustomerConsent1ccEntity::CONSENT_JSON => [
                'one_cc_email_customer_consent' => true,
                'one_cc_whatsapp_customer_consent' => false,
            ]
        ]);

        $card = $this->fixtures->create(
            'card',
            [
                'created_at'  => $timestamp,
                'issuer'      => 'HDFC',
                'last4'       => '1111',
                'merchant_id' => Account::TEST_ACCOUNT,
                'network'     => 'Visa',
                'type'        => 'debit',
                'vault'       => 'visa',
                'vault_token' => 'test_token',
            ],
        );

        $this->fixtures->create(
            'token',
            [
                'id'              => 'M9EQ5OztDvu5oh',
                'acknowledged_at' => $timestamp,
                'card_id'         => $card->getId(),
                'created_at'      => $timestamp,
                'customer_id'     => $customerId,
                'expired_at'      => '1706725799',
                'merchant_id'     => Account::TEST_ACCOUNT,
                'method'          => 'card',
                'status'          => 'active',
                'token'           => '1000lcardtoken',
                'used_at'         => $timestamp,
            ],
        );
        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testFindOrCreateGlobalCustomerForAnExistingCustomerOnOneClickCheckoutForTripleConsent(): void
    {
        $customerId = 'zMRVsEqPxuwiGl';

        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);

        $timestamp = 1688365852;

        $this->fixtures->create('customer', [
            'id'          => $customerId,
            'merchant_id' => Account::SHARED_ACCOUNT,
            'contact'     => '+919878543210',
            'email'       => 'testexistingglobalcustomer@razorpay.com',
        ]);

        $this->fixtures->create('address', [
            AddressEntity::ID => 'M9ICOyJ139iODr',
            AddressEntity::ENTITY_ID => $customerId,
            AddressEntity::ENTITY_TYPE => 'customer',
            AddressEntity::SOURCE_TYPE => 'shopify',
            AddressEntity::TYPE => Type::BILLING_ADDRESS,
            AddressEntity::LINE1 => 'billing address line 1',
            AddressEntity::CITY => 'Bengaluru',
            AddressEntity::STATE => 'Karnataka',
            AddressEntity::ZIPCODE => '560030',
            AddressEntity::CREATED_AT => $timestamp,
        ]);

        // Third Party Address
        $this->fixtures->create('address', [
            AddressEntity::ID => 'M9ICOzBhiakey8',
            AddressEntity::ENTITY_ID => $customerId,
            AddressEntity::ENTITY_TYPE => 'customer',
            AddressEntity::SOURCE_TYPE => 'payment_pages',
            AddressEntity::TYPE => Type::SHIPPING_ADDRESS,
            AddressEntity::LINE1 => 'shipping address line 1',
            AddressEntity::CITY => 'Bengaluru',
            AddressEntity::STATE => 'Karnataka',
            AddressEntity::ZIPCODE => '560029',
            AddressEntity::CREATED_AT => $timestamp,
        ]);

        $this->fixtures->create('address_consent_1cc', [
            AddressConsent1ccEntity::CUSTOMER_ID => $customerId,
            AddressConsent1ccEntity::CREATED_AT => $timestamp,
        ]);

        $this->fixtures->create('customer_consent_1cc', [
            CustomerConsent1ccEntity::CONTACT => '+919878543210',
            CustomerConsent1ccEntity::MERCHANT_ID => Account::TEST_ACCOUNT,
            CustomerConsent1ccEntity::STATUS => true,
            CustomerConsent1ccEntity::CONSENT_JSON => [
                'one_cc_email_customer_consent' => true,
                'one_cc_whatsapp_customer_consent' => false,
            ]
        ]);

        $card = $this->fixtures->create(
            'card',
            [
                'created_at'  => $timestamp,
                'issuer'      => 'HDFC',
                'last4'       => '1111',
                'merchant_id' => Account::TEST_ACCOUNT,
                'network'     => 'Visa',
                'type'        => 'debit',
                'vault'       => 'visa',
                'vault_token' => 'test_token',
            ],
        );

        $this->fixtures->create(
            'token',
            [
                'id'              => 'M9EQ5OztDvu5oh',
                'acknowledged_at' => $timestamp,
                'card_id'         => $card->getId(),
                'created_at'      => $timestamp,
                'customer_id'     => $customerId,
                'expired_at'      => '1706725799',
                'merchant_id'     => Account::TEST_ACCOUNT,
                'method'          => 'card',
                'status'          => 'active',
                'token'           => '1000lcardtoken',
                'used_at'         => $timestamp,
            ],
        );

        $this->setSplitzWithOutput("true", 1);

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }


    protected function mockSession($appToken = 'capp_1000000custapp'): void
    {
        $data = ['test_app_token' => $appToken ];

        $this->session($data);
    }
    private function setSplitzWithOutput($output, $count = 1)
    {
        $splitz = $this->sampleSplitzOutput;
        $splitz["response"]["variant"]["variables"][0]["value"] = $output;
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly($count))->method('evaluateRequest')->willReturn($splitz);
        $this->app[WrapperConstants::SPLITZ_SERVICE] = $splitzMock;
        return $splitz;
    }

    private $sampleSplitzOutput = [
        'status_code' => 200,
        'response' => [
            'id' => '10000000000000',
            'project_id' => 'K1ZCHBSn7hbCMN',
            'experiment' => [
                'id' => 'K1ZaAGS9JfAUHj',
                'name' => 'CallSyncDviationAPI',
                'exclusion_group_id' => '',
            ],
            'variant' => [
                'id' => 'K1ZaAHZ7Lnumc6',
                'name' => 'Dummy Enabled',
                'variables' => [
                    [
                        'key' => 'enabled',
                        'value' => 'true',
                    ]
                ],
                'experiment_id' => 'K1ZaAGS9JfAUHj',
                'weight' => 100,
                'is_default' => false
            ],
            'Reason' => 'bucketer',
            'steps' => [
                'sampler',
                'exclusion',
                'audience',
                'assign_bucket'
            ]
        ]
    ];

    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {

        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }
}
