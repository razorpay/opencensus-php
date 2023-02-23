<?php

namespace Functional\Customer;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Customer\Truecaller\AuthRequest\Service;
use Mockery;
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

    public function testVerifyTruecallerRequestForSucessResponse(): void
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

    protected function verifyTruecallerAuthRequest(array $content)
    {
        $request = array(
            'url' => '/customers/truecaller/verify',
            'method' => 'post',
            'content' => $content,
        );

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
