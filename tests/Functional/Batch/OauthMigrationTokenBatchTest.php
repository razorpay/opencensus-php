<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Batch\Header;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;

class OauthMigrationTokenBatchTest extends TestCase
{
    use OAuthTrait;
    use BatchTestTrait;

    protected $authServiceMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/OauthMigrationTokenBatchTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);
    }

    public function testCreateOauthMigrationBatch()
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $client->getId();

        $this->fixtures->create('user', ['id' => '10000000UserId']);

        $mappingData = [
            'user_id'     => '10000000UserId',
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $entries = $this->getOAuthMigrationBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $requestParams = [
            'client_id'           => $client->getId(),
            'user_id'             => '10000000UserId',
            'redirect_uri'        => 'http://localhost',
            'partner_merchant_id' => '10000000000000',
        ];

        $this->authServiceMock
            ->expects($this->exactly(2))
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
                ]);

        $this->startTest($testData);
    }

    public function testCreateOauthMigrationBatchInvalidInput()
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['client_id'] = $client->getId();

        $this->fixtures->create('user', ['id' => '10000000UserId']);

        $mappingData = [
            'user_id'     => '10000000UserId',
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $entries = $this->getOAuthMigrationBatchFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    protected function getOAuthMigrationBatchFileEntries(): array
    {
        $this->fixtures->create('merchant', ['id' => 'OAuthMerchant1']);

        $this->fixtures->create('merchant', ['id' => 'OAuthMerchant2']);

        return [
            [Header::MERCHANT_ID => 'OAuthMerchant1'],
            [Header::MERCHANT_ID => 'OAuthMerchant2'],
        ];
    }
}
