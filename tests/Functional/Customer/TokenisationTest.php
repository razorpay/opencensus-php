<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;
use Carbon\Carbon;
use \WpOrg\Requests\Response;
use RZP\Constants\Entity;
use RZP\Models\Card\Network;
use RZP\Models\Card\Vault;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Merchant\Account;
use RZP\Services\CardVault;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Services\Mock\DataLakePresto;
use RZP\Services\TerminalsService;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Feature;

class TokenisationTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use TerminalTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TokenTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testBulkTokenisationWhenValidTokenAndBelongsToGivenMerchantExpectTokenisationSuccess(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        extract($this->setUpDataForTokenisation());

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'visa');

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testBulkTokenisationWhenValidTokenAndBelongsToDifferentMerchantExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        extract($this->setUpDataForTokenisation('10000000000001'));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, '100025custcard', $timestamp);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],0);
    }

    public function testBulkTokenisationWhenValidTokenAndBelongsToGlobalMerchantExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('100000Razorpay', $timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId,$timestamp);

        $testData['request']['content']['merchant_id'] = '100000Razorpay';

        $testData['request']['content']['token_ids'][] = $tokenId;

        $obj = $this;

        $this->makeRequestAndCatchException(function () use ($testData, $obj) {
            $obj->runRequestResponseFlow($testData);
        }, BadRequestValidationFailureException::class, 'The selected merchant id is invalid.');
    }

    public function testBulkTokenisationWhenValidTokenAndBelongsToInternationalCardExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        extract($this->setUpDataForTokenisation());

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'US', '1');

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testBulkTokenisationWhenValidTokenAndBelongsToMerchantNotOnboardedOnNetworkExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        extract($this->setUpDataForTokenisation());

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::MC]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'US');

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testBulkTokenisationWhenTokenHasMethodWhichIsNotCardBelongsToValidMerchantExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        extract($this->setUpDataForTokenisation());

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, 'wallet', $tokenId, $timestamp);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],0);
    }

    public function testBulkTokenisationWhenMultipleValidTokensOfValidMerchantExpectsTokenisationSuccessOnAllTokens(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC, Network::RUPAY]);

        $tokenNames = ['10008cardToken', '10009cardToken', '10010cardToken', '10011cardToken'];

        $tokenIds = ['100021custcard', '100023custcard', '100024custcard', '100026custcard'];

        $cardIds = ['100000011lcard', '100000013lcard', '100000014lcard', '100000015lcard'];

        $iinIds = ['411140', '411141', '411142', '411143'];

        $testData['request']['content']['merchant_id'] = '10000000000099';

        $testData['response']['content']['merchantId'] = '10000000000099';

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000099']);

        $merchantId = $merchant['id'];

        $this->fixtures->merchant->addFeatures(['async_tokenisation'], $merchantId);

        $tokenIdsCount = count($tokenIds);
        $cardIdsCount = count($cardIds);

        for ($i = 0; $i < $tokenIdsCount; $i++)
        {
            extract($this->setUpDataForTokenisation('10000000000099', null, 'Visa', 'rzpvault', $tokenIds[$i]));

            $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, $cardIds[$i], $iinIds[$i], $tokenNames[$i]);
        }

        $tokenTokens = [];

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);
            $tokenTokens[] = $token->getToken();
        }

        $testData['request']['content']['token_ids'] = $tokenTokens;

        $response = $this->runRequestResponseFlow($testData);

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);

            $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

            $this->assertEquals($card->getVault(), 'visa');

            $this->assertEquals($card->getMerchantId(), '10000000000099');
        }

        $this->assertEquals($response['inputTokenIdsCount'], 4);

        $this->assertEquals($response['triggeredTokenIdsCount'],4);
    }

    public function testBulkTokenisationWhenMultipleValidTokenIdsOfValidMerchantExpectsTokenisationSuccessOnAllTokens(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC, Network::RUPAY]);

        $tokenNames = ['10008cardToken', '10009cardToken', '10010cardToken', '10011cardToken'];

        $tokenIds = ['100021custcard', '100023custcard', '100024custcard', '100026custcard'];

        $cardIds = ['100000011lcard', '100000013lcard', '100000014lcard', '100000015lcard'];

        $iinIds = ['411140', '411141', '411142', '411143'];

        $testData['request']['content']['merchant_id'] = '10000000000099';

        $testData['request']['content']['token_ids'] = $tokenIds;

        $testData['response']['content']['merchantId'] = '10000000000099';

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000099']);

        $merchantId = $merchant['id'];

        $this->fixtures->merchant->addFeatures(['async_tokenisation'], $merchantId);

        $tokenIdsCount = count($tokenIds);
        $cardIdsCount = count($cardIds);

        for ($i = 0; $i < $tokenIdsCount; $i++)
        {
            extract($this->setUpDataForTokenisation('10000000000099', null, 'Visa', 'rzpvault', $tokenIds[$i]));

            $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, $cardIds[$i], $iinIds[$i], $tokenNames[$i]);
        }

        $response = $this->runRequestResponseFlow($testData);

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);

            $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

            $this->assertEquals('visa', $card->getVault());

            $this->assertEquals('10000000000099', $card->getMerchantId());
        }

        $this->assertEquals(4, $response['inputTokenIdsCount']);

        $this->assertEquals(4, $response['triggeredTokenIdsCount']);
    }

    public function testBulkTokenisationWhenTokenIsAlreadyTokenisedExpectsTokenisationFailure(): void
    {
        $terminalService = \Mockery::mock(TerminalsService::class, [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('terminals_service', $terminalService);

        $terminalService->shouldReceive('fetchMerchantTokenisationOnboardedNetworks')
            ->times(0)
            ->andReturnUsing(function ()
            {
                return ['VISA', 'MC', 'RUPAY'];
            });

        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        extract($this->setUpDataForTokenisation('10000000000000', null, 'Visa', 'visa'));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testBulkTokenisationWhenTokenExpiredFailure(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        extract($this->setUpDataForTokenisation());

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN',
            null, '100000007lcard', 411140, '10007cardToken', false, '1639686868');

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testAsyncTokenisationWhenValidTokenBelongsToValidMerchantExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'visa');

        $this->assertEquals($card['merchant_id'], $merchantId);
    }

    public function testAsyncTokenisationWhenValidTokenFetchOnboardedNetworksFromCacheExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000', $timestamp));

        $cacheKey = $merchantId . '_tokenisation_onboarded_networks';

        $this->app['cache']->put($cacheKey, json_encode(['RUPAY','MC','VISA']));

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'visa');

        $this->assertEquals($card['merchant_id'], $merchantId);
    }

    public function testAsyncTokenisationWhenFeatureNotEnabledOnMerchantExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId,false);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenMerchantNotOnboardedOnRequiredNetworkExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::MC]);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenTokenBelongsToInternationalCardExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000', $timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'US', 1);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenTokenConsentIsNotReceivedExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        extract($this->setUpDataForTokenisation());

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenTokenMethodIsNotCardExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        extract($this->setUpDataForTokenisation());

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, 'wallet', $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenTokenExpiredFailure(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000', $timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN',
            null, '100000007lcard', 411140, '10007cardToken', false, '1639686868');

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenMultipleTokensBelongsToSingleMerchantExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000099']);

        $merchantId = $merchant['id'];

        $this->fixtures->merchant->addFeatures(['async_tokenisation'], $merchantId);

        $this->mockDataLakeToReturnTokenIds();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC, Network::RUPAY]);

        $tokenNames = ['10008cardToken', '10009cardToken', '10010cardToken', '10011cardToken'];

        $tokenIds = ['100021custcard', '100023custcard', '100024custcard', '100026custcard'];

        $cardIds = ['100000011lcard', '100000013lcard', '100000014lcard', '100000015lcard'];

        $iinIds = ['411140', '411141', '411142', '411143'];

        $tokenIdsCount = count($tokenIds);
        $cardIdsCount = count($cardIds);

        for ($i = 0; $i < $tokenIdsCount; $i++)
        {
            extract($this->setUpDataForTokenisation('10000000000099', $timestamp, 'Visa', 'rzpvault', $tokenIds[$i]));

            $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, $cardIds[$i], $iinIds[$i], $tokenNames[$i]);
        }

        $response = $this->runRequestResponseFlow($testData);

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);

            $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

            $this->assertEquals($card->getVault(), 'visa');

            $this->assertEquals($card->getMerchantId(), '10000000000099');
        }
    }

    public function testAsyncTokenisationWhenMultipleTokensBelongsToMultipleMerchantExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC, Network::RUPAY]);

        $tokenNames = ['10008cardToken', '10009cardToken', '10010cardToken', '10011cardToken'];

        $tokenIds = ['100021custcard', '100023custcard', '100024custcard', '100026custcard'];

        $cardIds = ['100000011lcard', '100000013lcard', '100000014lcard', '100000015lcard'];

        $iinIds = ['411141', '411142', '411143', '411144'];

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000099']);

        $merchantId = $merchant['id'];

        $this->fixtures->merchant->addFeatures(['async_tokenisation'], $merchantId);

        $this->mockDataLakeToReturnTokenIds();

        $tokenIdsCount = count($tokenIds);
        $cardIdsCount = count($cardIds);

        for ($i = 0; $i < $tokenIdsCount; $i++)
        {
            extract($this->setUpDataForTokenisation('10000000000099', $timestamp, 'Visa', 'rzpvault', $tokenIds[$i]));

            $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, $cardIds[$i], $iinIds[$i], $tokenNames[$i]);
        }

        extract($this->setUpDataForTokenisation('10000000000100', $timestamp, 'MasterCard', 'rzpvault', '100030custcard'));

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $response = $this->runRequestResponseFlow($testData);

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);

            $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

            $this->assertEquals($card->getVault(), 'visa');

            $this->assertEquals($card->getMerchantId(), '10000000000099');
        }

        $token = $this->getDbEntityById('token', '100030custcard');

        $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

        $this->assertEquals($card->getVault(), 'mastercard');

        $this->assertEquals($card->getMerchantId(), '10000000000100');
    }

    public function testAsyncTokenisationWhenMultipleTokensOfDifferentNetworksExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        $networkVsVault = [
            'Visa'       => 'visa',
            'MasterCard' => 'mastercard',
            'RuPay'      => 'rupay',
            'Discover'   => 'rzpvault'
        ];

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000099']);

        $merchantId = $merchant['id'];

        $this->fixtures->merchant->addFeatures(['async_tokenisation'], $merchantId);

        $this->mockDataLakeToReturnTokenIds();

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC, Network::RUPAY]);

        $tokenNames = ['10008cardToken', '10009cardToken', '10010cardToken', '10011cardToken'];

        $networks = ['Visa', 'RuPay', 'MasterCard', 'Discover'];

        $tokenIds = ['100021custcard', '100023custcard', '100024custcard', '100026custcard'];

        $cardIds = ['100000011lcard', '100000013lcard', '100000014lcard', '100000015lcard'];

        $iinIds = ['411140', '411141', '411142', '411143'];

        $tokenIdsCount = count($tokenIds);
        $cardIdsCount = count($cardIds);

        for ($i = 0; $i < $tokenIdsCount; $i++)
        {
            extract($this->setUpDataForTokenisation('10000000000099', $timestamp, $networks[$i], 'rzpvault', $tokenIds[$i]));

            $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, $cardIds[$i], $iinIds[$i], $tokenNames[$i]);
        }

        $response = $this->runRequestResponseFlow($testData);

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);

            $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

            $this->assertEquals($card->getVault(), $networkVsVault[$networks[$i]]);

            $this->assertEquals($card->getMerchantId(), '10000000000099');
        }
    }

    public function testAsyncTokenisationWhenTokenIsAlreadyTokenisedExpectsTokenisationFailure(): void
    {
        $terminalService = \Mockery::mock(TerminalsService::class, [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('terminals_service', $terminalService);

        $terminalService->shouldReceive('fetchMerchantTokenisationOnboardedNetworks')
            ->times(1)
            ->andReturnUsing(function ()
            {
                return ['VISA', 'MC', 'RUPAY'];
            });

        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000', $timestamp, 'Visa', 'visa'));

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGlobalCardAsyncTokenisationPassingBatchSizeSuccess(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $testData['request']['content']['batch_size'] = '10';

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation(Account::SHARED_ACCOUNT, $timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', 0);

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'visa');

        $this->assertEquals($card['merchant_id'], $merchantId);
    }

    public function testGlobalCardAsyncTokenisationWhenValidTokenFetchOnboardedNetworksFromCacheExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation(Account::SHARED_ACCOUNT, $timestamp));

        $cacheKey = $merchantId . '_tokenisation_onboarded_networks';

        $this->app['cache']->put($cacheKey, json_encode(['MC','VISA']));

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'visa');

        $this->assertEquals($card['merchant_id'], $merchantId);
    }

    public function testGlobalCardAsyncTokenisationWhenBatchSizeGreaterThan10000ValidationFailure(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisationValidationFailure'];

        $this->ba->appAuth();

        $response = $this->runRequestResponseFlow($testData);
    }

    public function testGlobalCardAsyncTokenisationWhenMerchantNotOnboardedOnRequiredNetworkExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation(Account::SHARED_ACCOUNT, $timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::MC]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testGlobalCardAsyncTokenisationWhenTokenBelongsToInternationalCardExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation(Account::SHARED_ACCOUNT, $timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'US', 1);

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testGlobalCardAsyncTokenisationWhenTokenConsentIsNotReceivedExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        extract($this->setUpDataForTokenisation(Account::SHARED_ACCOUNT));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testGlobalCardAsyncTokenisationWhenTokenMethodIsNotCardExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        extract($this->setUpDataForTokenisation(Account::SHARED_ACCOUNT));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, 'wallet', $tokenId, $timestamp);

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testGlobalCardAsyncTokenisationWhenTokenIsRecurringAndRupayExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract(
            $this->setUpDataForTokenisation(
                Account::SHARED_ACCOUNT,
                $timestamp,
                Network::getFullName(Network::RUPAY)
            )
        );

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::RUPAY]);

        $this->prepareData($merchantId);

        $this->buildData('RuPay', $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, '100000007lcard', '411140', '10007cardToken', true);

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testGlobalTokenisationWhenTokenExpiredFailure(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        extract($this->setUpDataForTokenisation(Account::SHARED_ACCOUNT));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN',
            null, '100000007lcard', 411140, '10007cardToken', false, '1639686868');

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testGlobalCardAsyncTokenisationForMultipleTokensExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        $this->prepareData('100000Razorpay');

        $merchantId = '100000Razorpay';

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC]);

        $tokenNames = ['10008cardToken', '10009cardToken', '10010cardToken', '10011cardToken'];

        $tokenIds = ['100021custcard', '100023custcard', '100024custcard', '100026custcard'];

        $cardIds = ['100000011lcard', '100000013lcard', '100000014lcard', '100000015lcard'];

        $iinIds = ['411140', '411141', '411142', '411143'];

        $tokenIdsCount = count($tokenIds);

        $cardIdsCount = count($cardIds);

        for ($i = 0; $i < $tokenIdsCount; $i++)
        {
            extract(
                $this->setUpDataForTokenisation(
                    Account::SHARED_ACCOUNT,
                    $timestamp,
                    Network::getFullName(Network::VISA),
                    Vault::RZP_VAULT,
                    $tokenIds[$i]
                )
            );

            $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, $cardIds[$i], $iinIds[$i], $tokenNames[$i]);
        }

        $response = $this->runRequestResponseFlow($testData);

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);

            $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

            $this->assertEquals($card->getVault(), 'visa');

            $this->assertEquals($card->getMerchantId(), '100000Razorpay');
        }
    }

    public function testGlobalCardAsyncTokenisationWhenMultipleTokensOfDifferentNetworksExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        $networkVsVault = [
            'Visa'       => 'visa',
            'MasterCard' => 'mastercard',
            'RuPay'      => 'rzpvault',
            'Discover'   => 'rzpvault'
        ];

        $this->prepareData('100000Razorpay');

        $merchantId = '100000Razorpay';

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC]);

        $tokenNames = ['10008cardToken', '10009cardToken', '10010cardToken', '10011cardToken'];

        $networks = ['Visa', 'RuPay', 'MasterCard', 'Discover'];

        $tokenIds = ['100021custcard', '100023custcard', '100024custcard', '100026custcard'];

        $cardIds = ['100000011lcard', '100000013lcard', '100000014lcard', '100000015lcard'];

        $iinIds = ['411140', '411141', '411142', '411143'];

        $tokenIdsCount = count($tokenIds);

        $cardIdsCount = count($cardIds);

        for ($i = 0; $i < $tokenIdsCount; $i++)
        {
            extract(
                $this->setUpDataForTokenisation(
                    Account::SHARED_ACCOUNT,
                    $timestamp,
                    $networks[$i],
                    Vault::RZP_VAULT,
                    $tokenIds[$i]
                )
            );

            $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, $cardIds[$i], $iinIds[$i], $tokenNames[$i]);
        }

        $response = $this->runRequestResponseFlow($testData);

        for ($i = 0; $i < $cardIdsCount; $i++)
        {
            $token = $this->getDbEntityById('token', $tokenIds[$i]);

            $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

            $this->assertEquals($card->getVault(), $networkVsVault[$networks[$i]]);

            $this->assertEquals($card->getMerchantId(), '100000Razorpay');
        }
    }

    public function testGlobalCardAsyncTokenisationWhenTokenIsAlreadyTokenisedExpectsTokenisationFailure(): void
    {
        $terminalService = \Mockery::mock(TerminalsService::class, [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('terminals_service', $terminalService);

        $terminalService->shouldReceive('fetchMerchantTokenisationOnboardedNetworks')
            ->times(1)
            ->andReturnUsing(function ()
            {
                return ['VISA', 'MC'];
            });

        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract(
            $this->setUpDataForTokenisation(
                Account::SHARED_ACCOUNT,
                $timestamp,
                Network::getFullName(Network::VISA),
                Constants::VISA
            )
        );

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $response = $this->runRequestResponseFlow($testData);
    }

    protected function mockCardVaultWithMigrateToken(): void
    {
        $app = App::getFacadeRoot();

        $cardVault = Mockery::mock(CardVault::class, [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock(CardVault::class, [$app, 'mpan'])->makePartial();

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
                    $response['value'] = '4012001038443335';
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
                    $response['providerReferenceId'] = "12345678910123";
                    $response['fingerprint'] = strrev($token);
                    $response['last4'] = 1234;

                    $token_iin = 411111;

                    $expiry_year = $input['card']['expiry_year'];
                    if (strlen($expiry_year) === 2)
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

                case 'tokens/cryptogram':
                    $response['service_provider_tokens'] = [
                        [
                            'type'  => 'network',
                            'name'  => 'Visa',
                            'provider_data'  => [
                                'token_number' => '4044649165235890',
                                'cryptogram_value' => 'test',
                                'token_expiry_month' => 12,
                                'token_expiry_year' => 2024,
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

    protected function mockTerminalServiceForMakeRequest($gateways): void
    {
        $terminalsServiceMock = $this->getTerminalsServiceMock();

        $terminalsServiceMock->shouldReceive('makeRequest')
            ->andReturnUsing(function () use ($gateways) {
                $data = [];
                foreach ($gateways as $gateway) {
                    $data[] = ["gateway" => $gateway];
                }
                $response =  new \WpOrg\Requests\Response;
                $responseData = ['data' => $data];
                $response->body = json_encode($responseData);
                $response->status_code = 200;
                return $response;
            });
    }

    protected function prepareData($merchantId, $activateFeature = false): void
    {
        if (($merchantId !== '10000000000000') and
            ($merchantId !== '100000Razorpay'))
        {
            $merchant = $this->fixtures->create('merchant', ['id' => $merchantId]);

            $merchantId = $merchant['id'];
        }

        if($activateFeature === true)
        {
            $this->fixtures->merchant->addFeatures(['async_tokenisation'], $merchantId);
        }
    }

    protected function mockDataLakeToReturnTokenIds(): void
    {
        $prestoService = \Mockery::mock(DataLakePresto::class, [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('datalake.presto', $prestoService);

        $prestoService->shouldReceive('getDataFromDataLake')
            ->andReturnUsing(function (string $query)
            {
                $position = strpos($query,"merchant_id");

                $merchantId = substr($query,$position+15,14);

                if ($merchantId === '10000000000000')
                {
                    return [
                        [
                            'id' => '100022custcard',
                        ],
                    ];
                }
                elseif ($merchantId === '10000000000100')
                {
                    return [
                        [
                            'id' => '100030custcard',
                        ],
                    ];
                }
                else
                {
                    return [
                        [
                            'id' => '100021custcard',
                        ],
                        [
                            'id' => '100023custcard',
                        ],
                        [
                            'id' => '100024custcard',
                        ],
                        [
                            'id' => '100026custcard',
                        ],
                    ];
                }
            });
    }

    protected function buildData(
        $network,
        $merchantId,
        $vault,
        $methodTest,
        $tokenId,
        $timestamp,
        $country = 'IN',
        $international = null,
        $cardId = '100000007lcard',
        $iinId = '411140',
        $tokenName = '10007cardToken',
        $recurring = false,
        $expiredAt = '9999999999'
    ): void
    {
        $iin_test = $this->fixtures->iin->create(
            [
                'iin'     => $iinId,
                'country' => $country,
                'issuer'  => 'HDFC',
                'network' => $network,
                'flows'   => [
                    '3ds' => '1',
                    'headless_otp'  => '1',
                ]
            ]
        );

        $card_before_test = $this->fixtures->card->create(
            [
                'id'            => $cardId,
                'merchant_id'   => $merchantId,
                'name'          => 'test',
                'iin'           => $iin_test['iin'],
                'country'       => $country,
                'expiry_month'  => '12',
                'expiry_year'   => '2100',
                'issuer'        => 'HDFC',
                'network'       => $network,
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => $vault,
                'vault_token'   => 'test_token',
                'international' => $international,
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'token'           => $tokenName,
                'customer_id'     => '10000gcustomer',
                'method'          => $methodTest,
                'card_id'         => $card_before_test['id'],
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => $timestamp,
                'recurring'       => $recurring,
                'expired_at'      => $expiredAt
            ]
        );
    }

    protected function setUpDataForTokenisation(
        $merchantId = '10000000000000',
        $timestamp = null,
        $network = 'Visa',
        $vault = 'rzpvault',
        $tokenId = '100022custcard'
    ): array
    {
        $this->mockCardVaultWithMigrateToken();

        $methodTest = 'card';

        return [
            'methodTest' => $methodTest,
            'tokenId'    => $tokenId,
            'timestamp'  => $timestamp,
            'merchantId' => $merchantId,
            'network'    => $network,
            'vault'      => $vault,
        ];
    }

    protected function getToken($payment)
    {
        return $payment->localToken ?? $payment->globalToken;
    }

    protected function doFirstPaymentThroughTokenisingTheCard($isLocal = false, $network = 'visa')
    {
        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::AMEX]);

        $payment = $this->getDefaultPaymentArray();
        if($network == "american express") {
            $payment['card']['cvv'] = '1234';
        }
        $payment['_']['library'] = 'razorpayjs';
        $payment['save'] = 1;
        if ($isLocal === true)
        {
            $payment['customer_id'] = 'cust_100000customer';
        }

        $response = $this->doAuthPayment($payment);

        $payment = $this->getDbEntityById('payment', $response['razorpay_payment_id']);

        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals("passed", $payment['two_factor_auth']);

        $token = $this->getToken($payment);

        $tokenCard = $token->card;

        $paymentCard = $payment->card;

        $this->assertNotNull($payment['token_id']);

        $this->assertEquals('401200', $tokenCard['iin']);
        $this->assertEquals($token['card_id'], $tokenCard['id']);
        $this->assertEquals($tokenCard['vault'], $network);
        $this->assertEquals('credit', $tokenCard['type']);
        $this->assertNull($paymentCard['trivia']);

        return $isLocal ? $token['id'] : $token['token'];
    }

    /**
     * Exp2 - for global and local saved card traffic based on combination of (issuer,network,card type),
     * three list are maintained -
     * 1.Blacklist - (100% will go through actual card)
     * 2.Ramp up list - x% traffic will go through tokenised card
     * 3.Whitelist - everything else will go through tokenised card
     *
     */
    public function testIsRepeatPaymentProcessedWithTokenisedCardOnLocalMerchantWhenExp2ReturnsTrue()
    {
        $this->mockCardVaultWithMigrateToken();

        $this->mockRazorXTreatment('on');

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid']);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $tokenId = $this->doFirstPaymentThroughTokenisingTheCard(true);

        $payment = $this->getDefaultTokenIdPaymentArray($tokenId);

        $payment['customer_id'] = 'cust_100000customer';

        $paymentResponse = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $paymentResponse['razorpay_payment_id']);

        $paymentCard = $payment2->card;

        $token = $this->getToken($payment2);

        $tokenCard = $token->card;

        $this->assertEquals($tokenId, $token['id']);
        $this->assertEquals('visa', $tokenCard['vault']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals("passed", $payment2['two_factor_auth']);
        $this->assertNotNull($payment2['token_id']);
        $this->assertEquals(1, $paymentCard['trivia']);

        //assert token card

        $this->assertEquals('2024', $tokenCard['expiry_year']);
        $this->assertEquals('12', $tokenCard['expiry_month']);
        $this->assertEquals('3335', $tokenCard['last4']);
        $this->assertEquals('2024', $paymentCard['token_expiry_year']);
        $this->assertEquals('12', $tokenCard['token_expiry_month']);
        $this->assertEquals('5890', $paymentCard['token_last4']);

        //replace below lines to token_expiry_year and token_expiry_month ,once they are populated
        $this->assertEquals('9999', $paymentCard['expiry_year']);
        $this->assertEquals('0', $paymentCard['expiry_month']);
        $this->assertEquals('3335', $paymentCard['last4']);
        $this->assertEquals('2024', $paymentCard['token_expiry_year']);
        $this->assertEquals('12', $paymentCard['token_expiry_month']);
        $this->assertEquals('5890', $paymentCard['token_last4']);
        $this->assertEquals('credit', $paymentCard['type']);
        $this->assertEquals('404464916', $paymentCard['token_iin']);
        $this->assertEquals('400782', $paymentCard['iin']);
        $this->assertEquals($paymentCard['vault'], 'rzpvault');
    }

    public function testIsRepeatPaymentProcessedWithTokenisedCardOnLocalMerchantWhenExp2ReturnsTrueAmex()
    {
        $this->mockCardVaultWithMigrateToken();

        $this->mockRazorXTreatment('on');

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid']);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'American Express',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $this->fixtures->edit('iin', 401200, [
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'American Express',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $tokenId = $this->doFirstPaymentThroughTokenisingTheCard(true, 'american express');

        $payment = $this->getDefaultTokenIdPaymentArray($tokenId);

        $payment['card']['cvv'] = '1234';

        $payment['customer_id'] = 'cust_100000customer';

        $paymentResponse = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $paymentResponse['razorpay_payment_id']);

        $paymentCard = $payment2->card;

        $token = $this->getToken($payment2);

        $tokenCard = $token->card;

        $this->assertEquals($tokenId, $token['id']);
        $this->assertEquals('american express', $tokenCard['vault']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals("passed", $payment2['two_factor_auth']);
        $this->assertNotNull($payment2['token_id']);
        $this->assertEquals(1, $paymentCard['trivia']);

        //assert token card

        $this->assertEquals('2024', $tokenCard['expiry_year']);
        $this->assertEquals('12', $tokenCard['expiry_month']);
        $this->assertEquals('3335', $tokenCard['last4']);
        $this->assertEquals('2024', $paymentCard['token_expiry_year']);
        $this->assertEquals('12', $tokenCard['token_expiry_month']);
        $this->assertEquals('5890', $paymentCard['token_last4']);

        //replace below lines to token_expiry_year and token_expiry_month ,once they are populated
        $this->assertEquals('9999', $paymentCard['expiry_year']);
        $this->assertEquals('0', $paymentCard['expiry_month']);
        $this->assertEquals('3335', $paymentCard['last4']);
        $this->assertEquals('2024', $paymentCard['token_expiry_year']);
        $this->assertEquals('12', $paymentCard['token_expiry_month']);
        $this->assertEquals('5890', $paymentCard['token_last4']);
        $this->assertEquals('credit', $paymentCard['type']);
        $this->assertEquals('404464916', $paymentCard['token_iin']);
        $this->assertEquals('400782', $paymentCard['iin']);
        $this->assertEquals($paymentCard['vault'], 'rzpvault');
    }

    /**
     * Exp1 - for global saved card traffic whose token merchant_id is 100000Razorpay
     * only y% of traffic will go through tokenised card
     *
     * Exp2 - for global and local saved card traffic based on combination of (issuer,network,card type)
     */
    public function testIsRepeatPaymentProcessedWithTokenisedCardOnGlobalMerchantWhenExp1AndExp2ReturnsTrue()
    {
        $this->mockSession();

        $this->mockCardVaultWithMigrateToken();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->mockRazorXTreatment('on');

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '100000Razorpay');
        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '10000000000000');

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $tokenToken = $this->doFirstPaymentThroughTokenisingTheCard(false);

        $payment = $this->getDefaultTokenIdPaymentArray($tokenToken);

        $payment[Payment::TOKEN] = $tokenToken;

        $paymentResponse = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $paymentResponse['razorpay_payment_id']);

        $paymentCard = $payment2->card;

        $token = $this->getToken($payment2);

        $tokenCard = $token->card;

        $this->assertEquals($tokenToken, $token['token']);
        $this->assertEquals('visa', $tokenCard['vault']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals("passed", $payment2['two_factor_auth']);
        $this->assertNotNull($payment2['token_id']);
        $this->assertEquals(1, $paymentCard['trivia']);

        //replace below lines to token_expiry_year and token_expiry_month ,once they are populated
        $this->assertEquals('9999', $paymentCard['expiry_year']); // we can 't get the expiry details  for token pans since we are not storing it
        $this->assertEquals('0', $paymentCard['expiry_month']);
        $this->assertEquals('404464916', $paymentCard['token_iin']);
        $this->assertEquals('400782', $paymentCard['iin']);
        $this->assertEquals('credit', $paymentCard['type']);
        $this->assertEquals($paymentCard['vault'], 'rzpvault');
    }

    /**
     * Exp2 - for global and local saved card traffic based on combination of (issuer,network,card type)
     *
     */
    public function testIsRepeatPaymentProcessedWithActualCardOnLocalMerchantWhenExp2ReturnsFalse()
    {
        $this->markTestSkipped();

        $this->mockCardVaultWithMigrateToken();

        $this->mockRazorXTreatment('off');

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid']);

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $tokenId = $this->doFirstPaymentThroughTokenisingTheCard(true);

        $payment = $this->getDefaultTokenIdPaymentArray($tokenId);

        $payment['customer_id'] = 'cust_100000customer';

        $paymentResponse = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $paymentResponse['razorpay_payment_id']);

        $paymentCard = $payment2->card;

        $token = $this->getToken($payment2);

        $tokenCard = $token->card;

        $this->assertEquals($tokenId, $token['id']);
        $this->assertEquals('visa', $tokenCard['vault']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals("passed", $payment2['two_factor_auth']);
        $this->assertNotNull($payment2['token_id']);
        $this->assertNull($paymentCard['trivia']);
        $this->assertEquals('2024', $paymentCard['expiry_year']);
        $this->assertEquals('12', $paymentCard['expiry_month']);
        $this->assertEquals('3335', $paymentCard['last4']);
        $this->assertNull($paymentCard['token_expiry_year']);
        $this->assertNull($paymentCard['token_expiry_month']);
        $this->assertNull($paymentCard['token_last4']);
        $this->assertEquals('credit', $paymentCard['type']);
        $this->assertEquals('401200', $paymentCard['iin']);
        $this->assertEquals($paymentCard['vault'], 'rzpvault');
    }

    /**
     * Exp1 - for global saved card traffic whose token merchant_id is 100000Razorpay
     * only y% of traffic will go through tokenised card
     *
     * Exp2 - for global and local saved card traffic based on combination of (issuer,network,card type)
     */
    public function testIsRepeatPaymentProcessedWithActualCardOnGlobalMerchantWhenExp1ReturnFalseExp2ReturnsTrue()
    {
        $this->mockSession();

        $output = [];

        $this->mockSplitzTreatment($output);

        $this->mockRazorXTreatment('on');

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '100000Razorpay');
        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '10000000000000');

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $tokenToken = $this->doFirstPaymentThroughTokenisingTheCard(false);

        $payment = $this->getDefaultTokenIdPaymentArray($tokenToken);

        $payment[Payment::TOKEN] = $tokenToken;

        $paymentResponse = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $paymentResponse['razorpay_payment_id']);

        $paymentCard = $payment2->card;

        $token = $this->getToken($payment2);

        $tokenCard = $token->card;

        $this->assertEquals($tokenToken, $token['token']);
        $this->assertEquals('visa', $tokenCard['vault']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals("passed", $payment2['two_factor_auth']);
        $this->assertNotNull($payment2['token_id']);

        $this->assertEquals('9999', $paymentCard['expiry_year']); // we cant get the expiry of card for token pans payments since we are not storing it
        $this->assertEquals('0', $paymentCard['expiry_month']);
        $this->assertEquals('credit', $paymentCard['type']);

        //$this->assertNull($paymentCard['trivia']);
        //$this->assertEquals('401200', $paymentCard['iin']);
        /**
         * Payment goes through tokenised card since it is dual vault token
         * and exp1 is only for global tokens
         */
        $this->assertEquals(1, $paymentCard['trivia']);
        $this->assertEquals('400782', $paymentCard['iin']);

        $this->assertEquals($paymentCard['vault'], 'rzpvault');
    }

    /**
     * Exp1 - for global saved card traffic whose token merchant_id is 100000Razorpay
     * only y% of traffic will go through tokenised card
     *
     * Exp2 - for global and local saved card traffic based on combination of (issuer,network,card type)
     */
    public function testIsRepeatPaymentProcessedWithActualCardOnGlobalMerchantWhenExp1ReturnFalseExp2ReturnsFalse()
    {
        $this->markTestSkipped();

        $this->mockSession();

        $output = [];

        $this->mockSplitzTreatment($output);

        $this->mockRazorXTreatment('off');

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '100000Razorpay');
        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '10000000000000');

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $tokenToken = $this->doFirstPaymentThroughTokenisingTheCard(false);

        $payment = $this->getDefaultTokenIdPaymentArray($tokenToken);

        $payment[Payment::TOKEN] = $tokenToken;

        $paymentResponse = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $paymentResponse['razorpay_payment_id']);

        $paymentCard = $payment2->card;

        $token = $this->getToken($payment2);

        $tokenCard = $token->card;

        $this->assertEquals($tokenToken, $token['token']);
        $this->assertEquals('visa', $tokenCard['vault']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals("passed", $payment2['two_factor_auth']);
        $this->assertNotNull($payment2['token_id']);
        $this->assertNull($paymentCard['trivia']);
        $this->assertEquals('2024', $paymentCard['expiry_year']);
        $this->assertEquals('12', $paymentCard['expiry_month']);
        $this->assertEquals('credit', $paymentCard['type']);
        $this->assertNull($paymentCard['token_iin']);
        $this->assertEquals('401200', $paymentCard['iin']);
        $this->assertEquals($paymentCard['vault'], 'rzpvault');
    }

    /**
     * Exp1 - for global saved card traffic whose token merchant_id is 100000Razorpay
     * only y% of traffic will go through tokenised card
     *
     * Exp2 - for global and local saved card traffic based on combination of (issuer,network,card type)
     */
    public function testIsRepeatPaymentProcessedWithActualCardOnGlobalMerchantWhenExp1ReturnTrueExp2ReturnsFalse()
    {
        $this->markTestSkipped();

        $this->mockSession();

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variant_on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $this->mockRazorXTreatment('off');

        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '100000Razorpay');
        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid'], '10000000000000');

        $this->fixtures->iin->create([
            'iin'     => '400782',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'type'    => 'credit',
            'flows'   => [
                '3ds' => '1',
                'ivr' => '1',
                'otp' => '1',
            ]
        ]);

        $tokenToken = $this->doFirstPaymentThroughTokenisingTheCard(false);

        $payment = $this->getDefaultTokenIdPaymentArray($tokenToken);

        $payment[Payment::TOKEN] = $tokenToken;

        $paymentResponse = $this->doAuthPayment($payment);

        $payment2 = $this->getDbEntityById('payment', $paymentResponse['razorpay_payment_id']);

        $paymentCard = $payment2->card;

        $token = $this->getToken($payment2);

        $tokenCard = $token->card;

        $this->assertEquals($tokenToken, $token['token']);
        $this->assertEquals('visa', $tokenCard['vault']);
        $this->assertEquals('authorized', $payment2['status']);
        $this->assertEquals("passed", $payment2['two_factor_auth']);
        $this->assertNotNull($payment2['token_id']);
        $this->assertNull($paymentCard['trivia']);
        $this->assertEquals('2024', $paymentCard['expiry_year']);
        $this->assertEquals('12', $paymentCard['expiry_month']);
        $this->assertEquals('credit', $paymentCard['type']);
        $this->assertNull($paymentCard['token_iin']);
        $this->assertEquals('401200', $paymentCard['iin']);
        $this->assertEquals($paymentCard['vault'], 'rzpvault');
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = ['test_app_token' => $appToken];

        $this->session($data);
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    protected function mockRazorXTreatment($value = 'on'): void
    {
        $this->ba->proxyAuth();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturnCallback(static function ($mid, $feature, $mode) use ($value) {
                if ($feature === 'card_payments_authorize_all_terminals') {
                    return 'off';
                }

                if ($feature === RazorxTreatment::STORE_EMPTY_VALUE_FOR_NON_EXEMPTED_CARD_METADATA) {
                    return 'off';
                }

                return 'on';
            });
    }

    protected function mockCardVaultService()
    {
        $cardService = \Mockery::mock('RZP\Services\CardPaymentService')->makePartial();

        $this->app->instance('card.payments', $cardService);

        $cardService->shouldReceive('sendRequest')
            ->with('POST', Mockery::type('string'), Mockery::type('array'))
            ->andReturnUsing(function (string $method, string $url, array $input)
            {
                $input = $input['input'];

                switch ($url)
                {
                    case 'action/authorize':

                        $payment = $input['payment'];

                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);

                        $content = [
                            'Message' => [
                                'PAReq' => [
                                    'Merchant' => [
                                        'acqBIN' => '11111111111',
                                        'merID'  => '12AB,cd/34-EF  -g,5/H-67'
                                    ],
                                    'CH' => [
                                        'acctID' => 'NTU2NzYzMDAwMDAwMjAwNA==',
                                    ],
                                    'Purchase' => [
                                        'xid'    => base64_encode(str_pad($payment['id'], 20, '0', STR_PAD_LEFT)),
                                        'date'    => \Carbon\Carbon::createFromTimestamp($payment['created_at'], 'Asia/Kolkata')->format('Ymd H:m:s'),
                                        'amount' => '500.00',
                                        'purchAmount' => '50000',
                                        'currency' => '356',
                                        'exponent' => 2,
                                    ]
                                ]
                            ],
                        ];

                        $content['Message']['@attributes']['id'] = $payment['id'];

                        $xml = \Lib\Formatters\Xml::create('ThreeDSecure', $content);

                        $xml = zlib_encode($xml, 15);
                        $xml = base64_encode($xml);

                        return [
                            'data' => [
                                'content' => [
                                    'TermUrl' => $input['callbackUrl'],
                                    'PaReq' => $xml,
                                    'MD' => $payment['id'],
                                ],
                                'method' => 'post',
                                'url' =>  'https://api.razorpay.com/v1/gateway/acs/mpi_blade',
                            ],
                            'payment' => [
                                'terminal_id' => $terminal->getId(),
                                'auth_type' => null,
                                'authentication_gateway' => 'mpi_blade'
                            ],
                        ];
                    case 'action/callback':
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
                        return [
                            'data' => [
                                'acquirer' => [
                                    'reference2' => 'test'
                                ],
                                'two_factor_auth' => 'Y'
                            ],
                            'payment' => [
                                'auth_type' => "3ds",
                            ],
                        ];

                    case 'action/capture':
                        return [
                            'data' => [
                                'status' => 'captured',
                            ],
                        ];

                    case 'action/pay':
                        $this->assertEquals('test', $input['card']['cryptogram_value']);
                        $this->assertTrue($input['card']['tokenised']);
                        $this->assertEquals('PayU', $input['card']['token_provider']);
                        return [
                            'data' => [
                                'acquirer' => [
                                    'reference2' => 'test'
                                ],
                                'two_factor_auth' => 'Y'
                            ],
                            'payment' => [
                                'auth_type' => "3ds",
                            ],
                        ];

                    default:
                        return null;
                }
            });
    }

    protected function getDefaultTokenIdPaymentArray($tokenId)
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566'
        );

        $payment[Payment::TOKEN] = 'token_' . $tokenId;

        return $payment;
    }

    public function testAsyncTokenisationWhenValidTokenBelongsToValidMerchantExpectsTokenisationSuccessForRecurring(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $output = [];

        $this->mockSplitzTreatment($output);

        $this->mockRazorXTreatment('on');

        $this->ba->appAuth();

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA]);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $this->fixtures->token->edit($tokenId,
                [   'recurring_status'     => 'confirmed',
                    'recurring'            => 1
                ]);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($card['vault'], 'visa');

        $this->assertEquals($token['recurring'], true);

        $this->assertEquals($card['merchant_id'], $merchantId);
    }
}
