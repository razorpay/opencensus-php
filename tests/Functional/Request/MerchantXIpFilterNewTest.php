<?php

namespace RZP\Tests\Functional\Request;

//use Redis;
use Request;
//use Mockery;
use Exception;
use ApiResponse;
use RZP\Models\Settings;
use Razorpay\OAuth\Client;
use RZP\Models\Pricing\Fee;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class MerchantXIpFilterNewTest extends TestCase
{
    use OAuthTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use TestsBusinessBanking;

    protected $mid;
    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantXIpFilterNewTestData.php';

        parent::setUp();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->setUpMerchantForBusinessBankingLive(false, 10000);
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $this->merchantId = '10000000000000';

        $this->fixtures->on('live')->create('balance',
            [
                'id' => 'testBalance000',
                'merchant_id' => '10000000000000',
                'type' => 'primary',
                'balance' => 100000,
                'account_number' => '2224440041626905',
                'currency' => 'INR',
            ]);
        $this->balanceId = 'testBalance000';
    }

    protected function createContactEntityArray($params = []) // DO NOT Modify
    {
        $contact = [
            'id' => 'cont1000000000',
            'name' => 'Test Testing',
            'email' => 'test@razorpay.com',
            'contact' => '987654321',
            'type' => 'self',
            'active' => 1,
        ];
        $contact = array_merge($contact, (array) $params);
        return $contact;
    }

    protected function createBankAccountEntityArray($params = []) // DO NOT Modify
    {
        $bankAccount = [
            'id' => 'bnk10000000000',
            'beneficiary_name' => 'Test Tester',
            'entity_id' => 'cont1000000000',
            'type' => 'contact',
            'ifsc_code' => 'SBIN0007105',
            'account_number' => '111000',
            'merchant_id' => $this->merchantId,
        ];
        $bankAccount = array_merge($bankAccount, (array) $params);
        return $bankAccount;
    }

    // Create Contact and Bank Account before calling this
    protected function createBankingFundAccountEntityArray($params = []) // DO NOT Modify
    {
        $fundAccount = [
            'id' => 'fa100000000000',
            'merchant_id' => $this->merchantId,
            'source_type' => 'contact',
            'source_id' => 'cont1000000000',
            'account_type' => 'bank_account',
            'account_id' => 'bnk10000000000',
            'active' => 1,
        ];
        $fundAccount = array_merge($fundAccount, (array) $params);
        return $fundAccount;
    }

    protected function createPayoutEntityArray($params = []) // DO NOT Modify
    {
        $payout = [
            'id' => '10000000000001',
            'merchant_id' => $this->merchantId,
            'fund_account_id' => 'fa100000000000',
            'balance_id' => $this->balanceId,
            'amount' => 100,
            'mode' => 'UPI',
            'currency' => 'INR',
            'purpose' => 'test',
        ];
        $payout = array_merge($payout, (array) $params);
        return $payout;
    }

    protected function resetRedisKeysForIpWhitelist($isReset = true)
    {
        if($isReset === true)
        {
            $redisKey = 'ip_config_10000000000000_api_payouts';
            $redisKey2 = 'ip_config_10000000000000_api_fund_account_validation';

            $this->app['redis']->del($redisKey);
            $this->app['redis']->del($redisKey2);
        }
    }


   //Tests if merchant is enabled on feature and not opted out and has no ip whitelisted, then request should fail.
    public function testPayoutCreateGetsErrorForNoWhitelistedIps()
    {
        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());

        $this->startTest();

        $this->resetRedisKeysForIpWhitelist();
    }

    //Tests if merchant is enabled on feature and not opted out and has no ip whitelisted, then request should fail.
//    public function testPayoutCreateGetsErrorForNoWhitelistedIpsWhenRedisDown()
//    {
//        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);
//
//        $this->mockRedisResponse();
//
//        $data = $this->testData['testPayoutCreateGetsErrorForNoWhitelistedIps'];
//
//        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
//        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
//        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());
//
//        $this->startTest($data);
//    }

    //Tests if merchant is enabled on feature and not opted out and has a set of ips whitelisted, but request IP is different then should fail.
    public function testPayoutCreateGetsErrorForNonWhitelistedIp()
    {
        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);

        $ipList = ['1.1.1.1', '2.2.2.2'];

        $redisKey = 'ip_config_10000000000000_api_payouts';

        $redisKey2 = 'ip_config_10000000000000_api_fund_account_validation';

        $this->app['redis']->sadd($redisKey, $ipList);

        $this->app['redis']->sadd($redisKey2, $ipList);

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());

        $this->startTest();

        $this->resetRedisKeysForIpWhitelist();
    }

    //Tests if merchant is enabled on feature and not opted out and has a set of ips whitelisted, but request IP is different then should fail.
//    public function testPayoutCreateGetsErrorForNonWhitelistedIpWhenRedisDown()
//    {
//        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);
//
//        $this->mockRedisResponse();
//
//        $ipList = ['1.1.1.1', '2.2.2.2'];
//
//        $data = $this->testData['testPayoutCreateGetsErrorForNonWhitelistedIp'];
//
//        $merchant = $this->getDbEntityById('merchant', 10000000000000,true);
//
//        $accessor = Settings\Accessor::for($merchant, Settings\Module::IP_WHITELIST_CONFIG,'live');
//
//        $accessor->upsert('api_payouts', json_encode($ipList))->save();
//
//        $accessor->upsert('api_fund_account_validation', json_encode($ipList))->save();
//
//        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
//        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
//        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());
//
//        $this->startTest($data);
//    }

    //Tests if merchant is enabled on feature and not opted out and request IP is one of the whitelisted ones, request should succeed
    public function testPayoutCreateGetsExpectedResponseForWhitelistedIps()
    {
        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);

        $ipList = ['10.0.123.123', '2.2.2.2'];

        $redisKey = 'ip_config_10000000000000_api_payouts';

        $redisKey2 = 'ip_config_10000000000000_api_fund_account_validation';

        $this->app['redis']->sadd($redisKey, $ipList);

        $this->app['redis']->sadd($redisKey2, $ipList);

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());

        $this->startTest();

        $this->resetRedisKeysForIpWhitelist();
    }

    //Tests if merchant is enabled on feature and not opted out and request IP is one of the whitelisted ones, request should succeed
//    public function testPayoutCreateGetsExpectedResponseForWhitelistedIpsWhenRedisDown()
//    {
//        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);
//
//        $ipList = ['10.0.123.123', '2.2.2.2'];
//
//        //$this->mockRedisResponse();
//
//        $data = $this->testData['testPayoutCreateGetsExpectedResponseForWhitelistedIps'];
//
//        $merchant = $this->getDbEntityById('merchant', 10000000000000,true);
//
//        $accessor = Settings\Accessor::for($merchant, Settings\Module::IP_WHITELIST_CONFIG,'live');
//
//        $accessor->upsert('api_payouts', json_encode($ipList))->save();
//
//        $accessor->upsert('api_fund_account_validation', json_encode($ipList))->save();
//
//        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
//        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
//        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());
//
//        $this->startTest($data);
//    }

    //Tests if merchant is not enabled on feature, then old flow should apply and no new whitelist should apply.
    public function testExpectedResponseForMerchantNotEnabledOnFeature()
    {
        $ipList = ['10.0.0.122', '2.2.2.2'];

        $redisKey = 'ip_config_10000000000000_api_payouts';

        $redisKey2 = 'ip_config_10000000000000_api_fund_account_validation';

        $this->app['redis']->sadd($redisKey, $ipList);

        $this->app['redis']->sadd($redisKey2, $ipList);

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());

        $this->startTest();

        $this->resetRedisKeysForIpWhitelist();
    }

    //Tests if merchant has opted out then whitelist should not apply and request should go fine.
    public function testExpectedResponseForMerchantOptedOut()
    {
        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);

        $ipList = ['*'];

        $redisKey = 'ip_config_10000000000000_api_payouts';

        $redisKey2 = 'ip_config_10000000000000_api_fund_account_validation';

        $this->app['redis']->sadd($redisKey, $ipList);

        $this->app['redis']->sadd($redisKey2, $ipList);

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());

        $this->startTest();

        $this->resetRedisKeysForIpWhitelist();
    }

//    protected function mockRedisResponse()
//    {
//        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['smembers'])
//                          ->getMock();
//
//        Redis::shouldReceive('connection')
//               ->andReturn($redisMock);
//
//        $redisMock->method('smembers')
//                  ->will($this->throwException(new Exception('failed to getv value from redis')));
//
//    }

    public function testExpectedResponseForMerchantFeatureEnabledButServiceMappingNotFound()
    {
        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist']);

        $ipList = ['10.0.0.122', '2.2.2.2'];

        $redisKey = 'ip_config_10000000000000_api_payouts';

        $redisKey2 = 'ip_config_10000000000000_api_fund_account_validation';

        $this->app['redis']->sadd($redisKey, $ipList);

        $this->app['redis']->sadd($redisKey2, $ipList);

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());

        $this->startTest();

        $this->resetRedisKeysForIpWhitelist();
    }

    // Test that, for a request from valid Partner Oauth with `rx_partner_read_write` scope, whitelisting is skipped for non whitelisted IP
    public function testForFundAccountFetchCallByPartnerOauthForNonWhitelistedIp()
    {
        $this->fixtures->on('live')->merchant->addFeatures(['enable_ip_whitelist', 'enable_approval_via_oauth']);

        $ipList = ['1.1.1.1', '2.2.2.2'];

        $redisKey = 'ip_config_10000000000000_api_payouts';

        $redisKey2 = 'ip_config_10000000000000_api_fund_account_validation';

        $this->app['redis']->sadd($redisKey, $ipList);

        $this->app['redis']->sadd($redisKey2, $ipList);

        $this->fixtures->on('live')->create('contact', $this->createContactEntityArray());
        $this->fixtures->on('live')->create('bank_account', $this->createBankAccountEntityArray());
        $this->fixtures->on('live')->create('fund_account', $this->createBankingFundAccountEntityArray());

        $client = Client\Entity::factory()->create(['environment' => 'prod']);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['rx_partner_read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();

        $this->resetRedisKeysForIpWhitelist();
    }
}
