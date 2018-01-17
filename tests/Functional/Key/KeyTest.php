<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class KeyTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
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

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/keys/rzp_test_TheTestAuthKey';

        $this->ba->proxyAuth('rzp_test_' . $id);
        $content = $this->startTest();
    }
}

