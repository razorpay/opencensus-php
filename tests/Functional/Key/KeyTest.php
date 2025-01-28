<?php

namespace RZP\Tests\Functional\Merchant;

use Crypt;
use Mail;
use Mockery;
use RZP\Exception;
use Rzp\Credcase\Apikey\V1\ApiKeyListResponse;
use Rzp\Credcase\Apikey\V1\ApiKeyResponse;
use RZP\Error\ErrorCode;
use RZP\Models\Key\CredcaseApi;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Traits\MocksSplitz;

class KeyTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use MocksSplitz;

    protected $credcaseMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/KeyData.php';
        parent::setUp();
    }

    /**
     * Checks that new key id generated is not
     * associated with time like other entity ids
     * The way to do this is to take a normal generated id
     * and compare it with new key generated. Comparison is
     * done for first few letters. If it's time dependant,
     * then those will be same
     */
    public function testNewKeyIdRandom()
    {
        $this->ba->proxyAuthTest();

        $content = $this->startTest();

        $id = $this->fixtures->generateUniqueId();
        $newKeyId = $content['new']['id'];
        // strip prefix
        $newKeyId = substr($newKeyId, 9);

        $str1 = substr($id, 0, 3);
        $str2 = substr($newKeyId, 0, 3);

        $this->assertNotEquals($str1, $str2);
    }

    public function testRegenerateKeyWhereMerchantIdIsDifferent()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $id = $merchant['id'];

        $user = $this->fixtures->user->createUserForMerchant($id);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/keys/rzp_test_TheTestAuthKey';

        $this->ba->proxyAuth('rzp_test_' . $id, $user->getId());

        $this->startTest();
    }

    public function testNewKeyWithOtp()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $id = $merchant['id'];

        $user = $this->fixtures->user->createUserForMerchant($id);

       $this->fixtures-> user-> createUserMerchantMapping([
            'merchant_id' => $merchant['id'],
            'user_id'     => $user['id'],
            'role'        => 'owner',
            'product'     => 'banking'
        ], 'test');

        $testData = & $this->testData[__FUNCTION__];

        $this->ba->proxyAuth('rzp_test_' . $id, $user->getId());

        $this->startTest();
    }

    public function testNewKeyWithWrongOtp()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $id = $merchant['id'];

        $user = $this->fixtures->user->createUserForMerchant($id);

        $this->fixtures-> user-> createUserMerchantMapping([
            'merchant_id' => $merchant['id'],
            'user_id'     => $user['id'],
            'role'        => 'owner',
            'product'     => 'banking'
        ], 'test');

        $testData = & $this->testData[__FUNCTION__];

        $this->ba->proxyAuth('rzp_test_' . $id, $user->getId());

        $this->startTest();
    }

    public function testGetKeysByNonOwnerUser()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');
        $id = $merchant['id'];

        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => $id,
            'role'        => 'finance',
        ]);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/keys';

        $this->ba->proxyAuth('rzp_test_' . $id, $user->toArrayPublic(), 'finance');
        $this->startTest();
    }

    /**
     * This is an explicit need for ePos app, on dashboard we don't
     * originally want to expose.
     */
    public function testGetKeysByEPosUser()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');
        $id = $merchant['id'];

        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => $id,
            'role'        => 'sellerapp',
        ]);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/keys';

        $this->ba->proxyAuth('rzp_test_' . $id, $user->toArrayPublic(), 'sellerapp');
        $this->startTest();
    }

    public function testCaActivatedMerchantCanCreateKeys()
    {
        $merchant = $this->fixtures->create('merchant', ['has_key_access' => true]);

        $id = $merchant['id'];

        $user = $this->fixtures->user->createBankingUserForMerchant($id);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $id,
            'business_type'     => '2'
        ]);

        $params = [
            'account_number'        => '2224440041626905',
            'merchant_id'           =>  $id,
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156'
        ];

        $this->fixtures->on('live')->create('banking_account', $params);

        $this->ba->proxyAuth('rzp_live_' . $id, $user->getId());

        $this->startTest();
    }


    public function testCaActivatedMerchantCanCreateKeysWithOtp()
    {
        $merchant = $this->fixtures->create('merchant', ['has_key_access' => true]);

        $id = $merchant['id'];

        $user = $this->fixtures->user->createBankingUserForMerchant($id);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $id,
            'business_type'     => '2'
        ]);

        $params = [
            'account_number'        => '2224440041626905',
            'merchant_id'           =>  $id,
            'account_type'          => 'current',
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156'
        ];

        $this->fixtures->on('live')->create('banking_account', $params);

        $this->ba->proxyAuth('rzp_live_' . $id, $user->getId());

        $this->startTest();
    }

    public function testIciciCaActivatedMerchantCanCreateKeys()
    {
        $this->testData[__FUNCTION__] = $this->testData['testCaActivatedMerchantCanCreateKeys'];

        $merchant = $this->fixtures->create('merchant', ['has_key_access' => true]);

        $id = $merchant['id'];

        $user = $this->fixtures->user->createBankingUserForMerchant($id);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $id,
            'business_type'     => '2',
            'bas_business_id'   => '10000000000000',
        ]);

        $this->fixtures->on('live')->create('balance',
            [
                'merchant_id'       => $id,
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
                'channel'           => 'icici',
            ]);

        $this->ba->proxyAuth('rzp_live_' . $id, $user->getId());

        $this->startTest();
    }

    public function testNonCaActivatedMerchantCannotCreateKeys()
    {
        $merchant = $this->fixtures->create('merchant');

        $id = $merchant['id'];

        $user = $this->fixtures->user->createBankingUserForMerchant($id);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $id,
            'business_type'     => '2'
        ]);

        $this->ba->proxyAuth('rzp_live_' . $id, $user->getId());

        $this->startTest();
    }

    protected function getActiveAPIKeyIds($merchantId, $userId)
    {
        $request = [
            'url'     => '/keys',
            'content' => [],
            'method'  => 'GET'
        ];

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userId);

        $response = $this->makeRequestAndGetContent($request);

        $keys     = $response['items'];

        $keyIds   = [];

        foreach ($keys as $key)
        {
            $keyIds[] = $key['id'];
        }

        return $keyIds;
    }

    public function testVaActivatedMerchantCreateKeys()
    {
        $merchant = $this->fixtures->create('merchant',['business_banking' => true , 'email' => 'test@gmail.com']);

        $id = $merchant['id'];
        $this->fixtures->terminal->createRXTerminal();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $id,
            'business_type'     => '3'
        ]);


        $dataToReplace = [
            'url' => '/merchants/va_activation/' . $id,
            'method' => 'POST',
            'content' => [
                'create_va' => true,
                'set_has_key_access' => true,
            ],
        ];

        $this->ba->adminAuth('live');

        $response = $this->makeRequestAndGetContent($dataToReplace);

        $user = $this->fixtures->user->createBankingUserForMerchant($id);


        $this->ba->proxyAuth('rzp_live_' . $id, $user->getId());

        $this->startTest();
    }

    protected function expectStorkSendSmsRequest($storkMock, $templateName, $destination, $expectedParms = [])
    {
        $storkMock->shouldReceive('sendSms')
                  ->times(1)
                  ->with(
                      Mockery::on(function ($mockInMode)
                      {
                          return true;
                      }),
                      Mockery::on(function ($actualPayload) use ($templateName, $destination, $expectedParms)
                      {

                          // We are sending null in contentParams in the payload if there is no SMS_TEMPLATE_KEYS present for that event
                          // Reference: app/Notifications/Dashboard/SmsNotificationService.php L:99
                          if(isset($actualPayload['contentParams']) === true)
                          {
                              $this->assertArraySelectiveEquals($expectedParms, $actualPayload['contentParams']);
                          }

                          if (($templateName !== $actualPayload['templateName']) or
                              ($destination !== $actualPayload['destination']))
                          {
                              return false;
                          }

                          return true;
                      }))
                  ->andReturnUsing(function ()
                  {
                      return ['success' => true];
                  });
    }

    protected function expectStorkWhatsappRequest($storkMock, $text, $destination): void
    {
        $storkMock->shouldReceive('sendWhatsappMessage')
            ->times(1)
            ->with(
                Mockery::on(function ($mode)
                {
                    return true;
                }),
                Mockery::on(function ($actualText) use($text)
                {
                    $actualText = trim(preg_replace('/\s+/', ' ', $actualText));

                    $text = trim(preg_replace('/\s+/', ' ', $text));

                    if ($actualText !== $text)
                    {
                        return false;
                    }

                    return true;
                }),
                Mockery::on(function ($actualReceiver) use($destination)
                {
                    if ($actualReceiver !== $destination)
                    {
                        return false;
                    }
                    return true;
                }),
                Mockery::on(function ($input)
                {
                    return true;
                }))
            ->andReturnUsing(function ()
            {
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

    protected function enableRazorXTreatmentForFeature($featureUnderTest, $value = 'on')
    {
        $mock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $mock->method('getTreatment')
            ->will(
                $this->returnCallback(
                    function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                    {
                        return $feature === $featureUnderTest ? $value : 'control';
                    }));

        $this->app->instance('razorx', $mock);
    }

    public function testBulkRegenerateApiKey()
    {
        Mail::fake();


        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock, 'sms.dashboard.bulk_regenerate_api_key', '1234567890', []);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hi,
                We have deactivated your  API keys, since we noticed that you are hardcoding them on your App. To continue accepting payments, we request you to generate new API keys.
                To generate API key in live mode:
                1. Log into Dashboard and switch to Live mode on the menu.
                2. Navigate to Settings - API Keys - Re-Generate Key to generate a new API key for live mode.
                3. Download the keys and save it securely.
                4. Ensure that Razorpay API secret is not included in the final Android or iOS build
                Thanks,
                Team Razorpay',
            '1234567890'
        );

        $merchantDetail             = $this->fixtures->create('merchant_detail');

        $merchantId                 = $merchantDetail->getEntityId();

        $user                       = $this->fixtures->user->createUserForMerchant($merchantId, [
                                                    'contact_mobile' => '1234567890',
                                                    'contact_mobile_verified' => true
                                               ]);

        $testData                   = & $this->testData[__FUNCTION__];

        $expectedFailedMerchant     =  $merchant = $this->fixtures->create('merchant', ['has_key_access' => false]);

        $expectedFailedMerchantId   = $expectedFailedMerchant['id'];

        $this->fixtures->merchant->setHasKeyAccess(true, $merchantId);

        $testData['request']['content']['merchant_ids'][]   = $merchantId;

        $testData['request']['content']['merchant_ids'][]   = $expectedFailedMerchantId;

        $testData['response']['content']['success_mids'][]  = $merchantId;

        $testData['response']['content']['failed_mids'][$expectedFailedMerchantId]   = 'You are not allowed to perform this operation';

        $initialActiveKeyIds = $this->getActiveAPIKeyIds($merchantId, $user->getId());

        $this->ba->adminAuth();

        $this->startTest();

        $updatedActiveKeyIds = $this->getActiveAPIKeyIds($merchantId, $user->getId());

        $this->assertNotEquals($updatedActiveKeyIds, $initialActiveKeyIds);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $this->assertEquals('emails.merchant.bulk_regenerate_api_keys', $mail->view);

            $mail->hasTo($user->getEmail());

            return true;
        });

    }

    public function testExpireKeys()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'Hoah6C9SnyNIs5']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);
        $testData                   = & $this->testData[__FUNCTION__];
        $testData['request']['content']['key_ids'][]   = $key->getKey();
        $testData['response']['content']['success'] = [0 => $key->getKey()];
        $this->startTest();
    }

    public function testExpireKeysWhenKeyDoesNotExist()
    {
        $testData                   = & $this->testData[__FUNCTION__];
        $testData['request']['content']['key_ids'][]   = 'invalid_key';
        $testData['response']['content']['success'] = [];
        $testData['response']['content']['failed'] = [ 0 => 'invalid_key'];
        $this->startTest();
    }

    /**
     * @dataProvider getKeysDataProvider
     */
    public function testGetKeys($expectedResponse, $mockException, $expectedItemCount, $expectedFirstId, $expectedEntity, $testFunction)
    {

        $merchant = $this->fixtures->create('merchant:with_keys');
        $id = $merchant['id'];

        if ($mockException) {
            $this->mockCredcaseApi(null, new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND));
        } else {
            foreach ($expectedResponse->getItems() as $keyResponse) {
                $keyResponse->setOwnerId($id);
            }
            $this->mockCredcaseApi($expectedResponse, null);
        }

        $user = $this->fixtures->user->createUserForMerchant($id);

        $this->mockAllSplitzTreatment();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/keys';
        $testData['request']['method'] = 'GET';

        $this->ba->proxyAuth('rzp_test_' . $id, $user->getId());

        $content = $this->startTest();

        $this->assertEquals($expectedItemCount, count($content['items']));
        if ($expectedItemCount > 0) {
            $this->assertEquals($expectedFirstId, $content['items'][0]['id']);
            $this->assertEquals($expectedEntity, $content['items'][0]['entity']);
        }
    }

    /**
     * Data provider for testGetKeys
     */
    public function getKeysDataProvider()
    {
        $apiKeyResponse1 = new ApiKeyResponse();
        $apiKeyResponse1->setId('rzp_test_AltTestAuthKey');
        $apiKeyResponse1->setEntity('key');

        $apiKeyResponse2 = new ApiKeyResponse();
        $apiKeyResponse2->setId('rzp_test_AltTestAuthKe1');
        $apiKeyResponse2->setEntity('key');

        $singleKeyResponse = new ApiKeyListResponse();
        $singleKeyResponse->setCount(1);
        $singleKeyResponse->setEntity('key');
        $singleKeyResponse->setItems([$apiKeyResponse1]);

        $multipleKeyResponse = new ApiKeyListResponse();
        $multipleKeyResponse->setCount(2);
        $multipleKeyResponse->setEntity('key');
        $multipleKeyResponse->setItems([$apiKeyResponse1, $apiKeyResponse2]);

        $singleKeyMismatch = new ApiKeyListResponse();
        $singleKeyMismatch->setCount(1);
        $singleKeyMismatch->setEntity('key');
        $singleKeyMismatch->setItems([$apiKeyResponse2]);


        return [
            'valid single key' => [
                'expectedResponse' => $singleKeyResponse,
                'mockException' => null,
                'expectedItemCount' => 1,
                'expectedFirstId' => 'rzp_test_AltTestAuthKey',
                'expectedEntity' => 'key',
                'testFunction' => 'testGetKeys'
            ],
            'valid multiple keys with mismatch' => [
                'expectedResponse' => $multipleKeyResponse,
                'mockException' => null,
                'expectedItemCount' => 1,
                'expectedFirstId' => 'rzp_test_AltTestAuthKey',
                'expectedEntity' => 'key',
                'testFunction' => 'testGetKeysCredcaseMismatchMultiple'
            ],
            'valid single key with credcase mismatch' => [
                'expectedResponse' => $singleKeyMismatch,
                'mockException' => null,
                'expectedItemCount' => 1,
                'expectedFirstId' => 'rzp_test_AltTestAuthKey',
                'expectedEntity' => 'key',
                'testFunction' => 'testGetKeysCredcaseMismatch'
            ],
            'credcase exception' => [
                'expectedResponse' => null,
                'mockException' => true,
                'expectedItemCount' => 1,
                'expectedFirstId' => 'rzp_test_AltTestAuthKey',
                'expectedEntity' => 'key',
                'testFunction' => 'testGetKeyswithCredcaseException'
            ]
        ];
    }

    protected function mockCredcaseApi($output = null, $exception = null)
    {
        $this->credcaseMock = Mockery::mock(CredcaseApi::class, [$this->app])->makePartial();
        $this->app->instance('credcase', $this->credcaseMock);

        $shouldReceive = $this->credcaseMock->shouldReceive('list')->byDefault();

        if ($exception) {
            $shouldReceive->andThrow($exception);
        } else {
            $shouldReceive->andReturn($output);
        }
    }


    public function testCreateKeyWithCountry()
    {
        $merchant = $this->fixtures->create('merchant',['country_code'=>'SG']);

        $user = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_'.$merchant->getId(), $user->getId());

        $res = $this->startTest();
        $this->assertMatchesRegularExpression('/rzp_test_sg_\w{14}/', $res['id']);
        $this->assertMatchesRegularExpression('/\w{24}/', $res['secret']);

        // Assert insertion of api key
        $this->assertCount(2, $this->getDbEntities('key'), 'key present in database');

        // assert that key got encrypted using rzp key
        $keyFromDb = $this->getDbEntityById('key', $res['id']);
        $decryptedSecret = Crypt::decrypt($keyFromDb['secret']);
        $this->assertEquals($decryptedSecret, $res['secret']);
        $this->assertEquals($decryptedSecret, $keyFromDb->getDecryptedSecret());
    }

    public function testGetKeysWithCountryCode()
    {
        $merchant = $this->fixtures->create('merchant',['country_code'=>'SG']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);

        $id = $merchant['id'];

        $user = $this->fixtures->user->createUserForMerchant($id);

        $this->mockAllSplitzResponseDisable();

        $testData = $this->testData['testGetKeys'];

        $testData['request']['url'] = '/keys';

        $this->ba->proxyAuth('rzp_test_' . $id, $user->getId());

        $content = $this->runRequestResponseFlow($testData);

        $this->assertEquals(1, count($content['items']));

        $this->assertEquals('rzp_test_sg_'.$key->getId(), $content['items'][0]['id']);

        $this->assertEquals('key', $content['items'][0]['entity']);
    }

    public function testGetKeysWithCountryCodeCredcaseFlow()
    {
        $merchant = $this->fixtures->create('merchant',['id' => 'PNWiRsflEJCLtj','country_code'=>'SG']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);

        $id = $merchant['id'];

        $user = $this->fixtures->user->createUserForMerchant($id);

        $testData = $this->testData['testGetKeys'];

        $testData['request']['url'] = '/keys';

        $apiKeyResponse1 = new ApiKeyResponse();
        $apiKeyResponse1->setId('rzp_test_sg_'.$key->getId());
        $apiKeyResponse1->setOwnerId($id);
        $apiKeyResponse1->setEntity('key');
        $singleKeyResponse = new ApiKeyListResponse();
        $singleKeyResponse->setCount(1);
        $singleKeyResponse->setEntity('key');
        $singleKeyResponse->setItems([$apiKeyResponse1]);

        $this->mockAllSplitzTreatment();

        $this->mockCredcaseApi($singleKeyResponse);

        $this->ba->proxyAuth('rzp_test_' . $id, $user->getId());

        $content = $this->startTest($testData);

        $this->assertEquals(1, count($content['items']));

        $this->assertEquals('rzp_test_sg_'.$key->getId(), $content['items'][0]['id']);

        $this->assertEquals('key', $content['items'][0]['entity']);
    }

}
