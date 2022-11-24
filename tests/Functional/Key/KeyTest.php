<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Mockery;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class KeyTest extends TestCase
{
    use RequestResponseFlowTrait;

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

    public function testGetKeys()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');
        $id = $merchant['id'];

        $user = $this->fixtures->user->createUserForMerchant($id);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/keys';

        $this->ba->proxyAuth('rzp_test_' . $id, $user->getId());

        $content = $this->startTest();

        $this->assertEquals(1, count($content['items']));

        $this->assertEquals('rzp_test_AltTestAuthKey', $content['items'][0]['id']);

        $this->assertEquals('key', $content['items'][0]['entity']);
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

        $this->enableRazorXTreatmentForFeature(RazorxTreatment::WHATSAPP_NOTIFICATIONS, 'on');

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

}
