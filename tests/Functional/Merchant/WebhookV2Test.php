<?php

namespace RZP\Tests\Functional\Merchant;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\ServerErrorException;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Helpers\WebhookV2Trait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class WebhookV2Test extends TestCase
{
    use WebhookV2Trait;
    use RequestResponseFlowTrait;
    use MocksDnsTrait;

    // Used in webhook trait
    protected $storkMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookV2Data.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->ba->proxyAuth();

        $this->setupMockDns();

        $this->mockStorkService();
    }

    public function testCreateWebhookForOauth()
    {
        $this->addOAuthTag();

        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkCreatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkCreateResponseBodyForOauth());

        $expected  = $this->getExpectedArgsForRequestMethod($this->getStorkCreatePayloadForPrimary(), '10000000000App', 'application', 'api-test');
        $mockeryOn = $this->attachEmptyWKCtxMatcherToArgsMatcher($this->getArgsMatcherForWebhook($expected));

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkCreateResponseBodyForOauth()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Create', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testCreateWebhookForOauthFailure()
    {
        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkCreatePayloadForPrimary();
        $this->startTest();
    }

    public function testCreateWebhookForBanking()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkCreatePayloadForBanking();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkCreateResponseBodyForBanking());

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                if ($path === '/twirp/rzp.stork.webhook.v1.WebhookAPI/Create')
                {
                    $this->assertArraySelectiveEquals(['webhook' => $this->getStorkCreatePayloadForBanking()], $payload);
                    $this->assertEquals('rx-test', $payload['webhook']['service']);
                    $this->assertEquals('10000000000000', $payload['webhook']['owner_id']);
                    $this->assertEquals('merchant', $payload['webhook']['owner_type']);
                    $this->assertEquals(json_decode('{}'), $payload['webhook']['context']);

                    return $this->getStorkResponse(['webhook' => $this->getStorkCreateResponseBodyForBanking()]);
                }
                return new \Requests_Response();
            })->times(2);

        $this->startTest();
    }

    public function testCreateWebhookForBankingAlreadyExistsFailure()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkCreatePayloadForBanking();

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                if ($path === '/twirp/rzp.stork.webhook.v1.WebhookAPI/List')
                {
                    return $this->getStorkResponse(['webhooks' => $this->getStorkListResponseBody()]);
                }
                return new \Requests_Response();
            });
        $this->startTest();
    }


    public function testCreateWebhookForPrimary()
    {
        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkCreatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkCreateResponseBodyForPrimary());

        $expected  = $this->getExpectedArgsForRequestMethod($this->getStorkCreatePayloadForPrimary(), '10000000000000', 'merchant', 'api-test');
        $mockeryOn = $this->attachEmptyWKCtxMatcherToArgsMatcher($this->getArgsMatcherForWebhook($expected));

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkCreateResponseBodyForPrimary()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Create', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testCreateWebhookOverrideToImplictValues()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCreateWebhookForPrimary'];

        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkCreatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkCreateResponseBodyForPrimary());

        $this->testData[__FUNCTION__]['request']['content']['context']     = 'wrong_context';
        $this->testData[__FUNCTION__]['request']['content']['owner_id']    = 'wrong_owner_id';
        $this->testData[__FUNCTION__]['request']['content']['owner_type']  = 'wrong_owner_type';

        $expected  = $this->getExpectedArgsForRequestMethod($this->getStorkCreatePayloadForPrimary(), '10000000000000', 'merchant');
        $mockeryOn = $this->attachEmptyWKCtxMatcherToArgsMatcher($this->getArgsMatcherForWebhook($expected));

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkCreateResponseBodyForPrimary()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Create', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    //event is not valid for the product
    public function testCreateWebhookInvalidProductEventFailure()
    {
        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkCreatePayloadForBanking();

        $this->startTest();
    }

    public function testGetWebhookForHosted()
    {
        $this->addOAuthTag();
        $this->ba->hostedAuth();

        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkGetResponseBodyWithSecret());

        $expected  = $this->getStorkGetPayloadForPrimary();
        $mockeryOn = $this->getArgsMatcherForWebhook($expected);

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkGetResponseBodyWithSecret()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/GetWithSecret', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testGetWebhookForBanking()
    {
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkGetResponseBody());

        $expected  = $this->getStorkGetPayloadForBanking();
        $mockeryOn = $this->getArgsMatcherForWebhook($expected);

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkGetResponseBody()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Get', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testGetWebhookForPrimary()
    {
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkGetResponseBody());

        $expected  = $this->getStorkGetPayloadForPrimary();
        $mockeryOn = $this->getArgsMatcherForWebhook($expected);

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkGetResponseBody()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Get', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testListWebhookForHosted()
    {
        $this->addOAuthTag();
        $this->ba->hostedAuth();

        $this->testData[__FUNCTION__]['response']['content'] = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => array_map(function ($v) { return $this->convertAllToUnixTimestamp($v); }, $this->getStorkListResponseBodyWithSecret()),
        ];

        $expected  = $this->getStorkListPayloadForPrimary();
        $mockeryOn = $this->getArgsMatcherForWebhook($expected);

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhooks' => $this->getStorkListResponseBodyWithSecret()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/ListWithSecret', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testListWebhookForBanking()
    {
        $this->testData[__FUNCTION__]['response']['content'] = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => array_map(function ($v) { return $this->convertAllToUnixTimestamp($v); }, $this->getStorkListResponseBody()),
        ];

        $expected  = $this->getStorkListPayloadForBanking();
        $mockeryOn = $this->getArgsMatcherForWebhook($expected);

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhooks' => $this->getStorkListResponseBody()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/List', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testListWebhookForPrimary()
    {
        $this->testData[__FUNCTION__]['response']['content'] = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => array_map(function ($v) { return $this->convertAllToUnixTimestamp($v); }, $this->getStorkListResponseBody()),
        ];

        $expected  = $this->getStorkListPayloadForPrimary();
        $mockeryOn = $this->getArgsMatcherForWebhook($expected);

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhooks' => $this->getStorkListResponseBody()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/List', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testUpdateWebhookForBanking()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->testData[__FUNCTION__]['request']['content'] = $this->getStorkUpdatePayloadForBanking();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkUpdateResponseBodyForBanking());

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                if ($path === '/twirp/rzp.stork.webhook.v1.WebhookAPI/Update')
                {
                    $this->assertArraySelectiveEquals(['webhook' => $this->getStorkUpdatePayloadForBanking()], $payload);
                    $this->assertEquals('rx-test', $payload['webhook']['service']);
                    $this->assertEquals('10000000000000', $payload['webhook']['owner_id']);
                    $this->assertEquals('merchant', $payload['webhook']['owner_type']);
                    $this->assertEquals(json_decode('{}'), $payload['webhook']['context']);

                    return $this->getStorkResponse(['webhook' => $this->getStorkUpdateResponseBodyForBanking()]);
                }
                if ($path === '/twirp/rzp.stork.webhook.v1.WebhookAPI/Get')
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkUpdateResponseBodyForBanking()]);
                }

                return new \Requests_Response();

            })->times(2);

        $this->startTest();
    }

    public function testUpdateWebhookForBankingNotExistsFailure()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->testData[__FUNCTION__]['request']['content'] = $this->getStorkUpdatePayloadForBanking();

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                return new \Requests_Response();
            });
        $this->startTest();
    }

    public function testUpdateWebhookForPrimary()
    {
        $this->testData[__FUNCTION__]['request']['content'] = $this->getStorkUpdatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkUpdateResponseBodyForPrimary());

        $expected  = $this->getExpectedArgsForRequestMethod($this->getStorkUpdatePayloadForPrimary(), '10000000000000', 'merchant', 'api-test');
        $mockeryOn = $this->attachEmptyWKCtxMatcherToArgsMatcher($this->getArgsMatcherForWebhook($expected));

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkUpdateResponseBodyForPrimary()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Update', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testUpdateWebhookOverrideToImplictValues()
    {
        $this->testData[__FUNCTION__] = $this->testData['testUpdateWebhookForPrimary'];

        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkUpdatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getStorkUpdateResponseBodyForPrimary());

        $this->testData[__FUNCTION__]['request']['content']['context']     = 'wrong_context';
        $this->testData[__FUNCTION__]['request']['content']['owner_id']    = 'wrong_owner_id';
        $this->testData[__FUNCTION__]['request']['content']['owner_type']  = 'wrong_owner_type';

        $expected  = $this->getExpectedArgsForRequestMethod([], '10000000000000', 'merchant');
        $mockeryOn = $this->attachEmptyWKCtxMatcherToArgsMatcher($this->getArgsMatcherForWebhook($expected));

        $this->mockServiceStorkRequest(
                function ($path, $payload)
                {
                    return $this->getStorkResponse(['webhook' => $this->getStorkUpdateResponseBodyForPrimary()]);
                })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Update', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    //event is not valid for the product
    public function testUpdateWebhookInvalidProductEventFailure()
    {
        $this->testData[__FUNCTION__]['request']['content']  = $this->getStorkUpdatePayloadForBanking();

        $this->startTest();
    }

    protected function addOAuthTag(string $merchantId = '10000000000000')
    {
        $merchant = Merchant\Entity::find($merchantId);
        $merchant->reTag(["oauth"]);
        $merchant->saveOrFail();
    }

    /**
     * it uses assertArrayEquals which internally uses assertSame on keys. Hence if you are
     * using this method, make be aware of this if $expected contain an object. It's because
     * in case of objects assertSame makes sure that object has the same reference.
     */
    protected function getArgsMatcherForWebhook($expected)
    {
        return function(array $actual) use ($expected)
        {
            try
            {
                $this->assertArraySelectiveEquals($expected, $actual);
            }
            catch (ExpectationFailedException $e)
            {
                return false;
            }
            return true;
        };
    }

    // pass an args matcher to this method. This method returns a function which
    // asserts for empty context. And also calls the args matcher with the actual arguments.
    protected function attachEmptyWKCtxMatcherToArgsMatcher($argsMatcher)
    {
        return function(array $actual) use ($argsMatcher)
        {
            try
            {
                $this->assertEquals(json_decode('{}'), $actual['webhook']['context']);
            }
            catch (ExpectationFailedException $e)
            {
                return false;
            }
            return $argsMatcher($actual);
        };
    }

    // CREATE/UPDATE only sends the following information.
    // helper method to build the expected arguments for the stork service's request method. If a key is empty, it doesn't
    // add it in the payload. Hence flexibility to use/not use a key.
    protected function getExpectedArgsForRequestMethod($payload, $ownerId = '', $ownerType = '', $service = '')
    {
        $args = $payload;
        $ownerId   !== '' ? $args['owner_id']   = $ownerId   : null;
        $ownerType !== '' ? $args['owner_type'] = $ownerType : null;
        $service   !== '' ? $args['service']    = $service   : null;
        return ['webhook' => $args];
    }
}
