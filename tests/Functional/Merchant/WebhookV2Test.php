<?php

namespace RZP\Tests\Functional\Merchant;
use Mail;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\ServerErrorException;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Tests\Functional\Helpers\WebhookV2Trait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class WebhookV2Test extends TestCase
{
    use WebhookV2Trait;
    use RequestResponseFlowTrait;
    use MocksDnsTrait;
    use OAuthTrait;
    use TestsBusinessBanking;

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

        // this is required so that traffic to webhook APIs can be routed to
        // the v2 path.
        $this->mockRazorxToReturnOn();
    }

    public function testCreateWebhookForPartner()
    {
        $this->assignMerchantAsPartnerAggregator();

        $this->addMerchantApplicationMapping();

        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->addCreatedUpdatedByEmail($this->convertAllToUnixTimestamp($this->getApiCreateResponseBodyForOauth()));

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

    public function testCreateWebhookForPartnerMerchantNoAppAccessFailure()
    {
        $this->assignMerchantAsPartnerAggregator();

        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForPrimary();
        $this->startTest();
    }

    public function testCreateWebhookForOauth()
    {
        $this->addOAuthTag();
        $this->addMerchantApplicationMapping();

        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->addCreatedUpdatedByEmail($this->convertAllToUnixTimestamp($this->getApiCreateResponseBodyForOauth()));

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
        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForPrimary();
        $this->startTest();
    }

    public function testCreateWebhookForBanking()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForBanking();
        $this->testData[__FUNCTION__]['response']['content'] = $this->addCreatedUpdatedByEmail($this->convertAllToUnixTimestamp($this->getApiCreateResponseBodyForBanking()));

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
            })->times(3);

        $this->startTest();
    }

    public function testCreateWebhookForBankingAlreadyExistsFailure()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForBanking();

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
        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getApiCreateResponseBodyForPrimary());

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

        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getApiCreateResponseBodyForPrimary());

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
        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiCreatePayloadForBanking();

        $this->startTest();
    }

    public function testGetWebhookForHosted()
    {
        $this->addOAuthTag();
        $this->ba->hostedAuth();

        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getApiGetResponseBodyWithSecret());

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
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getApiGetResponseBody());

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
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getApiGetResponseBody());

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
            'items'  => array_map(function ($v) { return $this->convertAllToUnixTimestamp($v); }, $this->getApiListResponseBodyWithSecret()),
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
            'items'  => array_map(function ($v) { return $this->convertAllToUnixTimestamp($v); }, $this->getApiListResponseBody()),
        ];

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

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
            'items'  => array_map(function ($v) { return $this->convertAllToUnixTimestamp($v); }, $this->getApiListResponseBody()),
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

    public function testListWebhookForPartner()
    {
        $this->assignMerchantAsPartnerAggregator();
        $this->addMerchantApplicationMapping();

        $this->testData[__FUNCTION__]['response']['content'] = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => array_map(function ($v) { return $this->convertAllToUnixTimestamp($v); }, $this->getApiListResponseBodyForApplication()),
        ];

        $expected  = $this->getStorkListPayloadForApplication();
        $mockeryOn = $this->getArgsMatcherForWebhook($expected);

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                return $this->getStorkResponse(['webhooks' => $this->getStorkListResponseBodyForApplication()]);
            })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/List', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testListWebhookForPartnerMerchantNotPartnerFailure()
    {
        $this->startTest();
    }

    public function testListWebhookForPartnerMerchantNoAppAccessFailure()
    {
        $this->assignMerchantAsPartnerAggregator();
        $this->startTest();
    }

    public function testUpdateWebhookForBanking()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->testData[__FUNCTION__]['request']['content'] = $this->getApiUpdatePayloadForBanking();
        $this->testData[__FUNCTION__]['response']['content'] = $this->addCreatedUpdatedByEmail($this->convertAllToUnixTimestamp($this->getApiUpdateResponseBodyForBanking()));

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

            })->times(3);

        $this->startTest();
    }

    public function testUpdateWebhookForBankingNotExistsFailure()
    {
        $this->fixtures->merchant->addFeatures(['payout']);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->testData[__FUNCTION__]['request']['content'] = $this->getApiUpdatePayloadForBanking();

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                return new \Requests_Response();
            });
        $this->startTest();
    }

    public function testUpdateWebhookForPrimary()
    {
        $this->testData[__FUNCTION__]['request']['content'] = $this->getApiUpdatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->addCreatedUpdatedByEmail($this->convertAllToUnixTimestamp($this->getApiUpdateResponseBodyForPrimary()));

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

    public function testUpdateWebhookForOauth()
    {
        $this->addOAuthTag();
        $this->addMerchantApplicationMapping();

        $this->testData[__FUNCTION__]['request']['content'] = $this->getApiUpdatePayloadForOauth();
        $this->testData[__FUNCTION__]['response']['content'] = $this->addCreatedUpdatedByEmail($this->convertAllToUnixTimestamp($this->getApiCreateResponseBodyForOauth()));

        $expected  = $this->getExpectedArgsForRequestMethod($this->getStorkUpdatePayloadForPrimary(), '10000000000App', 'application', 'api-test');
        $mockeryOn = $this->attachEmptyWKCtxMatcherToArgsMatcher($this->getArgsMatcherForWebhook($expected));

        $this->mockServiceStorkRequest(
            function ($path, $payload)
            {
                return $this->getStorkResponse(['webhook' => $this->getStorkCreateResponseBodyForOauth()]);
            })
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/Update', \Mockery::on($mockeryOn));

        $this->startTest();
    }

    public function testUpdateWebhookForOauthMerchantNotPartnerFailure()
    {
        $this->testData[__FUNCTION__]['request']['content'] = $this->getApiUpdatePayloadForOauth();
        $this->startTest();
    }

    public function testUpdateWebhookForOauthMerchantNoAppAccessFailure()
    {
        $this->addOAuthTag();
        $this->testData[__FUNCTION__]['request']['content'] = $this->getApiUpdatePayloadForOauth();
        $this->startTest();
    }

    public function testUpdateWebhookOverrideToImplictValues()
    {
        $this->testData[__FUNCTION__] = $this->testData['testUpdateWebhookForPrimary'];

        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiUpdatePayloadForPrimary();
        $this->testData[__FUNCTION__]['response']['content'] = $this->convertAllToUnixTimestamp($this->getApiUpdateResponseBodyForPrimary());

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
        $this->testData[__FUNCTION__]['request']['content']  = $this->getApiUpdatePayloadForBanking();

        $this->startTest();
    }

    public function testSendDisableWebhookEmailForStork()
    {
        Mail::fake();

        $this->startTest();

        $testData = $this->testData[__FUNCTION__.'Data'];
        // test mail sent
        Mail::assertQueued(WebhookMail::class, function ($mail) use ($testData)
        {
            $this->assertEquals($mail->viewData['url'], $testData['url']);

            $this->assertEquals($mail->viewData['mode'], $testData['mode']);

            $this->assertEquals($mail->viewData['subject'], $testData['subject']);

            return ($mail->hasFrom('alerts@razorpay.com') and ($mail->hasTo($testData['alert_email'])));
        });
    }

    protected function addOAuthTag(string $merchantId = '10000000000000')
    {
        $merchant = Merchant\Entity::find($merchantId);
        $merchant->reTag(["oauth"]);
        $merchant->saveOrFail();
    }

    protected function assignMerchantAsPartnerAggregator($merchantId = '10000000000000')
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => 'aggregator']);
    }

    protected function addMerchantApplicationMapping(string $appId = '10000000000App', string $merchantId = '10000000000000')
    {
        $this->createOAuthApplication(
            [
                'id'          => $appId,
                'merchant_id' => $merchantId,
            ]
        );

    }

    protected function mockRazorxToReturnOn()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');
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

    protected function addCreatedUpdatedByEmail(array $input): array
    {
        $user = $this->app['repo']->user->find('MerchantUser01');
        if (is_null($user) === true)
        {
            return $input;
        }
        if (isset($input['created_by']) === true)
        {
            $input['created_by_email'] = $user->email;
        }
        if (isset($input['updated_by']) === true)
        {
            $input['updated_by_email'] = $user->email;
        }
        return $input;
    }
}
