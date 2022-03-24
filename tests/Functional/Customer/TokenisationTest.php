<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;
use Carbon\Carbon;
use Requests_Response;
use RZP\Services\CardVault;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Services\Mock\DataLakePresto;
use RZP\Services\TerminalsService;
use RZP\Tests\Functional\Helpers\TerminalTrait;

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

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation());

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation('10000000000001'));

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('100000Razorpay', $timestamp));

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation());

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_mastercard'];

        extract($this->setUpDataForTokenisation());

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation());

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, 'wallet', $tokenId, $timestamp);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],0);
    }

    public function testBulkTokenisationWhenTokenIsRecurringAndBelongsToRupayOfValidMerchantExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        $gateways = ['tokenisation_rupay'];

        extract($this->setUpDataForTokenisation('10000000000000',null, 'RuPay'));

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId);

        $this->buildData('RuPay', $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, '100000007lcard', '411140', '10007cardToken', true);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rupay');

        $this->assertEquals($card['merchant_id'], $merchantId);

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testBulkTokenisationWhenCardIsRecurringAndBelongsToVisaExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation('10000000000000',null));

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, '100000007lcard', '411140', '10007cardToken', true);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');

        $this->assertEquals($card['merchant_id'], $merchantId);

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testBulkTokenisationWhenMultipleValidTokensOfValidMerchantExpectsTokenisationSuccessOnAllTokens(): void
    {
        $testData = $this->testData['testBulkTokenisation'];

        $this->ba->adminAuth();

        $gateways = ['tokenisation_visa', 'tokenisation_mastercard', 'tokenisation_rupay'];

        $this->mockTerminalServiceForMakeRequest($gateways);

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

            $this->assertEquals($card->getVault(), 'visa');

            $this->assertEquals($card->getMerchantId(), '10000000000099');
        }

        $this->assertEquals($response['inputTokenIdsCount'], 4);

        $this->assertEquals($response['triggeredTokenIdsCount'],4);
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

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation('10000000000000', null, 'Visa', 'visa'));

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $testData['request']['content']['token_ids'][] = $tokenId;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals($response['inputTokenIdsCount'], 1);

        $this->assertEquals($response['triggeredTokenIdsCount'],1);
    }

    public function testAsyncTokenisationWhenValidTokenBelongsToValidMerchantExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $gateways = ['tokenisation_visa'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_mastercard'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000', $timestamp));

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation());

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa'];

        extract($this->setUpDataForTokenisation());

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, 'wallet', $tokenId, $timestamp);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenTokenIsRecurringAndRupayExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $gateways = ['tokenisation_rupay'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp, 'RuPay'));

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId,true);

        $this->buildData('RuPay', $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, '100000007lcard', '411140', '10007cardToken', true);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rupay');

        $this->assertEquals($card['merchant_id'], $merchantId);
    }

    public function testAsyncTokenisationWhenTokenIsRecurringAndVisaExpectsTokenisationFailure(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $gateways = ['tokenisation_visa'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('10000000000000',$timestamp));

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId,true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp, 'IN', null, '100000007lcard', '411140', '10007cardToken', true);

        $this->mockDataLakeToReturnTokenIds();

        $response = $this->runRequestResponseFlow($testData);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testAsyncTokenisationWhenMultipleTokensBelongsToSingleMerchantExpectsTokenisationSuccess(): void
    {
        $testData = $this->testData['testAsyncTokenisation'];

        $this->ba->appAuth();

        $gateways = ['tokenisation_visa', 'tokenisation_mastercard', 'tokenisation_rupay'];

        $timestamp = Carbon::now()->getTimestamp();

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000099']);

        $merchantId = $merchant['id'];

        $this->fixtures->merchant->addFeatures(['async_tokenisation'], $merchantId);

        $this->mockDataLakeToReturnTokenIds();

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa', 'tokenisation_mastercard', 'tokenisation_rupay'];

        $timestamp = Carbon::now()->getTimestamp();

        $this->mockTerminalServiceForMakeRequest($gateways);

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

        $gateways = ['tokenisation_visa', 'tokenisation_mastercard', 'tokenisation_rupay'];

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

        $this->mockTerminalServiceForMakeRequest($gateways);

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
                                'token_reference_number' => $token,
                                'card_reference_number'  => strrev($token),
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

    protected function mockTerminalServiceForMakeRequest($gateways): void
    {
        $terminalsServiceMock = $this->getTerminalsServiceMock();

        $terminalsServiceMock->shouldReceive('makeRequest')
            ->andReturnUsing(function () use ($gateways) {
                $data = [];
                foreach ($gateways as $gateway) {
                    $data[] = ["gateway" => $gateway];
                }
                $response =  new Requests_Response;
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
        $recurring = false
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
                'recurring'       => $recurring
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

    public function testGlobalCardAsyncTokenisationSuccess(): void
    {
        $testData = $this->testData['testGlobalCardsAsyncTokenisation'];

        $this->ba->appAuth();

        $gateways = ['tokenisation_visa'];

        $timestamp = Carbon::now()->getTimestamp();

        extract($this->setUpDataForTokenisation('100000Razorpay', $timestamp));

        $this->mockTerminalServiceForMakeRequest($gateways);

        $this->prepareData($merchantId, true);

        $this->buildData($network, $merchantId, $vault, $methodTest, $tokenId, $timestamp);

        $response = $this->runRequestResponseFlow($testData);
    }
}
