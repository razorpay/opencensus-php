<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
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
}
