<?php

namespace Functional\Customer;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Address\AddressConsent1cc\Entity as AddressConsent1ccEntity;
use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Address\Type;
use RZP\Models\Customer\CustomerConsent1cc\Entity as CustomerConsent1ccEntity;
use RZP\Models\Customer\Truecaller\AuthRequest\Service;
use Mockery;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Trace\TraceCode;

class TruecallerTest extends TestCase
{
    use AttemptTrait;
    use DbEntityFetchTrait;
    use AttemptReconcileTrait;

    protected $truecallerClientMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TruecallerTestData.php';

        $this->truecallerClientMock = Mockery::mock('overload:\RZP\Models\Customer\Truecaller\Client')
            ->shouldAllowMockingMethod('fetchUserProfile')->makePartial();

        parent::setUp();
    }

    public function testTruecallerCallbackWithValidData(): void
    {
        $truecallerAuthRequest = (new Service())->create();
        $id = $truecallerAuthRequest->getId();

        $successContent = $this->testData[__FUNCTION__]['request']['successContent'];
        $userProfile = $this->testData[__FUNCTION__]['request']['userProfile'];
        $this->setTruecallerRequestId($id, $successContent);
        $this->mockTruecallerResponse($userProfile);
        $response = $this->sendCallback($successContent);
        $this->assertEquals([],$response);

        $userRejectedContent = $this->testData[__FUNCTION__]['request']['userRejectedContent'];
        $this->setTruecallerRequestId($id, $userRejectedContent);
        $response = $this->sendCallback($userRejectedContent);
        $this->assertEquals([],$response);

        $usedAnotherNumberContent = $this->testData[__FUNCTION__]['request']['usedAnotherNumberContent'];
        $this->setTruecallerRequestId($id, $usedAnotherNumberContent);
        $response = $this->sendCallback($usedAnotherNumberContent);
        $this->assertEquals([],$response);
    }

    public function testTruecallerCallbackWithInvalidData(): void
    {
        $requestId = ((new Service())->create())->getId();

        $contentWithoutRequestId = $this->testData[__FUNCTION__]['request']['contentWithoutRequestId'];
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('requestId field is required');
        $this->sendCallback($contentWithoutRequestId);

        $contentWithoutAccessToken = $this->testData[__FUNCTION__]['request']['contentWithoutAccessToken'];
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('accessToken field is required');
        $this->setTruecallerRequestId($requestId, $contentWithoutAccessToken);
        $this->sendCallback($contentWithoutAccessToken);

        $contentWithoutEndpoint = $this->testData[__FUNCTION__]['request']['contentWithoutEndpoint'];
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('endpoint field is required');
        $this->setTruecallerRequestId($requestId, $contentWithoutEndpoint);
        $this->sendCallback($contentWithoutEndpoint);
    }

    public function testVerifyTruecallerRequestWithInvalidData(): void
    {
        $this->ba->publicAuth();

        $contentWithoutRequestId = $this->testData[__FUNCTION__]['request']['contentWithoutRequestId'];
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('The request id field is required.');
        $this->verifyTruecallerAuthRequest($contentWithoutRequestId);

        $contentWithInvalidRequestId = $this->testData[__FUNCTION__]['request']['contentWithInvalidRequestId'];
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('Not a valid id: ' . $contentWithInvalidRequestId['request_id']);
        $this->verifyTruecallerAuthRequest($contentWithInvalidRequestId);
    }

    public function testVerifyTruecallerRequestInternalWithInvalidData(): void
    {
        $this->ba->publicAuth();

        $function = 'testVerifyTruecallerRequestWithInvalidData';
        $contentWithoutRequestId = $this->testData[$function]['request']['contentWithoutRequestId'];
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('The request id field is required.');
        $this->verifyTruecallerAuthRequest($contentWithoutRequestId, true);

        $contentWithInvalidRequestId = $this->testData[$function]['request']['contentWithInvalidRequestId'];
        $this->expectException(Exception\BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('Not a valid id: ' . $contentWithInvalidRequestId['request_id']);
        $this->verifyTruecallerAuthRequest($contentWithInvalidRequestId, true);
    }

    public function testVerifyTruecallerRequestForSuccessResponse(): void
    {
        $this->ba->publicAuth();

        $userProfile = $this->testData[__FUNCTION__]['request']['userProfile'];
        $callbackContent = $this->testData[__FUNCTION__]['request']['callbackContent'];

        $this->mockTruecallerResponse($userProfile);

        $requestId = ((new Service())->create())->getId();

        $content['request_id'] = $requestId;
        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyTruecallerAuthRequest($content);
        $this->assertEquals('pending', $response['status']);

        // verify that status after sending callback is resolved
        $this->setTruecallerRequestId($requestId, $callbackContent);
        $this->sendCallback($callbackContent);

        $response = $this->verifyTruecallerAuthRequest($content);
        $this->assertEquals('resolved', $response['status']);
        $this->assertEquals($userProfile['contact'], $response['contact']);
        $this->assertEquals($userProfile['email'], $response['email']);
        $this->assertArrayNotHasKey('global_customer_id', $response);
    }

    public function testVerifyTruecallerRequestInternalForSuccessResponse(): void
    {
        $this->ba->publicAuth();

        $function = 'testVerifyTruecallerRequestForSuccessResponse';
        $userProfile = $this->testData[$function]['request']['userProfile'];
        $callbackContent = $this->testData[$function]['request']['callbackContent'];

        $this->mockTruecallerResponse($userProfile);

        $requestId = ((new Service())->create())->getId();

        $content['request_id'] = $requestId;
        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyTruecallerAuthRequest($content, true);
        $this->assertEquals('pending', $response['status']);
        $this->assertArrayNotHasKey('global_customer_id', $response);

        // verify that status after sending callback is resolved
        $this->setTruecallerRequestId($requestId, $callbackContent);
        $this->sendCallback($callbackContent);

        $response = $this->verifyTruecallerAuthRequest($content, true);
        $this->assertEquals('resolved', $response['status']);
        $this->assertEquals($userProfile['contact'], $response['contact']);
        $this->assertEquals($userProfile['email'], $response['email']);
        $this->assertNotEmpty($response['global_customer_id']);

        $globalCustomerId = $response['global_customer_id'];

        $customer = $this->getDbEntityById('customer', $globalCustomerId);

        $this->assertEquals($customer->getEmail(), $response['email']);
        $this->assertEquals($customer->getContact(), $response['contact']);
    }

    public function testVerifyTruecallerRequestForSuccessResponseOneClickCheckout(): void
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

        $this->ba->publicAuth();

        $userProfile = $this->testData[__FUNCTION__]['request']['userProfile'];
        $callbackContent = $this->testData[__FUNCTION__]['request']['callbackContent'];

        $this->mockTruecallerResponse($userProfile);

        $requestId = ((new Service())->create())->getId();

        $content = [
            'request_id' => $requestId,
        ];

        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyOneCcTruecallerAuthRequest($content);
        $this->assertEquals('pending', $response['status']);

        // verify that status after sending callback is resolved
        $this->setTruecallerRequestId($requestId, $callbackContent);
        $this->sendCallback($callbackContent);

        $response = $this->verifyOneCcTruecallerAuthRequest($content);
        $this->assertEquals('resolved', $response['status']);
        $this->assertEquals($userProfile['contact'], $response['contact']);
        $this->assertEquals($userProfile['email'], $response['email']);
        $this->assertArrayNotHasKey('global_customer_id', $response);
        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['response']['content'], $response);
    }

    public function testVerifyTruecallerRequestInternalForSuccessResponseOneClickCheckout(): void
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

        $this->ba->publicAuth();

        $userProfile = $this->testData[__FUNCTION__]['request']['userProfile'];
        $callbackContent = $this->testData[__FUNCTION__]['request']['callbackContent'];

        $this->mockTruecallerResponse($userProfile);

        $requestId = ((new Service())->create())->getId();

        $content = [
            'request_id' => $requestId,
            'is_one_cc'  => 1,
        ];

        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyOneCcTruecallerAuthRequest($content, true);
        $this->assertEquals('pending', $response['status']);

        // verify that status after sending callback is resolved
        $this->setTruecallerRequestId($requestId, $callbackContent);
        $this->sendCallback($callbackContent);

        $response = $this->verifyOneCcTruecallerAuthRequest($content, true);
        $this->assertEquals('resolved', $response['status']);
        $this->assertEquals($userProfile['contact'], $response['contact']);
        $this->assertEquals($userProfile['email'], $response['email']);
        $this->assertArrayHasKey('global_customer_id', $response);
        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['response']['content'], $response);
    }

    public function testVerifyTruecallerRequestForRejectedResponse(): void
    {
        $this->ba->publicAuth();

        $userRejectedContent = $this->testData[__FUNCTION__]['request']['userRejectedContent'];
        $usedAnotherNumberContent = $this->testData[__FUNCTION__]['request']['usedAnotherNumberContent'];

        $requestId = ((new Service())->create())->getId();

        $content['request_id'] = $requestId;
        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyTruecallerAuthRequest($content);
        $this->assertEquals('pending', $response['status']);

        // send user_rejected in response
        $this->setTruecallerRequestId($requestId, $userRejectedContent);
        $this->sendCallback($userRejectedContent);

        $response = $this->verifyTruecallerAuthRequest($content);

        $this->assertEquals('rejected', $response['status']);
        $this->assertEquals('user_rejected', $response['code']);

        // send used_another_number
        $this->setTruecallerRequestId($requestId, $usedAnotherNumberContent);
        $this->sendCallback($usedAnotherNumberContent);

        $response = $this->verifyTruecallerAuthRequest($content);

        $this->assertEquals('rejected', $response['status']);
        $this->assertEquals('use_another_number', $response['code']);
    }

    public function testVerifyTruecallerRequestInternalForRejectedResponse(): void
    {
        $this->ba->publicAuth();

        $function = 'testVerifyTruecallerRequestForRejectedResponse';
        $userRejectedContent = $this->testData[$function]['request']['userRejectedContent'];
        $usedAnotherNumberContent = $this->testData[$function]['request']['usedAnotherNumberContent'];

        $requestId = ((new Service())->create())->getId();

        $content['request_id'] = $requestId;
        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyTruecallerAuthRequest($content, true);
        $this->assertEquals('pending', $response['status']);

        // send user_rejected in response
        $this->setTruecallerRequestId($requestId, $userRejectedContent);
        $this->sendCallback($userRejectedContent);

        $response = $this->verifyTruecallerAuthRequest($content, true);

        $this->assertEquals('rejected', $response['status']);
        $this->assertEquals('user_rejected', $response['code']);

        // send used_another_number
        $this->setTruecallerRequestId($requestId, $usedAnotherNumberContent);
        $this->sendCallback($usedAnotherNumberContent);

        $response = $this->verifyTruecallerAuthRequest($content, true);

        $this->assertEquals('rejected', $response['status']);
        $this->assertEquals('use_another_number', $response['code']);
    }

    public function testVerifyTruecallerRequestForErrorResponse(): void
    {
        $this->ba->publicAuth();

        $callbackContent = $this->testData[__FUNCTION__]['request']['callbackContent'];
        $accessDeniedContent = [
            'error' => 'access_denied',
        ];

        $this->mockTruecallerResponse($accessDeniedContent);

        $requestId = ((new Service())->create())->getId();

        $content['request_id'] = $requestId;
        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyTruecallerAuthRequest($content);
        $this->assertEquals('pending', $response['status']);

        // send user_rejected in response
        $this->setTruecallerRequestId($requestId, $callbackContent);
        $this->sendCallback($callbackContent);

        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_ACCESS_DENIED);

        $this->verifyTruecallerAuthRequest($content);
    }

    public function testVerifyTruecallerRequestInternalForErrorResponse(): void
    {
        $this->ba->publicAuth();

        $function = 'testVerifyTruecallerRequestForErrorResponse';
        $callbackContent = $this->testData[$function]['request']['callbackContent'];
        $accessDeniedContent = [
            'error' => 'access_denied',
        ];

        $this->mockTruecallerResponse($accessDeniedContent);

        $requestId = ((new Service())->create())->getId();

        $content['request_id'] = $requestId;
        $this->setTruecallerRequestIdForPolling($requestId, $content);

        // verifying that status before sending callback is in pending state
        $response = $this->verifyTruecallerAuthRequest($content, true);
        $this->assertEquals('pending', $response['status']);

        // send user_rejected in response
        $this->setTruecallerRequestId($requestId, $callbackContent);
        $this->sendCallback($callbackContent);

        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_ACCESS_DENIED);

        $this->verifyTruecallerAuthRequest($content, true);
    }

    public function testCreateTruecallerAuthRequestInternal(): void
    {
        $this->ba->checkoutServiceProxyAuth();

        $response = $this->startTest();

        $this->assertNotNull($response['id']);
        $this->assertNotNull($response['created_at']);
    }


    // helper fucntions
    protected function sendCallback(array $content)
    {
        $request = array(
            'url' => '/customers/truecaller/callback',
            'method' => 'post',
            'content' => $content,
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function setTruecallerRequestId(string $id, array &$content): void
    {
        $content['requestId'] = $id . '-01';
    }

    protected function setTruecallerRequestIdForPolling(string $id, array &$content): void
    {
        $content['request_id'] = $id . '-01';
    }

    protected function verifyTruecallerAuthRequest(array $content, bool $internal = false)
    {
        $request = [
            'url' => '/customers/truecaller/verify',
            'method' => 'post',
            'content' => $content,
        ];

        if ($internal) {
            $request['url'] = '/internal/customers/truecaller/verify';

            $this->ba->checkoutServiceProxyAuth();
        }

        return $this->makeRequestAndGetContent($request);
    }

    protected function verifyOneCcTruecallerAuthRequest(array $content, bool $internal = false)
    {
        $request = [
            'url' => '/1cc/customers/truecaller/verify',
            'method' => 'post',
            'content' => $content,
        ];

        if ($internal) {
            $request['url'] = '/internal/customers/truecaller/verify';

            $this->ba->checkoutServiceProxyAuth();
        }

        return $this->makeRequestAndGetContent($request);
    }

    protected function mockTruecallerResponse(array $response): void
    {
        $this->truecallerClientMock->shouldReceive('fetchUserProfile')
            ->andReturnUsing(static function ($accessToken, $endpoint, $requestId) use ($response) {
                return $response;
            })->once();
    }
}
