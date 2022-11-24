<?php

namespace RZP\Tests\Functional\Batch;

use Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Batch\Header;
use RZP\Jobs\Batch as BatchJob;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Mail\OAuth\AppAuthorized as OAuthAppAuthorizedMail;

class OauthMigrationTokenBatchTest extends TestCase
{
    use OAuthTrait;
    use BatchTestTrait;

    protected $authServiceMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/OauthMigrationTokenBatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();



        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);
    }

    public function testProcessOauthMigrationBatch()
    {
        Mail::fake();

        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $client->getId();

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '10000000UserId']);

        $entries = $this->getOAuthMigrationBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $returns = $this->getSuccessfulReturn();

        $this->setUpAuthServiceMock($client->getId(), $returns);

        $this->startTest($testData);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['processed_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals('processed', $batch['status']);

        Mail::assertNotQueued(OAuthAppAuthorizedMail::class);
    }

    public function testProcessOauthMigrationBatchWithTokenFailures()
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $client->getId();

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '10000000UserId']);

        $entries = $this->getOAuthMigrationBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $returns = $this->getSuccessfulReturn();

        $returns[1] = $returns[2] = [];

        $this->setUpAuthServiceMock($client->getId(), $returns);

        $this->startTest($testData);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['processed_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(2, $batch['failure_count']);
        $this->assertEquals('processed', $batch['status']);
    }

    public function testCreateOauthMigrationBatchInvalidInput()
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $client->getId();

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '10000000UserId']);

        $entries = $this->getOAuthMigrationBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testProcessOauthMigrationBatchInvalidInput()
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $client->getId();

        $this->fixtures->user->createUserForMerchant('10000000000000', ['id' => '10000000UserId']);

        $entries = $this->getOAuthMigrationBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest($testData);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['processed_count']);
        $this->assertEquals(0, $batch['success_count']);
        $this->assertEquals(3, $batch['failure_count']);
        $this->assertEquals('processed', $batch['status']);
    }

    protected function setUpAuthServiceMock(string $clientId, array $returns = [])
    {
        $requestParams = [
            'client_id'           => $clientId,
            'user_id'             => '10000000UserId',
            'redirect_uri'        => 'http://localhost',
            'partner_merchant_id' => '10000000000000',
        ];

        $this->authServiceMock
             ->expects($this->exactly(3))
             ->method('sendRequest')
             ->withConsecutive(
                [
                    'tokens/internal',
                    'POST',
                    array_merge($requestParams, ['merchant_id' => 'OAuthMerchant1'])
                ],
                [
                    'tokens/internal',
                    'POST',
                    array_merge($requestParams, ['merchant_id' => 'OAuthMerchant2'])
                ],
                [
                    'tokens/internal',
                    'POST',
                    array_merge($requestParams, ['merchant_id' => 'OAuthMerchant3'])
                ])
             ->willReturnOnConsecutiveCalls($returns[0], $returns[1], $returns[2]);
    }

    protected function getOAuthMigrationBatchFileEntries(): array
    {
        $this->fixtures->create('merchant', ['id' => 'OAuthMerchant1']);

        $this->fixtures->create('merchant', ['id' => 'OAuthMerchant2']);

        $this->fixtures->create('merchant', ['id' => 'OAuthMerchant3']);

        return [
            [Header::MERCHANT_ID => 'OAuthMerchant1'],
            [Header::MERCHANT_ID => 'OAuthMerchant2'],
            [Header::MERCHANT_ID => 'OAuthMerchant3'],
        ];
    }

    /**
     * This is the expected response if all is well with the
     * given input in the data file. Should be overridden for failure
     * cases as required.
     *
     * @return array
     */
    protected function getSuccessfulReturn(): array
    {
        return $this->testData['successfulReturn'];
    }
}
