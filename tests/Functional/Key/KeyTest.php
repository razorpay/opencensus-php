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

        $this->ba->appAuth();
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
}
