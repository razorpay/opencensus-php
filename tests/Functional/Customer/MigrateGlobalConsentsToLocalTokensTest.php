<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Card\Network;
use RZP\Services\Mock\DataLakePresto;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;

class MigrateGlobalConsentsToLocalTokensTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TerminalTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MigrateGlobalConsentsToLocalTokensTestData.php';

        parent::setUp();
    }

    public function testBulkCreateLocalTokensFromConsentWhenValidTokenAndMerchantExpectsLocalTokenCreation()
    {
        $this->ba->batchAppAuth();

        $this->mockCardVaultWithCryptogram();

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140');
        $this->fixturesToCreateToken('100022xytoken2', '100000003card2', '411141');
        $this->fixturesToCreateToken('100022xytoken3', '100000003card3', '411142');

        $this->fixtures->merchant->create(['id' => '10000merchant1']);
        $this->fixtures->merchant->create(['id' => '10000merchant2']);
        $this->fixtures->merchant->create(['id' => '10000merchant3']);

        $response = $this->startTest();

        $this->assertEquals(true, $response['items'][0]['success']);
        $this->assertEquals(true, $response['items'][1]['success']);
        $this->assertEquals(true, $response['items'][2]['success']);

        $token1 = $this->getDbEntity('token', ['merchant_id' => '10000merchant1']);
        $token2 = $this->getDbEntity('token', ['merchant_id' => '10000merchant2']);
        $token3 = $this->getDbEntity('token', ['merchant_id' => '10000merchant3']);

        $this->assertEquals('10000gcustomer', $token1->getCustomerId());
        $this->assertEquals('10000merchant1', $token1->card->getMerchantId());

        $this->assertEquals('10000gcustomer', $token2->getCustomerId());
        $this->assertEquals('10000merchant2', $token2->card->getMerchantId());

        $this->assertEquals('10000gcustomer', $token3->getCustomerId());
        $this->assertEquals('10000merchant3', $token3->card->getMerchantId());
    }

    public function testBulkCreateLocalTokensFromConsentWhenValidTokenisedTokenAndMerchantExpectsLocalTokenCreation()
    {
        $this->ba->batchAppAuth();

        $this->mockCardVaultWithCryptogram();

        $payload = $this->testData['testBulkCreateLocalTokensFromConsent'];

        $this->fixtures->merchant->create(['id' => '10000merchant1']);

        $this->fixtureToCreateTokenisedToken(
            'HDFC',
            'visa',
            '100000Razorpay',
            '10000gcustomer',
            '100022xytoken1'
        );

        $response = $this->startTest($payload);

        $this->assertEquals(true, $response['items'][0]['success']);

        $token = $this->getLastEntity('token', true);
        $card = $this->getLastEntity('card', true);


        $this->assertEquals('card_' . $token['card_id'], $card['id']);
        $this->assertEquals('rzpvault', $card['vault']);
        $this->assertEquals('10000merchant1', $token['merchant_id']);
        $this->assertEquals('10000gcustomer', $token['customer_id']);
        $this->assertEquals('10000merchant1', $card['merchant_id']);
    }

    public function testBulkCreateLocalTokensFromConsentWhenDuplicateTokenExpectsLocalTokenCreationFailure()
    {
        $this->markTestSkipped("Current dedupe logic will not work, respective team will pick this up");

        $this->mockCardVaultWithCryptogram();

        $this->ba->batchAppAuth();

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '401200');

        $this->fixtures->merchant->create(['id' => '10000merchant1']);

        $response = $this->startTest();

        $this->assertEquals(true, $response['items'][0]['success']);
        $this->assertEquals(false, $response['items'][1]['success']);

        $token1 = $this->getDbEntity('token', ['merchant_id' => '10000merchant1']);

        $this->assertEquals('10000gcustomer', $token1->getCustomerId());
        $this->assertEquals('10000merchant1', $token1->card->getMerchantId());
    }

    public function testBulkCreateLocalTokensFromConsentWhenExpiredTokenExpectsLocalTokenCreationFailure()
    {
        $this->ba->batchAppAuth();

        $payload = $this->testData['testBulkCreateLocalTokensFromConsent'];

        $inputFields = ['expired_at' => '1639686868'];

        $this->fixturesToCreateToken(
            '100022xytoken1',
            '100000003card1',
            '411140',
            '100000Razorpay',
            '10000gcustomer',
            $inputFields
        );

        $this->fixtures->merchant->create(['id' => '10000merchant1']);

        $response = $this->startTest($payload);

        $this->assertEquals(false, $response['items'][0]['success']);
    }

    public function testBulkCreateLocalTokensFromConsentWhenInvalidTokenAndValidMerchantExpectsLocalTokenCreationFailure()
    {
        $this->ba->batchAppAuth();

        $this->fixtures->merchant->create(['id' => '10000merchant1']);

        $response = $this->startTest();

        $this->assertEquals(false, $response['items'][0]['success']);
    }

    public function testBulkCreateLocalTokensFromConsentWhenValidTokenAndInvalidMerchantExpectsLocalTokenCreationFailure()
    {
        $this->ba->batchAppAuth();

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140');

        $response = $this->startTest();

        $this->assertEquals(false, $response['items'][0]['success']);
    }

    public function testBulkCreateLocalTokensFromConsentWhenGlobalMerchantIsPassedAsInputExpectsLocalTokenCreationFailure()
    {
        $this->ba->batchAppAuth();

        $payload = $this->testData['testBulkCreateLocalTokensFromConsent'];
        $payload['request']['content'][0]['merchantId'] = '100000Razorpay';
        $payload['response']['content']['items'][0]['merchantId'] = '100000Razorpay';

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140');

        $response = $this->startTest($payload);

        $this->assertEquals(false, $response['items'][0]['success']);
    }

    public function testBulkCreateLocalTokensFromConsentWhenTokenCustomerIsNotGlobalExpectsLocalTokenCreationFailure()
    {
        $this->ba->batchAppAuth();

        $payload = $this->testData['testBulkCreateLocalTokensFromConsent'];

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '100000Razorpay', '100000customer');

        $this->fixtures->merchant->create(['id' => '10000merchant1']);

        $response = $this->startTest($payload);

        $this->assertEquals(false, $response['items'][0]['success']);
    }

    public function testBulkCreateLocalTokensFromConsentWhenConsentIsNotReceivedExpectsLocalTokenCreationFailure()
    {
        $this->ba->batchAppAuth();

        $payload = $this->testData['testBulkCreateLocalTokensFromConsent'];

        $inputFields = ['set_acknowledged_at_to_null' => true];

        $this->fixturesToCreateToken(
            '100022xytoken1',
            '100000003card1',
            '411140',
            '100000Razorpay',
            '10000gcustomer',
            $inputFields
        );

        $this->fixtures->merchant->create(['id' => '10000merchant1']);

        $response = $this->startTest($payload);

        $this->assertEquals(false, $response['items'][0]['success']);
    }

    public function testBulkCreateLocalTokensFromConsentWhenOneInvalidTokenExpectsLocalTokenCreationFailureOnInvalidToken()
    {
        $this->ba->batchAppAuth();

        //local token - new local token creation fails on this token
        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '100000customer');

        //global token - new local token creation is successful
        $this->fixturesToCreateToken('100022xytoken2', '100000003card2', '411141');
        $this->fixturesToCreateToken('100022xytoken3', '100000003card3', '411142');

        $this->fixtures->merchant->create(['id' => '10000merchant1']);
        $this->fixtures->merchant->create(['id' => '10000merchant2']);
        $this->fixtures->merchant->create(['id' => '10000merchant3']);

        $response = $this->startTest();

        $this->assertEquals(false, $response['items'][0]['success']);
        $this->assertEquals(true, $response['items'][1]['success']);
        $this->assertEquals(true, $response['items'][2]['success']);
    }

    public function testAsyncTokenisationOfGlobalCustomerLocalToken()
    {
        $this->ba->appAuth();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->mockFetchConsentReceivedGlobalCustomerLocalTokens([['id' => '100022xytoken1', 'merchant_id' => '10000000000000', 'network' => 'Visa']]);

        $this->mockCardVaultWithMigrateToken();

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '10000gcustomer');

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());

        $this->startTest();

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('visa', $card->getVault());
    }

    public function testAsyncTokenisationOfGlobalCustomerLocalTokenWhenTokenExpiredExpectsTokenisationFailure()
    {
        $this->ba->appAuth();

        $payload = $this->testData['testAsyncTokenisationOfGlobalCustomerLocalToken'];

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->mockFetchConsentReceivedGlobalCustomerLocalTokens([]);

        $this->mockCardVaultWithMigrateToken();

        $inputFields = ['expired_at' => '1639686868'];

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '10000gcustomer', $inputFields);

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());

        $this->startTest($payload);

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());
    }

    public function testAsyncTokenisationOfGlobalCustomerLocalTokenWhenNetworkNotInSupportedNetworksExpectsTokenisationFailure()
    {
        $this->ba->appAuth();

        $payload = $this->testData['testAsyncTokenisationOfGlobalCustomerLocalToken'];

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::MAES]);

        $this->mockFetchConsentReceivedGlobalCustomerLocalTokens([]);

        $this->mockCardVaultWithMigrateToken();

        $inputFields = ['network' => 'Maestro'];

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '10000gcustomer', $inputFields);

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());

        $this->startTest($payload);

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());
    }

    public function testAsyncTokenisationOfGlobalCustomerLocalTokenWhenInternationalCardExpectsTokenisationFailure()
    {
        $this->ba->appAuth();

        $payload = $this->testData['testAsyncTokenisationOfGlobalCustomerLocalToken'];

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->mockFetchConsentReceivedGlobalCustomerLocalTokens([]);

        $this->mockCardVaultWithMigrateToken();

        $inputFields = ['country' => 'US', 'international' => '1'];

        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '10000gcustomer', $inputFields);

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());

        $this->startTest($payload);

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());
    }

    public function testAsyncTokenisationOfGlobalCustomerLocalTokenWhenMerchantIsNotOnboardedOnNetworkExpectsTokenisationFailure()
    {
        $this->ba->appAuth();

        $payload = $this->testData['testAsyncTokenisationOfGlobalCustomerLocalToken'];

        $this->mockFetchMerchantTokenisationOnboardedNetworks([]);

        $this->mockFetchConsentReceivedGlobalCustomerLocalTokens([]);

        $this->mockCardVaultWithMigrateToken();
        $this->fixturesToCreateToken(
            '100022xytoken1',
            '100000003card1',
            '411140',
            '10000000000000'
        );

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());

        $this->startTest($payload);

        $token = $this->getDbEntityById('token', '100022xytoken1');

        $card = $token->card;

        $this->assertEquals('rzpvault', $card->getVault());
    }

    public function testAsyncTokenisationOfGlobalCustomerLocalTokenWhenNotGlobalCustomerLocalTokenExpectsTokenisationFailure()
    {
        $this->ba->appAuth();

        $payload = $this->testData['testAsyncTokenisationOfGlobalCustomerLocalToken'];

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->mockFetchConsentReceivedGlobalCustomerLocalTokens([]);

        $this->mockCardVaultWithMigrateToken();

        // local customer local token
        $this->fixturesToCreateToken('100022xytoken1', '100000003card1', '411140', '10000000000000', '100000customer');
        // local customer global token
        $this->fixturesToCreateToken('100022xytoken2', '100000004card1', '411141', '100000Razorpay', '100000customer');
        // global customer global token
        $this->fixturesToCreateToken('100022xytoken3', '100000005card1', '411142', '100000Razorpay', '10000gcustomer');

        $token1 = $this->getDbEntityById('token', '100022xytoken1');
        $token2 = $this->getDbEntityById('token', '100022xytoken2');
        $token3 = $this->getDbEntityById('token', '100022xytoken3');

        $card1 = $token1->card;
        $card2 = $token2->card;
        $card3 = $token3->card;

        $this->assertEquals('rzpvault', $card1->getVault());
        $this->assertEquals('rzpvault', $card2->getVault());
        $this->assertEquals('rzpvault', $card3->getVault());

        $this->startTest($payload);

        $token1 = $this->getDbEntityById('token', '100022xytoken1');
        $token2 = $this->getDbEntityById('token', '100022xytoken2');
        $token3 = $this->getDbEntityById('token', '100022xytoken3');

        $card1 = $token1->card;
        $card2 = $token2->card;
        $card3 = $token3->card;

        $this->assertEquals('rzpvault', $card1->getVault());
        $this->assertEquals('rzpvault', $card2->getVault());
        $this->assertEquals('rzpvault', $card3->getVault());
    }

    protected function mockCardVaultWithMigrateToken()
    {
        $app = App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock('RZP\Services\CardVault', [$app, 'mpan'])->makePartial();

        $this->app->instance('mpan.cardVault', $mpanVault);

        $callable = function ($route, $method, $input)
        {
            $response = [
                'error' => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token'] = base64_encode($input['secret']);
                    $response['fingerprint'] = strrev(base64_encode($input['secret']));
                    $response['scheme'] = '0';
                    break;

                case 'detokenize':
                    $response['value'] = base64_decode($input['token']);
                    break;

                case 'validate':
                    if ($input['token'] === 'fail')
                    {
                        $response['success'] = false;
                    }
                    break;

                case 'token/renewal' :
                    $response['expiry_time'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;

                case 'tokens/migrate':
                    $response['success'] = true;

                    $response['provider'] = strtolower($input['iin']['network']);

                    $token = base64_encode($input['card']['vault_token']);
                    $response['token']  = $token;

                    $response['fingerprint'] = strrev($token);
                    $response['last4'] = 1234;

                    $token_iin = 411111;

                    $expiry_year = $input['card']['expiry_year'];
                    if (strlen($expiry_year) == 2)
                    {
                        $expiry_year = '20' . $expiry_year;
                    }

                    $response['service_provider_tokens'] = [
                        [
                            'id'             => 'spt_1234abcd',
                            'entity'         => 'service_provider_token',
                            'provider_type'  => 'network',
                            'provider_name'  => $input['iin']['network'],
                            'status'         => 'created',
                            'interoperable'  => true,
                            'provider_data'  => [
                                'token_reference_number'     => $token,
                                'payment_account_reference'  => strrev($token),
                                'token_expiry_month'     => $input['card']['expiry_month'],
                                'token_expiry_year'      => $expiry_year,
                                'token_iin'              => $token_iin,
                                'token_number'           => 411111,
                            ],
                        ]
                    ];
                    break;

                case 'delete':
                    break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $mpanVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', null)
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }

    protected function fixturesToCreateToken(
        $tokenId,
        $cardId,
        $iin,
        $merchantId = '100000Razorpay',
        $customerId = '10000gcustomer',
        $inputFields = []
    )
    {
        if ($iin !== '401200')
        {
            $this->fixtures->iin->create(
                [
                    'iin'     => $iin,
                    'country' => $inputFields['country'] ?? 'IN',
                    'issuer'  => 'HDFC',
                    'network' => $inputFields['network'] ?? 'Visa',
                    'flows'   => [
                        '3ds'          => '1',
                        'headless_otp' => '1',
                    ]
                ]
            );
        }

        $this->fixtures->card->create(
            [
                'id'            => $cardId,
                'merchant_id'   => $merchantId,
                'name'          => 'test',
                'iin'           => $iin,
                'expiry_month'  => '12',
                'expiry_year'   => '2100',
                'issuer'        => 'HDFC',
                'network'       => $inputFields['network'] ?? 'Visa',
                'last4'         => '3335',
                'type'          => 'debit',
                'vault'         => 'rzpvault',
                'vault_token'   => 'NDAxMjAwMTAzODQ0MzMzNQ==',
                'international' => $inputFields['international'] ?? null,
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'customer_id'     => $customerId,
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => isset($inputFields['set_acknowledged_at_to_null']) ? null :  Carbon::now()->getTimestamp(),
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
            ]
        );
    }

    protected function fixtureToCreateTokenisedToken(
        $issuer,
        $network,
        $merchantId,
        $customerId,
        $tokenId
    )
    {
        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'HDFC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ],
        ]);

        $this->fixtures->card->create(
            [
                'id'           => '100000003lcard',
                'merchant_id'  => $merchantId,
                'name'         => 'test',
                'iin'          => '401200',
                'expiry_month' => '12',
                'expiry_year'  => '2100',
                'issuer'       => $issuer,
                'network'      => $network,
                'last4'        => '1111',
                'type'         => 'credit',
                'vault'        => 'visa',
                'vault_token'  => 'NDAxMjAwMTAzODQ0MzMzNQ==',
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'token'           => '10003cardToken',
                'customer_id'     => $customerId,
                'method'          => 'card',
                'card_id'         => '100000003lcard',
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
            ]
        );
    }

    public function mockFetchConsentReceivedGlobalCustomerLocalTokens(array $tokenIds)
    {
        $prestoService = \Mockery::mock(DataLakePresto::class, [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('datalake.presto', $prestoService);

        $prestoService->shouldReceive('getDataFromDataLake')
            ->andReturnUsing(function (string $query) use ($tokenIds) {
                return $tokenIds;
            });
    }
}
